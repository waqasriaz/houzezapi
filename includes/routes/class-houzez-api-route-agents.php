<?php
/**
 * Agents Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Agents extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'agents';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get agents
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Agents', 'get_agents'),
            'permission_callback' => '__return_true',
        ));

        // Advanced agent search with multiple criteria
        register_rest_route($this->namespace, '/' . $this->rest_base . '/search', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Agents', 'search_agents'),
            'permission_callback' => '__return_true'
        ));

        // Get single agent
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Agents', 'get_agent'),
            'permission_callback' => '__return_true',
        ));

        // Create agent
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Agents', 'create_agent'),
            'permission_callback' => '__return_true'
        ));

        // Update agent
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array('Houzez_API_Endpoint_Agents', 'update_agent'),
            'permission_callback' => '__return_true'
        ));

        // Delete agent
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Agents', 'delete_agent'),
            'permission_callback' => '__return_true'
        ));

        // Get agent properties
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/properties', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Agents', 'get_agent_properties'),
            'permission_callback' => '__return_true'
        ));

        // Get agent categories
        register_rest_route($this->namespace, '/' . $this->rest_base . '/agent-categories', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Agents', 'get_agent_categories'),
            'permission_callback' => '__return_true'
        ));

        // Get agent cities
        register_rest_route($this->namespace, '/' . $this->rest_base . '/agent-cities', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Agents', 'get_agent_cities'),
            'permission_callback' => '__return_true'
        ));
        
        // Contact agent
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/contact', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Agents', 'contact_agent'),
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
                'agent_type' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Get reviews for a specific agent
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/reviews', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Agents', 'get_agent_reviews'),
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
        
        // Add review for a specific agent
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/reviews', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Agents', 'add_agent_review'),
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