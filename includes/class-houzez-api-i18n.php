<?php
/**
 * Define the internationalization functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_i18n {
    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'houzez-api',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
} 