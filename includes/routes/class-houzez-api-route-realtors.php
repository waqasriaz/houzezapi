<?php
/**
 * Realtors Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Realtors extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'realtor';

    /**
     * Register routes
     */
    public function register_routes() {
        // Contact realtor (agent or agency)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/contact', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Realtors', 'contact_realtor'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'email' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_email($param);
                    },
                    'sanitize_callback' => 'sanitize_email'
                ),
                'message' => array(
                    'required' => true,
                    'sanitize_callback' => 'wp_kses_post'
                ),
                'phone' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'user_type' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'realtor_type' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
    }

    /**
     * Initialize the class
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
}
