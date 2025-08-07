<?php
/**
 * The main plugin class
 *
 * @package Houzez_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API {
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var Houzez_API_Loader
     */
    protected $loader;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        $this->version = HOUZEZ_API_VERSION;
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Initialize the loader
        $this->loader = new Houzez_API_Loader();

        // Load admin settings only in admin area
        if (is_admin()) {
            require_once HOUZEZ_API_PLUGIN_DIR . 'admin/settings/class-houzez-api-settings.php';
            new Houzez_API_Settings();
        }
    }

    /**
     * Register all of the hooks related to the plugin functionality
     */
    private function init_hooks() {
        // Initialize API Keys
        $api_keys = new Houzez_API_Keys();
        $api_keys->init();

        // Initialize Cache
        // $cache = new Houzez_API_Cache();
        // $cache->init();

        // Initialize Migrations
        // $migrations = new Houzez_API_Migrations();
        // $migrations->init();

        // Initialize Helpers
        $helpers = new Houzez_API_Helper();
        $helpers->init();

        // Initialize Endpoints
        $agents_endpoint = new Houzez_API_Endpoint_Agents();
        $agents_endpoint->init();

        $properties_endpoint = new Houzez_API_Endpoint_Properties();
        $properties_endpoint->init();

        $agencies_endpoint = new Houzez_API_Endpoint_Agencies();
        $agencies_endpoint->init();

        $media_endpoint = new Houzez_API_Endpoint_Media();
        $media_endpoint->init();    
        
        $bulk_endpoint = new Houzez_API_Endpoint_Bulk();
        $bulk_endpoint->init();

        $auth_endpoint = new Houzez_API_Endpoint_Auth();
        $auth_endpoint->init();
        
        $users_endpoint = new Houzez_API_Endpoint_Users();
        $users_endpoint->init();
        
        $realtors_endpoint = new Houzez_API_Endpoint_Realtors();
        $realtors_endpoint->init();
        
        $payments_endpoint = new Houzez_API_Endpoint_Payments();
        $payments_endpoint->init();
        
        $social_login_endpoint = new Houzez_API_Endpoint_Social_Login();
        $social_login_endpoint->init();
        
        $notifications_endpoint = new Houzez_API_Endpoint_Notifications();
        $notifications_endpoint->init();
        
        // Initialize Push Notifications
        if (class_exists('Houzez_API_Push_Notifications')) {
            Houzez_API_Push_Notifications::get_instance();
        }
        
        // Check for plugin updates
        add_action('plugins_loaded', array('Houzez_API_Activator', 'check_update'));

        // Initialize AJAX Handler
        $ajax_handler = new Houzez_API_Ajax_Handler();
        $ajax_handler->init();
        
        // Load diagnostics in admin
        if (is_admin() && file_exists(HOUZEZ_API_PLUGIN_DIR . 'tests/test-notification-diagnostics.php')) {
            require_once HOUZEZ_API_PLUGIN_DIR . 'tests/test-notification-diagnostics.php';
        }

        // Initialize Routes
        $this->init_routes();
    }

    /**
     * Initialize API routes
     */
    private function init_routes() {
        $routes = array(
            new Houzez_API_Route_Auth(),
            new Houzez_API_Route_Properties(),
            new Houzez_API_Route_Agents(),
            new Houzez_API_Route_Agencies(),
            new Houzez_API_Route_Media(),
            new Houzez_API_Route_Bulk(),
            new Houzez_API_Route_Users(),
            new Houzez_API_Route_Realtors(),
            new Houzez_API_Route_Payments(),
            new Houzez_API_Route_Social_Login(),
            new Houzez_API_Route_Notifications()
        );

        foreach ($routes as $route) {
            $route->init();
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return Houzez_API_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
} 