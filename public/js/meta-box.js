(function ($) {
    $(document).ready(function () {
        console.log("Meta-box.js loaded successfully."); // 调试日志

        // 检查 wp.media 是否可用
        if (typeof wp === "undefined" || typeof wp.media === "undefined") {
            console.error("wp.media is not available."); // 错误日志
            return;
        }

        let mediaUploader; // 媒体库实例

        // 动态绑定上传按钮事件
        $(document).on("click", ".upload-button", function (e) {
            e.preventDefault();
            console.log("Upload button clicked."); // 调试日志

            // 如果文章尚未保存为草稿
            if (SuperAttachmentsAjax.post_id === 0) {
                console.log("Post is not saved. Saving draft...");
                saveDraftAndUpload(); // 保存草稿后再处理附件上传
                return;
            }

            openMediaUploader(); // 打开媒体库窗口
        });

        function openMediaUploader() {
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: "选择附件", // 窗口标题
                button: {
                    text: "添加到附件", // 按钮文字
                },
                multiple: false, // 禁止多选
            });

            mediaUploader.on("select", function () {
                const attachment = mediaUploader.state().get("selection").first().toJSON();
                console.log("Selected attachment:", attachment); // 调试日志

                // 上传附件到文章
                uploadAttachment(attachment);
            });

            mediaUploader.open();
        }

        function saveDraftAndUpload() {
            $.ajax({
                url: SuperAttachmentsAjax.ajax_url,
                type: "POST",
                data: {
                    action: "super_attachments_save_draft",
                    nonce: SuperAttachmentsAjax.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        console.log("Draft saved successfully. Post ID:", response.data.post_id); // 调试日志
                        SuperAttachmentsAjax.post_id = response.data.post_id; // 更新全局 post_id
                        openMediaUploader(); // 打开媒体库窗口
                    } else {
                        console.error("Failed to save draft:", response.data.message); // 错误日志
                        alert("无法保存草稿：" + response.data.message);
                    }
                },
                error: function () {
                    console.error("Error while saving draft."); // 错误日志
                    alert("保存草稿时发生错误，请稍后重试。");
                },
            });
        }

        function uploadAttachment(attachment) {
            console.log("Uploading attachment to Post ID:", SuperAttachmentsAjax.post_id); // 调试日志
            $.ajax({
                url: SuperAttachmentsAjax.ajax_url,
                type: "POST",
                data: {
                    action: "super_attachments_add_attachment",
                    nonce: SuperAttachmentsAjax.nonce,
                    post_id: SuperAttachmentsAjax.post_id, // 使用更新后的 post_id
                    file_id: attachment.id,
                    file_name: attachment.title,
                    file_url: attachment.url,
                    file_size: attachment.filesizeHumanReadable || "未知",
                },
                success: function (response) {
                    if (response.success) {
                        alert("附件已成功添加！");
                        refreshAttachmentTable(); // 刷新附件表格
                    } else {
                        console.error("Attachment upload failed:", response.data.message); // 错误日志
                        alert("上传失败：" + response.data.message);
                    }
                },
                error: function () {
                    console.error("Error while uploading attachment."); // 错误日志
                    alert("上传失败，请稍后重试。");
                },
            });
        }

        // 下载附件按钮事件
        $(document).on("click", ".download-button", function (e) {
            e.preventDefault(); // 阻止默认行为

            const button = $(this);
            const attachmentId = button.data("attachment-id");

            if (!attachmentId) {
                console.error("附件 ID 未定义，请检查 HTML 数据绑定。");
                alert("无效的附件，请联系管理员。");
                return;
            }

            // 构造跳转 URL
            const downloadUrl = `/super-attachments/download/${attachmentId}`;

            console.log(`跳转到下载页面：${downloadUrl}`);
            
            // 跳转到下载中间页
            window.location.href = downloadUrl;
        });

        // 删除附件按钮事件
        $(document).on("click", ".delete-button", function () {
            const row = $(this).closest("tr");
            const attachmentId = row.data("attachment-id");

            if (confirm("确定要删除该附件吗？")) {
                $.ajax({
                    url: SuperAttachmentsAjax.ajax_url,
                    type: "POST",
                    data: {
                        action: "super_attachments_delete_attachment",
                        nonce: SuperAttachmentsAjax.nonce,
                        attachment_id: attachmentId,
                    },
                    success: function (response) {
                        if (response.success) {
                            alert("附件已成功删除！");
                            refreshAttachmentTable(); // 刷新附件表格
                        } else {
                            alert("删除失败：" + response.data.message);
                        }
                    },
                    error: function () {
                        alert("删除失败，请稍后重试。");
                    },
                });
            }
        });

        // 编辑附件名称
        $(document).on("blur keypress", ".attachment-name", function (e) {
            if (e.type === "keypress" && e.which !== 13) {
                return; // 仅在按下回车键时触发
            }

            const input = $(this);
            const row = input.closest("tr");
            const attachmentId = row.data("attachment-id");
            const newName = input.val();

            $.ajax({
                url: SuperAttachmentsAjax.ajax_url,
                type: "POST",
                data: {
                    action: "super_attachments_update_attachment_name",
                    nonce: SuperAttachmentsAjax.nonce,
                    attachment_id: attachmentId,
                    new_name: newName,
                },
                success: function (response) {
                    if (response.success) {
                        alert("附件名称已更新！");
                    } else {
                        alert("更新失败：" + response.data.message);
                    }
                },
                error: function () {
                    alert("更新失败，请稍后重试。");
                },
            });
        });

        // 预览附件按钮事件
        $(document).on("click", ".preview-button", function () {
            const row = $(this).closest("tr");
            const attachmentId = row.data("attachment-id");

            window.open(
                SuperAttachmentsAjax.ajax_url +
                    "?action=super_attachments_preview_attachment&attachment_id=" +
                    attachmentId,
                "_blank"
            );
        });

        // 刷新附件表格内容
        function refreshAttachmentTable() {
            console.log("Refreshing attachment table..."); // 调试日志
            $.ajax({
                url: SuperAttachmentsAjax.ajax_url,
                type: "POST",
                data: {
                    action: "super_attachments_refresh_table",
                    post_id: SuperAttachmentsAjax.post_id,
                    nonce: SuperAttachmentsAjax.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        $(".attachments-meta-box").html(response.data.table_html);
                        console.log("Attachment table refreshed.");
                    } else {
                        console.error("Failed to refresh attachment table:", response.data.message);
                    }
                },
                error: function () {
                    console.error("Error while refreshing attachment table.");
                },
            });
        }
    });
})(jQuery);
