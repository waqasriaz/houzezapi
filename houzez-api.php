<?php
/**
 * Plugin Name: Houzez API
 * Plugin URI: https://themeforest.net/user/favethemes
 * Description: REST API endpoints for Houzez Real Estate WordPress Theme
 * Version: 1.0.0
 * Author: Waqas Riaz
 * Author URI: https://themeforest.net/user/favethemes
 * Text Domain: houzez-api
 * Domain Path: /languages
 *
 * @package Houzez_API
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HOUZEZ_API_VERSION', '1.0.0');
define('HOUZEZ_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HOUZEZ_API_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load the autoloader
require_once HOUZEZ_API_PLUGIN_DIR . 'includes/class-houzez-api-autoloader.php';

/**
 * The code that runs during plugin activation.
 */
function activate_houzez_api() {
    require_once HOUZEZ_API_PLUGIN_DIR . 'includes/class-houzez-api-activator.php';
    Houzez_API_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_houzez_api() {
    require_once HOUZEZ_API_PLUGIN_DIR . 'includes/class-houzez-api-deactivator.php';
    Houzez_API_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_houzez_api');
register_deactivation_hook(__FILE__, 'deactivate_houzez_api');



/**
 * Initialize the plugin
 */
function houzez_api_init() {
    $plugin = new Houzez_API();
    $plugin->run();
}

add_action('plugins_loaded', 'houzez_api_init');