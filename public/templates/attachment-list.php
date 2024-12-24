<?php
/**
 * 前台附件列表模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取当前文章的附件
global $wpdb;
$table_name = esc_sql($wpdb->prefix . 'super_attachments'); // 确保表名安全
$attachments = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$table_name` WHERE post_id = %d", get_the_ID()));

// 如果没有附件，不渲染列表
if (empty($attachments)) {
    return;
}
?>
<div class="attachment-list">
    <h3>附件下载</h3>
    <table>
        <thead>
            <tr>
                <th>附件名称</th>
                <th>文件大小</th>
                <th>上传时间</th>
                <th>下载次数</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($attachments as $attachment) : ?>
                <tr data-attachment-id="<?php echo esc_attr($attachment->id); ?>">
                    <td><?php echo esc_html($attachment->resource_name); ?></td>
                    <td><?php echo esc_html($attachment->file_size); ?></td>
                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($attachment->upload_time))); ?></td>
                    <td><?php echo intval($attachment->download_count); ?> 次</td>
                    <td>
                        <?php if (is_user_logged_in()) : ?>
                            <!-- 绑定正确的 attachment_id -->
                            <button class="download-button" data-attachment-id="<?php echo esc_attr($attachment->id); ?>">下载</button>
                            <!--<button class="preview-button" data-attachment-id="<?php echo esc_attr($attachment->id); ?>">预览</button>-->
                        <?php else : ?>
                            <span class="permission-required">请登录后下载        </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
