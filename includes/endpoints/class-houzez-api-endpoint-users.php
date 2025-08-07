<?php
/**
 * Users Endpoint
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Endpoint_Users extends Houzez_API_Base {
    /**
     * Initialize the class
     */
    public function init() {
        // No specific initialization needed for this endpoint
    }
    
    /**
     * Get user profile
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_user_profile($request) {
       
        $user_id = get_current_user_id();

        $requested_user_id = isset($request['user_id']) ? intval($request['user_id']) : $user_id;

        // Get user data
        $requested_user = get_user_by('ID', $requested_user_id);
        if (!$requested_user) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('User not found', 'houzez-api')
            ], 404);
        }

        // Check if viewing own profile or has admin/editor permissions
        $is_own_profile = ($requested_user_id == $user_id);
        $can_view_full_profile = $is_own_profile || Houzez_API_Auth::is_admin_or_editor();

        // Basic user data available to everyone
        $user_data = array(
            'user_id' => $requested_user->ID,
            'display_name' => $requested_user->display_name,
            'first_name' => $requested_user->first_name,
            'last_name' => $requested_user->last_name,
            'properties_count' => count_user_posts($requested_user->ID, 'property'),
            'role' => $requested_user->roles[0],
            'is_current_user' => $is_own_profile
        );

        // Get avatar URL - check for custom avatar first
        $custom_avatar = get_user_meta($requested_user->ID, 'fave_author_custom_picture', true);
        if (!empty($custom_avatar)) {
            $user_data['avatar_url'] = $custom_avatar;
        } else {
            $user_data['avatar_url'] = get_avatar_url($requested_user->ID);
        }

        // Add sensitive data only for own profile or admins/editors
        if ($can_view_full_profile) {
            $user_data['email'] = $requested_user->user_email;
            $user_data['username'] = $requested_user->user_login;
            $user_data['phone'] = sanitize_text_field(get_user_meta($requested_user->ID, 'fave_author_phone', true));
            $user_data['mobile'] = sanitize_text_field(get_user_meta($requested_user->ID, 'fave_author_mobile', true));
            $user_data['whatsapp'] = sanitize_text_field(get_user_meta($requested_user->ID, 'fave_author_whatsapp', true));
            $user_data['favorites'] = get_user_meta($requested_user->ID, 'fave_favorites', true);
        }

        // Allow plugins to modify the user data
        $user_data = apply_filters('houzez_api_user_profile_data', $user_data, $requested_user, $can_view_full_profile);

        return new WP_REST_Response([
            'success' => true,
            'data' => $user_data
        ], 200);
    }

    public static function get_user_package($request) {
        $user_id = get_current_user_id();

        $requested_user_id = isset($request['user_id']) ? intval($request['user_id']) : $user_id;

        // Get user data
        $requested_user = get_user_by('ID', $requested_user_id);
        if (!$requested_user) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('User not found', 'houzez-api')
            ], 404);
        }

        // Check if viewing own profile or has admin/editor permissions
        $is_own_profile = ($requested_user_id == $user_id);
        $can_view_full_profile = $is_own_profile || Houzez_API_Auth::is_admin_or_editor();

        if( $can_view_full_profile ) {  
            $package = self::get_user_package_info($requested_user_id);
        } else {
            $package = array(
                'package_id' => 0,
                'package_name' => '',
                'package_description' => '',
                'package_price' => 0,
                'package_duration' => 0,
                'package_status' => 'inactive'
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $package
        ], 200);
    }

    public static function get_user_package_info($user_id) {
        $remaining_listings = houzez_get_remaining_listings( $user_id );
        $pack_featured_remaining_listings = houzez_get_featured_remaining_listings( $user_id );
        $package_id = houzez_get_user_package_id( $user_id );

        if( $remaining_listings == -1 ) {
            $remaining_listings = esc_html__('Unlimited', 'houzez');
        }

        if( $pack_featured_remaining_listings == -1 ) {
            $pack_featured_remaining_listings = esc_html__('Unlimited', 'houzez');
        }

        if( !empty( $package_id ) ) {

            $seconds = 0;
            $pack_title = get_the_title( $package_id );
            $pack_listings = get_post_meta( $package_id, 'fave_package_listings', true );
            $pack_unmilited_listings = get_post_meta( $package_id, 'fave_unlimited_listings', true );
            $pack_featured_listings = get_post_meta( $package_id, 'fave_package_featured_listings', true );
            $pack_billing_period = get_post_meta( $package_id, 'fave_billing_time_unit', true );
            $pack_billing_frequency = get_post_meta( $package_id, 'fave_billing_unit', true );
            $pack_date =  get_user_meta( $user_id, 'package_activation',true );

            if( $pack_billing_period == 'Day')
                $pack_billing_period = 'days';
            elseif( $pack_billing_period == 'Week')
                $pack_billing_period = 'weeks';
            elseif( $pack_billing_period == 'Month')
                $pack_billing_period = 'months';
            elseif( $pack_billing_period == 'Year')
                $pack_billing_period = 'years';

            $expired_date = strtotime($pack_date. ' + '.$pack_billing_frequency.' '.$pack_billing_period);
            $expired_date = date_i18n( get_option('date_format').' '.get_option('time_format'),  $expired_date );

            if( $pack_unmilited_listings == 1 ) {
                $pack_listings = -1;
                $remaining_listings = -1;
            } else {
                $pack_listings = intval( $pack_listings );
                $remaining_listings = intval( $remaining_listings );
            }

            if( $pack_featured_listings == -1 ) {
                $pack_featured_listings = -1;
                $pack_featured_remaining_listings = -1;
            } else {
                $pack_featured_listings = intval( $pack_featured_listings );
                $pack_featured_remaining_listings = intval( $pack_featured_remaining_listings );
            }


            $package_info = array(
                'package_id' => $package_id,
                'package_title' => $pack_title,
                'package_listings' => $pack_listings,
                'package_remaining_listings' => $remaining_listings,
                'package_featured_listings' => $pack_featured_listings,
                'package_remaining_featured_listings' => $pack_featured_remaining_listings,
                'package_billing_period' => $pack_billing_period,
                'package_billing_frequency' => $pack_billing_frequency,
                'package_expired_date' => $expired_date
            );  
        } else {
            $package_info = array(
                'package_id' => 0,
                'package_title' => '',
                'package_listings' => 0,
                'package_remaining_listings' => 0,
                'package_featured_listings' => 0,
                'package_remaining_featured_listings' => 0,
                'package_billing_period' => '',
                'package_billing_frequency' => '',
                'package_expired_date' => ''
            );
        }

        return $package_info;
        
    }
    
    /**
     * Get current user's properties
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_my_properties($request) {
        $params = $request->get_params();
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;
        $status = isset($params['post_status']) ? $params['post_status'] : array('publish');
        
        // Check if user is admin or editor
        $is_privileged = Houzez_API_Helper::is_admin() || Houzez_API_Helper::is_editor();
        
        // For privileged users, default view is 'all', for regular users, always 'mine'
        $default_view = $is_privileged ? 'all' : 'mine';
        $view = isset($params['view']) ? sanitize_text_field($params['view']) : $default_view;
        
        // Force 'mine' view for non-privileged users
        if (!$is_privileged) {
            $view = 'mine';
        }
        
        // Query for properties
        $args = array(
            'post_type' => 'property',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post_status' => $status
        );
        
        // Add author filter if viewing only own properties
        if ($view === 'mine') {
            $args['author'] = $user_id;
        }

        $query = new WP_Query($args);
        $properties = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $properties[] = Houzez_API_Endpoint_Properties::format_property(get_the_ID(), $query->post);
            }
        }

        wp_reset_postdata();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'properties' => $properties,
                'pagination' => Houzez_API_Helper::get_pagination_data($query, $params),
                'view' => $view,
                'can_view_all' => $is_privileged
            ]
        ], 200);
    }
    
    /**
     * Update user profile
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function update_user_profile($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $data   = $request->get_params();
        $result = houzez_process_update_profile( $data, false );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return rest_ensure_response( $result );
    }
    
    /**
     * Upload avatar for user
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function upload_avatar($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $params = $request->get_params();
        $files = $request->get_file_params();
        
        // Get the requested user ID - validation is already handled by can_access_user_data permission callback
        $requested_user_id = isset($params['user_id']) ? intval($params['user_id']) : $user_id;

        // Check if file was uploaded
        if (empty($files['avatar'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No avatar file was uploaded', 'houzez-api')
            ], 400);
        }

        $file = $files['avatar'];
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid file type. Only JPG, PNG and GIF are allowed', 'houzez-api')
            ], 400);
        }

        // Include necessary files for media handling
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Handle file upload
        $attachment_id = media_handle_upload('avatar', 0);

        if (is_wp_error($attachment_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $attachment_id->get_error_message()
            ], 400);
        }

        // Update user meta with the new avatar
        update_user_meta($requested_user_id, 'custom_avatar', $attachment_id);
        
        
        // Get the avatar URL
        $avatar_url = wp_get_attachment_url($attachment_id);
        update_user_meta($requested_user_id, 'fave_author_custom_picture', $avatar_url);

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'user_id' => $requested_user_id,
                'avatar_id' => $attachment_id,
                'avatar_url' => $avatar_url,
                'is_current_user' => ($requested_user_id == $user_id)
            ],
            'message' => esc_html__('Avatar uploaded successfully', 'houzez-api')
        ], 201);
    }
    
    /**
     * Delete user profile
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_profile($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $params = $request->get_params();
        
        // Get the requested user ID - validation is already handled by can_access_user_data permission callback
        $requested_user_id = isset($params['user_id']) ? intval($params['user_id']) : $user_id;
        
        // Get user data for hooks and additional checks
        $user = get_user_by('ID', $requested_user_id);
        
        // Check if trying to delete an admin user
        if (in_array('administrator', $user->roles) && !current_user_can('manage_options')) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to delete an administrator account', 'houzez-api')
            ], 403);
        }

        // Optional: Reassign posts to another user
        $reassign_to = isset($params['reassign_to']) ? intval($params['reassign_to']) : null;
        
        // Allow developers to hook before user deletion
        do_action('houzez_before_delete_profile', $requested_user_id, $user);
        
        // Include the file that contains wp_delete_user function
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        
        // Delete the user
        $result = wp_delete_user($requested_user_id, $reassign_to);
        
        if (!$result) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to delete user profile', 'houzez-api')
            ], 500);
        }

        // Allow developers to hook after user deletion
        do_action('houzez_after_delete_profile', $requested_user_id);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('User profile deleted successfully', 'houzez-api')
        ], 200);
    }
    
    /**
     * Delete user (Admin only)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_user($request) {
        $current_user_id = get_current_user_id();
        
        // Permission checks are already handled by can_manage_admin_only permission callback
        
        $user_id = isset($request['user_id']) ? intval($request['user_id']) : 0;
        
        // Check if user exists
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('User not found', 'houzez-api')
            ], 404);
        }

        // Prevent deleting yourself through this endpoint
        if ($user_id == $current_user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You cannot delete your own account through this endpoint', 'houzez-api')
            ], 403);
        }

        // Optional: Reassign posts to another user
        $reassign_to = isset($request['reassign_to']) ? $request['reassign_to'] : null;
        
        // Include the file that contains wp_delete_user function
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        
        $result = wp_delete_user($user_id, $reassign_to);
        
        if (!$result) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to delete user', 'houzez-api')
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('User deleted successfully', 'houzez-api')
        ], 200);
    }
    
    /**
     * List users (Admin only)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function list_users($request) {
        // Permission checks are already handled by can_manage_admin_only permission callback
        
        $args = array(
            'number' => isset($request['per_page']) ? $request['per_page'] : 10,
            'paged' => isset($request['paged']) ? $request['paged'] : 1,
            'orderby' => isset($request['orderby']) ? $request['orderby'] : 'registered',
            'order' => isset($request['order']) ? $request['order'] : 'DESC',
        );

        // Add role filter if specified
        if (isset($request['role'])) {
            $args['role'] = $request['role'];
        }

        // Add search if specified
        if (isset($request['search'])) {
            $args['search'] = '*' . $request['search'] . '*';
        }

        $users = get_users($args);
        $total_users = count_users();

        $user_data = array_map(function($user) {
            return array(
                'id' => $user->ID,
                'email' => $user->user_email,
                'username' => $user->user_login,
                'display_name' => $user->display_name,
                'role' => $user->roles[0],
                'registered_date' => $user->user_registered,
                'properties_count' => count_user_posts($user->ID, 'property')
            );
        }, $users);

        return new WP_REST_Response([
            'success' => true,
            'data' => $user_data,
            'total' => $total_users['total_users'],
            'pages' => ceil($total_users['total_users'] / $args['number'])
        ], 200);
    }
    
    /**
     * Duplicate a property
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function duplicate_property($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $property_id = isset($request['property_id']) ? intval($request['property_id']) : 0;
        
        // Check if property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }
        
        // Check if user has permission to duplicate this property
        if ($property->post_author != $user_id && !Houzez_API_Auth::is_admin_or_editor()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to duplicate this property', 'houzez-api')
            ], 403);
        }
        
        // Create duplicate property
        $new_property_id = self::create_property_duplicate($property);
        
        if (!$new_property_id || is_wp_error($new_property_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to duplicate property', 'houzez-api')
            ], 500);
        }
        
        // Get the duplicated property data
        $new_property = get_post($new_property_id);
        $property_data = Houzez_API_Endpoint_Properties::format_property($new_property_id, $new_property);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Property duplicated successfully', 'houzez-api'),
            'data' => [
                'property_id' => $new_property_id,
                'property' => $property_data
            ]
        ], 201);
    }
    
    /**
     * Create a duplicate of a property
     *
     * @param WP_Post $post
     * @return int|WP_Error
     */
    private static function create_property_duplicate($post) {
        // Create new post data array
        $args = array(
            'post_author'    => get_current_user_id(),
            'post_title'     => $post->post_title . ' ' . esc_html__('(Copy)', 'houzez-api'),
            'post_content'   => $post->post_content,
            'post_excerpt'   => $post->post_excerpt,
            'post_status'    => 'draft',
            'post_type'      => $post->post_type,
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status
        );
        
        // Insert the post
        $new_post_id = wp_insert_post($args);
        
        if (is_wp_error($new_post_id)) {
            return $new_post_id;
        }
        
        // Get all current post terms and set them to the new post
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }
        
        // Duplicate all post meta
        $post_meta = get_post_meta($post->ID);
        if ($post_meta) {
            foreach ($post_meta as $meta_key => $meta_values) {
                if ($meta_key == '_wp_old_slug') continue; // Skip old slug
                foreach ($meta_values as $meta_value) {
                    add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
                }
            }
        }
        
        // Add a note in post meta to indicate it's a duplicate
        add_post_meta($new_post_id, '_houzez_duplicated_from', $post->ID);
        
        // Allow developers to hook after property duplication
        do_action('houzez_after_duplicate_property', $new_post_id, $post);
        
        return $new_post_id;
    }

    /**
     * property actions (Admin/Editor only)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function property_actions( WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $property_id = intval( $request->get_param( 'property_id' ) );
        $action    = sanitize_text_field( $request->get_param( 'action' ) );
        
        // Check if property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }
    
        if ( ! current_user_can( 'edit_post', $property_id ) ) {
            return new WP_Error( 'permission_denied', __( 'Permission denied.', 'houzez' ), array( 'status' => 403 ) );
        }
    
        $result = houzez_process_property_action( $property_id, $action, $user_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
    
        return rest_ensure_response( $result );
    }

    /**
     * REST API callback to mark a property as sold or unsold.
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response|WP_Error Response on success or error.
     */
    public static function mark_sold_property(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }
        
        $property_id = isset($request['property_id']) ? intval($request['property_id']) : 0;
        
        // Check if property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Check if user has permission to update this property
        if ($property->post_author != $user_id && !Houzez_API_Auth::is_admin_or_editor()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to update this property', 'houzez-api')
            ], 403);
        }

        $result = houzez_process_property_mark_sold($property_id, $user_id);
        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message()
            ], 400);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Property status updated successfully', 'houzez-api'),
            'data' => $result
        ], 200);
    }
    
    /**
     * Approve a property (Admin/Editor only)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function approve_property($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }
        
        $property_id = isset($request['property_id']) ? intval($request['property_id']) : 0;
        
        // Check if property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Check if user has permission to update this property
        if ($property->post_author != $user_id && !Houzez_API_Auth::is_admin_or_editor()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to update this property', 'houzez-api')
            ], 403);
        }
        
        // Store current status to check later
        $listing_status = get_post_status($property_id);
        
        // Update property status to publish
        $updated = wp_update_post(array(
            'ID' => $property_id,
            'post_status' => 'publish'
        ));
        
        if (!$updated || is_wp_error($updated)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to approve property', 'houzez-api')
            ], 500);
        }
        
        // Get property author
        $author_id = $property->post_author;
        
        // Send approval email
        $listing_title = get_the_title($property_id);
        $listing_url = get_permalink($property_id);
        
        $args = array(
            'listing_title' => $listing_title,
            'listing_url'   => $listing_url,
        );
        
        houzez_email_type(get_userdata($author_id)->user_email, 'listing_approved', $args);
        
        // If previously disapproved and user has available listings, update package
        if ('disapproved' === $listing_status && houzez_get_remaining_listings($author_id) > 0) {
            houzez_update_package_listings($author_id);
        }
        
        // Allow developers to hook after property approval
        do_action('houzez_after_property_approved', $property_id, $user_id);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Property approved successfully', 'houzez-api'),
            'data' => [
                'property_id' => $property_id,
                'status' => 'publish'
            ]
        ], 200);
    }
    
    /**
     * Disapprove a property (Admin/Editor only)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function disapprove_property($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }
        
        // Check if user has permission to disapprove properties
        if (!Houzez_API_Auth::is_admin_or_editor()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to disapprove properties', 'houzez-api')
            ], 403);
        }
        
        $property_id = isset($request['property_id']) ? intval($request['property_id']) : 0;
        
        // Check if property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }
        
        // Update property status to disapproved
        $updated = wp_update_post(array(
            'ID' => $property_id,
            'post_status' => 'disapproved'
        ));
        
        if (!$updated || is_wp_error($updated)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to disapprove property', 'houzez-api')
            ], 500);
        }
        
        // Get property author
        $author_id = $property->post_author;
        
        // Send disapproval email
        $listing_title = get_the_title($property_id);
        $listing_url = get_permalink($property_id);
        
        $args = array(
            'listing_title' => $listing_title,
            'listing_url'   => $listing_url,
        );
        
        houzez_email_type(get_userdata($author_id)->user_email, 'listing_disapproved', $args);
        
        // Adjust package listings if below package limit
        $package_id = get_the_author_meta('package_id', $author_id);
        $user_package_listings = (int) get_the_author_meta('package_listings', $author_id);
        $packagelistings = (int) get_post_meta($package_id, 'fave_package_listings', true);
        
        if ($user_package_listings < $packagelistings) {
            update_user_meta($author_id, 'package_listings', $user_package_listings + 1);
        }
        
        // Allow developers to hook after property disapproval
        do_action('houzez_after_property_disapproved', $property_id, $user_id);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Property disapproved successfully', 'houzez-api'),
            'data' => [
                'property_id' => $property_id,
                'status' => 'disapproved'
            ]
        ], 200);
    }
    
    /**
     * Put a property on hold
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function hold_property($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }
        
        $property_id = isset($request['property_id']) ? intval($request['property_id']) : 0;
        
        // Check if property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }
        
        // Check if user has permission to update this property
        if ($property->post_author != $user_id && !Houzez_API_Auth::is_admin_or_editor()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to update this property', 'houzez-api')
            ], 403);
        }

        // Check if user has permission to update this property
        if ($property->post_author != $user_id && !Houzez_API_Auth::is_admin_or_editor()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to update this property', 'houzez-api')
            ], 403);
        }
        
        // Update property status to draft
        $updated = wp_update_post(array(
            'ID' => $property_id,
            'post_status' => 'on_hold'
        ));
        
        if (!$updated || is_wp_error($updated)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to put property on hold', 'houzez-api')
            ], 500);
        }
        
        // Allow developers to hook after property is put on hold
        do_action('houzez_after_property_on_hold', $property_id, $user_id);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Property put on hold successfully', 'houzez-api'),
            'data' => [
                'property_id' => $property_id,
                'status' => 'on_hold'
            ]
        ], 200);
    }
    
    /**
     * Make a property live
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function live_property($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }
        
        $property_id = isset($request['property_id']) ? intval($request['property_id']) : 0;
        
        // Check if property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }
        
        // Check if user has permission to update this property
        if ($property->post_author != $user_id && !Houzez_API_Auth::is_admin_or_editor()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to update this property', 'houzez-api')
            ], 403);
        }
        
        // Check if auto-approval is enabled
        $auto_approval = houzez_option('listings_admin_approved');
        $new_status = ($auto_approval == 'no' || Houzez_API_Auth::is_admin_or_editor()) ? 'publish' : 'pending';
        
        // Update property status
        $updated = wp_update_post(array(
            'ID' => $property_id,
            'post_status' => $new_status
        ));
        
        if (!$updated || is_wp_error($updated)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to make property live', 'houzez-api')
            ], 500);
        }
        
        // Allow developers to hook after property is made live
        do_action('houzez_after_property_live', $property_id, $user_id, $new_status);
        
        $message = ($new_status == 'publish') 
            ? esc_html__('Property is now live', 'houzez-api') 
            : esc_html__('Property submitted for approval', 'houzez-api');
        
        return new WP_REST_Response([
            'success' => true,
            'message' => $message,
            'data' => [
                'property_id' => $property_id,
                'status' => $new_status
            ]
        ], 200);
    }

    /**
     * Get user's favorite properties
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_favorite_properties($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $params = $request->get_params();
        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        // Get favorite property IDs
        $favorite_ids = get_user_meta($user_id, 'houzez_favorites', true);
        $favorite_ids = empty($favorite_ids) ? array() : (array)$favorite_ids;

        if (empty($favorite_ids)) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'properties' => array(),
                    'total' => 0,
                    'pages' => 0,
                    'page' => $paged,
                    'per_page' => $per_page
                ]
            ], 200);
        }

        $args = array(
            'post_type' => 'property',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post__in' => $favorite_ids,
            'post_status' => 'publish'
        );

        $query = new WP_Query($args);
        $properties = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $properties[] = Houzez_API_Endpoint_Properties::format_property(get_the_ID(), $query->post);
            }
        }

        wp_reset_postdata();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'properties' => $properties,
                'pagination' => Houzez_API_Helper::get_pagination_data($query, $params)
            ]
        ], 200);
    }

    /**
     * Get user invoices
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_invoices($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        // Check if requesting a specific invoice
        $invoice_id = isset($request['invoice_id']) ? intval($request['invoice_id']) : 0;
        
        if ($invoice_id > 0) {
            return self::get_single_invoice($invoice_id, $user_id);
        }

        $params = $request->get_params();
        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 20;
        
        // Get filter parameters
        $invoice_status = isset($params['invoice_status']) ? sanitize_text_field($params['invoice_status']) : '';
        $invoice_type = isset($params['invoice_type']) ? sanitize_text_field($params['invoice_type']) : '';
        $startDate = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : '';
        $endDate = isset($params['end_date']) ? sanitize_text_field($params['end_date']) : '';
        $mine = isset($params['mine']) ? filter_var($params['mine'], FILTER_VALIDATE_BOOLEAN) : false;

        $meta_query = array();
        $date_query = array();

        $invoices_args = array(
            'post_type' => 'houzez_invoice',
            'posts_per_page' => $per_page,
            'paged' => $paged
        );

        // If not admin/editor, only show user's invoices
        if (!Houzez_API_Auth::is_admin_or_editor()) {
            $meta_query[] = array(
                'key' => 'HOUZEZ_invoice_buyer',
                'value' => $user_id,
                'compare' => '='
            );
        }

        // Filter by author if 'mine' parameter is true
        if ($mine) {
            $invoices_args['author'] = $user_id;
        }

        // Filter by invoice status
        if (!empty($invoice_status)) {
            $meta_query[] = array(
                'key' => 'invoice_payment_status',
                'value' => $invoice_status,
                'type' => 'NUMERIC',
                'compare' => '='
            );
        }

        // Filter by invoice type
        if (!empty($invoice_type)) {
            $meta_query[] = array(
                'key' => 'HOUZEZ_invoice_for',
                'value' => $invoice_type,
                'type' => 'CHAR',
                'compare' => 'LIKE'
            );
        }

        // Add date filters
        if (!empty($startDate)) {
            $date_query[] = array('after' => $startDate);
        }
        if (!empty($endDate)) {
            $date_query[] = array('before' => $endDate);
        }

        // Add meta query if exists
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $invoices_args['meta_query'] = $meta_query;
        }

        // Add date query if exists
        if (!empty($date_query)) {
            $invoices_args['date_query'] = $date_query;
        }

        $invoice_query = new WP_Query($invoices_args);
        $invoices = array();

        if ($invoice_query->have_posts()) {
            while ($invoice_query->have_posts()) {
                $invoice_query->the_post();
                $invoice_id = get_the_ID();
                
                $invoices[] = self::format_invoice_data($invoice_id);
            }
        }

        wp_reset_postdata();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'pagination' => Houzez_API_Helper::get_pagination_data($invoice_query, $params)
            ]
        ], 200);
    }

    /**
     * Get single invoice detail
     *
     * @param int $invoice_id
     * @param int $user_id
     * @return WP_REST_Response
     */
    private static function get_single_invoice($invoice_id, $user_id) {
        // Check if invoice exists
        $invoice = get_post($invoice_id);
        if (!$invoice || $invoice->post_type !== 'houzez_invoice') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invoice not found', 'houzez-api')
            ], 404);
        }

        // Check if user has permission to view this invoice
        $invoice_buyer = get_post_meta($invoice_id, 'HOUZEZ_invoice_buyer', true);
        if (!Houzez_API_Auth::is_admin_or_editor() && $invoice_buyer != $user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to view this invoice', 'houzez-api')
            ], 403);
        }

        // Get invoice data
        $invoice_data = self::format_invoice_data($invoice_id);

        // Add company info
        $invoice_data['company_info'] = array(
            'logo' => houzez_option('invoice_logo', false, 'url'),
            'company_name' => houzez_option('invoice_company_name'),
            'address' => houzez_option('invoice_address'),
            'phone' => houzez_option('invoice_phone'),
            'additional_info' => houzez_option('invoice_additional_info'),
            'thank_you_note' => houzez_option('invoice_thankyou')
        );

        return new WP_REST_Response([
            'success' => true,
            'data' => $invoice_data
        ], 200);
    }

    /**
     * Format invoice data
     *
     * @param int $invoice_id
     * @return array
     */
    private static function format_invoice_data($invoice_id) {
        $invoice = get_post($invoice_id);
        $invoice_meta = houzez_get_invoice_meta($invoice_id);
        $buyer_id = get_post_meta($invoice_id, 'HOUZEZ_invoice_buyer', true);
        $buyer = get_user_by('ID', $buyer_id);

        // Get billing type
        $billing_type = get_post_meta($invoice_id, 'HOUZEZ_invoice_type', true);
        if ($billing_type == 'Recurring') {
            $billing_type = esc_html__('Recurring', 'houzez');
        } elseif ($billing_type == 'One Time') {
            $billing_type = esc_html__('One Time', 'houzez');
        }

        // Get billing for
        $billing_for = get_post_meta($invoice_id, 'HOUZEZ_invoice_for', true);
        if ($billing_for == 'listing' || $billing_for == 'Listing') {
            $billing_for = esc_html__('Listing', 'houzez');
        } elseif ($billing_for == 'UPGRADE TO FEATURED') {
            $billing_for = esc_html__('Upgrade to Featured', 'houzez');
        } elseif ($billing_for == 'package' || $billing_for == 'Package') {
            $billing_for = esc_html__('Membership Plan', 'houzez') . ' ' . get_the_title(get_post_meta($invoice_id, 'HOUZEZ_invoice_item_id', true));
        }

        // Format buyer name
        $buyer_name = '';
        if ($buyer) {
            if (!empty($buyer->first_name) && !empty($buyer->last_name)) {
                $buyer_name = $buyer->first_name . ' ' . $buyer->last_name;
            } else {
                $buyer_name = $buyer->display_name;
            }
        }

        return array(
            'id' => $invoice_id,
            'title' => get_the_title($invoice_id),
            'date' => array(
                'created' => get_the_date('c', $invoice_id),
                'formatted' => get_the_date(get_option('date_format'), $invoice_id)
            ),
            'status' => get_post_status($invoice_id),
            'payment_status' => get_post_meta($invoice_id, 'invoice_payment_status', true),
            'type' => $billing_type,
            'billing_for' => $billing_for,
            'item_id' => get_post_meta($invoice_id, 'HOUZEZ_invoice_item_id', true),
            'price' => array(
                'amount' => get_post_meta($invoice_id, 'HOUZEZ_invoice_price', true),
                'formatted' => houzez_get_invoice_price($invoice_meta['invoice_item_price'])
            ),
            'payment_method' => get_post_meta($invoice_id, 'HOUZEZ_invoice_payment_method', true),
            'buyer' => array(
                'id' => $buyer_id,
                'name' => $buyer_name,
                'email' => $buyer ? $buyer->user_email : '',
                'address' => get_user_meta($buyer_id, 'fave_author_address', true)
            ),
            'purchase_date' => get_post_meta($invoice_id, 'HOUZEZ_purchase_date', true)
        );
    }

    /**
     * Get user's saved searches
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_saved_searches($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $params = $request->get_params();
        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        // Calculate offset
        $offset = ($paged - 1) * $per_page;

        // Get total count first
        $table_name = $wpdb->prefix . 'houzez_search';
        $total_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE auther_id = %d",
            $user_id
        );
        $total_items = $wpdb->get_var($total_query);

        // Get paginated results
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE auther_id = %d ORDER BY id DESC LIMIT %d OFFSET %d",
            $user_id,
            $per_page,
            $offset
        );
        
        $results = $wpdb->get_results($query);

        if (empty($results)) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'saved_searches' => array(),
                    'pagination' => array(
                        'total' => 0,
                        'per_page' => $per_page,
                        'current_page' => $paged,
                        'total_pages' => 0
                    )
                ]
            ], 200);
        }

        $saved_searches = array();
        foreach ($results as $search) {
            // Get the query parameters
            $search_args = unserialize(base64_decode($search->query));
            parse_str($search->url, $search_params);

            $search_data = array(
                'id' => intval($search->id),
                'author_id' => intval($search->auther_id),
                'url' => $search->url,
                'search_link' => add_query_arg($search->url, houzez_get_template_link('template/template-search.php')),
                'email' => $search->email,
                'time' => $search->time,
                'parameters' => array()
            );

            // Add keyword if exists
            if (!empty($search_args['s'])) {
                $search_data['parameters']['keyword'] = $search_args['s'];
            }

            // Add location from search URI
            if (!empty($search_params['search_location'])) {
                $search_data['parameters']['location'] = urldecode($search_params['search_location']);
            }

            // Handle taxonomy queries
            if (isset($search_args['tax_query'])) {
                $taxonomy_labels = array(
                    'property_status' => 'status',
                    'property_type' => 'type',
                    'property_city' => 'city',
                    'property_country' => 'country',
                    'property_state' => 'state',
                    'property_area' => 'area',
                    'property_label' => 'label'
                );

                foreach ($search_args['tax_query'] as $tax_query) {
                    if (isset($tax_query['taxonomy'], $tax_query['terms']) && isset($taxonomy_labels[$tax_query['taxonomy']])) {
                        $term_value = hz_saved_search_term($tax_query['terms'], $tax_query['taxonomy']);
                        if (!empty($term_value)) {
                            $search_data['parameters'][$taxonomy_labels[$tax_query['taxonomy']]] = $term_value;
                        }
                    }
                }
            }

            // Handle meta queries
            if (isset($search_args['meta_query']) && is_array($search_args['meta_query'])) {
                $meta_mapping = array(
                    'fave_property_bedrooms' => 'bedrooms',
                    'fave_property_bathrooms' => 'bathrooms',
                    'fave_property_rooms' => 'rooms',
                    'fave_property_garage' => 'garage',
                    'fave_property_year' => 'year_built',
                    'fave_property_id' => 'property_id',
                    'fave_property_price' => 'price',
                    'fave_property_size' => 'size',
                    'fave_property_land' => 'land_area',
                    'fave_property_zip' => 'zip'
                );

                self::process_meta_query($search_args['meta_query'], $meta_mapping, $search_data['parameters']);
            }

            // Add features if they exist
            if (isset($search_args['tax_query'])) {
                foreach ($search_args['tax_query'] as $tax_query) {
                    if (isset($tax_query['taxonomy']) && $tax_query['taxonomy'] === 'property_feature') {
                        $search_data['parameters']['features'] = is_array($tax_query['terms']) ? $tax_query['terms'] : array($tax_query['terms']);
                        break;
                    }
                }
            }

            $saved_searches[] = $search_data;
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'saved_searches' => $saved_searches,
                'pagination' => array(
                    'total' => intval($total_items),
                    'per_page' => $per_page,
                    'current_page' => $paged,
                    'total_pages' => ceil($total_items / $per_page)
                )
            ]
        ], 200);
    }

    /**
     * Helper function to process meta query values
     *
     * @param array $meta_query
     * @param array $meta_mapping
     * @param array &$parameters
     */
    private static function process_meta_query($meta_query, $meta_mapping, &$parameters) {
        foreach ($meta_query as $query) {
            if (isset($query['relation'])) {
                self::process_meta_query($query, $meta_mapping, $parameters);
                continue;
            }

            if (isset($query['key']) && isset($meta_mapping[$query['key']])) {
                $param_key = $meta_mapping[$query['key']];
                
                if (isset($query['value'])) {
                    if (is_array($query['value'])) {
                        // Handle range values
                        if (isset($query['compare']) && in_array($query['compare'], array('BETWEEN', 'NOT BETWEEN'))) {
                            $parameters[$param_key . '_min'] = $query['value'][0];
                            $parameters[$param_key . '_max'] = $query['value'][1];
                        } else {
                            $parameters[$param_key] = implode(',', $query['value']);
                        }
                    } else {
                        $parameters[$param_key] = $query['value'];
                    }
                }
            }
        }
    }

    /**
     * Delete saved search
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_saved_search($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $search_id = isset($request['search_id']) ? intval($request['search_id']) : 0;
        if (!$search_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid search ID', 'houzez-api')
            ], 400);
        }

        $table_name = $wpdb->prefix . 'houzez_search';
        
        // Check if search exists and belongs to user
        $search = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d AND auther_id = %d",
            $search_id,
            $user_id
        ));

        if (!$search) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Saved search not found or access denied', 'houzez-api')
            ], 404);
        }

        // Delete the saved search
        $deleted = $wpdb->delete(
            $table_name,
            array('id' => $search_id),
            array('%d')
        );

        if (!$deleted) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to delete saved search', 'houzez-api')
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Saved search deleted successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Delete invoice
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_invoice($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $invoice_id = isset($request['invoice_id']) ? intval($request['invoice_id']) : 0;
        if (!$invoice_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid invoice ID', 'houzez-api')
            ], 400);
        }

        // Check if invoice exists
        $invoice = get_post($invoice_id);
        if (!$invoice || $invoice->post_type !== 'houzez_invoice') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invoice not found', 'houzez-api')
            ], 404);
        }

        // Check if user has permission to delete this invoice
        $invoice_buyer = get_post_meta($invoice_id, 'HOUZEZ_invoice_buyer', true);
        if (!Houzez_API_Auth::is_admin_or_editor() && $invoice_buyer != $user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to delete this invoice', 'houzez-api')
            ], 403);
        }

        // Delete the invoice
        $deleted = wp_delete_post($invoice_id, true);
        if (!$deleted) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to delete invoice', 'houzez-api')
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Invoice deleted successfully', 'houzez-api')
        ], 200);
    }
} 