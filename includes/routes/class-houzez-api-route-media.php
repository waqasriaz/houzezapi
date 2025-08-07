<?php

/**
 * Media Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Media extends Houzez_API_Route
{
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'media';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // Upload media
        register_rest_route($this->namespace, '/' . $this->rest_base . '/upload', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Media', 'upload_media'),
            'permission_callback' => array('Houzez_API_Auth', 'can_upload_media'),
        ));

        // Get media
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Media', 'get_media'),
            'permission_callback' => array('Houzez_API_Auth', 'can_view_media'),
        ));

        // Delete media
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Media', 'delete_media'),
            'permission_callback' => array('Houzez_API_Auth', 'can_delete_media')
        ));

        // Update media
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array('Houzez_API_Endpoint_Media', 'update_media'),
            'permission_callback' => array('Houzez_API_Auth', 'can_edit_media')
        ));
    }



    /**
     * Initialize the class
     */
    public function init()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
}
