<?php
/**
 * Meta Box 附件表格模板
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="attachments-meta-box">
    <table class="widefat fixed">
        <thead>
            <tr>
                <th>附件名称</th>
                <th>文件大小</th>
                <th>上传时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($attachments)) : ?>
                <?php foreach ($attachments as $attachment) : ?>
                    <tr data-attachment-id="<?php echo esc_attr($attachment->id); ?>">
                        <td>
                            <input 
                                type="text" 
                                class="attachment-name" 
                                value="<?php echo esc_attr($attachment->resource_name); ?>" 
                                placeholder="请输入附件名称"
                            />
                        </td>
                        <td><?php echo esc_html($attachment->file_size); ?></td>
                        <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($attachment->upload_time))); ?></td>
                        <td>
                            <!--<button class="preview-button" data-attachment-id="<?php echo esc_attr($attachment->id); ?>">预览</button>-->
                            <button class="download-button" data-attachment-id="<?php echo esc_attr($attachment->id); ?>">下载</button>
                            <button type="button" class="button delete-button">删除</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4">尚未上传附件。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <button type="button" class="button upload-button">上传附件</button>
</div>
