<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// First get all tables from migrations table
$migrations_table = $wpdb->prefix . 'houzez_api_migrations';

// Get all migrations in reverse order (newest first) to handle dependencies
$migrations = $wpdb->get_results("SELECT migration FROM {$migrations_table} ORDER BY migration DESC");

if ($migrations) {
    // Load each migration file to get table names
    $plugin_dir = plugin_dir_path(__FILE__);

    foreach ($migrations as $migration) {
        $migration_file = $plugin_dir . 'includes/migrations/' . $migration->migration;
        
        if (file_exists($migration_file)) {
            // Include the migration file
            require_once $migration_file;
            
            // Get class name from filename
            $parts = explode('_', str_replace('.php', '', $migration->migration));
            $timestamp = implode('', array_slice($parts, 0, 6)); // Get YYYYMMDDHHMMSS
            $description = implode('', array_slice($parts, 6));
            $description = str_replace(' ', '', ucwords(str_replace('_', ' ', $description)));
            $class_name = 'Houzez_API_Migration_' . $timestamp . $description;
            
            // If class exists and has down method, call it to drop the table
            if (class_exists($class_name)) {
                $migration_obj = new $class_name();
                if (method_exists($migration_obj, 'down')) {
                    try {
                        $migration_obj->down(); // This will drop the table
                        error_log("Successfully rolled back migration: " . $migration->migration);
                    } catch (Exception $e) {
                        error_log("Error rolling back migration " . $migration->migration . ": " . $e->getMessage());
                    }
                }
            }
        }
    }
}

// Finally drop the migrations table itself
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}houzez_api_migrations");

// Delete plugin options
$options = array(
    'houzez_api_db_version',
    'houzez_api_version',
    'houzez_api_activated',
    'houzez_api_settings',
    'houzez_api_cache_stats',
    'houzez_api_last_cache_prune',
    'houzez_api_last_cache_warm'
);

foreach ($options as $option) {
    delete_option($option);
}

// Clear all transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_houzez_api_%'");

// Clear scheduled hooks
wp_clear_scheduled_hook('houzez_api_warm_cache'); 