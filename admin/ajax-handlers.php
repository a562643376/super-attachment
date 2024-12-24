<?php
/**
 * 超级附件 - 后台 AJAX 处理程序
 * 处理后台的 AJAX 请求。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 添加附件到文章或临时存储
 */
add_action('wp_ajax_super_attachments_add_attachment', 'admin_super_attachments_add_attachment_handler');
function admin_super_attachments_add_attachment_handler() {
    check_ajax_referer('super_attachments_nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    $file_id = intval($_POST['file_id']);
    $file_name = sanitize_text_field($_POST['file_name']);
    $file_url = esc_url_raw($_POST['file_url']);
    $file_size = sanitize_text_field($_POST['file_size']);

    if (empty($file_id) || empty($file_url)) {
        wp_send_json_error(['message' => '无效的文件信息。']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'super_attachments';

    $is_temporary = empty($post_id) ? 1 : 0;

    $result = $wpdb->insert($table_name, [
        'post_id' => $is_temporary ? null : $post_id,
        'resource_name' => $file_name,
        'file_url' => $file_url,
        'file_id' => $file_id,
        'upload_time' => current_time('mysql'),
        'file_size' => $file_size,
        'temporary' => $is_temporary,
        'temp_time' => $is_temporary ? current_time('mysql') : null,
    ]);

    if ($result === false) {
        wp_send_json_error(['message' => '数据库操作失败。']);
    }

    wp_send_json_success(['message' => '附件已成功添加！']);
}

/**
 * 删除附件
 */
add_action('wp_ajax_super_attachments_delete_attachment', 'admin_super_attachments_delete_attachment_handler');
function admin_super_attachments_delete_attachment_handler() {
    check_ajax_referer('super_attachments_nonce', 'nonce');

    $attachment_id = intval($_POST['attachment_id']);

    if (empty($attachment_id)) {
        wp_send_json_error(['message' => '无效的附件 ID。']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'super_attachments';

    $attachment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $attachment_id));

    if (!$attachment) {
        wp_send_json_error(['message' => '未找到附件记录。']);
    }

    $deleted = wp_delete_attachment($attachment->file_id, true);
    if (!$deleted) {
        wp_send_json_error(['message' => '无法删除媒体库文件。']);
    }

    $result = $wpdb->delete($table_name, ['id' => $attachment_id]);

    if ($result === false) {
        wp_send_json_error(['message' => '无法从数据库中删除记录。']);
    }

    wp_send_json_success(['message' => '附件已成功删除！']);
}

/**
 * 更新附件名称
 */
add_action('wp_ajax_super_attachments_update_attachment_name', 'admin_super_attachments_update_attachment_name_handler');
function admin_super_attachments_update_attachment_name_handler() {
    check_ajax_referer('super_attachments_nonce', 'nonce');

    $attachment_id = intval($_POST['attachment_id']);
    $new_name = sanitize_text_field($_POST['new_name']);

    if (empty($attachment_id) || empty($new_name)) {
        wp_send_json_error(['message' => '无效的附件 ID 或名称。']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'super_attachments';

    $result = $wpdb->update($table_name, ['resource_name' => $new_name], ['id' => $attachment_id]);

    if ($result === false) {
        wp_send_json_error(['message' => '无法更新附件名称。']);
    }

    wp_send_json_success(['message' => '附件名称已更新！']);
}

/**
 * 附件预览
 */
add_action('wp_ajax_super_attachments_preview_attachment', 'admin_super_attachments_preview_attachment_handler');
add_action('wp_ajax_nopriv_super_attachments_preview_attachment', 'admin_super_attachments_preview_attachment_handler');

function admin_super_attachments_preview_attachment_handler() {
    $attachment_id = intval($_GET['attachment_id']);

    if (empty($attachment_id)) {
        wp_die('无效的附件 ID。');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'super_attachments';

    $attachment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $attachment_id));

    if (!$attachment) {
        wp_die('未找到附件记录。');
    }

    $file_url = esc_url($attachment->file_url);
    if (empty($file_url)) {
        wp_die('无法找到附件 URL。');
    }

    wp_redirect($file_url);
    exit;
}

/**
 * 刷新附件表格内容
 */
add_action('wp_ajax_super_attachments_refresh_table', 'admin_super_attachments_refresh_table_handler');
function admin_super_attachments_refresh_table_handler() {
    check_ajax_referer('super_attachments_nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    if (empty($post_id)) {
        wp_send_json_error(['message' => '无效的文章 ID。']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'super_attachments';

    $attachments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post_id));

    ob_start();
    include plugin_dir_path(__FILE__) . '../public/templates/attachment-table.php';
    $table_html = ob_get_clean();

    wp_send_json_success(['table_html' => $table_html]);
}
