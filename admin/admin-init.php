<?php
/**
 * 注册后台脚本和样式
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 加载前台脚本和样式
 */
function super_attachments_enqueue_scripts() {
    if (is_singular('post')) { // 仅在文章详情页加载
        // 加载前台样式
        wp_enqueue_style(
            'super-attachments-front-style',
            SUPER_ATTACHMENTS_URL . 'public/css/style.css',
            [],
            SUPER_ATTACHMENTS_VERSION
        );

        // 加载前台脚本
        wp_enqueue_script(
            'super-attachments-front-script',
            SUPER_ATTACHMENTS_URL . 'public/js/ajax-requests.js',
            ['jquery'],
            SUPER_ATTACHMENTS_VERSION,
            true
        );

        // 本地化前台脚本数据
        wp_localize_script('super-attachments-front-script', 'SuperAttachmentsAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('super_attachments_nonce'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'super_attachments_enqueue_scripts');