<?php
/**
 * Meta Box 定义与功能
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 在文章编辑页面添加 Meta Box
 */
function super_attachments_add_meta_box() {
    add_meta_box(
        'super_attachments_meta_box',          // Meta Box ID
        '附件管理',                              // Meta Box 标题
        'super_attachments_render_meta_box',  // 回调函数，用于渲染 Meta Box
        'post',                                // 显示在文章类型
        'normal',                              // 显示在内容区域
        'high'                                 // 优先级
    );
}
add_action('add_meta_boxes', 'super_attachments_add_meta_box');

/**
 * 渲染 Meta Box 内容
 * 
 * @param WP_Post $post 当前编辑的文章对象
 */
function super_attachments_render_meta_box($post) {
    // 获取当前文章的附件列表
    global $wpdb;
    $table_name = esc_sql($wpdb->prefix . 'super_attachments'); // 确保表名安全
    $attachments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM `$table_name` WHERE post_id = %d",
        $post->ID
    ));

    // 包含表格模板文件
    include plugin_dir_path(__FILE__) . '../public/templates/attachment-table.php';
}
