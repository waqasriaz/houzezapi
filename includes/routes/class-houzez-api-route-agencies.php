<?php
/**
 * Agencies Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Agencies extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'agencies';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get agencies
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Agencies', 'get_agencies'),
            'permission_callback' => '__return_true',
        ));

        // Advanced agent search with multiple criteria
        register_rest_route($this->namespace, '/' . $this->rest_base . '/search', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Agencies', 'search_agencies'),
            'permission_callback' => '__return_true'
        ));

        // Get single agency
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Agencies', 'get_agency'),
            'permission_callback' => '__return_true',
        ));

        // Create agency
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Agencies', 'create_agency'),
            'permission_callback' => '__return_true'
        ));

        // Update agency
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array('Houzez_API_Endpoint_Agencies', 'update_agency'),
            'permission_callback' => '__return_true'
        ));

        // Delete agency
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Agencies', 'delete_agency'),
            'permission_callback' => '__return_true'
        ));

        // Get agency agents
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/agents', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Agencies', 'get_agency_agents'),
            'permission_callback' => '__return_true'
        ));

        // Get agency properties
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/properties', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Agencies', 'get_agency_properties'),
            'permission_callback' => '__return_true'
        ));

        // Get reviews for a specific agency
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/reviews', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Agencies', 'get_agency_reviews'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'sort_by' => array(
                    'required' => false,
                    'default' => 'd_date',
                    'validate_callback' => function($param) {
                        return in_array($param, array('a_rating', 'd_rating', 'a_date', 'd_date'));
                    }
                ),
                'paged' => array(
                    'required' => false,
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 10,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Add review for a specific agency
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/reviews', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Agencies', 'add_agency_review'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'rating' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 1 && $param <= 5;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'title' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'content' => array(
                    'required' => true,
                    'sanitize_callback' => 'wp_kses_post'
                ),
                'email' => array(
                    'required' => false,
                    'validate_callback' => function($param) {
                        return is_email($param);
                    },
                    'sanitize_callback' => 'sanitize_email'
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