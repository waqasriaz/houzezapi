<?php
/**
 * Notifications Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Notifications extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'notifications';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get all notifications
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'get_notifications'),
            'permission_callback' => array($this, 'is_user_logged_in'),
            'args' => $this->get_collection_params()
        ));
        
        // Get single notification
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'get_notification'),
            'permission_callback' => array($this, 'is_user_logged_in'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Create notification (admin only)
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'create_notification'),
            'permission_callback' => array($this, 'is_admin'),
            'args' => $this->get_notification_args()
        ));
        
        // Mark as read
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/read', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'mark_as_read'),
            'permission_callback' => array($this, 'is_user_logged_in')
        ));
        
        // Mark multiple as read
        register_rest_route($this->namespace, '/' . $this->rest_base . '/mark-read', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'mark_multiple_as_read'),
            'permission_callback' => array($this, 'is_user_logged_in'),
            'args' => array(
                'ids' => array(
                    'required' => true,
                    'type' => 'array',
                    'items' => array(
                        'type' => 'integer'
                    )
                )
            )
        ));
        
        // Mark all as read
        register_rest_route($this->namespace, '/' . $this->rest_base . '/mark-all-read', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'mark_all_as_read'),
            'permission_callback' => array($this, 'is_user_logged_in')
        ));
        
        // Delete notification
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'delete_notification'),
            'permission_callback' => array($this, 'is_user_logged_in')
        ));
        
        // Delete multiple notifications
        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete-multiple', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'delete_multiple_notifications'),
            'permission_callback' => array($this, 'is_user_logged_in'),
            'args' => array(
                'ids' => array(
                    'required' => true,
                    'type' => 'array',
                    'items' => array(
                        'type' => 'integer'
                    )
                )
            )
        ));
        
        // Get unread count
        register_rest_route($this->namespace, '/' . $this->rest_base . '/unread-count', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'get_unread_count'),
            'permission_callback' => array($this, 'is_user_logged_in')
        ));
        
        // Get notification preferences
        register_rest_route($this->namespace, '/' . $this->rest_base . '/preferences', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'get_preferences'),
            'permission_callback' => array($this, 'is_user_logged_in')
        ));
        
        // Update notification preferences
        register_rest_route($this->namespace, '/' . $this->rest_base . '/preferences', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'update_preferences'),
            'permission_callback' => array($this, 'is_user_logged_in'),
            'args' => $this->get_preference_args()
        ));
        
        // Subscribe to push notifications
        register_rest_route($this->namespace, '/' . $this->rest_base . '/subscribe', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'subscribe_push_notifications'),
            'permission_callback' => array($this, 'is_user_logged_in'),
            'args' => array(
                'device_token' => array(
                    'required' => true,
                    'type' => 'string'
                ),
                'device_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('ios', 'android', 'web')
                )
            )
        ));
        
        // Unsubscribe from push notifications
        register_rest_route($this->namespace, '/' . $this->rest_base . '/unsubscribe', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Notifications', 'unsubscribe_push_notifications'),
            'permission_callback' => array($this, 'is_user_logged_in'),
            'args' => array(
                'device_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('ios', 'android', 'web')
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

    /**
     * Check if user is logged in
     */
    public function is_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Check if user is admin
     */
    public function is_admin() {
        return current_user_can('manage_options');
    }

    /**
     * Get collection parameters
     */
    private function get_collection_params() {
        return array(
            'page' => array(
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1
            ),
            'per_page' => array(
                'type' => 'integer',
                'default' => 20,
                'minimum' => 1,
                'maximum' => 100
            ),
            'type' => array(
                'type' => 'string',
                'enum' => Houzez_API_Endpoint_Notifications::NOTIFICATION_TYPES
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('all', 'read', 'unread'),
                'default' => 'all'
            ),
            'priority' => array(
                'type' => 'string',
                'enum' => array_keys(Houzez_API_Endpoint_Notifications::PRIORITIES)
            ),
            'category' => array(
                'type' => 'string'
            ),
            'date_from' => array(
                'type' => 'string',
                'format' => 'date'
            ),
            'date_to' => array(
                'type' => 'string',
                'format' => 'date'
            )
        );
    }

    /**
     * Get notification arguments
     */
    private function get_notification_args() {
        return array(
            'user_id' => array(
                'type' => 'integer'
            ),
            'user_email' => array(
                'type' => 'string',
                'format' => 'email'
            ),
            'title' => array(
                'required' => true,
                'type' => 'string'
            ),
            'message' => array(
                'required' => true,
                'type' => 'string'
            ),
            'type' => array(
                'required' => true,
                'type' => 'string',
                'enum' => Houzez_API_Endpoint_Notifications::NOTIFICATION_TYPES
            ),
            'priority' => array(
                'type' => 'string',
                'enum' => array_keys(Houzez_API_Endpoint_Notifications::PRIORITIES),
                'default' => 'medium'
            ),
            'data' => array(
                'type' => 'object'
            )
        );
    }

    /**
     * Get preference arguments
     */
    private function get_preference_args() {
        return array(
            'push_enabled' => array(
                'type' => 'boolean'
            ),
            'email_enabled' => array(
                'type' => 'boolean'
            ),
            'email_frequency' => array(
                'type' => 'string',
                'enum' => array('instant', 'daily', 'weekly')
            ),
            'disabled_types' => array(
                'type' => 'array',
                'items' => array(
                    'type' => 'string',
                    'enum' => Houzez_API_Endpoint_Notifications::NOTIFICATION_TYPES
                )
            ),
            'quiet_hours' => array(
                'type' => 'object',
                'properties' => array(
                    'enabled' => array(
                        'type' => 'boolean'
                    ),
                    'start' => array(
                        'type' => 'string',
                        'pattern' => '^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$'
                    ),
                    'end' => array(
                        'type' => 'string',
                        'pattern' => '^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$'
                    )
                )
            )
        );
    }
} 