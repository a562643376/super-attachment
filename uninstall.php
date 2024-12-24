<?php
/**
 * 插件卸载脚本
 * 删除数据库表并清理插件相关的数据。
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 定义数据库表名
$table_name = esc_sql($wpdb->prefix . 'super_attachments');

// 删除插件数据库表
$wpdb->query("DROP TABLE IF EXISTS `$table_name`");

// 清理插件相关选项
delete_option('super_attachments_rules_flushed');