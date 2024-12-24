<?php
/**
 * Plugin Name: 超级附件
 * Plugin URI: https://www.6480i.com/
 * Description: 超级附件是一款专为 WordPress 开发附件管理插件，支持文章附件上传、下载功能。通过插件，用户可以为文章添加多个附件的下载管理，支持仅登录用户下载。提供附件下载统计功能，便于用户了解附件的下载量及使用情况，不暴露文件下载链接，防采集、防恶意下载，让附件管理更高效、更安全。
 * Version: 1.0.0
 * Author: Allen
 * Author URI: https://www.6480i.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright (c) 2024 Allen
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 */

if (!defined('ABSPATH')) {
    exit; // 防止直接访问文件
}

// 包含核心文件
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/download-handler.php';
require_once plugin_dir_path(__FILE__) . 'admin/ajax-handlers.php';

/**
 * 插件激活时的操作
 */
register_activation_hook(__FILE__, 'super_attachments_activate');
function super_attachments_activate() {
    // 创建数据库表
    super_attachments_create_database();

    // 刷新重写规则
    flush_rewrite_rules();
}

/**
 * 插件卸载时的操作
 */
register_uninstall_hook(__FILE__, 'super_attachments_uninstall');
function super_attachments_uninstall() {
    // 删除数据库表
    super_attachments_delete_database();

    // 刷新重写规则
    flush_rewrite_rules();
}

/**
 * 插件初始化
 */
add_action('plugins_loaded', 'super_attachments_init');
function super_attachments_init() {
    // 加载必要的脚本与样式
    add_action('admin_enqueue_scripts', 'super_attachments_admin_enqueue');
    add_action('wp_enqueue_scripts', 'super_attachments_frontend_enqueue');

    // 在文章编辑页面加载附件上传界面
    add_action('add_meta_boxes', 'super_attachments_add_meta_box');
    add_action('save_post', 'super_attachments_save_post');
}

/**
 * 管理后台脚本和样式
 */
function super_attachments_admin_enqueue($hook) {
    if ($hook !== 'post-new.php' && $hook !== 'post.php') {
        return;
    }

    wp_enqueue_script(
        'super-attachments-meta-box',
        plugin_dir_url(__FILE__) . 'public/js/meta-box.js',
        ['jquery'],
        '1.0.0',
        true
    );
    wp_enqueue_style(
        'super-attachments-meta-box',
        plugin_dir_url(__FILE__) . 'public/css/style.css'
    );

    wp_localize_script('super-attachments-meta-box', 'SuperAttachmentsAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('super_attachments_nonce'),
        'post_id'  => get_the_ID(),
    ]);
}

/**
 * 前端脚本和样式
 */
function super_attachments_frontend_enqueue() {
    if (!is_single()) {
        return;
    }

    wp_enqueue_script(
        'super-attachments-frontend',
        plugin_dir_url(__FILE__) . 'public/js/ajax-requests.js',
        ['jquery'],
        '1.0.0',
        true
    );
    wp_enqueue_style(
        'super-attachments-frontend-style',
        plugin_dir_url(__FILE__) . 'public/css/style.css',
        [],
        '1.0.0'
    );

    wp_localize_script('super-attachments-frontend', 'SuperAttachmentsAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('super_attachments_nonce'),
    ]);
}

/**
 * 添加附件管理框
 */
function super_attachments_add_meta_box() {
    add_meta_box(
        'super_attachments_meta_box',
        '附件管理',
        'super_attachments_meta_box_callback',
        'post',
        'normal',
        'high'
    );
}

/**
 * 附件管理框回调
 */
function super_attachments_meta_box_callback($post) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'super_attachments';

    // 获取当前文章的附件
    $attachments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post->ID));

    // 加载模板
    include plugin_dir_path(__FILE__) . 'public/templates/attachment-table.php';
}

/**
 * 保存文章时处理附件绑定
 */
function super_attachments_save_post($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    if (wp_is_post_revision($post_id)) {
        return $post_id;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'super_attachments';

    // 获取临时附件并绑定到文章
    $attachments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE post_id IS NULL AND temporary = 1 AND temp_time > %s",
        date('Y-m-d H:i:s', strtotime('-30 minutes'))
    ));

    foreach ($attachments as $attachment) {
        $wpdb->update(
            $table_name,
            ['post_id' => $post_id, 'temporary' => 0],
            ['id' => $attachment->id]
        );
    }

    return $post_id;
}

/**
 * 清理过期的附件
 */
add_action('wp_scheduled_delete', 'super_attachments_cleanup');
function super_attachments_cleanup() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'super_attachments';

    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE temporary = 1 AND temp_time < %s",
        date('Y-m-d H:i:s', strtotime('-30 minutes'))
    ));
}

/**
 * 在文章详情页内容末尾显示附件信息
 */
add_filter('the_content', 'super_attachments_display_attachments');
function super_attachments_display_attachments($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        global $wpdb;
        $post_id = get_the_ID();
        $table_name = esc_sql($wpdb->prefix . 'super_attachments');

        $attachments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `$table_name` WHERE post_id = %d",
            $post_id
        ));

        if (empty($attachments)) {
            return $content;
        }

        ob_start();
        include plugin_dir_path(__FILE__) . 'public/templates/attachment-list.php';
        $attachments_html = ob_get_clean();

        $content .= $attachments_html;
    }

    return $content;
}

/**
 * 刷新重写规则
 */
add_action('init', 'super_attachments_add_rewrite_rule');
function super_attachments_add_rewrite_rule() {
    add_rewrite_rule(
        '^super-attachments-download/([^/]+)/?$',
        'index.php?super_attachments_download=$matches[1]',
        'top'
    );
    flush_rewrite_rules();
}

/**
 * 注册自定义查询变量
 */
add_filter('query_vars', 'super_attachments_query_vars');
function super_attachments_query_vars($vars) {
    $vars[] = 'super_attachments_download';
    return $vars;
}

/**
 * 添加中间页的 Rewrite Rule
 */
add_action('init', 'super_attachments_add_download_page_rewrite_rule');
function super_attachments_add_download_page_rewrite_rule() {
    add_rewrite_rule(
        '^super-attachments/download/([0-9]+)/?$',
        'index.php?super_attachment_download_page=$matches[1]',
        'top'
    );
    flush_rewrite_rules();
}

/**
 * 注册自定义查询变量
 */
add_filter('query_vars', 'super_attachments_download_page_query_vars');
function super_attachments_download_page_query_vars($vars) {
    $vars[] = 'super_attachment_download_page';
    return $vars;
}
