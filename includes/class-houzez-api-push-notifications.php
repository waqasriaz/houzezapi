<?php
/**
 * Push Notification Handler for Houzez API
 * 
 * Handles sending push notifications to mobile devices when notifications are created
 * 
 * @package Houzez_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Push_Notifications {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Screen mapping for notification types
     */
    const SCREEN_MAPPING = [
        // Messages
        'messages' => 'messages',
        'message_received' => 'messages',
        
        // Inquiries & Contact
        'inquiry_received' => 'inquiries',
        'property_agent_contact' => 'inquiries',
        'property_schedule_tour' => 'inquiries',
        'contact_agent' => 'inquiries',
        'contact_agency' => 'inquiries',
        'contact_owner' => 'inquiries',
        'showing_scheduled' => 'inquiries',
        'showing_reminder' => 'inquiries',
        
        // Reviews
        'review' => 'reviews',
        'review_received' => 'reviews',
        
        // Payments & Invoices
        'payment_received' => 'invoices',
        'payment_confirmation' => 'invoices',
        'new_wire_transfer' => 'invoices',
        'admin_new_wire_transfer' => 'invoices',
        'recurring_payment' => 'invoices',
        'purchase_activated_pack' => 'invoices',
        'purchase_activated' => 'invoices',
        
        // Reports
        'property_report' => 'reports',
        'report' => 'reports',
        
        // Documents
        'document_uploaded' => 'documents',
        
        // Listing Management
        'listing_approved' => 'my_properties',
        'listing_expired' => 'my_properties',
        'listing_disapproved' => 'my_properties',
        'free_listing_expired' => 'my_properties',
        'featured_listing_expired' => 'my_properties',
        'paid_submission_listing' => 'my_properties',
        'admin_paid_submission_listing' => 'my_properties',
        'featured_submission_listing' => 'my_properties',
        'admin_featured_submission_listing' => 'my_properties',
        'free_submission_listing' => 'my_properties',
        'admin_free_submission_listing' => 'my_properties',
        'admin_update_listing' => 'my_properties',
        'admin_expired_listings' => 'my_properties',
        
        // Property Details
        'property_saved' => 'property_details',
        'property_matched' => 'property_details',
        'property_matching' => 'property_details',
        'property_price_drop' => 'property_details',
        'property_status_change' => 'property_details',
        'price_update' => 'property_details',
        
        // User Profile
        'new_user_register' => 'profile',
        'admin_new_user_register' => 'profile',
        'admin_user_register_approval' => 'profile',
        'verification_status' => 'profile',
        'membership_cancelled' => 'profile',
        'agent_assigned' => 'profile',
        
        // Settings
        'system_update' => 'settings',
        'marketing_promotion' => 'settings',
        
        // Search/Matching
        'matching_submissions' => 'search',
    ];
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into notification creation
        add_action('houzez_api_notification_created', array($this, 'send_push_notification'), 10, 4);
        
        // Add settings fields
        add_action('admin_init', array($this, 'add_settings_fields'));
        
        // REST API endpoints for device registration
        add_action('rest_api_init', array($this, 'register_device_endpoints'));
    }
    
    /**
     * Send push notification when a notification is created
     */
    public function send_push_notification($notification_id, $user_id, $type, $extra_data) {
        // Get push notification settings
        $push_enabled = get_option('houzez_api_push_enabled', '0');
        if ($push_enabled !== '1') {
            return;
        }
        
        // Get user's push preferences
        $user_push_enabled = get_user_meta($user_id, 'houzez_push_notifications_enabled', true);
        if ($user_push_enabled === '0') {
            return;
        }
        
        // Get notification data
        $notification = get_post($notification_id);
        if (!$notification) {
            return;
        }
        
        // Get push service
        $push_service = get_option('houzez_api_push_service', 'onesignal');
        
        // Get user's device tokens
        $device_tokens = get_user_meta($user_id, 'houzez_device_tokens', true);
        if (empty($device_tokens) || !is_array($device_tokens)) {
            return;
        }
        
        // Prepare notification data
        $push_data = $this->prepare_push_data($notification, $type, $extra_data);
        
        // Send via appropriate service
        switch ($push_service) {
            case 'onesignal':
                $this->send_via_onesignal($device_tokens, $push_data, $user_id);
                break;
            case 'firebase':
                $this->send_via_firebase($device_tokens, $push_data);
                break;
            case 'custom':
                do_action('houzez_send_custom_push_notification', $device_tokens, $push_data, $user_id);
                break;
        }
    }
    
    /**
     * Prepare push notification data
     */
    private function prepare_push_data($notification, $type, $extra_data) {
        // Get translatable notification type label
        $title = Houzez_API_Endpoint_Notifications::get_notification_type_label($type);
        $message = wp_strip_all_tags($notification->post_content);
        
        // Truncate message for push notification limits
        if (strlen($message) > 150) {
            $message = substr($message, 0, 147) . '...';
        }
        
        // Get notification priority
        $priority = get_post_meta($notification->ID, 'priority', true);
        if (empty($priority)) {
            $priority = 'medium';
        }
        
        // Build comprehensive push notification payload
        $push_data = [
            'title' => $title,
            'message' => $message,
            'notification_id' => $notification->ID,
            'type' => $type,
            'priority' => $priority,
            'timestamp' => current_time('timestamp'),
            'created_at' => $notification->post_date,
        ];
        
        // Add screen navigation data from constant
        if (isset(self::SCREEN_MAPPING[$type])) {
            $push_data['screen'] = self::SCREEN_MAPPING[$type];
        }
        
        // Add structured extra data
        if (!empty($extra_data)) {
            // Common fields
            if (isset($extra_data['property_id']) || isset($extra_data['listing_id'])) {
                $push_data['property_id'] = $extra_data['property_id'] ?? $extra_data['listing_id'];
            }
            if (isset($extra_data['property_title']) || isset($extra_data['listing_title'])) {
                $push_data['property_title'] = $extra_data['property_title'] ?? $extra_data['listing_title'];
            }
            
            // Contact fields
            if (isset($extra_data['sender_name'])) {
                $push_data['sender_name'] = $extra_data['sender_name'];
            }
            if (isset($extra_data['sender_email'])) {
                $push_data['sender_email'] = $extra_data['sender_email'];
            }
            if (isset($extra_data['sender_phone'])) {
                $push_data['sender_phone'] = $extra_data['sender_phone'];
            }
            
            // Message/Thread fields
            if (isset($extra_data['thread_id'])) {
                $push_data['thread_id'] = $extra_data['thread_id'];
            }
            if (isset($extra_data['sender_id'])) {
                $push_data['sender_id'] = $extra_data['sender_id'];
            }
            
            // Payment fields
            if (isset($extra_data['invoice_no'])) {
                $push_data['invoice_no'] = $extra_data['invoice_no'];
            }
            if (isset($extra_data['total_price'])) {
                $push_data['total_price'] = $extra_data['total_price'];
            }
            
            // Schedule fields
            if (isset($extra_data['schedule_date'])) {
                $push_data['schedule_date'] = $extra_data['schedule_date'];
            }
            if (isset($extra_data['schedule_time'])) {
                $push_data['schedule_time'] = $extra_data['schedule_time'];
            }
            
            // Include any additional data
            $push_data['data'] = $extra_data;
        }
        
        // Build deep link for mobile navigation
        $push_data['deep_link'] = $this->build_push_deep_link($type, $push_data);
        
        return $push_data;
    }
    
    /**
     * Build deep link for push notifications
     */
    private function build_push_deep_link($type, $data) {
        $deep_link = 'realestate://notification';
        
        switch ($type) {
            case 'messages':
            case 'message_received':
                $deep_link = isset($data['thread_id']) 
                    ? 'realestate://messages/' . $data['thread_id']
                    : 'realestate://messages';
                break;
                
            case 'property_saved':
            case 'property_matched':
            case 'property_price_drop':
            case 'property_status_change':
            case 'price_update':
                $deep_link = isset($data['property_id'])
                    ? 'realestate://property/' . $data['property_id']
                    : 'realestate://properties';
                break;
                
            case 'inquiry_received':
            case 'property_agent_contact':
            case 'property_schedule_tour':
            case 'contact_agent':
            case 'contact_agency':
            case 'contact_owner':
            case 'showing_scheduled':
            case 'showing_reminder':
                $deep_link = 'realestate://inquiries';
                break;
                
            case 'review':
            case 'review_received':
                $deep_link = isset($data['property_id'])
                    ? 'realestate://property/' . $data['property_id'] . '/reviews'
                    : 'realestate://reviews';
                break;
                
            case 'payment_received':
            case 'payment_confirmation':
            case 'new_wire_transfer':
            case 'recurring_payment':
                $deep_link = isset($data['invoice_no'])
                    ? 'realestate://invoice/' . $data['invoice_no']
                    : 'realestate://invoices';
                break;
                
            case 'listing_approved':
            case 'listing_expired':
            case 'listing_disapproved':
            case 'free_listing_expired':
            case 'featured_listing_expired':
                $deep_link = isset($data['property_id'])
                    ? 'realestate://my-properties/' . $data['property_id']
                    : 'realestate://my-properties';
                break;
                
            case 'property_report':
            case 'report':
                $deep_link = 'realestate://reports';
                break;
                
            case 'document_uploaded':
                $deep_link = 'realestate://documents';
                break;
                
            case 'matching_submissions':
                $deep_link = 'realestate://search';
                break;
                
            default:
                $deep_link = 'realestate://notifications';
                break;
        }
        
        return $deep_link;
    }
    
    /**
     * Send via OneSignal
     */
    private function send_via_onesignal($device_tokens, $push_data, $user_id) {
        $app_id = get_option('houzez_api_onesignal_app_id');
        $api_key = get_option('houzez_api_onesignal_api_key');
        
        if (empty($app_id) || empty($api_key)) {
            return;
        }
        
        // Get OneSignal player IDs for this user
        $player_ids = [];
        foreach ($device_tokens as $token) {
            if (isset($token['platform']) && $token['platform'] === 'onesignal' && !empty($token['player_id'])) {
                $player_ids[] = $token['player_id'];
            }
        }
        
        if (empty($player_ids)) {
            return;
        }
        
        $fields = [
            'app_id' => $app_id,
            'include_player_ids' => $player_ids,
            'headings' => ['en' => $push_data['title']],
            'contents' => ['en' => $push_data['message']],
            'data' => $push_data,
            'ios_badgeType' => 'Increase',
            'ios_badgeCount' => 1,
        ];
        
        $response = wp_remote_post('https://onesignal.com/api/v1/notifications', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $api_key,
            ],
            'body' => json_encode($fields),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            error_log('Houzez API - OneSignal Error: ' . $response->get_error_message());
        }
    }
    
    /**
     * Send via Firebase Cloud Messaging
     */
    private function send_via_firebase($device_tokens, $push_data) {
        $server_key = get_option('houzez_api_firebase_server_key');
        
        if (empty($server_key)) {
            return;
        }
        
        // Get FCM tokens
        $fcm_tokens = [];
        foreach ($device_tokens as $token) {
            if (isset($token['platform']) && in_array($token['platform'], ['android', 'ios']) && !empty($token['token'])) {
                $fcm_tokens[] = $token['token'];
            }
        }
        
        if (empty($fcm_tokens)) {
            return;
        }
        
        // Send to each token
        foreach ($fcm_tokens as $token) {
            $fields = [
                'to' => $token,
                'notification' => [
                    'title' => $push_data['title'],
                    'body' => $push_data['message'],
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => $push_data,
                'priority' => 'high',
            ];
            
            $response = wp_remote_post('https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'key=' . $server_key,
                ],
                'body' => json_encode($fields),
                'timeout' => 30,
            ]);
            
            if (is_wp_error($response)) {
                error_log('Houzez API - FCM Error: ' . $response->get_error_message());
            }
        }
    }
    
    /**
     * Register device endpoints
     */
    public function register_device_endpoints() {
        // Register device token
        register_rest_route('houzez-api/v1', '/notifications/register-device', [
            'methods' => 'POST',
            'callback' => array($this, 'register_device'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => [
                'platform' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['ios', 'android', 'onesignal'],
                ],
                'token' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'player_id' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'device_id' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);
        
        // Unregister device token
        register_rest_route('houzez-api/v1', '/notifications/unregister-device', [
            'methods' => 'POST',
            'callback' => array($this, 'unregister_device'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => [
                'device_id' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);
    }
    
    /**
     * Register device for push notifications
     */
    public function register_device($request) {
        $user_id = get_current_user_id();
        $platform = $request->get_param('platform');
        $token = $request->get_param('token');
        $player_id = $request->get_param('player_id');
        $device_id = $request->get_param('device_id');
        
        // Validate based on platform
        if ($platform === 'onesignal' && empty($player_id)) {
            return new WP_Error('missing_player_id', __('Player ID is required for OneSignal', 'houzez-api'), ['status' => 400]);
        }
        
        if (in_array($platform, ['ios', 'android']) && empty($token)) {
            return new WP_Error('missing_token', __('Token is required for FCM', 'houzez-api'), ['status' => 400]);
        }
        
        // Get existing tokens
        $device_tokens = get_user_meta($user_id, 'houzez_device_tokens', true);
        if (!is_array($device_tokens)) {
            $device_tokens = [];
        }
        
        // Add or update device token
        $device_tokens[$device_id] = [
            'platform' => $platform,
            'token' => $token,
            'player_id' => $player_id,
            'registered_at' => current_time('mysql'),
            'last_active' => current_time('mysql'),
        ];
        
        // Save tokens
        update_user_meta($user_id, 'houzez_device_tokens', $device_tokens);
        
        // Enable push notifications for user if not already enabled
        $push_enabled = get_user_meta($user_id, 'houzez_push_notifications_enabled', true);
        if ($push_enabled !== '0') {
            update_user_meta($user_id, 'houzez_push_notifications_enabled', '1');
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Device registered successfully', 'houzez-api'),
        ], 200);
    }
    
    /**
     * Unregister device
     */
    public function unregister_device($request) {
        $user_id = get_current_user_id();
        $device_id = $request->get_param('device_id');
        
        // Get existing tokens
        $device_tokens = get_user_meta($user_id, 'houzez_device_tokens', true);
        if (!is_array($device_tokens)) {
            return new WP_REST_Response([
                'success' => true,
                'message' => __('Device not found', 'houzez-api'),
            ], 200);
        }
        
        // Remove device
        if (isset($device_tokens[$device_id])) {
            unset($device_tokens[$device_id]);
            update_user_meta($user_id, 'houzez_device_tokens', $device_tokens);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Device unregistered successfully', 'houzez-api'),
        ], 200);
    }
    
    /**
     * Check authentication
     */
    public function check_authentication() {
        return is_user_logged_in();
    }
    
    /**
     * Add settings fields
     */
    public function add_settings_fields($settings_page = 'houzez_api_settings') {
        add_settings_section(
            'houzez_api_push_settings',
            __('Push Notification Settings', 'houzez-api'),
            null,
            'houzez_api_settings'
        );
        
        // Enable push notifications
        add_settings_field(
            'houzez_api_push_enabled',
            __('Enable Push Notifications', 'houzez-api'),
            array($this, 'render_checkbox_field'),
            'houzez_api_settings',
            'houzez_api_push_settings',
            ['field' => 'houzez_api_push_enabled']
        );
        
        // Push service
        add_settings_field(
            'houzez_api_push_service',
            __('Push Service', 'houzez-api'),
            array($this, 'render_select_field'),
            'houzez_api_settings',
            'houzez_api_push_settings',
            [
                'field' => 'houzez_api_push_service',
                'options' => [
                    'onesignal' => 'OneSignal',
                    'firebase' => 'Firebase Cloud Messaging',
                    'custom' => 'Custom (via hook)',
                ]
            ]
        );
        
        // OneSignal settings
        add_settings_field(
            'houzez_api_onesignal_app_id',
            __('OneSignal App ID', 'houzez-api'),
            array($this, 'render_text_field'),
            'houzez_api_settings',
            'houzez_api_push_settings',
            ['field' => 'houzez_api_onesignal_app_id']
        );
        
        add_settings_field(
            'houzez_api_onesignal_api_key',
            __('OneSignal API Key', 'houzez-api'),
            array($this, 'render_text_field'),
            'houzez_api_settings',
            'houzez_api_push_settings',
            ['field' => 'houzez_api_onesignal_api_key']
        );
        
        // Firebase settings
        add_settings_field(
            'houzez_api_firebase_server_key',
            __('Firebase Server Key', 'houzez-api'),
            array($this, 'render_text_field'),
            'houzez_api_settings',
            'houzez_api_push_settings',
            ['field' => 'houzez_api_firebase_server_key']
        );
        
        // Register settings
        register_setting('houzez_api_settings', 'houzez_api_push_enabled');
        register_setting('houzez_api_settings', 'houzez_api_push_service');
        register_setting('houzez_api_settings', 'houzez_api_onesignal_app_id');
        register_setting('houzez_api_settings', 'houzez_api_onesignal_api_key');
        register_setting('houzez_api_settings', 'houzez_api_firebase_server_key');
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $field = $args['field'];
        $value = get_option($field, '0');
        ?>
        <input type="checkbox" name="<?php echo esc_attr($field); ?>" value="1" <?php checked($value, '1'); ?>>
        <?php
    }
    
    /**
     * Render select field
     */
    public function render_select_field($args) {
        $field = $args['field'];
        $value = get_option($field, 'onesignal');
        $options = $args['options'];
        ?>
        <select name="<?php echo esc_attr($field); ?>">
            <?php foreach ($options as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render text field
     */
    public function render_text_field($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        ?>
        <input type="text" name="<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php
    }
}

// Initialize
Houzez_API_Push_Notifications::get_instance(); 