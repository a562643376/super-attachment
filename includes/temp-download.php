<?php
/**
 * 临时下载链接处理
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 临时链接处理逻辑
 */
add_action('template_redirect', 'super_attachments_temp_download_handler');
function super_attachments_temp_download_handler() {
    // 检查是否为临时下载路由
    $temp_code = get_query_var('super_temp_download');
    if (!$temp_code) {
        return; // 非临时下载请求
    }

    global $wpdb;
    $table_name = esc_sql($wpdb->prefix . 'super_attachments'); // 确保表名安全

    // 解码临时链接
    $attachment_id = intval(base64_decode($temp_code));
    if (!$attachment_id) {
        wp_die('无效的下载请求。');
    }

    // 验证 nonce
    $nonce = $_GET['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'super_attachments_download')) {
        wp_die('非法请求。');
    }

    // 验证哈希
    $hash = $_GET['hash'] ?? '';
    $expected_hash = hash_hmac('sha256', $attachment_id . ':' . $nonce, SECURE_AUTH_KEY);
    if ($hash !== $expected_hash) {
        wp_die('无效的下载链接。');
    }

    // 获取附件信息
    $attachment = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_name` WHERE id = %d", $attachment_id));
    if (!$attachment) {
        wp_die('附件未找到。');
    }

    // 验证链接是否过期
    $current_time = current_time('timestamp');
    $expiry_time = strtotime($attachment->upload_time) + (10 * 60); // 上传时间 + 10分钟

    if ($current_time > $expiry_time) {
        wp_die('下载链接已过期。');
    }

    // 增加下载次数
    $updated = $wpdb->query($wpdb->prepare(
        "UPDATE `$table_name` SET download_count = download_count + 1 WHERE id = %d",
        $attachment_id
    ));
    if ($updated === false) {
        wp_die('无法更新下载次数，请稍后重试。');
    }

    // 重定向到文件 URL
    wp_redirect($attachment->file_url);
    exit;
}
