<?php
/**
 * JWT Shield compatibility helper
 *
 * Provides a unified interface for working with JWT Shield Pro and JWT Shield Lite
 *
 * @since      1.0.0
 * @package    Houzez_API
 * @subpackage Houzez_API/includes/helpers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_JWT_Helper {

    /**
     * Check if any version of JWT Shield is available
     *
     * @return bool
     */
    public static function is_jwt_shield_available() {
        return class_exists('Jwt_Shield_Auth') || class_exists('Jwt_Shield_Lite_Auth');
    }

    /**
     * Get the JWT Shield auth instance
     *
     * @return object|null
     */
    public static function get_jwt_auth_instance() {
        if (class_exists('Jwt_Shield_Auth')) {
            return Jwt_Shield_Auth::instance();
        } elseif (class_exists('Jwt_Shield_Lite_Auth')) {
            return Jwt_Shield_Lite_Auth::instance();
        }
        
        return null;
    }

    /**
     * Generate JWT token
     *
     * @param WP_REST_Request|array $request
     * @return array|WP_Error
     */
    public static function generate_token($request) {
        $jwt_auth = self::get_jwt_auth_instance();
        
        if (!$jwt_auth) {
            return new WP_Error(
                'jwt_shield_not_found',
                esc_html__('JWT Shield authentication plugin is not installed or activated', 'houzez-api'),
                array('status' => 500)
            );
        }

        return $jwt_auth->generate_token($request);
    }

    /**
     * Check which version of JWT Shield is active
     *
     * @return string|false Returns 'pro', 'lite', or false
     */
    public static function get_jwt_shield_version() {
        if (class_exists('Jwt_Shield_Auth')) {
            return 'pro';
        } elseif (class_exists('Jwt_Shield_Lite_Auth')) {
            return 'lite';
        }
        
        return false;
    }

    /**
     * Check if Pro version is active
     *
     * @return bool
     */
    public static function is_pro_version() {
        return self::get_jwt_shield_version() === 'pro';
    }

    /**
     * Check if Lite version is active
     *
     * @return bool
     */
    public static function is_lite_version() {
        return self::get_jwt_shield_version() === 'lite';
    }

    /**
     * Get JWT Shield capabilities
     *
     * @return array
     */
    public static function get_capabilities() {
        $version = self::get_jwt_shield_version();
        
        if (!$version) {
            return array();
        }

        $capabilities = array(
            'basic_auth' => true,
            'token_generation' => true,
            'token_validation' => true,
        );

        if ($version === 'pro') {
            $capabilities = array_merge($capabilities, array(
                'refresh_tokens' => true,
                'token_analytics' => true,
                'ip_management' => true,
                'advanced_security' => true,
                'multiple_algorithms' => true,
                'token_revocation' => true,
                'email_notifications' => true,
            ));
        }

        return $capabilities;
    }

    /**
     * Check if a specific feature is available
     *
     * @param string $feature
     * @return bool
     */
    public static function has_feature($feature) {
        $capabilities = self::get_capabilities();
        return isset($capabilities[$feature]) && $capabilities[$feature];
    }

    /**
     * Get user-friendly plugin name
     *
     * @return string
     */
    public static function get_plugin_name() {
        $version = self::get_jwt_shield_version();
        
        if ($version === 'pro') {
            return 'JWT Shield Pro';
        } elseif ($version === 'lite') {
            return 'JWT Shield Lite';
        }
        
        return 'JWT Shield';
    }

    /**
     * Get appropriate error message for missing JWT Shield
     *
     * @return string
     */
    public static function get_missing_plugin_message() {
        return sprintf(
            esc_html__('JWT authentication is required. Please install and activate %s or %s plugin.', 'houzez-api'),
            'JWT Shield Pro',
            'JWT Shield Lite'
        );
    }

    /**
     * Get instruction message for current JWT Shield version
     *
     * @param string $feature Feature name that requires Pro version
     * @return string|null
     */
    public static function get_upgrade_message($feature = null) {
        if (!self::is_lite_version()) {
            return null;
        }

        if ($feature) {
            return sprintf(
                esc_html__('This feature (%s) requires JWT Shield Pro. Please upgrade to access advanced features.', 'houzez-api'),
                $feature
            );
        }

        return esc_html__('Some features require JWT Shield Pro. Please upgrade to access advanced functionality.', 'houzez-api');
    }

    /**
     * Check if system meets JWT requirements
     *
     * @return bool
     */
    public static function meets_requirements() {
        // Check if JWT Shield is available
        if (!self::is_jwt_shield_available()) {
            return false;
        }

        // Check if required functions exist
        $auth = self::get_jwt_auth_instance();
        if (!$auth || !method_exists($auth, 'generate_token')) {
            return false;
        }

        return true;
    }
} 