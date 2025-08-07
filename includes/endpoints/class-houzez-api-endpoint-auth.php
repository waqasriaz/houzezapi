<?php
/**
 * Auth Functions
 *
 * @package Houzez_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure JWT helper is loaded
if (!class_exists('Houzez_API_JWT_Helper')) {
    require_once HOUZEZ_API_PLUGIN_DIR . 'includes/helpers/class-houzez-api-jwt-helper.php';
}

class Houzez_API_Endpoint_Auth extends Houzez_API_Base {

    /**
     * Initialize the class
     */
    public function init() {
        // No initialization needed for static methods
    }
    
    /**
     * Register user
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function register_user($request) {
        // Allow developers to hook before registration process starts
        do_action('houzez_before_register', $request);

        $params = $request->get_params();
        
        // Check if registration is allowed
        if (get_option('users_can_register') != 1) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Access denied.', 'houzez-api')
            ], 403);
        }

        // Check password setting
        $enable_password = houzez_option('enable_password');

        // Validate required fields
        if (empty($params['email']) || empty($params['username'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Email and username are required', 'houzez-api')
            ], 400);
        }

        // Password validation
        if ($enable_password == 'yes') {
            if (empty($params['password'])) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => esc_html__('Password is required', 'houzez-api')
                ], 400);
            }
            $user_password = $params['password'];
        } else {
            // Generate random password
            $user_password = wp_generate_password(12, false);
        }

        // Define allowed user roles
        $user_roles = array('houzez_agency', 'houzez_agent', 'houzez_buyer', 'houzez_seller', 'houzez_owner');
        
        // Get default role
        $user_role = get_option('default_role');
        
        // If default role is administrator, set it to subscriber
        if ($user_role == 'administrator') {
            $user_role = 'subscriber';
        }
        
        // Check if role is provided and valid
        if (isset($params['role']) && $params['role'] != '') {
            $requested_role = sanitize_text_field($params['role']);
            if (in_array($requested_role, $user_roles)) {
                $user_role = $requested_role;
            } else {
                // Keep the default role if provided role is not in allowed list
                $user_role = $user_role;
            }
        }

        $username = sanitize_text_field($params['username']);
        $email = sanitize_email($params['email']);
        $first_name = isset($params['first_name']) ? sanitize_text_field($params['first_name']) : '';
        $last_name = isset($params['last_name']) ? sanitize_text_field($params['last_name']) : '';
        $phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';

        // Validate username
        if (strlen($username) < 3) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Username must be at least 3 characters', 'houzez-api')
            ], 400);
        }

        if (preg_match("/^[0-9A-Za-z_]+$/", $username) == 0) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid username (do not use special characters or spaces)', 'houzez-api')
            ], 400);
        }

        if (username_exists($username)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('This username is already registered', 'houzez-api')
            ], 400);
        }

        // Validate email
        if (!is_email($email)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid email address', 'houzez-api')
            ], 400);
        }

        if (email_exists($email)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('This email address is already registered', 'houzez-api')
            ], 400);
        }

        // Allow developers to hook before user creation
        do_action('houzez_before_register_user_creation', $params);

        // Create user
        $user_id = wp_create_user($username, $user_password, $email);

        if (is_wp_error($user_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $user_id->get_error_message()
            ], 400);
        }

        // Update user role and metadata
        wp_update_user([
            'ID' => $user_id,
            'role' => $user_role,
            'first_name' => $first_name,
            'last_name' => $last_name
        ]);

        // Update phone number based on role
        if ($user_role == 'houzez_agency') {
            update_user_meta($user_id, 'fave_author_phone', $phone);
        } else {
            update_user_meta($user_id, 'fave_author_mobile', $phone);
        }

        // Handle agent/agency creation if needed
        $user_as_agent = houzez_option('user_as_agent');
        if ($user_as_agent == 'yes') {
            $display_name = !empty($first_name) && !empty($last_name) ? 
                $first_name . ' ' . $last_name : $username;

            if ($user_role == 'houzez_agent' || $user_role == 'author') {
                houzez_register_as_agent($display_name, $email, $user_id, $phone);
            } else if ($user_role == 'houzez_agency') {
                houzez_register_as_agency($display_name, $email, $user_id, $phone);
            }
        }

        $email_verification_enabled = false; // You'll need to add the option in Houzez settings
        $token = null;

        if ($email_verification_enabled) {
            // Add email verification
            do_action('houzez_email_verification', $user_id);
            
            $message = esc_html__('Registration successful. Please check your email to verify your account.', 'houzez-api');
        } else {
            
            $message = ($enable_password == 'yes') 
                ? esc_html__('Registration successful', 'houzez-api')
                : esc_html__('Registration successful. Check your email for login credentials.', 'houzez-api');
        }

        // Send notification email
        houzez_wp_new_user_notification($user_id, $user_password, $phone);

        // Allow developers to hook after registration is complete
        do_action('houzez_after_register', $user_id);

        // Additional hook specific to API registration
        do_action('houzez_api_after_register', $user_id, $request);

        // Get approval status if user approval system is enabled
        $approval_status = null;
        $requires_approval = false;
        $approval_enabled = houzez_login_option('enable_user_approval', 0);
        
        if ($approval_enabled) {
            $status = get_user_meta($user_id, 'houzez_account_approved', true);
            if ($status !== '' && $status !== null) {
                $status_int = intval($status);
                switch ($status_int) {
                    case 1:
                        $approval_status = 'approved';
                        break;
                    case 0:
                        $approval_status = 'pending';
                        $requires_approval = true;
                        break;
                    case -1:
                        $approval_status = 'declined';
                        break;
                    case 2:
                        $approval_status = 'suspended';
                        break;
                    default:
                        $approval_status = 'approved';
                }
            } else {
                $approval_status = 'approved';
            }
        }

        // Update message if approval is required
        if ($requires_approval) {
            $message = esc_html__('Registration successful. Your account is pending approval from an administrator.', 'houzez-api');
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => array_filter([
                'user_id' => $user_id,
                'password_generated' => ($enable_password != 'yes'),
                'requires_verification' => $email_verification_enabled,
                'requires_approval' => $requires_approval,
                'approval_status' => $approval_status
            ]),
            'message' => $message
        ], 200);
    }

    /**
     * Login user
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function login_user($request) {
        // Allow developers to hook before login attempt
        do_action('houzez_before_login', $request);

        $enable_paid_submission = houzez_option('enable_paid_submission');
        
        $params = $request->get_params();
        
        // Validate required fields
        if (empty($params['username']) || empty($params['password'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Username and password are required', 'houzez-api')
            ], 400);
        }

        $username = sanitize_text_field($params['username']);
        $password = $params['password'];
        $remember = isset($params['remember']) ? (bool) $params['remember'] : false;

        // Check if username is email and convert to username
        if (is_email($username)) {
            $user_by_email = get_user_by('email', $username);
            if ($user_by_email) {
                $username = $user_by_email->user_login;
            }
        }

        // Check if JWT Shield plugin is available
        if (!Houzez_API_JWT_Helper::is_jwt_shield_available()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => Houzez_API_JWT_Helper::get_missing_plugin_message()
            ], 500);
        }

        // Prepare request for token generation
        $token_request = new WP_REST_Request('POST');
        $token_request->set_param('username', $username);
        $token_request->set_param('password', $password);

        // Generate token (this will also authenticate the user)
        $token_result = Houzez_API_JWT_Helper::generate_token($token_request);

        if (is_wp_error($token_result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $token_result->get_error_message()
            ], $token_result->get_error_data()['status'] ?? 500);
        }

        // Get the authenticated user
        $user = get_user_by('login', $username);
        if (!$user) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('User not found', 'houzez-api')
            ], 404);
        }

        // Check if email is verified (if verification is enabled)
        $email_verified = get_user_meta($user->ID, 'houzez_email_verified', true);
        if (metadata_exists('user', $user->ID, 'houzez_email_verified') && !$email_verified) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Please verify your email address before logging in', 'houzez-api')
            ], 403);
        }

        // Check if user approval is enabled and user is approved
        $approval_enabled = houzez_login_option('enable_user_approval', 0);
        if ($approval_enabled) {
            $approval_status = get_user_meta($user->ID, 'houzez_account_approved', true);
            if ($approval_status !== '' && $approval_status !== null) {
                $status = intval($approval_status);
                if ($status === -1) {
                    return new WP_REST_Response([
                        'success' => false,
                        'message' => esc_html__('Your registration has been declined', 'houzez-api')
                    ], 403);
                } elseif ($status === 0) {
                    return new WP_REST_Response([
                        'success' => false,
                        'message' => esc_html__('Your account is pending approval. Please wait for an administrator to activate it', 'houzez-api')
                    ], 403);
                } elseif ($status === 2) {
                    return new WP_REST_Response([
                        'success' => false,
                        'message' => esc_html__('Your account has been suspended. Please contact an administrator for assistance', 'houzez-api')
                    ], 403);
                }
            }
        }

        $token = $token_result['token'];

        // Get user's agent/agency ID if exists
        $agent_id = get_user_meta($user->ID, 'fave_author_agent_id', true);
        $agency_id = get_user_meta($user->ID, 'fave_author_agency_id', true);

        // Get avatar URL - check for custom avatar first
        $custom_avatar = get_user_meta($user->ID, 'fave_author_custom_picture', true);
        if (!empty($custom_avatar)) {
            $avatar_url = $custom_avatar;
        } else {
            $avatar_url = get_avatar_url($user->ID);
        }

        // Get approval status if user approval system is enabled
        $approval_status = null;
        $approval_enabled = houzez_login_option('enable_user_approval', 0);
        if ($approval_enabled) {
            $status = get_user_meta($user->ID, 'houzez_account_approved', true);
            if ($status !== '' && $status !== null) {
                $status_int = intval($status);
                switch ($status_int) {
                    case 1:
                        $approval_status = 'approved';
                        break;
                    case 0:
                        $approval_status = 'pending';
                        break;
                    case -1:
                        $approval_status = 'declined';
                        break;
                    case 2:
                        $approval_status = 'suspended';
                        break;
                    default:
                        $approval_status = 'approved';
                }
            } else {
                $approval_status = 'approved'; // Default for users without status
            }
        }

        // Get user data
        $user_data = array(
            'user_id' => $user->ID,
            'token' => $token,
            'email' => $user->user_email,
            'username' => $user->user_login,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar_url' => $avatar_url,
            'phone' => get_user_meta($user->ID, 'fave_author_phone', true),
            'mobile' => get_user_meta($user->ID, 'fave_author_mobile', true),
            'whatsapp' => get_user_meta($user->ID, 'fave_author_whatsapp', true),
            'role' => $user->roles[0],
            'registered_date' => $user->user_registered,
            'agent_id' => $agent_id ? intval($agent_id) : null,
            'agency_id' => $agency_id ? intval($agency_id) : null,
            'approval_status' => $approval_status
        );

        if( $enable_paid_submission == 'membership' ) {
            $user_data['remaining_listings'] = houzez_get_remaining_listings($user->ID);
        }

        // Allow developers to hook after successful login
        do_action('houzez_after_login', $user->ID, $request);

        // Additional hook specific to API login
        do_action('houzez_api_after_login', $user->ID, $request);

        return new WP_REST_Response([
            'success' => true,
            'data' => $user_data,
            'message' => esc_html__('Login successful', 'houzez-api')
        ], 200);
    }

    /**
     * Reset password
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function reset_password($request) {
        $params = $request->get_params();
        
        if (empty($params['email'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Email is required', 'houzez-api')
            ], 400);
        }

        $user_email = sanitize_email($params['email']);

        if (!is_email($user_email)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid email address', 'houzez-api')
            ], 400);
        }

        $user = get_user_by('email', $user_email);
        
        if (!$user) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('There is no user registered with that email address', 'houzez-api')
            ], 404);
        }

        // Generate reset key
        $key = get_password_reset_key($user);
        
        if (is_wp_error($key)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $key->get_error_message()
            ], 400);
        }

        // Get reset link
        $reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');

        // Prepare email content
        $message = esc_html__('Someone has requested a password reset for the following account:', 'houzez-api') . "\r\n\r\n";
        $message .= network_home_url('/') . "\r\n\r\n";
        $message .= sprintf(esc_html__('Username: %s', 'houzez-api'), $user->user_login) . "\r\n\r\n";
        $message .= esc_html__('If this was a mistake, ignore this email and nothing will happen.', 'houzez-api') . "\r\n\r\n";
        $message .= esc_html__('To reset your password, visit the following address:', 'houzez-api') . "\r\n\r\n";
        $message .= $reset_link . "\r\n";

        // Get site name
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        // Email subject
        $title = sprintf(esc_html__('[%s] Password Reset Request', 'houzez-api'), $site_name);

        // Filter hooks for customization
        $title = apply_filters('retrieve_password_title', $title, $user->user_login, $user);
        $message = apply_filters('retrieve_password_message', $message, $key, $user->user_login, $user);

        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        if (wp_mail($user->user_email, wp_specialchars_decode($title), $message, $headers)) {
            do_action('houzez_after_reset_password_email_sent', $user);

            return new WP_REST_Response([
                'success' => true,
                'message' => esc_html__('Password reset instructions have been sent to your email address', 'houzez-api')
            ], 200);
        }

        return new WP_REST_Response([
            'success' => false,
            'message' => esc_html__('The email could not be sent. Possible reason: your host may have disabled the mail() function', 'houzez-api')
        ], 500);
    }

    /**
     * Logout user
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function logout_user($request) {
        // Allow developers to hook before logout
        do_action('houzez_before_logout', $request);

        // Get the Authorization header
        $auth_header = $request->get_header('Authorization');
        
        // Check if Authorization header is provided
        if (empty($auth_header)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authorization header is required', 'houzez-api')
            ], 401);
        }
        
        // Extract the token from the Authorization header
        // Format should be "Bearer {token}"
        $token_parts = explode(' ', $auth_header);
        if (count($token_parts) !== 2 || $token_parts[0] !== 'Bearer') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid Authorization header format', 'houzez-api')
            ], 401);
        }
        
        $token = $token_parts[1];

        // Check if JWT Shield plugin is available
        if (!Houzez_API_JWT_Helper::is_jwt_shield_available()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => Houzez_API_JWT_Helper::get_missing_plugin_message()
            ], 500);
        }

        // Get current user
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No authenticated user found', 'houzez-api')
            ], 401);
        }

        // Handle token invalidation
        // Since JWT tokens are stateless, we can't truly invalidate them server-side
        // But we can provide hooks for developers to implement their own token invalidation
        // or blacklisting mechanism if needed
        do_action('houzez_api_invalidate_token', $token, $user_id);
        
        // Use WordPress's built-in logout function to properly clean up the session
        wp_logout();

        // Additional hook specific to API logout
        do_action('houzez_api_after_logout', $user_id, $request);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Logout successful', 'houzez-api')
        ], 200);
    }
} 