<?php
/**
 * Social Login Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure JWT helper is loaded
if (!class_exists('Houzez_API_JWT_Helper')) {
    require_once HOUZEZ_API_PLUGIN_DIR . 'includes/helpers/class-houzez-api-jwt-helper.php';
}

class Houzez_API_Endpoint_Social_Login extends Houzez_API_Base {

    /**
     * Initialize the class
     */
    public function init() {
        // No initialization needed
    }

    /**
     * Get Facebook OAuth URL
     */
    public static function get_facebook_oauth_url($request) {
        try {
            $facebook_api = houzez_option('facebook_api_key');
            $facebook_secret = houzez_option('facebook_secret');

            if (empty($facebook_api) || empty($facebook_secret)) {
                return new WP_Error(
                    'facebook_not_configured',
                    esc_html__('Facebook API keys are not configured.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            // Check if Facebook plugin is available
            $facebook_dir = WP_PLUGIN_DIR . '/houzez-login-register/social/Facebook/';
            if (!file_exists($facebook_dir . 'autoload.php')) {
                return new WP_Error(
                    'facebook_sdk_missing',
                    esc_html__('Facebook SDK is not available.', 'houzez-api'),
                    array('status' => 500)
                );
            }

            require_once $facebook_dir . 'autoload.php';

            $redirect_uri = $request->get_param('redirect_uri') ?: 'urn:ietf:wg:oauth:2.0:oob';

            $fb = new Facebook\Facebook([
                'app_id' => $facebook_api,
                'app_secret' => $facebook_secret,
                'default_graph_version' => 'v3.2',
            ]);

            $helper = $fb->getRedirectLoginHelper();
            $permissions = array('public_profile', 'email');
            $loginUrl = $helper->getLoginUrl($redirect_uri, $permissions);

            $response_data = array(
                'success' => true,
                'data' => array(
                    'oauth_url' => $loginUrl,
                    'redirect_uri' => $redirect_uri,
                    'permissions' => $permissions,
                    'app_id' => $facebook_api
                )
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'facebook_oauth_error',
                esc_html__('Error generating Facebook OAuth URL: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Facebook authentication with code
     */
    public static function facebook_auth($request) {
        try {
            $code = $request->get_param('code');
            $state = $request->get_param('state');

            $facebook_api = houzez_option('facebook_api_key');
            $facebook_secret = houzez_option('facebook_secret');

            if (empty($facebook_api) || empty($facebook_secret)) {
                return new WP_Error(
                    'facebook_not_configured',
                    esc_html__('Facebook API keys are not configured.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $facebook_dir = WP_PLUGIN_DIR . '/houzez-login-register/social/Facebook/';
            require_once $facebook_dir . 'autoload.php';

            $fb = new Facebook\Facebook([
                'app_id' => $facebook_api,
                'app_secret' => $facebook_secret,
                'default_graph_version' => 'v3.2',
            ]);

            $helper = $fb->getRedirectLoginHelper();

            try {
                $accessToken = $helper->getAccessToken();
            } catch (Facebook\Exceptions\FacebookResponseException $e) {
                return new WP_Error(
                    'facebook_graph_error',
                    'Graph returned an error: ' . $e->getMessage(),
                    array('status' => 400)
                );
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                return new WP_Error(
                    'facebook_sdk_error',
                    'Facebook SDK returned an error: ' . $e->getMessage(),
                    array('status' => 400)
                );
            }

            if (!$accessToken) {
                return new WP_Error(
                    'facebook_access_token_error',
                    esc_html__('Error getting access token.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            return self::process_facebook_user($fb, $accessToken);

        } catch (Exception $e) {
            return new WP_Error(
                'facebook_auth_error',
                esc_html__('Error during Facebook authentication: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Facebook login with access token
     */
    public static function facebook_login($request) {
        try {
            $access_token = $request->get_param('access_token');

            $facebook_api = houzez_option('facebook_api_key');
            $facebook_secret = houzez_option('facebook_secret');

            if (empty($facebook_api) || empty($facebook_secret)) {
                return new WP_Error(
                    'facebook_not_configured',
                    esc_html__('Facebook API keys are not configured.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $facebook_dir = WP_PLUGIN_DIR . '/houzez-login-register/social/Facebook/';
            require_once $facebook_dir . 'autoload.php';

            $fb = new Facebook\Facebook([
                'app_id' => $facebook_api,
                'app_secret' => $facebook_secret,
                'default_graph_version' => 'v3.2',
            ]);

            // Create access token object
            $accessToken = new Facebook\Authentication\AccessToken($access_token);

            return self::process_facebook_user($fb, $accessToken);

        } catch (Exception $e) {
            return new WP_Error(
                'facebook_login_error',
                esc_html__('Error during Facebook login: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get Google OAuth URL
     */
    public static function get_google_oauth_url($request) {
        try {
            $google_client_id = houzez_option('google_client_id');
            $google_client_secret = houzez_option('google_secret');

            if (empty($google_client_id) || empty($google_client_secret)) {
                return new WP_Error(
                    'google_not_configured',
                    esc_html__('Google OAuth credentials are not configured.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $google_dir = WP_PLUGIN_DIR . '/houzez-login-register/social/';
            if (!file_exists($google_dir . 'google/Google_Client.php')) {
                return new WP_Error(
                    'google_sdk_missing',
                    esc_html__('Google SDK is not available.', 'houzez-api'),
                    array('status' => 500)
                );
            }

            require_once $google_dir . 'google/Google_Client.php';
            require_once $google_dir . 'google/contrib/Google_Oauth2Service.php';

            $redirect_uri = $request->get_param('redirect_uri') ?: 'urn:ietf:wg:oauth:2.0:oob';

            $client = new Google_Client();
            $client->setApplicationName('Houzez API');
            $client->setClientId($google_client_id);
            $client->setClientSecret($google_client_secret);
            $client->setRedirectUri($redirect_uri);
            $client->setScopes(array('email', 'profile'));

            $authUrl = $client->createAuthUrl();

            $response_data = array(
                'success' => true,
                'data' => array(
                    'oauth_url' => $authUrl,
                    'redirect_uri' => $redirect_uri,
                    'scopes' => array('email', 'profile'),
                    'client_id' => $google_client_id
                )
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'google_oauth_error',
                esc_html__('Error generating Google OAuth URL: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Google authentication with code
     */
    public static function google_auth($request) {
        try {
            $code = $request->get_param('code');
            $redirect_uri = $request->get_param('redirect_uri');

            $google_client_id = houzez_option('google_client_id');
            $google_client_secret = houzez_option('google_secret');

            if (empty($google_client_id) || empty($google_client_secret)) {
                return new WP_Error(
                    'google_not_configured',
                    esc_html__('Google OAuth credentials are not configured.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $google_dir = WP_PLUGIN_DIR . '/houzez-login-register/social/';
            require_once $google_dir . 'google/Google_Client.php';
            require_once $google_dir . 'google/contrib/Google_Oauth2Service.php';

            $client = new Google_Client();
            $client->setApplicationName('Houzez API');
            $client->setClientId($google_client_id);
            $client->setClientSecret($google_client_secret);
            
            if ($redirect_uri) {
                $client->setRedirectUri($redirect_uri);
            }
            
            $client->setScopes(array('email', 'profile'));

            $google_oauthV2 = new Google_Oauth2Service($client);

            try {
                $client->authenticate($code);
                $access_token = $client->getAccessToken();
            } catch (Exception $e) {
                return new WP_Error(
                    'google_auth_failed',
                    esc_html__('Google authentication failed: ', 'houzez-api') . $e->getMessage(),
                    array('status' => 400)
                );
            }

            if (!$access_token) {
                return new WP_Error(
                    'google_access_token_error',
                    esc_html__('Error getting Google access token.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            return self::process_google_user($google_oauthV2, $access_token);

        } catch (Exception $e) {
            return new WP_Error(
                'google_auth_error',
                esc_html__('Error during Google authentication: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Google login with access token
     */
    public static function google_login($request) {
        try {
            $access_token = $request->get_param('access_token');

            $google_client_id = houzez_option('google_client_id');
            $google_client_secret = houzez_option('google_secret');

            if (empty($google_client_id) || empty($google_client_secret)) {
                return new WP_Error(
                    'google_not_configured',
                    esc_html__('Google OAuth credentials are not configured.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $google_dir = WP_PLUGIN_DIR . '/houzez-login-register/social/';
            require_once $google_dir . 'google/Google_Client.php';
            require_once $google_dir . 'google/contrib/Google_Oauth2Service.php';

            $client = new Google_Client();
            $client->setApplicationName('Houzez API');
            $client->setClientId($google_client_id);
            $client->setClientSecret($google_client_secret);
            $client->setScopes(array('email', 'profile'));

            $google_oauthV2 = new Google_Oauth2Service($client);

            // Set the access token
            $client->setAccessToken($access_token);

            return self::process_google_user($google_oauthV2, $access_token);

        } catch (Exception $e) {
            return new WP_Error(
                'google_login_error',
                esc_html__('Error during Google login: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Link Facebook account to existing user
     */
    public static function link_facebook_account($request) {
        try {
            $facebook_id = $request->get_param('facebook_id');
            $email = $request->get_param('email');

            // Validate inputs
            if (empty($facebook_id) || empty($email)) {
                return new WP_Error(
                    'missing_parameters',
                    esc_html__('Facebook ID and email are required.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            // Check if user exists
            $user = get_user_by('email', $email);
            if (!$user) {
                return new WP_Error(
                    'user_not_found',
                    esc_html__('User with this email does not exist.', 'houzez-api'),
                    array('status' => 404)
                );
            }

            // Link Facebook account
            update_option('houzez_user_facebook_id_' . $facebook_id, $email);

            $response_data = array(
                'success' => true,
                'data' => array(
                    'message' => 'Facebook account linked successfully.',
                    'user_id' => $user->ID,
                    'email' => $email,
                    'facebook_id' => $facebook_id
                )
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'link_facebook_error',
                esc_html__('Error linking Facebook account: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get social login configuration
     */
    public static function get_social_config($request) {
        try {
            $facebook_enabled = !empty(houzez_option('facebook_api_key')) && !empty(houzez_option('facebook_secret'));
            $google_enabled = !empty(houzez_option('google_client_id')) && !empty(houzez_option('google_secret'));

            $response_data = array(
                'success' => true,
                'data' => array(
                    'facebook' => array(
                        'enabled' => $facebook_enabled,
                        'app_id' => $facebook_enabled ? houzez_option('facebook_api_key') : null,
                    ),
                    'google' => array(
                        'enabled' => $google_enabled,
                        'client_id' => $google_enabled ? houzez_option('google_client_id') : null,
                    ),
                    'available_providers' => array_filter(array(
                        $facebook_enabled ? 'facebook' : null,
                        $google_enabled ? 'google' : null,
                    ))
                )
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'social_config_error',
                esc_html__('Error retrieving social configuration: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Process Facebook user data and login/register
     */
    private static function process_facebook_user($fb, $accessToken) {
        try {
            if (!$accessToken || $accessToken->isExpired()) {
                return new WP_Error(
                    'facebook_token_expired',
                    esc_html__('Facebook access token is expired.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $fb->setDefaultAccessToken($accessToken);

            try {
                $response = $fb->get('/me?fields=first_name,last_name,email,id', $accessToken);
                $user_data = $response->getGraphObject()->asArray();
            } catch (Exception $e) {
                return new WP_Error(
                    'facebook_api_error',
                    esc_html__('Error fetching user data from Facebook: ', 'houzez-api') . $e->getMessage(),
                    array('status' => 400)
                );
            }

            $profile_image_url = 'https://graph.facebook.com/' . $user_data['id'] . '/picture?width=300&height=300';

            $fb_email = isset($user_data['email']) ? $user_data['email'] : null;
            $fb_firstname = $user_data['first_name'];
            $fb_lastname = $user_data['last_name'];
            $facebook_id = $user_data['id'];

            // Handle case where email is not available
            if (is_null($fb_email)) {
                $linked_email = get_option('houzez_user_facebook_id_' . $facebook_id);
                if (empty($linked_email)) {
                    // Store Facebook info for later linking
                    $fb_info = array(
                        'id' => $facebook_id,
                        'picture_url' => $profile_image_url,
                        'first_name' => $fb_firstname,
                        'last_name' => $fb_lastname,
                    );
                    update_option('houzez_user_facebook_info_' . $facebook_id, $fb_info);

                    return new WP_Error(
                        'email_required_for_linking',
                        esc_html__('Email permission required. Please link your Facebook account to an existing email.', 'houzez-api'),
                        array(
                            'status' => 400,
                            'facebook_id' => $facebook_id,
                            'requires_linking' => true,
                            'user_info' => $fb_info
                        )
                    );
                } else {
                    $fb_email = $linked_email;
                }
            }

            return self::handle_social_user($fb_email, $fb_firstname, $fb_lastname, $facebook_id, $profile_image_url, 'facebook');

        } catch (Exception $e) {
            return new WP_Error(
                'facebook_process_error',
                esc_html__('Error processing Facebook user: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Process Google user data and login/register
     */
    private static function process_google_user($google_oauthV2, $access_token) {
        try {
            $user_data = $google_oauthV2->userinfo->get();

            $google_id = $user_data['id'];
            $display_name = $user_data['name'];
            $email = $user_data['email'];
            $first_name = isset($user_data['given_name']) ? $user_data['given_name'] : '';
            $last_name = isset($user_data['family_name']) ? $user_data['family_name'] : '';
            $profile_image_url = isset($user_data['picture']) ? filter_var($user_data['picture'], FILTER_VALIDATE_URL) : '';

            return self::handle_social_user($email, $first_name, $last_name, $google_id, $profile_image_url, 'google');

        } catch (Exception $e) {
            return new WP_Error(
                'google_process_error',
                esc_html__('Error processing Google user: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Handle social user login/registration
     */
    private static function handle_social_user($email, $first_name, $last_name, $social_id, $profile_image_url, $provider) {
        try {
            $display_name = trim($first_name . ' ' . $last_name);
            $username = explode('@', $email)[0];

            // Check if user exists
            $user = get_user_by('email', $email);

            if ($user) {
                // User exists, log them in
                $token_data = self::generate_jwt_token($user, $social_id);
                
                // Check if token generation failed
                if (is_wp_error($token_data)) {
                    return $token_data;
                }
                
                // Get user data in same format as normal login
                $user_data = self::format_user_response($user, $token_data['token'], $provider, $social_id);
                
                return new WP_REST_Response([
                    'success' => true,
                    'data' => $user_data,
                    'message' => esc_html__('Login successful', 'houzez-api')
                ], 200);

            } else {
                // User doesn't exist, register them
                if (function_exists('houzez_register_user_social')) {
                    houzez_register_user_social($email, $username, $display_name, $social_id, $profile_image_url);
                } else {
                    // Fallback registration
                    $user_data = array(
                        'user_login' => self::generate_unique_username($username),
                        'user_email' => $email,
                        'display_name' => $display_name,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'user_pass' => wp_generate_password(),
                    );

                    $user_id = wp_insert_user($user_data);
                    
                    if (is_wp_error($user_id)) {
                        return new WP_Error(
                            'registration_failed',
                            esc_html__('Failed to register user: ', 'houzez-api') . $user_id->get_error_message(),
                            array('status' => 500)
                        );
                    }

                    // Set profile image
                    if ($profile_image_url) {
                        update_user_meta($user_id, 'profile_image_url', $profile_image_url);
                    }
                }

                // Get the newly created user
                $new_user = get_user_by('email', $email);
                $token_data = self::generate_jwt_token($new_user, $social_id);
                
                // Check if token generation failed
                if (is_wp_error($token_data)) {
                    return $token_data;
                }

                // Get user data in same format as normal login
                $user_data = self::format_user_response($new_user, $token_data['token'], $provider, $social_id);

                return new WP_REST_Response([
                    'success' => true,
                    'data' => $user_data,
                    'message' => esc_html__('Registration and login successful', 'houzez-api')
                ], 200);
            }

        } catch (Exception $e) {
            return new WP_Error(
                'social_auth_error',
                esc_html__('Error during social authentication: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Format user response to match normal login response
     */
    private static function format_user_response($user, $token, $provider = null, $social_id = null) {
        $enable_paid_submission = houzez_option('enable_paid_submission');
        
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

        // Build user data in same format as normal login
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

        if ($enable_paid_submission == 'membership') {
            $user_data['remaining_listings'] = houzez_get_remaining_listings($user->ID);
        }
        
        // Add social login specific fields
        if ($provider) {
            $user_data['social_provider'] = $provider;
        }
        if ($social_id) {
            $user_data['social_id'] = $social_id;
        }

        return $user_data;
    }

    /**
     * Generate JWT token for user using JWT Shield plugin
     */
    private static function generate_jwt_token($user, $social_id = null) {
        // Check if JWT Shield plugin is available
        if (!Houzez_API_JWT_Helper::is_jwt_shield_available()) {
            return new WP_Error(
                'jwt_shield_missing',
                Houzez_API_JWT_Helper::get_missing_plugin_message(),
                array('status' => 500)
            );
        }

        try {
            // For social login users, the password is typically their social ID
            // This is consistent with how houzez_register_user_social works
            $social_password = $social_id;
            
            // If social_id is not provided, try to get it from user meta or fallback
            if (empty($social_password)) {
                // Try to find existing social password
                $social_password = get_user_meta($user->ID, 'houzez_social_login_pass', true);
                
                if (empty($social_password)) {
                    // Generate a secure password for social users and store it
                    $social_password = wp_generate_password(16, false);
                    wp_set_password($social_password, $user->ID);
                    update_user_meta($user->ID, 'houzez_social_login_pass', $social_password);
                }
            }

            // Prepare request for token generation
            $token_request = new WP_REST_Request('POST');
            $token_request->set_param('username', $user->user_login);
            $token_request->set_param('password', $social_password);

            // Generate token
            $token_result = Houzez_API_JWT_Helper::generate_token($token_request);
            
            if (is_wp_error($token_result)) {
                return $token_result;
            }

            return array(
                'token' => $token_result['token']
            );
            
        } catch (Exception $e) {
            return new WP_Error(
                'token_generation_failed',
                esc_html__('Token generation failed: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Generate unique username
     */
    private static function generate_unique_username($username) {
        if (function_exists('houzez_generate_unique_username')) {
            return houzez_generate_unique_username($username);
        }

        $original_username = $username;
        $counter = 1;
        
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        return $username;
    }
} 