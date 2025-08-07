<?php
/**
 * Notifications Endpoint
 *
 * @package Houzez_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Endpoint_Notifications extends Houzez_API_Base {

    /**
     * Custom post type for notifications
     */
    const POST_TYPE = 'houzez_notification';
    
    /**
     * Notification types
     */
    const NOTIFICATION_TYPES = [
        // Property & Listings
        'property_saved',
        'property_matched',
        'property_price_drop',
        'property_status_change',
        'property_matching',
        'price_update',
        
        // Contact & Communication
        'inquiry_received',
        'messages',
        'message_received',
        'property_agent_contact',
        'property_schedule_tour',
        'contact_agent',
        'contact_agency',
        'contact_owner',
        'property_report',
        'review',
        'review_received',
        
        // Scheduling & Tours
        'showing_scheduled',
        'showing_reminder',
        
        // User Management
        'new_user_register',
        'admin_new_user_register',
        'admin_user_register_approval',
        'user_approved',
        'user_declined', 
        'user_suspended',
        'verification_status',
        'membership_cancelled',
        'agent_assigned',
        
        // Payments & Finance
        'payment_received',
        'payment_confirmation',
        'new_wire_transfer',
        'admin_new_wire_transfer',
        'recurring_payment',
        'purchase_activated_pack',
        'purchase_activated',
        
        // Listing Management
        'listing_approved',
        'listing_expired',
        'listing_disapproved',
        'paid_submission_listing',
        'admin_paid_submission_listing',
        'featured_submission_listing',
        'admin_featured_submission_listing',
        'free_submission_listing',
        'admin_free_submission_listing',
        'admin_update_listing',
        'free_listing_expired',
        'featured_listing_expired',
        'admin_expired_listings',
        
        // Admin & System
        'report',
        'system_update',
        'document_uploaded',
        
        // Marketing & Matching
        'marketing_promotion',
        'matching_submissions',
    ];
    
    /**
     * Notification priorities
     */
    const PRIORITIES = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'urgent' => 4
    ];

    /**
     * Initialize the endpoint
     */
    public function init() {
        add_action('init', [$this, 'register_notification_post_type']);
        add_action('init', [$this, 'register_notification_taxonomies']);
        add_action('houzez_send_notification', [$this, 'capture_houzez_notification'], 10, 1);
        add_action('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'custom_column'], 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', [$this, 'sortable_columns']);
        $this->setup_hooks();
    }

    private function setup_hooks() {
        // Hook into Houzez email notifications
        add_action('houzez_send_notification', array($this, 'capture_houzez_notification'));
        
        // Hook into user verification events
        add_action('houzez_after_verification_request', array($this, 'capture_verification_submitted'), 10, 2);
        add_action('houzez_after_approve_verification', array($this, 'capture_verification_approved'), 10, 2);
        add_action('houzez_after_reject_verification', array($this, 'capture_verification_rejected'), 10, 3);
        add_action('houzez_after_revoke_verification', array($this, 'capture_verification_revoked'), 10, 2);
        add_action('houzez_after_request_info', array($this, 'capture_verification_info_requested'), 10, 3);
        add_action('houzez_after_additional_info_submission', array($this, 'capture_verification_info_submitted'), 10, 2);
    }

    /**
     * Register notification post type
     */
    public function register_notification_post_type() {
        // Check if post type already exists from other plugins
        if (post_type_exists(self::POST_TYPE)) {
            return;
        }
        
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Notifications', 'houzez-api'),
                'singular_name' => __('Notification', 'houzez-api'),
                'view_item' => __('View Notification', 'houzez-api'),
                'view_items' => __('View Notifications', 'houzez-api'),
                'search_items' => __('Search Notifications', 'houzez-api'),
                'not_found' => __('No notifications found', 'houzez-api'),
                'not_found_in_trash' => __('No notifications found in trash', 'houzez-api'),
                'all_items' => __('All Notifications', 'houzez-api'),
                'menu_name' => __('Notifications', 'houzez-api')
            ],
            'public' => false,
            'has_archive' => false,
            'rewrite' => false,
            'supports' => ['title', 'custom-fields'],
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'can_export' => true,
            'delete_with_user' => false,
            'menu_icon' => 'dashicons-bell',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'capabilities' => [
                'create_posts' => 'do_not_allow',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'do_not_allow',
                'edit_published_posts' => 'do_not_allow',
                'delete_posts' => 'manage_options',
                'delete_others_posts' => 'manage_options',
                'delete_published_posts' => 'manage_options',
                'delete_private_posts' => 'manage_options',
                'read_private_posts' => 'manage_options',
                'publish_posts' => 'do_not_allow'
            ]
        ]);
    }

    /**
     * Register notification taxonomies
     */
    public function register_notification_taxonomies() {
        // Notification category taxonomy
        register_taxonomy('notification_category', self::POST_TYPE, [
            'labels' => [
                'name' => __('Categories', 'houzez-api'),
                'singular_name' => __('Category', 'houzez-api')
            ],
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => true,
            'rewrite' => false
        ]);
    }

    /**
     * Capture notifications from Houzez
     */
    public function capture_houzez_notification($args) {
        if (!isset($args['to']) || empty($args['to'])) {
            return;
        }
        
        $emails = $args['to'];
        
        // Handle array of emails
        if (is_array($emails)) {
            foreach ($emails as $email) {
                $this->process_notification_for_email($email, $args);
            }
        } else {
            $this->process_notification_for_email($emails, $args);
        }
    }
    
    /**
     * Process notification for a single email
     */
    private function process_notification_for_email($email, $args) {
        // Validate email
        $email = is_email($email);
        if (!$email) {
            return;
        }
        
        $user = get_user_by('email', $email);
        
        $title = isset($args['title']) ? $args['title'] : '';
        $message = isset($args['message']) ? $args['message'] : '';
        $type = isset($args['type']) ? $args['type'] : 'general';
        
        // Add standard args for tag replacement
        $args['website_url'] = get_option('siteurl');
        $args['website_name'] = get_option('blogname');
        $args['user_email'] = $email;
        
        if ($user) {
            $args['username'] = $user->user_login;
        }
        
        // Replace tags in title and message
        foreach ($args as $key => $val) {
            if (is_string($val)) {
                $title = str_replace('%' . $key, $val, $title);
                $message = str_replace('%' . $key, $val, $message);
            }
        }
        
        // If type is not in our allowed types, use default
        if (!in_array($type, self::NOTIFICATION_TYPES)) {
            $type = 'inquiry_received'; // Default to inquiry for most contact forms
        }
        
        // Clean up the message
        $message = $this->clean_notification_message($message);
        
        // Extract additional data
        $extra_data = [];
        foreach ($args as $key => $value) {
            if (!in_array($key, ['title', 'message', 'type', 'to'])) {
                $extra_data[$key] = $value;
            }
        }
        
        // Determine priority based on type
        $priority = $this->get_priority_by_type($type);
        
        // Create notification in our system
        $notification_id = $this->create_notification_post($user->ID, $title, $message, $type, $priority, $extra_data);
    }

    /**
     * Create notification post
     */
    public function create_notification_post($user_id, $title, $message, $type, $priority = 'medium', $extra_data = []) {
        // Check if notification preferences allow this type
        $preferences = get_user_meta($user_id, 'houzez_notification_preferences', true);
        if ($preferences && isset($preferences['disabled_types']) && in_array($type, $preferences['disabled_types'])) {
            return;
        }
        
        $notification_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $message,
            'post_status' => 'publish',
            'post_type' => self::POST_TYPE,
            'meta_input' => [
                'user_id' => $user_id,
                'user_email' => get_userdata($user_id)->user_email,
                'notification_type' => $type,
                'priority' => $priority,
                'is_read' => '0',
                'extra_data' => json_encode($extra_data)
            ]
        ]);
        
        // Set category based on type
        $category = $this->get_category_by_type($type);
        if ($category) {
            wp_set_object_terms($notification_id, $category, 'notification_category');
        }
        
        // Trigger action for other plugins to hook into
        do_action('houzez_api_notification_created', $notification_id, $user_id, $type, $extra_data);
        
        return $notification_id;
    }

    /**
     * Get notifications for API endpoint
     */
    public static function get_notifications($request) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        $type = $request->get_param('type');
        $status = $request->get_param('status');
        $priority = $request->get_param('priority');
        $category = $request->get_param('category');
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        $args = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => [
                [
                    'key' => 'user_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        // Filter by type
        if ($type) {
            $args['meta_query'][] = [
                'key' => 'notification_type',
                'value' => $type,
                'compare' => '='
            ];
        }
        
        // Filter by read status
        if ($status === 'unread') {
            $args['meta_query'][] = [
                'key' => 'is_read',
                'value' => '0',
                'compare' => '='
            ];
        } elseif ($status === 'read') {
            $args['meta_query'][] = [
                'key' => 'is_read',
                'value' => '1',
                'compare' => '='
            ];
        }
        
        // Filter by priority
        if ($priority) {
            $args['meta_query'][] = [
                'key' => 'priority',
                'value' => $priority,
                'compare' => '='
            ];
        }
        
        // Filter by category
        if ($category) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'notification_category',
                    'field' => 'slug',
                    'terms' => $category
                ]
            ];
        }
        
        // Filter by date range
        if ($date_from || $date_to) {
            $date_query = [];
            if ($date_from) {
                $date_query['after'] = $date_from;
            }
            if ($date_to) {
                $date_query['before'] = $date_to;
            }
            $args['date_query'] = [$date_query];
        }
        
        $query = new WP_Query($args);
        $notifications = [];
        
        foreach ($query->posts as $post) {
            $notifications[] = self::format_notification($post);
        }
        
        // Update last checked time
        update_user_meta($user_id, 'houzez_last_notification_check', current_time('timestamp'));
        
        // Get notification statistics
        $stats = self::get_notification_stats($user_id);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $page,
                'stats' => $stats
            ]
        ], 200);
    }

    /**
     * Get single notification
     */
    public static function get_notification($request) {
        $notification_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $notification = get_post($notification_id);
        
        if (!$notification || $notification->post_type !== self::POST_TYPE) {
            return new WP_Error('not_found', __('Notification not found', 'houzez-api'), ['status' => 404]);
        }
        
        $notification_user_id = get_post_meta($notification_id, 'user_id', true);
        
        if ($notification_user_id != $user_id) {
            return new WP_Error('forbidden', __('You do not have permission to view this notification', 'houzez-api'), ['status' => 403]);
        }
        
        // Mark as read
        update_post_meta($notification_id, 'is_read', '1');
        update_post_meta($notification_id, 'read_at', current_time('mysql'));
        
        return new WP_REST_Response([
            'success' => true,
            'data' => self::format_notification($notification)
        ], 200);
    }

    /**
     * Create notification via API
     */
    public static function create_notification($request) {
        // Check admin permissions
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'forbidden',
                esc_html__('You do not have permission to create notifications.', 'houzez-api'),
                array('status' => 403)
            );
        }
        
        $user_email = $request->get_param('user_email');
        $user_id = $request->get_param('user_id');
        $title = $request->get_param('title');
        $message = $request->get_param('message');
        $type = $request->get_param('type');
        $priority = $request->get_param('priority') ?: 'medium';
        $data = $request->get_param('data') ?: [];
        
        // Get user by email or ID
        if ($user_email) {
            $user = get_user_by('email', $user_email);
            if ($user) {
                $user_id = $user->ID;
            }
        }
        
        if (!$user_id) {
            return new WP_Error('invalid_user', __('Invalid user', 'houzez-api'), ['status' => 400]);
        }
        
        $instance = new self();
        
        // Create notification
        $notification_id = $instance->create_notification_post($user_id, $title, $message, $type, $priority, $data);
        
        if (!$notification_id) {
            return new WP_Error('creation_failed', __('Failed to create notification', 'houzez-api'), ['status' => 500]);
        }
        
        // Trigger houzez_send_notification for compatibility
        $notificationArgs = [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'to' => get_userdata($user_id)->user_email
        ];
        $notificationArgs = array_merge($notificationArgs, $data);
        
        do_action('houzez_send_notification', $notificationArgs);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => $notification_id,
                'message' => __('Notification created successfully', 'houzez-api')
            ]
        ], 201);
    }

    /**
     * Mark notification as read
     */
    public static function mark_as_read($request) {
        $notification_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $notification = get_post($notification_id);
        
        if (!$notification || $notification->post_type !== self::POST_TYPE) {
            return new WP_Error('not_found', __('Notification not found', 'houzez-api'), ['status' => 404]);
        }
        
        $notification_user_id = get_post_meta($notification_id, 'user_id', true);
        
        if ($notification_user_id != $user_id) {
            return new WP_Error('forbidden', __('You do not have permission to update this notification', 'houzez-api'), ['status' => 403]);
        }
        
        update_post_meta($notification_id, 'is_read', '1');
        update_post_meta($notification_id, 'read_at', current_time('mysql'));
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Notification marked as read', 'houzez-api')
        ], 200);
    }

    /**
     * Mark multiple notifications as read
     */
    public static function mark_multiple_as_read($request) {
        $ids = $request->get_param('ids');
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $updated = 0;
        
        foreach ($ids as $notification_id) {
            $notification = get_post($notification_id);
            
            if (!$notification || $notification->post_type !== self::POST_TYPE) {
                continue;
            }
            
            $notification_user_id = get_post_meta($notification_id, 'user_id', true);
            
            if ($notification_user_id != $user_id) {
                continue;
            }
            
            update_post_meta($notification_id, 'is_read', '1');
            update_post_meta($notification_id, 'read_at', current_time('mysql'));
            $updated++;
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => sprintf(__('%d notifications marked as read', 'houzez-api'), $updated),
            'updated' => $updated
        ], 200);
    }

    /**
     * Mark all notifications as read
     */
    public static function mark_all_as_read($request) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $args = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'user_id',
                    'value' => $user_id,
                    'compare' => '='
                ],
                [
                    'key' => 'is_read',
                    'value' => '0',
                    'compare' => '='
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        $updated = 0;
        
        foreach ($query->posts as $notification_id) {
            update_post_meta($notification_id, 'is_read', '1');
            update_post_meta($notification_id, 'read_at', current_time('mysql'));
            $updated++;
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => sprintf(__('%d notifications marked as read', 'houzez-api'), $updated),
            'updated' => $updated
        ], 200);
    }

    /**
     * Delete notification
     */
    public static function delete_notification($request) {
        $notification_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $notification = get_post($notification_id);
        
        if (!$notification || $notification->post_type !== self::POST_TYPE) {
            return new WP_Error('not_found', __('Notification not found', 'houzez-api'), ['status' => 404]);
        }
        
        $notification_user_id = get_post_meta($notification_id, 'user_id', true);
        
        if ($notification_user_id != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', __('You do not have permission to delete this notification', 'houzez-api'), ['status' => 403]);
        }
        
        $deleted = wp_delete_post($notification_id, true);
        
        if (!$deleted) {
            return new WP_Error('deletion_failed', __('Failed to delete notification', 'houzez-api'), ['status' => 500]);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Notification deleted successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Delete multiple notifications
     */
    public static function delete_multiple_notifications($request) {
        $ids = $request->get_param('ids');
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $deleted = 0;
        
        foreach ($ids as $notification_id) {
            $notification = get_post($notification_id);
            
            if (!$notification || $notification->post_type !== self::POST_TYPE) {
                continue;
            }
            
            $notification_user_id = get_post_meta($notification_id, 'user_id', true);
            
            if ($notification_user_id != $user_id && !current_user_can('manage_options')) {
                continue;
            }
            
            if (wp_delete_post($notification_id, true)) {
                $deleted++;
            }
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => sprintf(__('%d notifications deleted', 'houzez-api'), $deleted),
            'deleted' => $deleted
        ], 200);
    }

    /**
     * Get unread notification count
     */
    public static function get_unread_count($request) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $args = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'user_id',
                    'value' => $user_id,
                    'compare' => '='
                ],
                [
                    'key' => 'is_read',
                    'value' => '0',
                    'compare' => '='
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        $last_check = get_user_meta($user_id, 'houzez_last_notification_check', true);
        
        // Get counts by type
        $counts_by_type = [];
        foreach (self::NOTIFICATION_TYPES as $type_key) {
            $type_args = $args;
            $type_args['meta_query'][] = [
                'key' => 'notification_type',
                'value' => $type_key,
                'compare' => '='
            ];
            $type_query = new WP_Query($type_args);
            if ($type_query->found_posts > 0) {
                $counts_by_type[$type_key] = $type_query->found_posts;
            }
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'unread_count' => $query->found_posts,
                'counts_by_type' => $counts_by_type,
                'last_check' => $last_check ? date('Y-m-d H:i:s', $last_check) : null
            ]
        ], 200);
    }

    /**
     * Get notification preferences
     */
    public static function get_preferences($request) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $preferences = get_user_meta($user_id, 'houzez_notification_preferences', true);
        
        if (!$preferences) {
            $preferences = self::get_default_preferences();
        }
        
        // Add available notification types with labels
        $available_types = [];
        foreach (self::NOTIFICATION_TYPES as $type) {
            $available_types[$type] = self::get_notification_type_label($type);
        }
        $preferences['available_types'] = $available_types;
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $preferences
        ], 200);
    }

    /**
     * Update notification preferences
     */
    public static function update_preferences($request) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $preferences = $request->get_params();
        
        // Remove non-preference parameters
        unset($preferences['rest_route']);
        
        // Validate preferences
        $valid_preferences = [];
        
        if (isset($preferences['push_enabled'])) {
            $valid_preferences['push_enabled'] = (bool) $preferences['push_enabled'];
        }
        
        if (isset($preferences['email_enabled'])) {
            $valid_preferences['email_enabled'] = (bool) $preferences['email_enabled'];
        }
        
        if (isset($preferences['disabled_types']) && is_array($preferences['disabled_types'])) {
            $valid_preferences['disabled_types'] = array_intersect($preferences['disabled_types'], self::NOTIFICATION_TYPES);
        }
        
        if (isset($preferences['email_frequency'])) {
            $valid_preferences['email_frequency'] = in_array($preferences['email_frequency'], ['instant', 'daily', 'weekly']) ? $preferences['email_frequency'] : 'instant';
        }
        
        if (isset($preferences['quiet_hours']) && is_array($preferences['quiet_hours'])) {
            $valid_preferences['quiet_hours'] = [
                'enabled' => isset($preferences['quiet_hours']['enabled']) ? (bool) $preferences['quiet_hours']['enabled'] : false,
                'start' => isset($preferences['quiet_hours']['start']) ? $preferences['quiet_hours']['start'] : '22:00',
                'end' => isset($preferences['quiet_hours']['end']) ? $preferences['quiet_hours']['end'] : '08:00'
            ];
        }
        
        update_user_meta($user_id, 'houzez_notification_preferences', $valid_preferences);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Preferences updated successfully', 'houzez-api'),
            'data' => $valid_preferences
        ], 200);
    }

    /**
     * Subscribe to push notifications
     */
    public static function subscribe_push_notifications($request) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $device_token = $request->get_param('device_token');
        $device_type = $request->get_param('device_type');
        
        // Store device token
        $devices = get_user_meta($user_id, 'houzez_push_devices', true);
        if (!$devices) {
            $devices = [];
        }
        
        $devices[$device_type] = [
            'token' => $device_token,
            'subscribed_at' => current_time('mysql'),
            'active' => true
        ];
        
        update_user_meta($user_id, 'houzez_push_devices', $devices);
        
        // Also update OneSignal if available
        do_action('houzez_api_push_subscribe', $user_id, $device_token, $device_type);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Successfully subscribed to push notifications', 'houzez-api')
        ], 200);
    }

    /**
     * Unsubscribe from push notifications
     */
    public static function unsubscribe_push_notifications($request) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                esc_html__('Authentication required.', 'houzez-api'),
                array('status' => 401)
            );
        }
        
        $device_type = $request->get_param('device_type');
        
        $devices = get_user_meta($user_id, 'houzez_push_devices', true);
        
        if ($devices && isset($devices[$device_type])) {
            $devices[$device_type]['active'] = false;
            update_user_meta($user_id, 'houzez_push_devices', $devices);
        }
        
        // Also update OneSignal if available
        do_action('houzez_api_push_unsubscribe', $user_id, $device_type);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Successfully unsubscribed from push notifications', 'houzez-api')
        ], 200);
    }

    /**
     * Format notification for response
     */
    private static function format_notification($post) {
        $notification_id = $post->ID;
        $extra_data = json_decode(get_post_meta($notification_id, 'extra_data', true), true);
        if (!is_array($extra_data)) {
            $extra_data = [];
        }
        
        // Get categories
        $categories = wp_get_object_terms($notification_id, 'notification_category', ['fields' => 'names']);
        
        // Build action data based on notification type
        $type = get_post_meta($notification_id, 'notification_type', true);
        $action_data = self::build_action_data($type, $extra_data);
        
        // Parse notification data for structured mobile app consumption
        $parsed_data = self::parse_notification_data($type, $extra_data);
        
        // Get read status and timing
        $is_read = (bool) get_post_meta($notification_id, 'is_read', true);
        $read_at = get_post_meta($notification_id, 'read_at', true);
        $priority = get_post_meta($notification_id, 'priority', true);
        
        return [
            // Basic notification information
            'id' => $notification_id,
            'title' => $post->post_title,
            'message' => $post->post_content,
            'description' => $post->post_content, // Alternative field name for compatibility
            'type' => $type,
            'type_label' => self::get_notification_type_label($type),
            
            // Status and priority
            'priority' => $priority,
            'is_read' => $is_read,
            'read_at' => $read_at,
            
            // Timing information
            'date' => $post->post_date,
            'created_at' => $post->post_date,
            'created_at_gmt' => $post->post_date_gmt,
            'timestamp' => strtotime($post->post_date),
            'time_ago' => human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' ' . __('ago', 'houzez-api'),
            
            // User information
            'user_id' => get_post_meta($notification_id, 'user_id', true),
            'user_email' => get_post_meta($notification_id, 'user_email', true),
            
            // Categories and organization
            'categories' => $categories,
            'category' => !empty($categories) ? $categories[0] : 'general',
            
            /**
             * Data Fields Explanation:
             * 
             * 'data' = Raw extra data as originally stored (unprocessed)
             * 'extra_data' = Parsed and structured data by notification type (processed for mobile apps)
             * 'action' = Navigation/action data for mobile app screens and buttons
             */
            'data' => $extra_data, // Raw extra data from original source
            'extra_data' => $parsed_data, // Parsed and structured data by notification type
            'action' => $action_data, // Mobile navigation and action data
            
            // Mobile app specific fields
            'notification_id' => $notification_id,
            'badge_count' => self::get_unread_count_for_user(get_post_meta($notification_id, 'user_id', true)),
            
            // Additional fields for rich notifications
            'image_url' => isset($extra_data['image_url']) ? $extra_data['image_url'] : null,
            'deep_link' => self::build_deep_link($type, $parsed_data),
        ];
    }

    /**
     * Parse notification data for mobile app consumption
     */
    private static function parse_notification_data($type, $extra_data) {
        $parsed_data = [];
        
        switch ($type) {
            case 'review':
            case 'review_received':
                $parsed_data = [
                    'type' => $type,
                    'listing_id' => isset($extra_data['listing_id']) ? $extra_data['listing_id'] : null,
                    'listing_title' => isset($extra_data['listing_title']) ? $extra_data['listing_title'] : null,
                    'review_post_type' => isset($extra_data['review_post_type']) ? $extra_data['review_post_type'] : 'property'
                ];
                break;
                
            case 'matching_submissions':
                $parsed_data = [
                    'type' => $type,
                    'search_url' => isset($extra_data['search_url']) ? $extra_data['search_url'] : null,
                    'listing_count' => isset($extra_data['listing_count']) ? $extra_data['listing_count'] : 0
                ];
                break;
                
            case 'admin_free_submission_listing':
            case 'free_submission_listing':
            case 'admin_paid_submission_listing':
            case 'paid_submission_listing':
            case 'admin_featured_submission_listing':
            case 'featured_submission_listing':
            case 'listing_approved':
            case 'listing_expired':
            case 'listing_disapproved':
                $parsed_data = [
                    'type' => $type,
                    'listing_id' => isset($extra_data['listing_id']) ? $extra_data['listing_id'] : null,
                    'listing_title' => isset($extra_data['listing_title']) ? $extra_data['listing_title'] : null,
                    'listing_url' => isset($extra_data['listing_url']) ? $extra_data['listing_url'] : null
                ];
                break;
                
            case 'messages':
            case 'message_received':
                $parsed_data = [
                    'type' => $type,
                    'thread_id' => isset($extra_data['thread_id']) ? $extra_data['thread_id'] : null,
                    'property_id' => isset($extra_data['property_id']) ? $extra_data['property_id'] : null,
                    'property_title' => isset($extra_data['property_title']) ? $extra_data['property_title'] : null,
                    'sender_id' => isset($extra_data['sender_id']) ? $extra_data['sender_id'] : null,
                    'sender_display_name' => isset($extra_data['sender_display_name']) ? $extra_data['sender_display_name'] : null,
                    'sender_picture' => isset($extra_data['sender_picture']) ? $extra_data['sender_picture'] : null,
                    'receiver_id' => isset($extra_data['receiver_id']) ? $extra_data['receiver_id'] : null,
                    'receiver_display_name' => isset($extra_data['receiver_display_name']) ? $extra_data['receiver_display_name'] : null,
                    'receiver_picture' => isset($extra_data['receiver_picture']) ? $extra_data['receiver_picture'] : null
                ];
                break;
                
            case 'property_agent_contact':
            case 'property_schedule_tour':
            case 'contact_agent':
            case 'contact_agency':
            case 'contact_owner':
                $parsed_data = [
                    'type' => $type,
                    'sender_name' => isset($extra_data['sender_name']) ? $extra_data['sender_name'] : null,
                    'sender_email' => isset($extra_data['sender_email']) ? $extra_data['sender_email'] : null,
                    'sender_phone' => isset($extra_data['sender_phone']) ? $extra_data['sender_phone'] : null,
                    'property_id' => isset($extra_data['property_id']) ? $extra_data['property_id'] : null,
                    'property_title' => isset($extra_data['property_title']) ? $extra_data['property_title'] : null,
                    'property_link' => isset($extra_data['property_link']) ? $extra_data['property_link'] : null,
                    'sender_message' => isset($extra_data['sender_message']) ? $extra_data['sender_message'] : null,
                    'user_type' => isset($extra_data['user_type']) ? $extra_data['user_type'] : null
                ];
                
                // Additional data for tour scheduling
                if ($type === 'property_schedule_tour') {
                    $parsed_data['schedule_date'] = isset($extra_data['schedule_date']) ? $extra_data['schedule_date'] : null;
                    $parsed_data['schedule_time'] = isset($extra_data['schedule_time']) ? $extra_data['schedule_time'] : null;
                    $parsed_data['schedule_tour_type'] = isset($extra_data['schedule_tour_type']) ? $extra_data['schedule_tour_type'] : null;
                }
                break;
                
            case 'payment_received':
            case 'payment_confirmation':
            case 'new_wire_transfer':
            case 'recurring_payment':
                $parsed_data = [
                    'type' => $type,
                    'invoice_no' => isset($extra_data['invoice_no']) ? $extra_data['invoice_no'] : null,
                    'total_price' => isset($extra_data['total_price']) ? $extra_data['total_price'] : null,
                    'payment_details' => isset($extra_data['payment_details']) ? $extra_data['payment_details'] : null,
                    'merchant' => isset($extra_data['merchant']) ? $extra_data['merchant'] : null
                ];
                break;
                
            case 'new_user_register':
            case 'admin_new_user_register':
            case 'admin_user_register_approval':
            case 'membership_cancelled':
            case 'user_approved':
            case 'user_declined':
            case 'user_suspended':
                $parsed_data = [
                    'type' => $type,
                    'user_login_register' => isset($extra_data['user_login_register']) ? $extra_data['user_login_register'] : null,
                    'user_email_register' => isset($extra_data['user_email_register']) ? $extra_data['user_email_register'] : null,
                    'user_phone_register' => isset($extra_data['user_phone_register']) ? $extra_data['user_phone_register'] : null,
                    'admin_user_link' => isset($extra_data['admin_user_link']) ? $extra_data['admin_user_link'] : null
                ];
                break;
                
            default:
                $parsed_data = [
                    'type' => $type
                ];
                // Include any extra data as is
                foreach ($extra_data as $key => $value) {
                    $parsed_data[$key] = $value;
                }
                break;
        }
        
        return $parsed_data;
    }

    /**
     * Build action data based on notification type
     */
    private static function build_action_data($type, $extra_data) {
        // First parse the notification data
        $parsed_data = self::parse_notification_data($type, $extra_data);
        
        $action = [
            'type' => 'none',
            'label' => '',
            'url' => '',
            'data' => $parsed_data
        ];
        
        switch ($type) {
            // Messages and Communication
            case 'messages':
            case 'message_received':
                $action['type'] = 'navigate';
                $action['label'] = __('View Message', 'houzez-api');
                $action['screen'] = 'messages';
                if (isset($extra_data['thread_id'])) {
                    $action['data']['thread_id'] = $extra_data['thread_id'];
                }
                break;
                
            // Property Related Actions
            case 'property_saved':
            case 'property_matched':
            case 'property_matching':
            case 'property_price_drop':
            case 'price_update':
            case 'property_status_change':
                $action['type'] = 'navigate';
                $action['label'] = __('View Property', 'houzez-api');
                $action['screen'] = 'property_details';
                if (isset($extra_data['listing_id'])) {
                    $action['data']['property_id'] = $extra_data['listing_id'];
                }
                break;
                
            // Contact Forms and Inquiries
            case 'inquiry_received':
            case 'showing_scheduled':
            case 'property_agent_contact':
            case 'property_schedule_tour':
            case 'contact_agent':
            case 'contact_agency':
            case 'contact_owner':
                $action['type'] = 'navigate';
                $action['label'] = __('View Details', 'houzez-api');
                $action['screen'] = 'inquiries';
                break;
                
            // Reviews
            case 'review':
            case 'review_received':
                $action['type'] = 'navigate';
                $action['label'] = __('View Review', 'houzez-api');
                $action['screen'] = 'reviews';
                if (isset($extra_data['listing_id'])) {
                    $action['data']['property_id'] = $extra_data['listing_id'];
                }
                break;
                
            // Matching and Recommendations
            case 'matching_submissions':
                $action['type'] = 'url';
                $action['label'] = __('View Matches', 'houzez-api');
                if (isset($extra_data['search_url'])) {
                    $action['url'] = $extra_data['search_url'];
                }
                break;
                
            // Payment and Financial
            case 'payment_received':
            case 'payment_confirmation':
            case 'new_wire_transfer':
            case 'recurring_payment':
                $action['type'] = 'navigate';
                $action['label'] = __('View Invoice', 'houzez-api');
                $action['screen'] = 'invoices';
                if (isset($extra_data['invoice_id'])) {
                    $action['data']['invoice_id'] = $extra_data['invoice_id'];
                }
                break;
                
            // Listing Management
            case 'listing_expired':
            case 'listing_approved':
            case 'listing_disapproved':
            case 'free_listing_expired':
            case 'featured_listing_expired':
            case 'paid_submission_listing':
            case 'admin_paid_submission_listing':
            case 'featured_submission_listing':
            case 'admin_featured_submission_listing':
            case 'free_submission_listing':
            case 'admin_free_submission_listing':
            case 'admin_update_listing':
            case 'admin_expired_listings':
                $action['type'] = 'navigate';
                $action['label'] = __('Manage Listing', 'houzez-api');
                $action['screen'] = 'my_properties';
                if (isset($extra_data['listing_id'])) {
                    $action['data']['property_id'] = $extra_data['listing_id'];
                }
                break;
                
            // User Management
            case 'new_user_register':
            case 'admin_new_user_register':
            case 'admin_user_register_approval':
            case 'membership_cancelled':
            case 'user_approved':
            case 'user_declined':
            case 'user_suspended':
                $action['type'] = 'navigate';
                $action['label'] = __('View Profile', 'houzez-api');
                $action['screen'] = 'profile';
                if (isset($extra_data['user_id'])) {
                    $action['data']['user_id'] = $extra_data['user_id'];
                }
                break;
                
            // Reports
            case 'property_report':
            case 'report':
                $action['type'] = 'navigate';
                $action['label'] = __('View Report', 'houzez-api');
                $action['screen'] = 'reports';
                if (isset($extra_data['listing_id'])) {
                    $action['data']['property_id'] = $extra_data['listing_id'];
                }
                break;
                
            // System and Admin
            case 'system_update':
            case 'verification_status':
                $action['type'] = 'navigate';
                $action['label'] = __('View Details', 'houzez-api');
                $action['screen'] = 'settings';
                break;
                
            // Documents
            case 'document_uploaded':
                $action['type'] = 'navigate';
                $action['label'] = __('View Document', 'houzez-api');
                $action['screen'] = 'documents';
                if (isset($extra_data['document_id'])) {
                    $action['data']['document_id'] = $extra_data['document_id'];
                }
                break;
        }
        
        return $action;
    }
    
    /**
     * Get unread count for specific user
     */
    private static function get_unread_count_for_user($user_id) {
        if (!$user_id) {
            return 0;
        }
        
        $args = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'user_id',
                    'value' => $user_id,
                    'compare' => '='
                ],
                [
                    'key' => 'is_read',
                    'value' => '0',
                    'compare' => '='
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Build deep link for mobile app navigation
     */
    private static function build_deep_link($type, $parsed_data) {
        $deep_link = 'realestate://notification';
        
        switch ($type) {
            case 'messages':
            case 'message_received':
                if (isset($parsed_data['thread_id'])) {
                    $deep_link = 'realestate://messages/' . $parsed_data['thread_id'];
                } else {
                    $deep_link = 'realestate://messages';
                }
                break;
                
            case 'property_saved':
            case 'property_matched':
            case 'property_matching':
            case 'property_price_drop':
            case 'price_update':
            case 'property_status_change':
                if (isset($parsed_data['listing_id']) || isset($parsed_data['property_id'])) {
                    $property_id = $parsed_data['listing_id'] ?? $parsed_data['property_id'];
                    $deep_link = 'realestate://property/' . $property_id;
                } else {
                    $deep_link = 'realestate://properties';
                }
                break;
                
            case 'inquiry_received':
            case 'showing_scheduled':
            case 'property_agent_contact':
            case 'property_schedule_tour':
            case 'contact_agent':
            case 'contact_agency':
            case 'contact_owner':
                $deep_link = 'realestate://inquiries';
                break;
                
            case 'review':
            case 'review_received':
                if (isset($parsed_data['listing_id'])) {
                    $deep_link = 'realestate://property/' . $parsed_data['listing_id'] . '/reviews';
                } else {
                    $deep_link = 'realestate://reviews';
                }
                break;
                
            case 'matching_submissions':
                if (isset($parsed_data['search_url'])) {
                    $deep_link = 'realestate://search?url=' . urlencode($parsed_data['search_url']);
                } else {
                    $deep_link = 'realestate://search';
                }
                break;
                
            case 'payment_received':
            case 'payment_confirmation':
            case 'new_wire_transfer':
            case 'recurring_payment':
                if (isset($parsed_data['invoice_no'])) {
                    $deep_link = 'realestate://invoice/' . $parsed_data['invoice_no'];
                } else {
                    $deep_link = 'realestate://invoices';
                }
                break;
                
            case 'listing_expired':
            case 'listing_approved':
            case 'listing_disapproved':
            case 'free_listing_expired':
            case 'featured_listing_expired':
            case 'paid_submission_listing':
            case 'admin_paid_submission_listing':
            case 'featured_submission_listing':
            case 'admin_featured_submission_listing':
            case 'free_submission_listing':
            case 'admin_free_submission_listing':
            case 'admin_update_listing':
            case 'admin_expired_listings':
                if (isset($parsed_data['listing_id'])) {
                    $deep_link = 'realestate://my-properties/' . $parsed_data['listing_id'];
                } else {
                    $deep_link = 'realestate://my-properties';
                }
                break;
                
            case 'new_user_register':
            case 'admin_new_user_register':
            case 'admin_user_register_approval':
            case 'membership_cancelled':
            case 'user_approved':
            case 'user_declined':
            case 'user_suspended':
                $deep_link = 'realestate://profile';
                break;
                
            case 'property_report':
            case 'report':
                $deep_link = 'realestate://reports';
                break;
                
            case 'system_update':
            case 'verification_status':
                $deep_link = 'realestate://settings';
                break;
                
            case 'document_uploaded':
                $deep_link = 'realestate://documents';
                break;
                
            default:
                $deep_link = 'realestate://notifications';
                break;
        }
        
        return $deep_link;
    }

    /**
     * Clean notification message
     */
    private function clean_notification_message($message) {
        // Remove HTML tags
        $message = strip_tags($message, '<br>');
        
        // Convert line breaks
        $message = str_replace(['<br>', '<br/>', '<br />'], "\n", $message);
        
        // Remove multiple spaces
        $message = preg_replace('/\s+/', ' ', $message);
        
        // Remove template variables that weren't replaced
        $message = preg_replace('/%[a-zA-Z_]+%/', '', $message);
        
        // Trim
        $message = trim($message);
        
        return $message;
    }

    /**
     * Get priority by notification type
     */
    private function get_priority_by_type($type) {
        // Urgent: Critical admin actions and reports
        $urgent_priority_types = [
            'report', 'property_report', 'membership_cancelled', 'admin_expired_listings',
            'admin_new_user_register', 'admin_user_register_approval'
        ];
        
        // High: Important business activities
        $high_priority_types = [
            'payment_received', 'payment_confirmation', 'listing_expired', 'listing_disapproved',
            'verification_status', 'showing_scheduled', 'property_schedule_tour',
            'new_wire_transfer', 'admin_new_wire_transfer', 'recurring_payment',
            'purchase_activated_pack', 'purchase_activated', 'paid_submission_listing',
            'admin_paid_submission_listing', 'featured_submission_listing',
            'admin_featured_submission_listing', 'listing_approved',
            'free_listing_expired', 'featured_listing_expired',
            'user_approved', 'user_declined', 'user_suspended'
        ];
        
        // Low: System updates and general information
        $low_priority_types = [
            'system_update', 'marketing_promotion', 'property_saved', 'property_matched',
            'property_matching', 'matching_submissions'
        ];
        
        if (in_array($type, $urgent_priority_types)) {
            return 'urgent';
        } elseif (in_array($type, $high_priority_types)) {
            return 'high';
        } elseif (in_array($type, $low_priority_types)) {
            return 'low';
        } else {
            return 'medium';
        }
    }

    /**
     * Get category by notification type
     */
    private function get_category_by_type($type) {
        $categories = [
            // Property related notifications
            'property' => [
                'property_saved', 'property_matched', 'property_price_drop', 'property_status_change',
                'listing_expired', 'listing_approved', 'listing_disapproved', 'property_matching',
                'price_update', 'free_listing_expired', 'featured_listing_expired'
            ],
            
            // Communication and contact forms
            'communication' => [
                'messages', 'inquiry_received', 'review', 'property_agent_contact',
                'contact_agent', 'contact_agency', 'contact_owner', 'message_received', 'review_received'
            ],
            
            // Scheduling and appointments
            'scheduling' => [
                'showing_scheduled', 'showing_reminder', 'property_schedule_tour'
            ],
            
            // Financial transactions and payments
            'financial' => [
                'payment_received', 'payment_confirmation', 'new_wire_transfer', 'admin_new_wire_transfer',
                'recurring_payment', 'purchase_activated_pack', 'purchase_activated'
            ],
            
            // User management and registration
            'user_management' => [
                'new_user_register', 'admin_new_user_register', 'admin_user_register_approval',
                'membership_cancelled', 'verification_status', 'user_approved', 'user_declined', 'user_suspended',
                'verification_submitted', 'verification_approved', 'verification_rejected', 'verification_revoked',
                'verification_info_required', 'verification_info_submitted'
            ],
            
            // Listing submissions and management
            'listing_management' => [
                'paid_submission_listing', 'admin_paid_submission_listing', 'featured_submission_listing',
                'admin_featured_submission_listing', 'free_submission_listing', 'admin_free_submission_listing',
                'admin_update_listing', 'admin_expired_listings'
            ],
            
            // Marketing and recommendations
            'marketing' => [
                'marketing_promotion', 'matching_submissions'
            ],
            
            // Admin notifications and reports
            'admin' => [
                'report', 'property_report', 'system_update', 'agent_assigned'
            ],
            
            // Documents and media
            'documents' => [
                'document_uploaded'
            ]
        ];
        
        foreach ($categories as $category => $types) {
            if (in_array($type, $types)) {
                return $category;
            }
        }
        
        return 'general';
    }

    /**
     * Get notification statistics
     */
    private static function get_notification_stats($user_id) {
        $stats = [
            'total' => 0,
            'unread' => 0,
            'by_type' => [],
            'by_priority' => []
        ];
        
        // Total notifications
        $total_args = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'user_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ]
        ];
        $total_query = new WP_Query($total_args);
        $stats['total'] = $total_query->found_posts;
        
        // Unread notifications
        $unread_args = $total_args;
        $unread_args['meta_query'][] = [
            'key' => 'is_read',
            'value' => '0',
            'compare' => '='
        ];
        $unread_query = new WP_Query($unread_args);
        $stats['unread'] = $unread_query->found_posts;
        
        // By type
        foreach (self::NOTIFICATION_TYPES as $type_key) {
            $type_args = $total_args;
            $type_args['meta_query'][] = [
                'key' => 'notification_type',
                'value' => $type_key,
                'compare' => '='
            ];
            $type_query = new WP_Query($type_args);
            if ($type_query->found_posts > 0) {
                $stats['by_type'][$type_key] = $type_query->found_posts;
            }
        }
        
        // By priority
        foreach (self::PRIORITIES as $priority_key => $priority_value) {
            $priority_args = $total_args;
            $priority_args['meta_query'][] = [
                'key' => 'priority',
                'value' => $priority_key,
                'compare' => '='
            ];
            $priority_query = new WP_Query($priority_args);
            if ($priority_query->found_posts > 0) {
                $stats['by_priority'][$priority_key] = $priority_query->found_posts;
            }
        }
        
        return $stats;
    }

    /**
     * Get default notification preferences
     */
    private static function get_default_preferences() {
        return [
            'push_enabled' => true,
            'email_enabled' => true,
            'email_frequency' => 'instant',
            'disabled_types' => [],
            'quiet_hours' => [
                'enabled' => false,
                'start' => '22:00',
                'end' => '08:00'
            ]
        ];
    }

    /**
     * Set custom columns
     */
    public function set_custom_columns($columns) {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'title' => __('Title', 'houzez-api'),
            'message' => __('Message', 'houzez-api'),
            'user' => __('User', 'houzez-api'),
            'type' => __('Type', 'houzez-api'),
            //'priority' => __('Priority', 'houzez-api'),
            'status' => __('Status', 'houzez-api'),
            'date' => __('Date', 'houzez-api')
        ];
        
        return $columns;
    }

    /**
     * Render custom columns
     */
    public function custom_column($column, $post_id) {
        switch ($column) {
            case 'user':
                $user_id = get_post_meta($post_id, 'user_id', true);
                $user = get_user_by('id', $user_id);
                echo $user ? esc_html($user->display_name) : __('Unknown', 'houzez-api');
                break;
                
            case 'message':
                $post = get_post($post_id);
                $message = $post->post_content;
                // Truncate message to 100 characters for display
                if (strlen($message) > 100) {
                    //$message = substr($message, 0, 100) . '...';
                }
                echo '<div title="' . esc_attr($post->post_content) . '">' . esc_html($message) . '</div>';
                break;
                
            case 'type':
                $type = get_post_meta($post_id, 'notification_type', true);
                echo esc_html(self::get_notification_type_label($type));
                break;
                
            case 'priority':
                $priority = get_post_meta($post_id, 'priority', true);
                $priority_class = 'priority-' . esc_attr($priority);
                echo '<span class="' . $priority_class . '" style="padding: 2px 8px; border-radius: 3px; background-color: ';
                switch ($priority) {
                    case 'urgent':
                        echo '#dc3545; color: #fff;';
                        break;
                    case 'high':
                        echo '#fd7e14; color: #fff;';
                        break;
                    case 'medium':
                        echo '#ffc107; color: #000;';
                        break;
                    default:
                        echo '#6c757d; color: #fff;';
                }
                echo '">' . esc_html(ucfirst($priority)) . '</span>';
                break;
                
            case 'status':
                $is_read = get_post_meta($post_id, 'is_read', true);
                echo $is_read ? __('Read', 'houzez-api') : '<strong>' . __('Unread', 'houzez-api') . '</strong>';
                break;
        }
    }

    /**
     * Make columns sortable
     */
    public function sortable_columns($columns) {
        $columns['type'] = 'type';
        $columns['priority'] = 'priority';
        $columns['status'] = 'status';
        
        return $columns;
    }

    /**
     * Get translatable notification type label
     */
    public static function get_notification_type_label($type) {
        $labels = [
            // Generic notification types
            'property_saved' => __('Property Saved', 'houzez-api'),
            'property_matched' => __('New Property Match', 'houzez-api'),
            'property_price_drop' => __('Price Drop Alert', 'houzez-api'),
            'property_status_change' => __('Property Status Changed', 'houzez-api'),
            'inquiry_received' => __('New Property Inquiry', 'houzez-api'),
            'showing_scheduled' => __('Tour Scheduled', 'houzez-api'),
            'showing_reminder' => __('Tour Reminder', 'houzez-api'),
            'messages' => __('New Message', 'houzez-api'),
            'document_uploaded' => __('Document Uploaded', 'houzez-api'),
            'payment_received' => __('Payment Received', 'houzez-api'),
            'review' => __('New Review', 'houzez-api'),
            'agent_assigned' => __('Agent Assigned', 'houzez-api'),
            'verification_status' => __('Verification Status Update', 'houzez-api'),
            'system_update' => __('System Update', 'houzez-api'),
            'marketing_promotion' => __('Special Promotion', 'houzez-api'),
            'report' => __('Report Received', 'houzez-api'),
            
            // Houzez Contact & Communication
            'property_agent_contact' => __('New Property Contact', 'houzez-api'),
            'property_schedule_tour' => __('New Tour Request', 'houzez-api'),
            'contact_agent' => __('Agent Contact Request', 'houzez-api'),
            'contact_agency' => __('Agency Contact Request', 'houzez-api'),
            'contact_owner' => __('Owner Contact Request', 'houzez-api'),
            'property_report' => __('Property Report Filed', 'houzez-api'),
            'message_received' => __('Message Received', 'houzez-api'),
            'review_received' => __('Review Received', 'houzez-api'),
            
            // User Registration & Authentication
            'new_user_register' => __('Welcome! Registration Complete', 'houzez-api'),
            'admin_new_user_register' => __('User Registration Pending', 'houzez-api'),
            'admin_user_register_approval' => __('User Approval Required', 'houzez-api'),
            
            // Package & Payment Management
            'purchase_activated_pack' => __('Package Activated', 'houzez-api'),
            'purchase_activated' => __('Purchase Activated', 'houzez-api'),
            'payment_confirmation' => __('Payment Confirmed', 'houzez-api'),
            'new_wire_transfer' => __('Wire Transfer Request', 'houzez-api'),
            'admin_new_wire_transfer' => __('Wire Transfer Pending', 'houzez-api'),
            'recurring_payment' => __('Recurring Payment Processed', 'houzez-api'),
            'membership_cancelled' => __('Membership Cancelled', 'houzez-api'),
            
            // Listing Management
            'listing_expired' => __('Listing Expired', 'houzez-api'),
            'listing_approved' => __('Listing Approved', 'houzez-api'),
            'listing_disapproved' => __('Listing Needs Review', 'houzez-api'),
            'free_listing_expired' => __('Free Listing Expired', 'houzez-api'),
            'featured_listing_expired' => __('Featured Listing Expired', 'houzez-api'),
            'paid_submission_listing' => __('Paid Listing Submitted', 'houzez-api'),
            'admin_paid_submission_listing' => __('Paid Listing Pending Review', 'houzez-api'),
            'featured_submission_listing' => __('Featured Listing Submitted', 'houzez-api'),
            'admin_featured_submission_listing' => __('Featured Listing Pending', 'houzez-api'),
            'free_submission_listing' => __('Free Listing Submitted', 'houzez-api'),
            'admin_free_submission_listing' => __('Free Listing Pending Review', 'houzez-api'),
            'admin_update_listing' => __('Listing Updated', 'houzez-api'),
            'admin_expired_listings' => __('Expired Listing Resubmitted', 'houzez-api'),
            
            // Matching & Recommendations
            'matching_submissions' => __('Matching Properties Found', 'houzez-api'),
            'property_matching' => __('Properties Match Your Search', 'houzez-api'),
            'price_update' => __('Price Updated', 'houzez-api'),
            
            // User Management Actions
            'user_approved' => __('Account Approved', 'houzez-api'),
            'user_declined' => __('Account Declined', 'houzez-api'),
            'user_suspended' => __('Account Suspended', 'houzez-api'),
            
            // Verification System
            'verification_submitted' => __('Verification Submitted', 'houzez-api'),
            'verification_approved' => __('Verification Approved', 'houzez-api'),
            'verification_rejected' => __('Verification Rejected', 'houzez-api'),
            'verification_revoked' => __('Verification Revoked', 'houzez-api'),
            'verification_info_required' => __('Additional Info Required', 'houzez-api'),
            'verification_info_submitted' => __('Additional Info Submitted', 'houzez-api'),
        ];
        
        return isset($labels[$type]) ? $labels[$type] : $type;
    }

    /**
     * Get document type label from verification system
     * 
     * @param string $document_type The document type key
     * @return string The human-readable label
     */
    private function get_document_type_label($document_type) {
        // Try to get label from Houzez verification system first (preferred)
        global $houzez_user_verification;
        if ($houzez_user_verification && method_exists($houzez_user_verification, 'get_document_type_label')) {
            return $houzez_user_verification->get_document_type_label($document_type);
        }
        
        // Fallback to our own labels if verification system not available
        $document_types = [
            'id_card' => __('ID Card', 'houzez-api'),
            'passport' => __('Passport', 'houzez-api'),
            'drivers_license' => __('Driver\'s License', 'houzez-api'),
            'business_license' => __('Business License', 'houzez-api'),
            'other' => __('Other Document', 'houzez-api')
        ];
        
        return isset($document_types[$document_type]) ? $document_types[$document_type] : $document_type;
    }

    /**
     * Capture verification submitted event
     *
     * @param int $user_id
     * @param array $verification_data
     */
    public function capture_verification_submitted($user_id, $verification_data) {
        $user = get_userdata($user_id);
        if (!$user) return;

        $document_type = $verification_data['document_type'] ?? '';
        $document_type_label = $document_type ? $this->get_document_type_label($document_type) : __('Unknown', 'houzez-api');
        
        $this->create_notification_post(
            $user_id,
            __('Verification Request Submitted', 'houzez-api'),
            sprintf(__('Your verification request has been submitted and is under review. Document type: %s', 'houzez-api'), 
                $document_type_label),
            'verification_submitted',
            'medium',
            [
                'verification_data' => $verification_data,
                'document_type' => $document_type,
                'document_type_label' => $document_type_label,
                'submitted_on' => $verification_data['submitted_on'] ?? current_time('mysql')
            ]
        );


    }

    /**
     * Capture verification approved event
     *
     * @param int $user_id
     * @param array $verification_data
     */
    public function capture_verification_approved($user_id, $verification_data) {
        $user = get_userdata($user_id);
        if (!$user) return;

        $document_type = $verification_data['document_type'] ?? '';
        $document_type_label = $document_type ? $this->get_document_type_label($document_type) : '';
        
        $this->create_notification_post(
            $user_id,
            __('Verification Approved! 🎉', 'houzez-api'),
            __('Congratulations! Your account verification has been approved. Your profile now shows a verified badge.', 'houzez-api'),
            'verification_approved',
            'high',
            [
                'verification_data' => $verification_data,
                'approved_on' => $verification_data['processed_on'] ?? current_time('mysql'),
                'document_type' => $document_type,
                'document_type_label' => $document_type_label
            ]
        );


    }

    /**
     * Capture verification rejected event
     *
     * @param int $user_id
     * @param string $rejection_reason
     * @param array $verification_data
     */
    public function capture_verification_rejected($user_id, $rejection_reason, $verification_data) {
        $user = get_userdata($user_id);
        if (!$user) return;

        $message = __('Your verification request has been rejected.', 'houzez-api');
        if (!empty($rejection_reason)) {
            $message .= ' ' . sprintf(__('Reason: %s', 'houzez-api'), $rejection_reason);
        }
        $message .= ' ' . __('You may submit a new verification request if you wish.', 'houzez-api');

        $document_type = $verification_data['document_type'] ?? '';
        $document_type_label = $document_type ? $this->get_document_type_label($document_type) : '';
        
        $this->create_notification_post(
            $user_id,
            __('Verification Rejected', 'houzez-api'),
            $message,
            'verification_rejected',
            'high',
            [
                'verification_data' => $verification_data,
                'rejection_reason' => $rejection_reason,
                'rejected_on' => $verification_data['processed_on'] ?? current_time('mysql'),
                'document_type' => $document_type,
                'document_type_label' => $document_type_label
            ]
        );


    }

    /**
     * Capture verification revoked event
     *
     * @param int $user_id
     * @param array $verification_data
     */
    public function capture_verification_revoked($user_id, $verification_data) {
        $user = get_userdata($user_id);
        if (!$user) return;

        $document_type = $verification_data['document_type'] ?? '';
        $document_type_label = $document_type ? $this->get_document_type_label($document_type) : '';
        
        $this->create_notification_post(
            $user_id,
            __('Verification Revoked', 'houzez-api'),
            __('Your account verification has been revoked by an administrator. Please contact support if you believe this was done in error.', 'houzez-api'),
            'verification_revoked',
            'urgent',
            [
                'verification_data' => $verification_data,
                'revoked_on' => $verification_data['processed_on'] ?? current_time('mysql'),
                'document_type' => $document_type,
                'document_type_label' => $document_type_label
            ]
        );


    }

    /**
     * Capture verification info requested event
     *
     * @param int $user_id
     * @param string $additional_info
     * @param array $verification_data
     */
    public function capture_verification_info_requested($user_id, $additional_info, $verification_data) {
        $user = get_userdata($user_id);
        if (!$user) return;

        $message = __('Additional information is required for your verification request.', 'houzez-api');
        if (!empty($additional_info)) {
            $message .= ' ' . sprintf(__('Details: %s', 'houzez-api'), $additional_info);
        }
        $message .= ' ' . __('Please provide the requested information to continue with your verification.', 'houzez-api');

        $document_type = $verification_data['document_type'] ?? '';
        $document_type_label = $document_type ? $this->get_document_type_label($document_type) : '';
        
        $this->create_notification_post(
            $user_id,
            __('Additional Information Required', 'houzez-api'),
            $message,
            'verification_info_required',
            'high',
            [
                'verification_data' => $verification_data,
                'additional_info_request' => $additional_info,
                'requested_on' => $verification_data['processed_on'] ?? current_time('mysql'),
                'document_type' => $document_type,
                'document_type_label' => $document_type_label
            ]
        );


    }

    /**
     * Capture verification additional info submitted event
     *
     * @param int $user_id
     * @param array $verification_data
     */
    public function capture_verification_info_submitted($user_id, $verification_data) {
        $user = get_userdata($user_id);
        if (!$user) return;

        $additional_document_type = $verification_data['additional_document_type'] ?? '';
        $additional_document_type_label = $additional_document_type ? $this->get_document_type_label($additional_document_type) : __('Unknown', 'houzez-api');
        
        $this->create_notification_post(
            $user_id,
            __('Additional Information Submitted', 'houzez-api'),
            sprintf(__('Your additional information has been submitted and is under review. Document type: %s', 'houzez-api'), 
                $additional_document_type_label),
            'verification_info_submitted',
            'medium',
            [
                'verification_data' => $verification_data,
                'additional_document_type' => $additional_document_type,
                'additional_document_type_label' => $additional_document_type_label,
                'submitted_on' => $verification_data['additional_info_submitted_on'] ?? current_time('mysql'),
                'additional_notes' => $verification_data['additional_notes'] ?? ''
            ]
        );


    }
} 