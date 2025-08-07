<?php
/**
 * API Authentication
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Auth extends Houzez_API_Base {
    


    /**
     * Initialize the class
     */
    public function init() {
        // No initialization needed for static methods
    }

    /**
     * Check if user is authenticated via JWT
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public static function check_authentication($request) {
        // This will validate the JWT token
        $user_id = apply_filters('determine_current_user', false);
        
        if (!$user_id || $user_id === 0) {
            return new WP_Error(
                'rest_not_logged_in',
                esc_html__('You must be logged in to access this endpoint.', 'houzez-api'),
                array('status' => 401)
            );
        }

        return true;
    }

    /**
     * Check if current user is admin or editor
     *
     * @return bool
     */
    public static function is_admin_or_editor() {
        return Houzez_API_Helper::is_admin() || Houzez_API_Helper::is_editor();
    }

    /**
     * Check if user has permission to manage properties
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public static function can_manage_posts($request) {
        $result = self::check_authentication($request);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Check if user has permission to manage properties
        if (!self::is_admin_or_editor()) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permission to manage content', 'houzez-api'),
                array('status' => 403)
            );
        }
        
        return true;
    }

    /**
     * Check if user has admin permissions only
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public static function can_manage_admin_only($request) {
        $result = self::check_authentication($request);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Check if user is admin
        if (!Houzez_API_Helper::is_admin()) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permission to access this resource. Admin only.', 'houzez-api'),
                array('status' => 403)
            );
        }
        
        return true;
    }

    /**
     * Check if user has permission to access user data
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public static function can_access_user_data($request) {
        $result = self::check_authentication($request);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $current_user_id = get_current_user_id();
        
        // Validate user_id parameter if it exists
        if (isset($request['user_id'])) {
            // Check if user_id is numeric
            if (!is_numeric($request['user_id'])) {
                return new WP_Error(
                    'rest_invalid_param',
                    esc_html__('Invalid user ID format. User ID must be numeric.', 'houzez-api'),
                    array('status' => 400)
                );
            }
            
            $requested_user_id = intval($request['user_id']);
            
            // Check if user_id is valid (greater than 0)
            if ($requested_user_id <= 0) {
                return new WP_Error(
                    'rest_invalid_param',
                    esc_html__('Invalid user ID. User ID must be a positive number.', 'houzez-api'),
                    array('status' => 400)
                );
            }
            
            // Check if the requested user exists
            if (!get_user_by('ID', $requested_user_id)) {
                return new WP_Error(
                    'rest_user_not_found',
                    esc_html__('User not found.', 'houzez-api'),
                    array('status' => 404)
                );
            }
        } else {
            $requested_user_id = $current_user_id;
        }
        
        // Users can always access their own data
        if ($requested_user_id == $current_user_id) {
            return true;
        }
        
        // Only admins and editors can access other users' data
        if (!self::is_admin_or_editor()) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permission to access this user data', 'houzez-api'),
                array('status' => 403)
            );
        }
        
        return true;
    }

    /**
     * Check if user has permission to upload media
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public static function can_upload_media($request) {
        $result = self::check_authentication($request);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $user_id = get_current_user_id();
        
        // Check if user has upload_files capability
        if (!user_can($user_id, 'upload_files')) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permission to upload files.', 'houzez-api'),
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Check if user has permission to view media
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public static function can_view_media($request) {
        $result = self::check_authentication($request);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // For now, any authenticated user can view media
        // This can be customized based on specific requirements
        
        return true;
    }
    
    /**
     * Check if user has permission to edit media
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public static function can_edit_media($request) {
        $result = self::check_authentication($request);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $user_id = get_current_user_id();
        $attachment_id = isset($request['id']) ? absint($request['id']) : 0;
        
        if (!$attachment_id) {
            return new WP_Error(
                'rest_invalid_param',
                esc_html__('Invalid media ID.', 'houzez-api'),
                array('status' => 400)
            );
        }
        
        $attachment = get_post($attachment_id);
        
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return new WP_Error(
                'rest_not_found',
                esc_html__('Media not found.', 'houzez-api'),
                array('status' => 404)
            );
        }
        
        // Check if user is the author of the attachment or has edit_others_posts capability
        if ($attachment->post_author != $user_id && !user_can($user_id, 'edit_others_posts')) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permission to edit this media.', 'houzez-api'),
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Check if user has permission to delete media
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public static function can_delete_media($request) {
        $result = self::check_authentication($request);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $user_id = get_current_user_id();
        $attachment_id = isset($request['id']) ? absint($request['id']) : 0;
        
        if (!$attachment_id) {
            return new WP_Error(
                'rest_invalid_param',
                esc_html__('Invalid media ID.', 'houzez-api'),
                array('status' => 400)
            );
        }
        
        $attachment = get_post($attachment_id);
        
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return new WP_Error(
                'rest_not_found',
                esc_html__('Media not found.', 'houzez-api'),
                array('status' => 404)
            );
        }
        
        // Check if user is the author of the attachment or has delete_others_posts capability
        if ($attachment->post_author != $user_id && !user_can($user_id, 'delete_others_posts')) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permission to delete this media.', 'houzez-api'),
                array('status' => 403)
            );
        }
        
        return true;
    }
    
} 