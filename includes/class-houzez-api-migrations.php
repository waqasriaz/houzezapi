<?php
/**
 * Migrations Class
 *
 * @package Houzez_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Migrations extends Houzez_API_Base {
    /**
     * Migrations directory
     *
     * @var string
     */
    private static $migrations_dir;

    /**
     * Initialize migrations
     */
    public function init() {
        self::$migrations_dir = HOUZEZ_API_PLUGIN_DIR . 'includes/migrations';
        self::check_migrations();
    }

    /**
     * Check and run pending migrations
     */
    public function check_migrations() {
        global $wpdb;
        $migrations_table = $wpdb->prefix . 'houzez_api_migrations';
        
        // Get all migration files
        $files = scandir(self::$migrations_dir);
        $pending_migrations = array();
        
        // Get current batch number
        $current_batch = $wpdb->get_var("SELECT COALESCE(MAX(batch), 0) FROM $migrations_table");
        $next_batch = $current_batch + 1;
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.+\.php$/', $file)) {
                continue;
            }
            
            // Check if migration has been run
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $migrations_table WHERE migration = %s",
                $file
            ));
            
            if (!$exists) {
                $pending_migrations[$file] = $next_batch;
            }
        }
        
        if (!empty($pending_migrations)) {
            self::run_migrations($pending_migrations);
        }
    }

    /**
     * Run migrations
     *
     * @param array $migrations
     */
    private static function run_migrations($migrations) {
        global $wpdb;
        $migrations_table = $wpdb->prefix . 'houzez_api_migrations';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            $latest_timestamp = '0';
            
            foreach ($migrations as $file => $batch) {
                require_once self::$migrations_dir . '/' . $file;
                
                // Get timestamp and name parts
                $parts = explode('_', $file);
                $timestamp = implode('', array_slice($parts, 0, 6)); // Get YYYYMMDDHHMMSS
                
                // Keep track of latest timestamp
                if ($timestamp > $latest_timestamp) {
                    $latest_timestamp = $timestamp;
                }
                
                // Get the description part (everything after the timestamp)
                $description = implode('', array_slice($parts, 6));
                $description = str_replace('.php', '', $description);
                
                // Convert description to proper case (e.g., create_api_keys_table -> CreateApiKeysTable)
                $description = str_replace(' ', '', ucwords(str_replace('_', ' ', $description)));
                
                // Construct the class name
                $class_name = 'Houzez_API_Migration_' . $timestamp . $description;
                
                if (!class_exists($class_name)) {
                    throw new Exception("Migration class $class_name not found in $file");
                }
                
                $migration = new $class_name();
                
                if (!method_exists($migration, 'up')) {
                    throw new Exception("Migration $class_name does not have an up() method");
                }
                
                // Run migration
                $migration->up();
                
                // Record migration
                $wpdb->insert(
                    $migrations_table,
                    array(
                        'migration' => $file,
                        'batch' => $batch
                    ),
                    array('%s', '%d')
                );
                
                error_log("Completed migration: $file");
            }
            
            // Update database version to latest migration timestamp only
            if ($latest_timestamp !== '0') {
                update_option('houzez_api_db_version', $latest_timestamp);
            }
            
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Houzez API Migration Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Roll back migrations
     *
     * @param int $steps Number of batches to rollback
     */
    public static function rollback($steps = 1) {
        global $wpdb;
        $migrations_table = $wpdb->prefix . 'houzez_api_migrations';
        
        // Get batches to rollback
        $batches = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT batch FROM $migrations_table ORDER BY batch DESC LIMIT %d",
            $steps
        ));
        
        if (empty($batches)) {
            return;
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($batches as $batch) {
                // Get migrations for this batch
                $migrations = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $migrations_table WHERE batch = %d ORDER BY id DESC",
                    $batch
                ));
                
                foreach ($migrations as $migration) {
                    require_once self::$migrations_dir . '/' . $migration->migration;
                    
                    // Get class name from filename
                    $parts = explode('_', $migration->migration);
                    $timestamp = implode('', array_slice($parts, 0, 6));
                    $description = str_replace('.php', '', implode('', array_slice($parts, 6)));
                    
                    // Convert description to proper case
                    $description = str_replace(' ', '', ucwords(str_replace('_', ' ', $description)));
                    
                    $class_name = 'Houzez_API_Migration_' . $timestamp . $description;
                    
                    if (!class_exists($class_name)) {
                        throw new Exception("Migration class $class_name not found");
                    }
                    
                    $instance = new $class_name();
                    
                    if (!method_exists($instance, 'down')) {
                        throw new Exception("Migration $class_name does not have a down() method");
                    }
                    
                    // Run rollback
                    $instance->down();
                    
                    // Remove migration record
                    $wpdb->delete(
                        $migrations_table,
                        array('id' => $migration->id),
                        array('%d')
                    );
                    
                    error_log("Rolled back migration: " . $migration->migration);
                }
            }
            
            // Get the latest remaining migration timestamp
            $latest_migration = $wpdb->get_var(
                "SELECT migration FROM $migrations_table ORDER BY migration DESC LIMIT 1"
            );
            
            if ($latest_migration) {
                $parts = explode('_', $latest_migration);
                $timestamp = implode('', array_slice($parts, 0, 6));
                update_option('houzez_api_db_version', $timestamp);
            } else {
                // If no migrations left, set to initial version
                update_option('houzez_api_db_version', '20240321000000');
            }
            
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Houzez API Migration Rollback Error: ' . $e->getMessage());
            throw $e;
        }
    }
} 