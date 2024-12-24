jQuery(document).ready(function ($) {
    // 检查是否正确注入 SuperAttachmentsAjax
    if (typeof SuperAttachmentsAjax === "undefined") {
        console.error("SuperAttachmentsAjax 未定义，请检查 wp_localize_script 是否正确设置。");
        return;
    }

    console.log("SuperAttachmentsAjax:", SuperAttachmentsAjax);

    // 下载附件按钮点击事件
    $(".download-button").on("click", function (e) {
        e.preventDefault(); // 阻止默认跳转行为

        const button = $(this);
        const attachmentId = button.data("attachment-id");

        if (!attachmentId) {
            console.error("附件 ID 未定义，请检查 HTML 数据绑定。");
            alert("无效的附件，请联系管理员。");
            return;
        }

        // 打开中间页
        const middlePageUrl = `/super-attachments/download/${attachmentId}`;
        window.open(middlePageUrl, "_blank");
    });


    // 删除附件按钮点击事件
    $(document).on("click", ".delete-button", function () {
        const button = $(this);
        const row = button.closest("tr");
        const attachmentId = row.data("attachment-id");

        if (!confirm("确定要删除该附件吗？")) {
            return;
        }

        button.prop("disabled", true).text("正在删除...");

        $.ajax({
            url: SuperAttachmentsAjax.ajax_url,
            type: "POST",
            data: {
                action: "super_attachments_delete_attachment",
                nonce: SuperAttachmentsAjax.nonce,
                attachment_id: attachmentId,
            },
            success: function (response) {
                button.prop("disabled", false).text("删除");
                if (response.success) {
                    alert("附件已成功删除！");
                    row.remove(); // 删除表格行
                } else {
                    alert("删除失败：" + response.data.message);
                }
            },
            error: function () {
                button.prop("disabled", false).text("删除");
                alert("删除失败，请稍后重试。");
            },
        });
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

    // 预览附件
    $(document).on("click", ".preview-button", function () {
        const button = $(this);
        const row = button.closest("tr");
        const attachmentId = row.data("attachment-id");

        if (!attachmentId) {
            console.error("附件 ID 未定义，请检查 HTML 数据绑定。");
            alert("无效的附件，请联系管理员。");
            return;
        }

        window.open(
            `${SuperAttachmentsAjax.ajax_url}?action=super_attachments_preview_attachment&attachment_id=${attachmentId}`,
            "_blank"
        );
    });

    // 刷新附件表格内容
    function refreshAttachmentTable() {
        console.log("正在刷新附件表格...");
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
                    console.log("附件表格已刷新。");
                } else {
                    console.error("刷新附件表格失败：", response.data.message);
                }
            },
            error: function () {
                console.error("刷新附件表格时发生错误。");
            },
        });
    }
});
