<?php
class Houzez_API_Migration_20240321000001CreateApiKeysTable {
    
    /**
     * Run the migration
     */
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'houzez_api_keys';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            api_key varchar(64) NOT NULL,
            app_name varchar(191) NOT NULL,
            description text,
            status varchar(20) NOT NULL DEFAULT 'active',
            last_used datetime DEFAULT NULL,
            usage_count bigint(20) NOT NULL DEFAULT 0,
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY api_key (api_key),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Reverse the migration
     */
    public function down() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'houzez_api_keys';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
} 