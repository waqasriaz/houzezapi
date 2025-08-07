<?php
/**
 * Auth Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Auth extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'auth';

    /**
     * Register routes
     */
    public function register_routes() {
        // Register endpoint
        register_rest_route($this->namespace, '/' . $this->rest_base . '/register', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Auth', 'register_user'),
            'permission_callback' => '__return_true',
        ));

        // Login endpoint
        register_rest_route($this->namespace, '/' . $this->rest_base . '/login', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Auth', 'login_user'),
            'permission_callback' => '__return_true',
        ));

        // Password reset endpoint
        register_rest_route($this->namespace, '/' . $this->rest_base . '/reset-password', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Auth', 'reset_password'),
            'permission_callback' => '__return_true',
        ));

        // Logout endpoint
        register_rest_route($this->namespace, '/' . $this->rest_base . '/logout', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Auth', 'logout_user'),
            'permission_callback' => '__return_true',
        ));
    }


    /**
     * Initialize the class
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
} 