<?php
/**
 * 超级附件 - 文件下载处理模块
 * 提供文件下载和相关逻辑。
 */

if (!defined('ABSPATH')) {
    exit; // 防止直接访问文件
}

/**
 * 获取附件信息
 * @param int $attachment_id
 * @return object|false 附件记录或 false
 */
function super_attachments_get_attachment($attachment_id) {
    global $wpdb;
    $table_name = esc_sql($wpdb->prefix . 'super_attachments');

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_name` WHERE id = %d", $attachment_id));
}

/**
 * 增加附件下载次数
 * @param int $attachment_id
 * @return bool 成功或失败
 */
function super_attachments_increment_download_count($attachment_id) {
    global $wpdb;
    $table_name = esc_sql($wpdb->prefix . 'super_attachments');

    return $wpdb->query($wpdb->prepare(
        "UPDATE `$table_name` SET download_count = download_count + 1 WHERE id = %d",
        $attachment_id
    ));
}

/**
 * 处理文件下载请求
 * @param int $attachment_id
 * @return array|WP_Error 包含文件信息的数组或错误对象
 */
function super_attachments_process_download($attachment_id) {
    // 获取附件信息
    $attachment = super_attachments_get_attachment($attachment_id);
    if (!$attachment) {
        return new WP_Error('attachment_not_found', '未找到附件记录。');
    }

    // 验证文件 URL 是否有效
    $file_url = esc_url($attachment->file_url);
    if (empty($file_url)) {
        return new WP_Error('invalid_file_url', '附件的文件 URL 无效。');
    }

    // 增加下载次数
    $increment_result = super_attachments_increment_download_count($attachment_id);
    if ($increment_result === false) {
        return new WP_Error('update_failed', '无法更新下载次数，请稍后重试。');
    }

    // 添加调试日志
    error_log("Download Processed for Attachment ID {$attachment_id}: URL={$file_url}");

    // 返回文件信息
    return [
        'file_url' => $file_url,
        'file_name' => sanitize_file_name($attachment->resource_name),
        'download_count' => intval($attachment->download_count) + 1, // 更新后的下载次数
    ];
}

/**
 * 注册 AJAX 下载处理
 */
add_action('wp_ajax_super_attachments_download', 'super_attachments_download_handler');
add_action('wp_ajax_nopriv_super_attachments_download', 'super_attachments_download_handler');

/**
 * AJAX 下载请求处理
 */
function super_attachments_download_handler() {
    // 验证 nonce
    check_ajax_referer('super_attachments_nonce', 'nonce');

    // 获取附件 ID
    $attachment_id = intval($_POST['attachment_id']);
    if (empty($attachment_id)) {
        wp_send_json_error(['message' => '无效的附件 ID。']);
    }

    // 调用下载处理逻辑
    $result = super_attachments_process_download($attachment_id);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    // 返回成功响应
    wp_send_json_success($result);
}

/**
 * AJAX 预览请求处理
 */
add_action('wp_ajax_super_attachments_preview_attachment', 'super_attachments_preview_attachment_handler');
add_action('wp_ajax_nopriv_super_attachments_preview_attachment', 'super_attachments_preview_attachment_handler');

function super_attachments_preview_attachment_handler() {
    // 验证 nonce
    if (!isset($_REQUEST['attachment_id'])) {
        wp_die('无效的附件 ID。');
    }

    $attachment_id = intval($_REQUEST['attachment_id']);
    if (empty($attachment_id)) {
        wp_die('无效的附件 ID。');
    }

    // 获取附件信息
    $attachment = super_attachments_get_attachment($attachment_id);
    if (!$attachment) {
        wp_die('未找到附件记录。');
    }

    // 验证文件 URL 是否有效
    $file_url = esc_url($attachment->file_url);
    if (empty($file_url)) {
        wp_die('附件的文件 URL 无效。');
    }

    // 增加下载次数（如果预览也需要记录下载次数）
    super_attachments_increment_download_count($attachment_id);

    // 重定向到文件 URL
    wp_redirect($file_url);
    exit;
}

/**
 * 渲染中间页
 */
add_action('template_redirect', 'super_attachments_render_download_page');
function super_attachments_render_download_page() {
    $attachment_id = get_query_var('super_attachment_download_page');
    if (!$attachment_id) {
        return; // 不是中间页请求，忽略
    }

    $attachment_id = intval($attachment_id);
    if (empty($attachment_id)) {
        wp_die('无效的附件 ID。');
    }

    // 获取附件信息
    $attachment = super_attachments_get_attachment($attachment_id);
    if (!$attachment) {
        wp_die('附件未找到或已被删除。');
    }

    // 渲染中间页内容
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>正在请求下载...</title>
        <script src="<?php echo includes_url('/js/jquery/jquery.min.js'); ?>"></script>
    </head>
    <body>
        <h1>正在请求下载中，请稍候...</h1>
        <script>
            jQuery(document).ready(function ($) {
                $.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    method: "POST",
                    data: {
                        action: "super_attachments_download",
                        nonce: "<?php echo wp_create_nonce('super_attachments_nonce'); ?>",
                        attachment_id: <?php echo $attachment_id; ?>
                    },
                    success: function (response) {
                        if (response.success) {
                            // 触发文件下载
                            const downloadLink = document.createElement("a");
                            downloadLink.href = response.data.file_url;
                            downloadLink.download = response.data.file_name;
                            downloadLink.click();
                            window.close(); // 关闭中间页
                        } else {
                            alert(response.data.message || "下载失败，请稍后重试。");
                            window.close(); // 关闭中间页
                        }
                    },
                    error: function () {
                        alert("网络错误，请稍后重试。");
                        window.close(); // 关闭中间页
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}
