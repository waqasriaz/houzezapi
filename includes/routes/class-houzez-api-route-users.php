<?php
/**
 * Users Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Users extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'users';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get current user profile
        register_rest_route($this->namespace, '/' . $this->rest_base . '/me', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Users', 'get_user_profile'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Get current user profile
        register_rest_route($this->namespace, '/' . $this->rest_base . '/package', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Users', 'get_user_package'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Get current user's properties
        register_rest_route($this->namespace, '/' . $this->rest_base . '/my-properties', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Users', 'get_my_properties'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Update user profile
        register_rest_route($this->namespace, '/' . $this->rest_base . '/profile', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array('Houzez_API_Endpoint_Users', 'update_user_profile'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Upload profile image
        register_rest_route($this->namespace, '/' . $this->rest_base . '/upload-avatar', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Users', 'upload_avatar'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Delete user profile
        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete-profile', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Users', 'delete_profile'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Admin: Get any user's profile
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<user_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Users', 'get_user_profile'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Admin: Update any user's profile
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<user_id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array('Houzez_API_Endpoint_Users', 'update_user_profile'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Admin: Delete user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<user_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Users', 'delete_user'),
            'permission_callback' => array('Houzez_API_Auth', 'can_manage_admin_only'),
        ));

        // Admin: List all users
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Users', 'list_users'),
            'permission_callback' => array('Houzez_API_Auth', 'can_manage_admin_only'),
        ));

        // Duplicate property
        // register_rest_route($this->namespace, '/' . $this->rest_base . '/duplicate-property/(?P<property_id>\d+)', array(
        //     'methods' => 'POST',
        //     'callback' => array('Houzez_API_Endpoint_Users', 'duplicate_property'),
        //     'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        // ));

        // property actions (Admin/Editor only)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/property-actions', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Users', 'property_actions'),
            'permission_callback' => array('Houzez_API_Auth', 'can_manage_posts'),
        )); 

        // Admin/Editor: Approve property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/approve-property/(?P<property_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Users', 'approve_property'),
            'permission_callback' => array('Houzez_API_Auth', 'can_manage_posts'),
        ));

        // Admin/Editor: Disapprove property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/disapprove-property/(?P<property_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Users', 'disapprove_property'),
            'permission_callback' => array('Houzez_API_Auth', 'can_manage_posts'),
        ));

        // Put property on hold
        register_rest_route($this->namespace, '/' . $this->rest_base . '/hold-property/(?P<property_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Users', 'hold_property'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Make property live
        register_rest_route($this->namespace, '/' . $this->rest_base . '/live-property/(?P<property_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Users', 'live_property'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Mark property as sold
        register_rest_route($this->namespace, '/' . $this->rest_base . '/mark-sold-property/(?P<property_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Users', 'mark_sold_property'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Get user's favorite properties
        register_rest_route($this->namespace, '/' . $this->rest_base . '/favorite-properties', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Users', 'get_favorite_properties'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
            'args' => array(
                'paged' => array(
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 10,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                )
            )
        ));

        // Get user's invoices
        register_rest_route($this->namespace, '/' . $this->rest_base . '/invoices', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Users', 'get_invoices'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
            'args' => array(
                'paged' => array(
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 20,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'invoice_status' => array(
                    'required' => false,
                    'type' => 'string'
                ),
                'invoice_type' => array(
                    'required' => false,
                    'type' => 'string'
                ),
                'start_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date'
                ),
                'end_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date'
                ),
                'mine' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false
                )
            )
        ));

        // Get single invoice
        register_rest_route($this->namespace, '/' . $this->rest_base . '/invoices/(?P<invoice_id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Users', 'get_invoices'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Delete invoice
        register_rest_route($this->namespace, '/' . $this->rest_base . '/invoices/(?P<invoice_id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array('Houzez_API_Endpoint_Users', 'delete_invoice'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));

        // Get user's saved searches
        register_rest_route($this->namespace, '/' . $this->rest_base . '/saved-searches', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Users', 'get_saved_searches'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
            'args' => array(
                'paged' => array(
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 10,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                )
            )
        ));

        // Delete saved search
        register_rest_route($this->namespace, '/' . $this->rest_base . '/saved-searches/(?P<search_id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array('Houzez_API_Endpoint_Users', 'delete_saved_search'),
            'permission_callback' => array('Houzez_API_Auth', 'can_access_user_data'),
        ));
    }

    /**
     * Initialize the class
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
} 