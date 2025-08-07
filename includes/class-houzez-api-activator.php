<?php
/**
 * Fired during plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Activator {
    /**
     * Database version constant
     */
    const DB_VERSION = '20240321000000'; // Based on our first migration timestamp

    /**
     * Create core tables
     */
    private static function create_core_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Create migrations table
        $migrations_table = $wpdb->prefix . 'houzez_api_migrations';
        $sql = "CREATE TABLE IF NOT EXISTS $migrations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            migration varchar(191) NOT NULL,
            batch int NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY migration (migration)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Activate the plugin.
     */
    public static function activate() {
        // Create core tables first
        self::create_core_tables();

        // Create migrations directory if it doesn't exist
        $migrations_dir = HOUZEZ_API_PLUGIN_DIR . 'includes/migrations';
        if (!file_exists($migrations_dir)) {
            wp_mkdir_p($migrations_dir);
        }

        // Clear any existing caches
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_%'");

        // Set initial plugin activation flag
        if (!get_option('houzez_api_activated')) {
            update_option('houzez_api_activated', current_time('mysql'));
        }

        // Check if this is an update
        $current_version = get_option('houzez_api_version');
        $is_update = $current_version && $current_version !== HOUZEZ_API_VERSION;

        // Set plugin version
        update_option('houzez_api_version', HOUZEZ_API_VERSION);

        // Set initial database version if not set
        if (!get_option('houzez_api_db_version')) {
            update_option('houzez_api_db_version', self::DB_VERSION);
        }

        // Initialize and run migrations
        require_once HOUZEZ_API_PLUGIN_DIR . 'includes/class-houzez-api-migrations.php';
        $migrations = new Houzez_API_Migrations();
        $migrations->init();

        // If this is an update, force check for new migrations
        if ($is_update) {
            $migrations->check_migrations();
        }
    }

    /**
     * Check for plugin updates and run migrations if needed
     */
    public static function check_update() {
        $current_version = get_option('houzez_api_version');
        
        // If version hasn't changed, no need to check
        if ($current_version === HOUZEZ_API_VERSION) {
            return;
        }

        // Update version and run migrations
        update_option('houzez_api_version', HOUZEZ_API_VERSION);
        
        require_once HOUZEZ_API_PLUGIN_DIR . 'includes/class-houzez-api-migrations.php';
        $migrations = new Houzez_API_Migrations();
        $migrations->init();
    }
} 