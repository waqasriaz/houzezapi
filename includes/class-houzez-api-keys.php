<?php
/**
 * API Keys Class
 *
 * @package Houzez_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Keys extends Houzez_API_Base {
    /**
     * Table name for API keys
     *
     * @var string
     */
    private static $table_name;

    /**
     * Initialize the class
     */
    public function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'houzez_api_keys';
    }

    /**
     * Get all API keys
     *
     * @return array
     */
    public static function get_all_keys() {
        global $wpdb;
        
        $keys = $wpdb->get_results(
            "SELECT * FROM " . self::$table_name . " ORDER BY created_at DESC",
            ARRAY_A
        );

        return $keys ?: array();
    }

    /**
     * Get a single API key by key string
     *
     * @param string $api_key
     * @return array|null
     */
    public static function get_key($api_key) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::$table_name . " WHERE api_key = %s",
                $api_key
            ),
            ARRAY_A
        );
    }

    /**
     * Generate new API key
     *
     * @param string $app_name
     * @param string $description
     * @param int $expiry_days
     * @return string|false
     */
    public static function generate_key($app_name, $description = '', $expiry_days = 0) {
        global $wpdb;
        
        // Generate unique API key
        do {
            $api_key = wp_generate_password(32, false);
        } while (self::get_key($api_key));

        // Calculate expiry date
        $expires_at = null;
        if ($expiry_days > 0) {
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        }

        // Insert new key
        $wpdb->insert(
            self::$table_name,
            array(
                'user_id' => get_current_user_id(),
                'api_key' => $api_key,
                'app_name' => $app_name,
                'description' => $description,
                'status' => 'active',
                'expires_at' => $expires_at
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );

        if ($wpdb->insert_id) {
            return $api_key;
        }

        return false;
    }

    /**
     * Revoke an API key
     *
     * @param string $api_key
     * @return int|false
     */
    public static function revoke_key($api_key) {
        global $wpdb;
        
        return $wpdb->update(
            self::$table_name,
            array('status' => 'inactive'),
            array('api_key' => $api_key),
            array('%s'),
            array('%s')
        );
    }

    /**
     * Activate an API key
     *
     * @param string $api_key
     * @return int|false
     */
    public static function activate_key($api_key) {
        global $wpdb;
        
        return $wpdb->update(
            self::$table_name,
            array('status' => 'active'),
            array('api_key' => $api_key),
            array('%s'),
            array('%s')
        );
    }

    /**
     * Delete an API key
     *
     * @param string $api_key
     * @return int|false
     */
    public static function delete_key($api_key) {
        global $wpdb;
        
        return $wpdb->delete(
            self::$table_name,
            array('api_key' => $api_key),
            array('%s')
        );
    }

    /**
     * Update last used timestamp
     *
     * @param string $api_key
     * @return int|false
     */
    public static function update_last_access($api_key) {
        global $wpdb;
        
        return $wpdb->update(
            self::$table_name,
            array(
                'last_used' => current_time('mysql'),
                'usage_count' => $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COALESCE(usage_count, 0) + 1 FROM " . self::$table_name . " WHERE api_key = %s",
                        $api_key
                    )
                )
            ),
            array('api_key' => $api_key),
            array('%s', '%d'),
            array('%s')
        );
    }

    /**
     * Validate API key
     *
     * @param string $api_key
     * @return bool
     */
    public static function validate_key($api_key) {
        global $wpdb;
        
        $key = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::$table_name . "
                WHERE api_key = %s 
                AND status = 'active'
                AND (expires_at IS NULL OR expires_at > NOW())",
                $api_key
            )
        );

        if ($key) {
            self::update_last_access($api_key);
            return true;
        }

        return false;
    }

    /**
     * Clean expired keys
     *
     * @return int|false
     */
    public static function clean_expired_keys() {
        global $wpdb;
        
        return $wpdb->query(
            "UPDATE " . self::$table_name . "
            SET status = 'expired'
            WHERE status = 'active'
            AND expires_at IS NOT NULL
            AND expires_at <= NOW()"
        );
    }
} 