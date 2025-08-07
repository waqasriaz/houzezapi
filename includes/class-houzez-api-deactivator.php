<?php
/**
 * Fired during plugin deactivation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Deactivator {
    /**
     * Deactivate the plugin.
     */
    public static function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('houzez_api_warm_cache');
    }
} 