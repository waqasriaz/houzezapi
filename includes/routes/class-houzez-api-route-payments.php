<?php
/**
 * Payment Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Payments extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'payments';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get payment mode
        register_rest_route($this->namespace, '/' . $this->rest_base . '/mode', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Payments', 'get_payment_mode'),
            'permission_callback' => '__return_true',
        ));

        // Get payment settings
        register_rest_route($this->namespace, '/' . $this->rest_base . '/settings', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Payments', 'get_payment_settings'),
            'permission_callback' => '__return_true',
        ));

        // Get membership packages
        register_rest_route($this->namespace, '/' . $this->rest_base . '/packages', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Payments', 'get_packages'),
            'permission_callback' => '__return_true',
        ));

        // Get specific package details
        register_rest_route($this->namespace, '/' . $this->rest_base . '/packages/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Payments', 'get_package_details'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        // Get per listing prices
        register_rest_route($this->namespace, '/' . $this->rest_base . '/per-listing', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Payments', 'get_per_listing_prices'),
            'permission_callback' => '__return_true',
        ));

        // Get user's current package/membership status
        register_rest_route($this->namespace, '/' . $this->rest_base . '/user-status', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Payments', 'get_user_payment_status'),
            'permission_callback' => array($this, 'check_authentication'),
        ));

        // Get payment methods
        register_rest_route($this->namespace, '/' . $this->rest_base . '/methods', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Payments', 'get_payment_methods'),
            'permission_callback' => '__return_true',
        ));

        // Create payment intent/session
        register_rest_route($this->namespace, '/' . $this->rest_base . '/create-session', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Payments', 'create_payment_session'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'payment_type' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return in_array($param, array('membership', 'per_listing', 'featured'));
                    }
                ),
            ),
        ));

        // Cancel membership subscription
        register_rest_route($this->namespace, '/' . $this->rest_base . '/cancel-subscription', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Payments', 'cancel_subscription'),
            'permission_callback' => array($this, 'check_authentication'),
        ));

        // Cancel Stripe subscription
        register_rest_route($this->namespace, '/' . $this->rest_base . '/cancel-stripe', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Payments', 'cancel_stripe_subscription'),
            'permission_callback' => array($this, 'check_authentication'),
        ));

        // Cancel PayPal subscription
        register_rest_route($this->namespace, '/' . $this->rest_base . '/cancel-paypal', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Payments', 'cancel_paypal_subscription'),
            'permission_callback' => array($this, 'check_authentication'),
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