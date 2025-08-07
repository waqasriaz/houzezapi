<?php
/**
 * Social Login Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Social_Login extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'social';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get Facebook OAuth URL
        register_rest_route($this->namespace, '/' . $this->rest_base . '/facebook/oauth-url', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Social_Login', 'get_facebook_oauth_url'),
            'permission_callback' => '__return_true',
        ));

        // Facebook login/register with code
        register_rest_route($this->namespace, '/' . $this->rest_base . '/facebook/auth', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Social_Login', 'facebook_auth'),
            'permission_callback' => '__return_true',
            'args' => array(
                'code' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Facebook authorization code',
                ),
                'state' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Facebook state parameter',
                ),
            ),
        ));

        // Facebook login with access token (alternative method)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/facebook/login', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Social_Login', 'facebook_login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'access_token' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Facebook access token',
                ),
            ),
        ));

        // Get Google OAuth URL
        register_rest_route($this->namespace, '/' . $this->rest_base . '/google/oauth-url', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Social_Login', 'get_google_oauth_url'),
            'permission_callback' => '__return_true',
        ));

        // Google login/register with code
        register_rest_route($this->namespace, '/' . $this->rest_base . '/google/auth', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Social_Login', 'google_auth'),
            'permission_callback' => '__return_true',
            'args' => array(
                'code' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Google authorization code',
                ),
                'redirect_uri' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Redirect URI used in OAuth flow',
                ),
            ),
        ));

        // Google login with access token (alternative method)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/google/login', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Social_Login', 'google_login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'access_token' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Google access token',
                ),
            ),
        ));

        // Link Facebook account to existing user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/facebook/link', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Social_Login', 'link_facebook_account'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'facebook_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Facebook user ID',
                ),
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Email to link Facebook account to',
                ),
            ),
        ));

        // Get social login configuration
        register_rest_route($this->namespace, '/' . $this->rest_base . '/config', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Social_Login', 'get_social_config'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Check authentication
     */
    public function check_authentication($request) {
        return Houzez_API_Auth::check_authentication($request);
    }

    /**
     * Initialize the class
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
} 