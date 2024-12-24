<?php
/**
 * 数据库操作模块
 * 负责创建和删除超级附件插件的数据库表。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 创建数据库表
 */
function super_attachments_create_database() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'super_attachments';
    $charset_collate = $wpdb->get_charset_collate();

    // 创建附件数据库表
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT DEFAULT NULL,
        resource_name VARCHAR(255) NOT NULL,
        file_url TEXT NOT NULL,
        file_id INT NOT NULL,
        upload_time DATETIME NOT NULL,
        file_size VARCHAR(20) NOT NULL,
        download_count INT DEFAULT 0,
        temporary TINYINT DEFAULT 1, -- 是否为临时存储
        temp_time DATETIME NULL -- 临时存储时间
    ) $charset_collate;";

    // 执行数据库表创建操作
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/**
 * 删除数据库表
 */
function super_attachments_delete_database() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'super_attachments';
    // 删除数据库表
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

/**
 * 清理过期的临时附件
 * 删除超过有效期的临时附件（默认30分钟）
 */
function super_attachments_clean_temp_attachments() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'super_attachments';

    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE temporary = 1 AND temp_time < %s",
        date('Y-m-d H:i:s', strtotime('-30 minutes'))
    ));
}

// 注册定时任务清理
add_action('super_attachments_clean_temp_attachments_cron', 'super_attachments_clean_temp_attachments');
