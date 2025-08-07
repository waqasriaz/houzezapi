<?php
/**
 * Bulk Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Bulk extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'bulk';

    /**
     * Register routes
     */
    public function register_routes() {
        // Bulk create properties
        register_rest_route($this->namespace, '/' . $this->rest_base . '/properties', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Bulk', 'create_properties'),
            'permission_callback' => array($this, 'create_item_permissions_check')
        ));

        // Bulk update properties
        register_rest_route($this->namespace, '/' . $this->rest_base . '/properties', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array('Houzez_API_Endpoint_Bulk', 'update_properties'),
            'permission_callback' => array($this, 'update_item_permissions_check')
        ));

        // Bulk delete properties
        register_rest_route($this->namespace, '/' . $this->rest_base . '/properties', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Bulk', 'delete_properties'),
            'permission_callback' => array($this, 'delete_item_permissions_check')
        ));

        // Bulk create agents
        register_rest_route($this->namespace, '/' . $this->rest_base . '/agents', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Bulk', 'create_agents'),
            'permission_callback' => array($this, 'create_item_permissions_check')
        ));

        // Bulk update agents
        register_rest_route($this->namespace, '/' . $this->rest_base . '/agents', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array('Houzez_API_Endpoint_Bulk', 'update_agents'),
            'permission_callback' => array($this, 'update_item_permissions_check')
        ));

        // Bulk delete agents
        register_rest_route($this->namespace, '/' . $this->rest_base . '/agents', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Bulk', 'delete_agents'),
            'permission_callback' => array($this, 'delete_item_permissions_check')
        ));

        // Bulk create agencies
        register_rest_route($this->namespace, '/' . $this->rest_base . '/agencies', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Bulk', 'create_agencies'),
            'permission_callback' => array($this, 'create_item_permissions_check')
        ));

        // Bulk update agencies
        register_rest_route($this->namespace, '/' . $this->rest_base . '/agencies', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array('Houzez_API_Endpoint_Bulk', 'update_agencies'),
            'permission_callback' => array($this, 'update_item_permissions_check')
        ));

        // Bulk delete agencies
        register_rest_route($this->namespace, '/' . $this->rest_base . '/agencies', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Bulk', 'delete_agencies'),
            'permission_callback' => array($this, 'delete_item_permissions_check')
        ));
    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request) {
        return Houzez_API_Auth::check_api_permission($request);
    }

    /**
     * Check if a given request has access to create items
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public function create_item_permissions_check($request) {
        return Houzez_API_Auth::check_api_permission($request);
    }

    /**
     * Check if a given request has access to update a specific item
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request) {
        return Houzez_API_Auth::check_api_permission($request);
    }

    /**
     * Check if a given request has access to delete a specific item
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check($request) {
        return Houzez_API_Auth::check_api_permission($request);
    }

    /**
     * Initialize the class
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
} 