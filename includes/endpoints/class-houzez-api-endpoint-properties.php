<?php
/**
 * Properties Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Endpoint_Properties extends Houzez_API_Base {

    public static $is_mobile = false;
    
    /**
     * Get the list of built-in meta fields that are already included in the property meta array
     * 
     * @return array Array of meta keys that are considered built-in fields
     */
    private static function get_built_in_meta_fields() {
        return array(
            // Price related fields
            'fave_property_price',
            'fave_property_sec_price',
            'fave_property_price_postfix',
            'fave_property_price_prefix',
            'fave_show_price_placeholder',
            'fave_property_price_placeholder',
            'fave_currency_info',
            
            // Property details
            'fave_property_rooms',
            'fave_property_bedrooms',
            'fave_property_bathrooms',
            'fave_property_size',
            'fave_property_size_prefix',
            'fave_property_land',
            'fave_property_land_postfix',
            'fave_property_garage',
            'fave_property_garage_size',
            'fave_property_year',
            'fave_featured',
            'fave_loggedintoview',
            'fave_property_id',
            'fave_property_country',
            'fave_payment_status',
            
            // Media and virtual tour
            'fave_video_url',
            'fave_virtual_tour',
            'fave_video_image',
            'fave_property_images',
            'fave_attachments',
            
            // Location related
            'fave_property_zip',
            'fave_property_address',
            'fave_property_map_address',
            'houzez_geolocation_lat',
            'houzez_geolocation_long',
            'fave_property_location',
            'fave_property_map_street_view',
            'fave_property_map',
            
            // Agent related
            'fave_agents',
            'fave_agent_display_option',
            'fave_property_agency',
            
            // Additional features
            'additional_features',
            'fave_additional_features_enable',
            'fave_floor_plans_enable',
            'fave_multiunit_plans_enable',
            'fave_multi_units_ids',
            
            // Display settings
            'fave_single_top_area',
            'fave_single_content_area',
            
            // Ratings and booking
            'fave_rating',
            'fave_booking_shortcode',
            
            // Energy class
            'fave_energy_global_index',
            'fave_renewable_energy_global_index',
            'fave_energy_performance',
            'fave_epc_current_rating',
            'fave_epc_potential_rating',
            'fave_energy_class'
        );
    }
    
    /**
     * Initialize properties endpoint
     * 
     * Base initialization method for property-specific functionality.
     * Override this method to add custom hooks and filters.
     * 
     * @since 1.0.0
     */
    public function init() {
        // Add initialization code here when needed
    }
    
    /**
     * Check if the current request is from a mobile device
     *
     * @return bool
     */
    public static function is_mobile() {
        return self::$is_mobile;
    }
    
    /**
     * Get properties
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_properties($request) { 
        $params = $request->get_params();
        $_GET = $params; // Set $_GET for Houzez search functions compatibility

        self::$is_mobile = Houzez_API_Helper::is_mobile_request($request);

        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        $args = array(
            'post_type' => 'property',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post_status' => 'publish'
        );
        
        // Apply featured filter
        $args = self::filter_featured_properties($args, $params);
        
        $args = apply_filters('houzez_sold_status_filter', $args);
        $search_qry = houzez_prop_sort($args);

        $query = new WP_Query($search_qry);
        $properties = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $properties[] = self::format_property(get_the_ID(), $query->post);
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
     * Search properties
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function search_properties($request) {
        $params = $request->get_params();
        $_GET = $params; // Set $_GET for Houzez search functions compatibility

        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        $args = array(
            'post_type' => 'property',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post_status' => 'publish'
        );

        // Apply Houzez search filters
        $args = apply_filters('houzez_sold_status_filter', $args);
        $args = apply_filters('houzez20_search_filters', $args);
        $search_qry = houzez_prop_sort($args);

        $query = new WP_Query($search_qry);
        $properties = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $properties[] = self::format_property(get_the_ID(), $query->post);
            }
        }

        wp_reset_postdata();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'properties' => $properties,
                'pagination' => Houzez_API_Helper::get_pagination_data($query, $params),
                'search_args' => base64_encode( serialize( $search_qry ) )
            ]
        ], 200);
    }

    /**
     * Get single property details
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_property($request) {
        $property_id = absint($request['id']);
        
        if (!$property_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid property ID', 'houzez-api')
            ], 400);
        }

        $property = get_post($property_id);
        
        if (!$property) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        if ($property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid post type. Requested ID is not a property', 'houzez-api')
            ], 400);
        }

        if ($property->post_status !== 'publish') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property is not published', 'houzez-api')
            ], 403);
        }

        $requires_login = (bool) get_post_meta($property_id, 'fave_loggedintoview', true);
        if ($requires_login && !is_user_logged_in()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Login required to view this property', 'houzez-api'),
                'requires_login' => true
            ], 401);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => self::format_property($property_id)
        ], 200);
    }

    /**
     * Filter properties by featured status
     *
     * @param array $args WP_Query arguments
     * @param array $params Request parameters
     * @return array Modified WP_Query arguments
     */
    private static function filter_featured_properties($args, $params) {
        // Return unmodified args if featured parameter is not set
        if (!isset($params['featured'])) {
            return $args;
        }

        // Initialize meta_query if it doesn't exist
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = array();
        }

        // Convert existing meta_query to array if it's not already
        if (!is_array($args['meta_query'])) {
            $args['meta_query'] = array($args['meta_query']);
        }

        if ($params['featured'] === '1') {
            // Get featured properties
            $args['meta_query'][] = array(
                'key' => 'fave_featured',
                'value' => '1',
                'compare' => '='
            );
        } elseif ($params['featured'] === '0') {
            // Get non-featured properties
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => 'fave_featured',
                    'value' => '0',
                    'compare' => '='
                ),
                array(
                    'key' => 'fave_featured',
                    'compare' => 'NOT EXISTS'
                )
            );
        }

        return $args;
    }

    /**
     * Create property
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function create_property($request) {
        $params = $request->get_params();
        $params = $request->get_params();
        $_POST = $params; // Set $_POST to request parameters
        
        $new_property = array(
            'post_type' => 'property',
        );

        $property_id = apply_filters('houzez_submit_listing', $new_property);

        if (is_wp_error($property_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $property_id->get_error_message()
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => self::format_property($property_id),
            'message' => esc_html__('Property created successfully', 'houzez-api')
        ], 201);
    }

    /**
     * Update property
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function update_property($request) {
        $property_id = intval($request['id']);
        $params = $request->get_params();
        $_POST = $params; // Set $_POST to request parameters

        $current_user_id = get_current_user_id();

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You must be logged in to delete properties.', 'houzez-api')
            ], 401);
        }
        
        $property = get_post($property_id);
        
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Get agency agents if applicable
        $agency_agents_array = array();
        $agency_agents = houzez_get_agency_agents($current_user_id);
        if ($agency_agents) {
            $agency_agents_array = $agency_agents;
        }

        // Check if user has permission to delete
        if ($property->post_author != $current_user_id && 
            !Houzez_API_Helper::is_admin() && 
            !Houzez_API_Helper::is_editor() && 
            !in_array($property->post_author, $agency_agents_array)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to delete this property', 'houzez-api')
            ], 403);
        }

        $new_property = array(
            'ID' => $property_id,
        );

        $updated_id = apply_filters('houzez_submit_listing', $new_property);

        if (is_wp_error($updated_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $updated_id->get_error_message()
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => self::format_property($property_id),
            'message' => esc_html__('Property updated successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Delete property
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_property($request) {
        $property_id = intval($request['id']);
        $current_user_id = get_current_user_id();

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You must be logged in to delete properties.', 'houzez-api')
            ], 401);
        }
        
        if (!$current_user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Unauthorized access', 'houzez-api')
            ], 401);
        }
        
        // Get property
        $property = get_post($property_id);
        
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Get agency agents if applicable
        $agency_agents_array = array();
        $agency_agents = houzez_get_agency_agents($current_user_id);
        if ($agency_agents) {
            $agency_agents_array = $agency_agents;
        }

        // Check if user has permission to delete
        if ($property->post_author != $current_user_id && 
            !Houzez_API_Helper::is_admin() && 
            !Houzez_API_Helper::is_editor() && 
            !in_array($property->post_author, $agency_agents_array)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('You do not have permission to delete this property', 'houzez-api')
            ], 403);
        }

        // Get the package user ID
        $package_user_id = $current_user_id;
        $agent_agency_id = houzez_get_agent_agency_id($current_user_id);
        if ($agent_agency_id) {
            $package_user_id = $agent_agency_id;
        }

        // Delete property attachments if not draft
        if (get_post_status($property_id) != 'draft') {
            houzez_delete_property_attachments_frontend($property_id);
        }
        
        // Delete the property
        $deleted = wp_delete_post($property_id, true);
        
        if (!$deleted) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to delete property', 'houzez-api')
            ], 500);
        }

        // Increment package listings count
        houzez_plusone_package_listings($package_user_id);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Property deleted successfully', 'houzez-api')
        ], 200);
    }

    private static function get_property_contact_info($property_id, $post_meta) {
        $return_array = array(
            'data' => array(),
            'is_single' => true,
            'contact_type' => ''
        );

        $agent_display = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agent_display_option', true);
        if($agent_display == 'none') {
            return $return_array;
        }

        $return_array['contact_type'] = $agent_display;

        switch($agent_display) { 
            case 'agent_info':
                $return_array = self::get_agent_contact_info($property_id, $return_array);
                break;

            case 'agency_info':
                $return_array = self::get_agency_contact_info($property_id, $return_array);
                break;

            default:
                $author_id = get_post_field('post_author', $property_id);
                $return_array = self::get_author_contact_info($author_id, $return_array);
                break;
        }

        return $return_array;
    }

    private static function get_agent_contact_info($property_id, $return_array) {
        $agents_ids = get_post_meta($property_id, 'fave_agents', false);
        
        // Filter and make unique
        $agents_ids = array_unique(array_filter($agents_ids, function($hz) {
            return ($hz > 0);
        }));

        if(empty($agents_ids)) {
            return $return_array;
        }

        // Set single/multiple agent flag
        $return_array['is_single'] = (count($agents_ids) === 1);

        foreach($agents_ids as $agent_id) {
            $agent_id = intval($agent_id);
            if($agent_id <= 0) continue;

            $properties_count = Houzez_Query::agent_properties_count($agent_id);
            $agent_meta = get_post_meta($agent_id);
            $rating = Houzez_API_Helper::get_post_meta_value($agent_meta, 'houzez_total_rating', true);

            $agent_data = array(
                'id' => intval($agent_id),  // Cast to integer using intval()
                'name' => get_the_title($agent_id),
                'link' => get_post_permalink($agent_id),
                
                // Contact details
                'phone' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_office_num', true),
                'mobile' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_mobile', true),
                'whatsapp' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_whatsapp', true),
                'email' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_email', true),
                'skype' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_skype', true),
                
                // Professional info
                'position' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_position', true),
                'company' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_company', true),
                'service_area' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_service_area', true),
                'specialties' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_specialties', true),
                'tax_no' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_tax_no', true),
                'properties_count' => $properties_count,
                'rating' => !empty($rating) ? round((float)$rating, 1) : 0,
                
                // Social media
                'facebook' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_facebook', true),
                'twitter' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_twitter', true),
                'linkedin' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_linkedin', true),
                'instagram' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_instagram', true),
                'youtube' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_youtube', true),
                'telegram' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_telegram', true),
                'lineapp' => Houzez_API_Helper::get_post_meta_value($agent_meta, 'fave_agent_line_id', true)
            );

            // Format phone numbers for calls
            $agent_data['mobile_call'] = Houzez_API_Helper::format_phone_number($agent_data['mobile']);
            $agent_data['phone_call'] = Houzez_API_Helper::format_phone_number($agent_data['phone']);
            $agent_data['whatsapp_call'] = Houzez_API_Helper::format_phone_number($agent_data['whatsapp']);

            // Get agent picture
            $agent_data['picture'] = self::get_agent_picture($agent_id, 'agent');

            $return_array['data'][] = $agent_data;
        }

        return $return_array;
    }

    private static function get_agency_contact_info($property_id, $return_array) {
        $agency_id = get_post_meta($property_id, 'fave_property_agency', true);
        if(!$agency_id) {
            return $return_array;
        }

        $properties_count = Houzez_Query::agency_properties_count($agency_id);
        $agency_meta = get_post_meta($agency_id);
        $rating = Houzez_API_Helper::get_post_meta_value($agency_meta, 'houzez_total_rating', true);

        $agency_data = array(
            'id' => intval($agency_id),
            'name' => get_the_title($agency_id),
            'link' => get_post_permalink($agency_id),
            
            // Contact details
            'phone' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_phone', true),
            'mobile' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_mobile', true),
            'whatsapp' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_whatsapp', true),
            'email' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_email', true),
            'skype' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_skype', true),
            
            // Agency specific info
            'service_area' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_service_area', true),
            'specialties' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_specialties', true),
            'properties_count' => $properties_count,
            'rating' => !empty($rating) ? round((float)$rating, 1) : 0,
            
            // Social media
            'facebook' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_facebook', true),
            'twitter' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_twitter', true),
            'linkedin' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_linkedin', true),
            'instagram' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_instagram', true),
            'youtube' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_youtube', true),
            'telegram' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_telegram', true),
            'lineapp' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_line_id', true),
            'tax_no' => Houzez_API_Helper::get_post_meta_value($agency_meta, 'fave_agency_tax_no', true)
        );

        // Format phone numbers for calls
        $agency_data['mobile_call'] = Houzez_API_Helper::format_phone_number($agency_data['mobile']);
        $agency_data['phone_call'] = Houzez_API_Helper::format_phone_number($agency_data['phone']);
        $agency_data['whatsapp_call'] = Houzez_API_Helper::format_phone_number($agency_data['whatsapp']);

        // Get agency picture
        $agency_data['picture'] = self::get_agent_picture($agency_id, 'agency');

        $return_array['data'][] = $agency_data;
        return $return_array;
    }

    private static function get_author_contact_info($author_id, $return_array) {

        // Get agent's properties count
        $properties_count = Houzez_Query::agent_properties_count($author_id);
        $rating = get_post_meta($author_id, 'houzez_total_rating', true);

        $author_data = array(
            'id' => intval($author_id),
            'name' => get_the_author_meta('display_name', $author_id),
            'link' => get_author_posts_url($author_id),
            
            // Contact details
            'phone' => get_the_author_meta('fave_author_phone', $author_id),
            'mobile' => get_the_author_meta('fave_author_mobile', $author_id),
            'whatsapp' => get_the_author_meta('fave_author_whatsapp', $author_id),
            'email' => get_the_author_meta('email', $author_id),
            'skype' => get_the_author_meta('fave_author_skype', $author_id),
            
            // Professional info
            'position' => get_the_author_meta('fave_author_title', $author_id),
            'company' => get_the_author_meta('fave_author_company', $author_id),
            'service_area' => get_the_author_meta('fave_author_service_areas', $author_id),
            'specialties' => get_the_author_meta('fave_author_specialties', $author_id),
            'tax_no' => get_the_author_meta('fave_author_tax_no', $author_id),
            'properties_count' => $properties_count,
            'rating' => !empty($rating) ? round((float)$rating, 1) : 0,
            
            // Social media
            'facebook' => get_the_author_meta('fave_author_facebook', $author_id),
            'twitter' => get_the_author_meta('fave_author_twitter', $author_id),
            'linkedin' => get_the_author_meta('fave_author_linkedin', $author_id),
            'instagram' => get_the_author_meta('fave_author_instagram', $author_id),
            'youtube' => get_the_author_meta('fave_author_youtube', $author_id),
            'telegram' => get_the_author_meta('fave_author_telegram', $author_id),
            'lineapp' => get_the_author_meta('fave_author_line_id', $author_id),
        );

        // Format phone numbers for calls
        $author_data['mobile_call'] = Houzez_API_Helper::format_phone_number($author_data['mobile']);
        $author_data['phone_call'] = Houzez_API_Helper::format_phone_number($author_data['phone']);
        $author_data['whatsapp_call'] = Houzez_API_Helper::format_phone_number($author_data['whatsapp']);

        // Get author picture
        $author_picture = get_the_author_meta('fave_author_custom_picture', $author_id);
        $author_data['picture'] = !empty($author_picture) ? $author_picture : HOUZEZ_IMAGE . 'profile-avatar.png';

        $return_array['data'][] = $author_data;
        return $return_array;
    }

    private static function get_agent_picture($agent_id, $type = 'agent') {
        $thumb_id = get_post_thumbnail_id($agent_id);
        if($thumb_id) {
            $thumb_url_array = wp_get_attachment_image_src($thumb_id, array(150,150), true);
            return $thumb_url_array[0];
        }

        // Return placeholder if no picture
        if($type === 'agency') {
            $placeholder_url = houzez_option('houzez_agency_placeholder', false, 'url');
        } else {
            $placeholder_url = houzez_option('houzez_agent_placeholder', false, 'url');
        }

        return !empty($placeholder_url) ? $placeholder_url : HOUZEZ_IMAGE . 'profile-avatar.png';
    }

    private static function get_property_gallery_images($post_meta) {
        $gallery_images_ids = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_images');
        $gallery_images = [];

        // Define the specific sizes we want
        $desired_sizes = array(
            'full',
            'large'
        );
        
        foreach ($gallery_images_ids as $gallery_image_id) {
            $image_data = array();
            
            // Handle full size separately since it uses a different function
            $image_data['full'] = wp_get_attachment_url($gallery_image_id);
            
            // Get URLs for each specific size
            foreach ($desired_sizes as $size) {
                if ($size === 'full') continue; // Skip full size as we already have it
                
                $image = wp_get_attachment_image_src($gallery_image_id, $size);
                if ($image) {
                    $image_data[$size] = $image[0];
                }
            }
            
            $gallery_images[] = $image_data;
        }
        return $gallery_images;
    }

    private static function get_featured_image_sizes($property_id) {
        $thumbnail_id = get_post_thumbnail_id($property_id);
        if (!$thumbnail_id) {
            return null;
        }

        $image_data = array(
            'full' => wp_get_attachment_url($thumbnail_id)
        );
        
        // Define the specific sizes we want
        $desired_sizes = array(
            'large',
        );
        
        // Get URLs for each specific size
        foreach ($desired_sizes as $size) {
            $image = wp_get_attachment_image_src($thumbnail_id, $size);
            if ($image) {
                $image_data[$size] = $image[0];
            }
        }
        
        return $image_data;
    }

    private static function update_property_meta($property_id, $params) {
        $meta_fields = array(
            'price' => 'fave_property_price',
            'bedrooms' => 'fave_property_bedrooms',
            'bathrooms' => 'fave_property_bathrooms',
            'size' => 'fave_property_size',
            'address' => 'fave_property_address',
            'latitude' => 'houzez_geolocation_lat',
            'longitude' => 'houzez_geolocation_long'
        );

        foreach ($meta_fields as $param_key => $meta_key) {
            if (isset($params[$param_key])) {
                update_post_meta($property_id, $meta_key, sanitize_text_field($params[$param_key]));
            }
        }

        // Handle property type taxonomy
        if (isset($params['property_type'])) {
            wp_set_object_terms($property_id, sanitize_text_field($params['property_type']), 'property_type');
        }

        // Handle property features
        if (isset($params['features']) && is_array($params['features'])) {
            wp_set_object_terms($property_id, array_map('sanitize_text_field', $params['features']), 'property-feature');
        }

        do_action('houzez_api_after_property_meta_update', $property_id, $params);
    }

    private static function get_formatted_address($property_id, $post_meta = null) {
        $address_composer = get_option('houzez_mobile_export_settings');
        
        $address_fields = !empty($address_composer['listings']['addressFields']) ? $address_composer['listings']['addressFields'] : array();
        $temp_array = array();
        
        // Filter to only include enabled fields (value = 1)
        $enabled_fields = array_filter($address_fields, function($value) {
            return $value == 1;
        });
 
        foreach ($enabled_fields as $key => $value) {
            switch ($key) {
                case 'address':
                    $map_address = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_map_address', true);
                    if (!empty($map_address)) {
                        $temp_array[] = $map_address;
                    }
                    break;

                case 'street_address':
                    $property_address = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_address', true);
                    if (!empty($property_address)) {
                        $temp_array[] = $property_address;
                    }
                    break;

                case 'country':
                    $terms = wp_get_post_terms($property_id, 'property_country');
                    if (!is_wp_error($terms) && !empty($terms)) {
                        $temp_array[] = $terms[0]->name;
                    }
                    break;

                case 'state':
                    $terms = wp_get_post_terms($property_id, 'property_state');
                    if (!is_wp_error($terms) && !empty($terms)) {
                        $temp_array[] = $terms[0]->name;
                    }
                    break;

                case 'city':
                    $terms = wp_get_post_terms($property_id, 'property_city');
                    if (!is_wp_error($terms) && !empty($terms)) {
                        $temp_array[] = $terms[0]->name;
                    }
                    break;

                case 'area':
                    $terms = wp_get_post_terms($property_id, 'property_area');
                    if (!is_wp_error($terms) && !empty($terms)) {
                        $temp_array[] = $terms[0]->name;
                    }
                    break;
            }
        }

        return implode(", ", $temp_array);
    }

    /**
     * Get formatted floor plans data
     *
     * @param int $property_id
     * @return array
     */
    private static function get_floor_plans($post_meta) {
        $floor_plans = Houzez_API_Helper::get_post_meta_value($post_meta, 'floor_plans', true);
        
        if (empty($floor_plans)) {
            return array();
        }

        // If floor plans are stored as serialized string, unserialize them
        if (is_string($floor_plans)) {
            $floor_plans = unserialize($floor_plans);
        }

        $formatted_plans = array();

        if (is_array($floor_plans)) {
            foreach ($floor_plans as $plan) {
                if (!empty($plan)) {
                    $formatted_plan = array(
                        'title' => isset($plan['fave_plan_title']) ? $plan['fave_plan_title'] : '',
                        'rooms' => isset($plan['fave_plan_rooms']) ? $plan['fave_plan_rooms'] : '',
                        'bathrooms' => isset($plan['fave_plan_bathrooms']) ? $plan['fave_plan_bathrooms'] : '',
                        'price' => isset($plan['fave_plan_price']) ? $plan['fave_plan_price'] : '',
                        'price_postfix' => isset($plan['fave_plan_price_postfix']) ? $plan['fave_plan_price_postfix'] : '',
                        'size' => isset($plan['fave_plan_size']) ? $plan['fave_plan_size'] : '',
                        'size_postfix' => isset($plan['fave_plan_size_postfix']) ? $plan['fave_plan_size_postfix'] : '',
                        'description' => isset($plan['fave_plan_description']) ? $plan['fave_plan_description'] : '',
                        'image' => isset($plan['fave_plan_image']) ? $plan['fave_plan_image'] : '',
                    );

                    // Get image URL if image ID is provided
                    if (!empty($formatted_plan['image'])) {
                        $image_url = wp_get_attachment_url($formatted_plan['image']);
                        if ($image_url) {
                            $formatted_plan['image_url'] = $image_url;
                        }
                    }

                    $formatted_plans[] = $formatted_plan;
                }
            }
        }

        return $formatted_plans;
    }

    /**
     * Get formatted property documents
     *
     * @param int $property_id
     * @return array
     */
    private static function get_property_documents($post_meta) {
        $documents_ids = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_attachments');
        $documents = array();

        if (empty($documents_ids) || !is_array($documents_ids)) {
            return $documents;
        }

        // Handle both string and array formats
        if (is_string($documents_ids)) {
            $documents_ids = explode(',', $documents_ids);
        }

        foreach ($documents_ids as $doc_id) {
            $doc_id = trim($doc_id);
            if (empty($doc_id)) continue;

            $attachment = get_post($doc_id);
            if (!$attachment) continue;

            $doc_url = wp_get_attachment_url($doc_id);
            if (!$doc_url) continue;

            $documents[] = array(
                'id' => $doc_id,
                'title' => $attachment->post_title,
                'description' => $attachment->post_content,
                'url' => $doc_url,
                'size' => size_format(filesize(get_attached_file($doc_id))),
                'type' => get_post_mime_type($doc_id),
                'date' => get_the_date('c', $doc_id)
            );
        }

        return $documents;
    }

    /**
     * Get formatted additional features
     *
     * @param int $property_id
     * @return array
     */
    private static function get_additional_features($post_meta) {
        $additional_features = Houzez_API_Helper::get_post_meta_value($post_meta, 'additional_features', true);
        $formatted_features = array();

        if (empty($additional_features)) {
            return $formatted_features;
        }

        // If stored as serialized string, unserialize it
        if (is_string($additional_features)) {
            $additional_features = unserialize($additional_features);
        }

        if (is_array($additional_features)) {
            foreach ($additional_features as $feature) {
                if (!empty($feature)) {
                    $formatted_feature = array(
                        'title' => isset($feature['fave_additional_feature_title']) ? $feature['fave_additional_feature_title'] : '',
                        'value' => isset($feature['fave_additional_feature_value']) ? $feature['fave_additional_feature_value'] : '',
                    );
                    
                    // Only add if either title or value is not empty
                    if (!empty($formatted_feature['title']) || !empty($formatted_feature['value'])) {
                        $formatted_features[] = $formatted_feature;
                    }
                }
            }
        }

        return $formatted_features;
    }

    public static function get_multi_units_ids($post_meta) {
        $multi_units_ids = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_multi_units_ids', true);
        return array_map('intval', array_filter(
            is_string($multi_units_ids) ? explode(',', $multi_units_ids) : (array) $multi_units_ids
        ));
    }

    public static function get_energy_class($post_meta) {
        return array(
            'global_energy_index' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_energy_global_index', true),
            'renewable_energy_index' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_renewable_energy_global_index', true),
            'energy_performance' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_energy_performance', true),
            'epc_current_rating' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_epc_current_rating', true),
            'epc_potential_rating' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_epc_potential_rating', true),
            'energy_class' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_energy_class', true),
        );  
    }

    public static function format_property($property_id, $post = null) {
        // If post object wasn't passed, get it (for backward compatibility)
        if (!$post) {
            $post = get_post($property_id);
        }
        
        // Get all post meta in a single call
        $post_meta = get_post_meta($property_id);
        
        // Use the helper function to get meta values
        $property_price = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_price', true);
        $property_sec_price = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_sec_price', true);
        $price_postfix = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_price_postfix', true);

        // Format prices based on whether second price exists
        $formatted_price = !empty($property_sec_price) 
            ? houzez_get_property_price($property_price)
            : houzez_get_property_price($property_price) . '/' . $price_postfix;

        $formatted_second_price = !empty($property_sec_price)
            ? houzez_get_property_price($property_sec_price) . '/' . $price_postfix
            : '';

        $property = array(
            'id' => $property_id,
            'title' => get_the_title($property_id),
            'slug' => $post->post_name,
            'property_url' => get_permalink($property_id),
            'content' => get_the_content(null, false, $property_id),
            'excerpt' => get_the_excerpt($property_id),
            'status' => get_post_status($property_id),
            'date' => get_the_date('c', $property_id),
            'modified' => get_the_modified_date('c', $property_id),
            'meta' => array(
                'price' => $property_price,
                'formatted_price' => $formatted_price,
                'second_price' => $property_sec_price,
                'formatted_second_price' => $formatted_second_price,
                'price_prefix' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_price_prefix', true),
                'price_postfix' => $price_postfix,
                'show_price_placeholder' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_show_price_placeholder', true, true),
                'price_placeholder' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_price_placeholder', true),
                'rooms' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_rooms', true),
                'bedrooms' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_bedrooms', true),
                'bathrooms' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_bathrooms', true),
                'size' => houzez_get_area_size(Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_size', true)),
                'size_unit' => houzez_get_size_unit(Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_size_prefix', true)),
                'land_size' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_land', true),
                'land_size_unit' => houzez_get_size_unit(Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_land_postfix', true)),
                'garage' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_garage', true),
                'garage_size' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_garage_size', true),
                'year' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_year', true),
                'is_featured' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_featured', true, true),
                'logged_in_to_view' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_loggedintoview', true, true),
                'property_id' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_id', true),
                'video_url' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_video_url', true),
                'virtual_tour' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_virtual_tour', true),
                'zipcode' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_zip', true),
                'address' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_address', true),
                'map_address' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_map_address', true),
                'latitude' => Houzez_API_Helper::get_post_meta_value($post_meta, 'houzez_geolocation_lat', true),
                'longitude' => Houzez_API_Helper::get_post_meta_value($post_meta, 'houzez_geolocation_long', true),
                'property_location' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_location', true),
                'property_map_street_view' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_map_street_view', true, true),
                'property_map' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_map', true, true),
            ),
        );
        
        $property['meta']['formatted_address'] = self::get_formatted_address($property_id, $post_meta);
        $property['meta']['additional_features'] = self::get_additional_features($post_meta);
        $property['meta']['floor_plans'] = self::get_floor_plans($post_meta);
        $property['meta']['documents'] = self::get_property_documents($post_meta);
        $property['meta']['multi_units_ids'] = self::get_multi_units_ids($post_meta);
        $property['meta']['energy_class'] = self::get_energy_class($post_meta);

        // Add custom fields with fave_ prefix
        $built_in_fields = self::get_built_in_meta_fields();
        
        foreach ($post_meta as $meta_key => $meta_value) {
            // Check if the meta key starts with 'fave_' and is not a built-in field
            if (strpos($meta_key, 'fave_') === 0 && !in_array($meta_key, $built_in_fields)) {
                // Remove the 'fave_' prefix for cleaner keys
                $clean_key = str_replace('fave_', '', $meta_key);
                $property['meta'][$clean_key] = Houzez_API_Helper::get_post_meta_value($post_meta, $meta_key, false);
            }
        }
        
        // Define property taxonomies to fetch
        $taxonomies = array(
            'property_type' => 'property_type',
            'property_status' => 'property_status',
            'property_label' => 'property_label',
            'property_country' => 'property_country',
            'property_state' => 'property_state',
            'property_city' => 'property_city',
            'property_area' => 'property_area',
            'property_features' => 'property_feature',
        );
        
        // Get all taxonomy terms
        foreach ($taxonomies as $key => $taxonomy) {
            $property[$key] = Houzez_API_Helper::get_formatted_terms($taxonomy, $property_id);
        }

        $property['contact_info'] = self::get_property_contact_info($property_id, $post_meta);
        $property['featured_image'] = self::get_featured_image_sizes($property_id);
        $property['gallery_images'] = self::get_property_gallery_images($post_meta);

        $property['author'] = array(
            'id' => intval($post->post_author),
            'name' => get_the_author_meta('display_name', $post->post_author)
        );

        return apply_filters('houzez_api_property_data', $property, $property_id);
    }

    /**
     * Format property data for listing cards with minimal required fields
     * 
     * @param int $property_id Property ID
     * @param WP_Post|null $post Post object
     * @return array Formatted property data for card display
     */
    public static function format_property_mobile($property_id, $post = null) {
        if (!$post) {
            $post = get_post($property_id);
        }

        $post_meta = get_post_meta($property_id);

        $property_price = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_price', true);
        $property_sec_price = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_sec_price', true);
        $price_postfix = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_price_postfix', true);

        // Format prices based on whether second price exists
        $formatted_price = !empty($property_sec_price) 
            ? houzez_get_property_price($property_price)
            : houzez_get_property_price($property_price) . '/' . $price_postfix;

        $formatted_second_price = !empty($property_sec_price)
            ? houzez_get_property_price($property_sec_price) . '/' . $price_postfix
            : '';
        
        // Get agent display option
        $agent_display = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agent_display_option', true);
        $agent_info = array();

        // Get single agent info based on display option
        if ($agent_display === 'agent_info') {
            // Get first agent if multiple agents exist
            $agents_ids = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agents', false);
            $agents_ids = array_filter($agents_ids, function($hz) {
                return ($hz > 0);
            });

            if (!empty($agents_ids)) {
                $agent_id = reset($agents_ids); // Get first agent ID
                $mobile = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agent_mobile', true);
                $agent_info = array(
                    'id' => $agent_id,
                    'name' => get_the_title($agent_id),
                    'position' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agent_position', true),
                    'company' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agent_company', true),
                    'mobile' => $mobile,
                    'mobile_call' => Houzez_API_Helper::format_phone_number($mobile),
                    'email' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agent_email', true),
                    'picture' => self::get_agent_picture($agent_id, 'agent'),
                    'tax_no' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agent_tax_no', true)
                );
            }
        } elseif ($agent_display === 'agency_info') {
            $agency_id = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_agency', true);
            if ($agency_id) {
                $mobile = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agency_mobile', true);
                $agent_info = array(
                    'id' => $agency_id,
                    'name' => get_the_title($agency_id),
                    'position' => '',  // Agencies don't have position
                    'company' => get_the_title($agency_id), // Use agency name as company
                    'mobile' => $mobile,
                    'mobile_call' => Houzez_API_Helper::format_phone_number($mobile),
                    'email' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agency_email', true),
                    'picture' => self::get_agent_picture($agency_id, 'agency'),
                    'tax_no' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_agency_tax_no', true)
                );
            }
        } elseif ($post && $post->post_author) {
            // Author info
            $mobile = Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_author_mobile', true);
            $agent_info = array(
                'id' => $post->post_author,
                'name' => get_the_author_meta('display_name', $post->post_author),
                'position' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_author_title', true),
                'company' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_author_company', true),
                'mobile' => $mobile,
                'mobile_call' => Houzez_API_Helper::format_phone_number($mobile), 
                'email' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_author_email', true),
                'picture' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_author_custom_picture', true),
                'tax_no' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_author_tax_no', true)
            );
            
            // Set default picture if none exists
            if (empty($agent_info['picture'])) {
                $agent_info['picture'] = HOUZEZ_IMAGE . 'profile-avatar.png';
            }
        }

        $property = array(
            'id' => $property_id,
            'title' => get_the_title($property_id),
            'slug' => $post->post_name,
            'property_url' => get_permalink($property_id),
            'meta' => array(
                'price' => $property_price,
                'formatted_price' => $formatted_price,
                'second_price' => $property_sec_price,
                'formatted_second_price' => $formatted_second_price,
                'show_price_placeholder' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_show_price_placeholder', true, true),
                'price_prefix' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_price_prefix', true),
                'price_postfix' => $price_postfix,
                'price_placeholder' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_price_placeholder', true),
                'rooms' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_rooms', true),
                'bedrooms' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_bedrooms', true),
                'bathrooms' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_bathrooms', true),
                'garage' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_garage', true),
                'garage_size' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_garage_size', true),
                'size' => houzez_get_area_size(Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_size', true)),
                'size_unit' => houzez_get_size_unit(Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_size_prefix', true)),
                'land_size' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_land', true),
                'land_size_unit' => houzez_get_size_unit(Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_land_postfix', true)),
                'property_id' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_property_id', true),
                'is_featured' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_featured', true, true),
                'logged_in_to_view' => Houzez_API_Helper::get_post_meta_value($post_meta, 'fave_loggedintoview', true, true),
                'card_address' => self::get_formatted_address($property_id, $post_meta),
            ),
            'property_type' => Houzez_API_Helper::get_formatted_terms('property_type', $property_id),
            'property_status' => Houzez_API_Helper::get_formatted_terms('property_status', $property_id),
            'property_label' => Houzez_API_Helper::get_formatted_terms('property_label', $property_id),
            'featured_image' => self::get_featured_image_sizes($property_id),
            'gallery_images' => self::get_property_gallery_images($post_meta),
            'contact_info' => array(
                'data' => array($agent_info), // Wrap in array to match existing structure
                'type' => $agent_display,
                'is_single' => true
            )
        );

        // Add custom fields with fave_ prefix
        $property['meta']['custom_fields'] = array();
        $built_in_fields = self::get_built_in_meta_fields();
        
        foreach ($post_meta as $meta_key => $meta_value) {
            // Check if the meta key starts with 'fave_' and is not a built-in field
            if (strpos($meta_key, 'fave_') === 0 && !in_array($meta_key, $built_in_fields)) {
                // Remove the 'fave_' prefix for cleaner keys
                $clean_key = str_replace('fave_', '', $meta_key);
                $property['meta']['custom_fields'][$clean_key] = Houzez_API_Helper::get_post_meta_value($post_meta, $meta_key, true);
            }
        }

        return apply_filters('houzez_api_property_card_data', $property, $property_id);
    }

    public static function get_property_types($request) {
        $params = $request->get_params();
        
        return Houzez_API_Helper::get_taxonomy_terms(
            $params,
            'property_type'
        );
    }

    public static function get_property_status($request) {
        $params = $request->get_params();
        
        return Houzez_API_Helper::get_taxonomy_terms(
            $params,
            'property_status'
        );
    }

    public static function get_property_labels($request) {
        $params = $request->get_params();
        
        return Houzez_API_Helper::get_taxonomy_terms(
            $params,
            'property_label'
        );
    }

    public static function get_property_countries($request) {
        $params = $request->get_params();
        
        return Houzez_API_Helper::get_taxonomy_terms(
            $params,
            'property_country'
        );
    }

    public static function get_property_states($request) {
        $params = $request->get_params();
        $country_slug = isset($params['country_slug']) ? $params['country_slug'] : '';
        
        // If no country_slug is provided, return all states
        if (empty($country_slug)) {
            return Houzez_API_Helper::get_taxonomy_terms(
                $params,
                'property_state'
            );
        }
        
        // If country_slug is provided, filter states by country
        // Get base arguments from helper
        $args = Houzez_API_Helper::get_taxonomy_query_args($params, 'property_state');
        
        $terms = get_terms($args);
        
        if (is_wp_error($terms)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No states found', 'houzez-api')
            ], 404);
        }

        $filtered_terms = array();
        foreach ($terms as $term) {
            $term_meta = get_option("_houzez_property_state_" . $term->term_id);
            if (!empty($term_meta) && 
                isset($term_meta['parent_country']) && 
                sanitize_title($term_meta['parent_country']) === $country_slug) {
                $filtered_terms[] = $term;
            }
        }

        if (empty($filtered_terms)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No states found for this country', 'houzez-api')
            ], 404);
        }

        // Apply pagination if number parameter is set
        if (!empty($params['number'])) {
            $offset = isset($params['offset']) ? intval($params['offset']) : 0;
            $number = intval($params['number']);
            $filtered_terms = array_slice($filtered_terms, $offset, $number);
        }

        // Format terms and ensure sequential array
        $formatted_terms = array_values(array_map([Houzez_API_Helper::class, 'format_taxonomy_term'], $filtered_terms));
        
        return new WP_REST_Response([
            'success' => true,
            'result' => $formatted_terms
        ]);
    }

    public static function get_property_cities($request) {
        $params = $request->get_params();
        $state_slug = isset($params['state_slug']) ? $params['state_slug'] : '';
        
        // If no state_slug is provided, return all cities
        if (empty($state_slug)) {
            return Houzez_API_Helper::get_taxonomy_terms(
                $params,
                'property_city'
            );
        }
        
        // If state_slug is provided, filter cities by state
        // Get base arguments from helper
        $args = Houzez_API_Helper::get_taxonomy_query_args($params, 'property_city');
        
        $terms = get_terms($args);
        
        if (is_wp_error($terms)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No cities found', 'houzez-api')
            ], 404);
        }

        $filtered_terms = array();
        foreach ($terms as $term) {
            $term_meta = get_option("_houzez_property_city_" . $term->term_id);
            if (!empty($term_meta) && 
                isset($term_meta['parent_state']) && 
                sanitize_title($term_meta['parent_state']) === $state_slug) {
                $filtered_terms[] = $term;
            }
        }

        if (empty($filtered_terms)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No cities found for this state', 'houzez-api')
            ], 404);
        }

        // Apply pagination if number parameter is set
        if (!empty($params['number'])) {
            $offset = isset($params['offset']) ? intval($params['offset']) : 0;
            $number = intval($params['number']);
            $filtered_terms = array_slice($filtered_terms, $offset, $number);
        }

        // Format terms and ensure sequential array
        $formatted_terms = array_values(array_map([Houzez_API_Helper::class, 'format_taxonomy_term'], $filtered_terms));
        
        return new WP_REST_Response([
            'success' => true,
            'result' => $formatted_terms
        ]);
    }

    public static function get_property_areas($request) {
        $params = $request->get_params();
        $city_slug = isset($params['city_slug']) ? $params['city_slug'] : '';
        
        // If no city_slug is provided, return all areas
        if (empty($city_slug)) {
            return Houzez_API_Helper::get_taxonomy_terms(
                $params,
                'property_area'
            );
        }
        
        // If city_slug is provided, filter areas by city
        // Get base arguments from helper
        $args = Houzez_API_Helper::get_taxonomy_query_args($params, 'property_area');
        
        $terms = get_terms($args);
        
        if (is_wp_error($terms)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No areas found', 'houzez-api')
            ], 404);
        }

        $filtered_terms = array();
        foreach ($terms as $term) {
            $term_meta = get_option("_houzez_property_area_" . $term->term_id);
            if (!empty($term_meta) && 
                isset($term_meta['parent_city']) && 
                sanitize_title($term_meta['parent_city']) === $city_slug) {
                $filtered_terms[] = $term;
            }
        }

        if (empty($filtered_terms)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No areas found for this city', 'houzez-api')
            ], 404);
        }

        // Apply pagination if number parameter is set
        if (!empty($params['number'])) {
            $offset = isset($params['offset']) ? intval($params['offset']) : 0;
            $number = intval($params['number']);
            $filtered_terms = array_slice($filtered_terms, $offset, $number);
        }

        // Format terms and ensure sequential array
        $formatted_terms = array_values(array_map([Houzez_API_Helper::class, 'format_taxonomy_term'], $filtered_terms));
        
        return new WP_REST_Response([
            'success' => true,
            'result' => $formatted_terms
        ]);
    }

    public static function get_property_features($request) {
        $params = $request->get_params();
        
        return Houzez_API_Helper::get_taxonomy_terms(
            $params,
            'property_feature'
        );
    }

    public static function get_areas_by_city($request) {
        $city_slug = $request['city_slug'];
        $params = $request->get_params();

        // Get base arguments from helper
        $args = Houzez_API_Helper::get_taxonomy_query_args($params, 'property_area');
        
        $terms = get_terms($args);
        
        if (is_wp_error($terms)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No areas found', 'houzez-api')
            ], 404);
        }

        $filtered_terms = array();
        foreach ($terms as $term) {
            $term_meta = get_option("_houzez_property_area_" . $term->term_id);
            if (!empty($term_meta) && 
                isset($term_meta['parent_city']) && 
                sanitize_title($term_meta['parent_city']) === $city_slug) {
                $filtered_terms[] = $term;
            }
        }

        if (empty($filtered_terms)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No areas found for this city', 'houzez-api')
            ], 404);
        }

        // Apply pagination if number parameter is set
        if (!empty($params['number'])) {
            $offset = isset($params['offset']) ? intval($params['offset']) : 0;
            $number = intval($params['number']);
            $filtered_terms = array_slice($filtered_terms, $offset, $number);
        }

        // Format terms and ensure sequential array
        $formatted_terms = array_values(array_map([Houzez_API_Helper::class, 'format_taxonomy_term'], $filtered_terms));
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $formatted_terms
        ], 200);
    }

    /**
     * Add property media
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function add_property_media($request) {
        $property_id = $request['id'];
        $files = $request->get_file_params();

        if (empty($files)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No files were uploaded', 'houzez-api')
            ], 400);
        }

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $uploaded_ids = array();
        foreach ($files as $file) {
            $attachment_id = media_handle_upload($file, $property_id);
            
            if (is_wp_error($attachment_id)) {
                continue;
            }
            
            $uploaded_ids[] = $attachment_id;
        }

        if (empty($uploaded_ids)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to upload media', 'houzez-api')
            ], 500);
        }

        // Add to property gallery
        $gallery_images = get_post_meta($property_id, 'fave_property_images', false);
        $gallery_images = array_merge($gallery_images, $uploaded_ids);
        update_post_meta($property_id, 'fave_property_images', $gallery_images);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Media uploaded successfully', 'houzez-api'),
            'data' => [
                'media_ids' => $uploaded_ids
            ]
        ], 201);
    }

    /**
     * Remove property media
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function remove_property_media($request) {
        $property_id = $request['id'];
        $media_id = $request['media_id'];

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Remove from property gallery
        $gallery_images = get_post_meta($property_id, 'fave_property_images', false);
        $gallery_images = array_diff($gallery_images, array($media_id));
        update_post_meta($property_id, 'fave_property_images', $gallery_images);

        // Delete the attachment
        $deleted = wp_delete_attachment($media_id, true);

        if (!$deleted) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to delete media', 'houzez-api')
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Media deleted successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Reorder property media
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function reorder_property_media($request) {
        $property_id = $request['id'];
        $media_order = $request->get_param('media_order');

        if (!is_array($media_order)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid media order format', 'houzez-api')
            ], 400);
        }

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        update_post_meta($property_id, 'fave_property_images', $media_order);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Media order updated successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Add property document
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function add_property_document($request) {
        $property_id = $request['id'];
        $files = $request->get_file_params();

        if (empty($files)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No files were uploaded', 'houzez-api')
            ], 400);
        }

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $uploaded_ids = array();
        foreach ($files as $file) {
            $attachment_id = media_handle_upload($file, $property_id);
            
            if (is_wp_error($attachment_id)) {
                continue;
            }
            
            $uploaded_ids[] = $attachment_id;
        }

        if (empty($uploaded_ids)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to upload documents', 'houzez-api')
            ], 500);
        }

        // Add to property documents
        $documents = get_post_meta($property_id, 'fave_attachments', false);
        $documents = array_merge($documents, $uploaded_ids);
        update_post_meta($property_id, 'fave_attachments', $documents);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Documents uploaded successfully', 'houzez-api'),
            'data' => [
                'document_ids' => $uploaded_ids
            ]
        ], 201);
    }

    /**
     * Remove property document
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function remove_property_document($request) {
        $property_id = $request['id'];
        $document_id = $request['document_id'];

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Remove from property documents
        $documents = get_post_meta($property_id, 'fave_attachments', false);
        $documents = array_diff($documents, array($document_id));
        update_post_meta($property_id, 'fave_attachments', $documents);

        // Delete the attachment
        $deleted = wp_delete_attachment($document_id, true);

        if (!$deleted) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to delete document', 'houzez-api')
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Document deleted successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Add floor plan
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function add_floor_plan($request) {
        $property_id = $request['id'];
        $plan_data = $request->get_params();

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Handle floor plan image upload if present
        if (isset($_FILES['plan_image'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('plan_image', $property_id);
            if (!is_wp_error($attachment_id)) {
                $plan_data['fave_plan_image'] = $attachment_id;
            }
        }

        // Get existing floor plans
        $floor_plans = get_post_meta($property_id, 'floor_plans', true);
        if (!is_array($floor_plans)) {
            $floor_plans = array();
        }

        // Add new floor plan
        $floor_plans[] = array(
            'fave_plan_title' => sanitize_text_field($plan_data['title'] ?? ''),
            'fave_plan_rooms' => sanitize_text_field($plan_data['rooms'] ?? ''),
            'fave_plan_bathrooms' => sanitize_text_field($plan_data['bathrooms'] ?? ''),
            'fave_plan_price' => sanitize_text_field($plan_data['price'] ?? ''),
            'fave_plan_price_postfix' => sanitize_text_field($plan_data['price_postfix'] ?? ''),
            'fave_plan_size' => sanitize_text_field($plan_data['size'] ?? ''),
            'fave_plan_size_postfix' => sanitize_text_field($plan_data['size_postfix'] ?? ''),
            'fave_plan_description' => wp_kses_post($plan_data['description'] ?? ''),
            'fave_plan_image' => $plan_data['fave_plan_image'] ?? ''
        );

        update_post_meta($property_id, 'floor_plans', $floor_plans);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Floor plan added successfully', 'houzez-api'),
            'data' => [
                'plan_id' => count($floor_plans) - 1
            ]
        ], 201);
    }

    /**
     * Update floor plan
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function update_floor_plan($request) {
        $property_id = $request['id'];
        $plan_id = $request['plan_id'];
        $plan_data = $request->get_params();

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Get existing floor plans
        $floor_plans = get_post_meta($property_id, 'floor_plans', true);
        if (!is_array($floor_plans) || !isset($floor_plans[$plan_id])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Floor plan not found', 'houzez-api')
            ], 404);
        }

        // Handle floor plan image upload if present
        if (isset($_FILES['plan_image'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('plan_image', $property_id);
            if (!is_wp_error($attachment_id)) {
                $plan_data['fave_plan_image'] = $attachment_id;
            }
        }

        // Update floor plan
        $floor_plans[$plan_id] = array(
            'fave_plan_title' => sanitize_text_field($plan_data['title'] ?? $floor_plans[$plan_id]['fave_plan_title']),
            'fave_plan_rooms' => sanitize_text_field($plan_data['rooms'] ?? $floor_plans[$plan_id]['fave_plan_rooms']),
            'fave_plan_bathrooms' => sanitize_text_field($plan_data['bathrooms'] ?? $floor_plans[$plan_id]['fave_plan_bathrooms']),
            'fave_plan_price' => sanitize_text_field($plan_data['price'] ?? $floor_plans[$plan_id]['fave_plan_price']),
            'fave_plan_price_postfix' => sanitize_text_field($plan_data['price_postfix'] ?? $floor_plans[$plan_id]['fave_plan_price_postfix']),
            'fave_plan_size' => sanitize_text_field($plan_data['size'] ?? $floor_plans[$plan_id]['fave_plan_size']),
            'fave_plan_size_postfix' => sanitize_text_field($plan_data['size_postfix'] ?? $floor_plans[$plan_id]['fave_plan_size_postfix']),
            'fave_plan_description' => wp_kses_post($plan_data['description'] ?? $floor_plans[$plan_id]['fave_plan_description']),
            'fave_plan_image' => $plan_data['fave_plan_image'] ?? $floor_plans[$plan_id]['fave_plan_image']
        );

        update_post_meta($property_id, 'floor_plans', $floor_plans);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Floor plan updated successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Delete floor plan
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_floor_plan($request) {
        $property_id = $request['id'];
        $plan_id = $request['plan_id'];

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Get existing floor plans
        $floor_plans = get_post_meta($property_id, 'floor_plans', true);
        if (!is_array($floor_plans) || !isset($floor_plans[$plan_id])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Floor plan not found', 'houzez-api')
            ], 404);
        }

        // Delete floor plan image if exists
        if (!empty($floor_plans[$plan_id]['fave_plan_image'])) {
            wp_delete_attachment($floor_plans[$plan_id]['fave_plan_image'], true);
        }

        // Remove floor plan
        unset($floor_plans[$plan_id]);
        $floor_plans = array_values($floor_plans); // Reindex array
        update_post_meta($property_id, 'floor_plans', $floor_plans);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Floor plan deleted successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Add additional feature
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function add_additional_feature($request) {
        $property_id = $request['id'];
        $feature_data = $request->get_params();

        if (empty($feature_data['title']) || empty($feature_data['value'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Title and value are required', 'houzez-api')
            ], 400);
        }

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Get existing features
        $additional_features = get_post_meta($property_id, 'additional_features', true);
        if (!is_array($additional_features)) {
            $additional_features = array();
        }

        // Add new feature
        $additional_features[] = array(
            'fave_additional_feature_title' => sanitize_text_field($feature_data['title']),
            'fave_additional_feature_value' => sanitize_text_field($feature_data['value'])
        );

        update_post_meta($property_id, 'additional_features', $additional_features);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Feature added successfully', 'houzez-api'),
            'data' => [
                'feature_id' => count($additional_features) - 1
            ]
        ], 201);
    }

    /**
     * Remove additional feature
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function remove_additional_feature($request) {
        $property_id = $request['id'];
        $feature_id = $request['feature_id'];

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        // Get existing features
        $additional_features = get_post_meta($property_id, 'additional_features', true);
        if (!is_array($additional_features) || !isset($additional_features[$feature_id])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Feature not found', 'houzez-api')
            ], 404);
        }

        // Remove feature
        unset($additional_features[$feature_id]);
        $additional_features = array_values($additional_features); // Reindex array
        update_post_meta($property_id, 'additional_features', $additional_features);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Feature removed successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Toggle featured status
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function toggle_featured($request) {
        $property_id = $request['id'];
        $featured = $request->get_param('featured');

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }
        update_post_meta($property_id, 'fave_featured', (bool) $featured);

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Featured status updated successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Update property status
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function update_property_status($request) {
        $property_id = $request['id'];
        $status = $request->get_param('status');

        if (!in_array($status, array('publish', 'pending', 'draft', 'private'))) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid status', 'houzez-api')
            ], 400);
        }

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }

        $updated = wp_update_post(array(
            'ID' => $property_id,
            'post_status' => $status
        ));

        if (is_wp_error($updated)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $updated->get_error_message()
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Property status updated successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Get similar properties
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_similar_properties($request) {
        $params = $request->get_params();
        $property_id = $params['id'];
        $limit = $params['limit'];
        $criteria = $params['criteria'];
        $sort_by = $params['sort_by'];

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_Error('no_property', 'Property not found', array('status' => 404));
        }

        // Get similar properties using the new function
        $similar_query = houzez_get_similar_properties($property_id, $criteria, $limit, $sort_by);
        $properties = array();

        if ($similar_query->have_posts()) {
            while ($similar_query->have_posts()) {
                $similar_query->the_post();
                $properties[] = self::format_property(get_the_ID(), $similar_query->post);
            }
        }

        wp_reset_postdata();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'properties' => $properties,
                'pagination' => Houzez_API_Helper::get_pagination_data($similar_query, $params)
            ]
        ], 200);
    }

    /**
     * Get reviews for a specific property
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or WP_Error
     */
    public static function get_property_reviews($request) {
        $property_id = intval($request['id']);
        
        // Verify property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_Error(
                'property_not_found',
                'Property not found',
                array('status' => 404)
            );
        }
        
        if ($property->post_status !== 'publish') {
            return new WP_Error(
                'property_not_published',
                'Property is not published',
                array('status' => 404)
            );
        }

        // Get pagination parameters
        $params = $request->get_params();
        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;
        $sort_by = isset($params['sort_by']) ? sanitize_text_field($params['sort_by']) : 'd_date';

        $args = array(
            'post_type' => 'houzez_reviews',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'meta_query' => array(
                array(
                    'key' => 'review_property_id',
                    'value' => $property_id,
                    'compare' => '='
                )
            ),
            'post_status' => 'publish'
        );
        
        // Apply sorting
        if ($sort_by == 'a_rating') {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'review_stars';
            $args['order'] = 'ASC';
        } else if ($sort_by == 'd_rating') {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'review_stars';
            $args['order'] = 'DESC';
        } else if ($sort_by == 'a_date') {
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
        } else if ($sort_by == 'd_date') {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        $reviews_query = new WP_Query($args);
        $reviews = array();

        if ($reviews_query->have_posts()) {
            while ($reviews_query->have_posts()) {
                $reviews_query->the_post();
                $review_id = get_the_ID();
                
                $reviews[] = array(
                    'id' => $review_id,
                    'title' => get_the_title(),
                    'content' => get_the_content(null, false, $review_id),
                    'rating' => get_post_meta($review_id, 'review_stars', true),
                    'total_likes' => get_post_meta(get_the_ID(), 'review_likes', true), 
                    'total_dislikes' => get_post_meta(get_the_ID(), 'review_dislikes', true),
                    'date' => get_the_date('c'),
                    'author' => array(
                        'id' => get_the_author_meta('ID'),
                        'name' => get_the_author(),
                        'avatar' => get_avatar_url(get_the_author_meta('ID'))
                    )
                );
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'reviews' => $reviews,
                'pagination' => Houzez_API_Helper::get_pagination_data($reviews_query, $params)
            ]
        ], 200);
    }

    /**
     * Add a review for a property
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function add_property_review($request) {
        $property_id = intval($request['id']);
        $user_id = get_current_user_id();
        
        // Verify property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }
        
        if ($property->post_status !== 'publish') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property is not published', 'houzez-api')
            ], 403);
        }
        
        // Check if user is authenticated
        if (!$user_id && empty($request['email'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication or email required', 'houzez-api')
            ], 401);
        }
        
        // Setup data for houzez_submit_review function
        $_POST = array(
            'review_stars' => sanitize_text_field($request['rating']),
            'review_title' => sanitize_text_field($request['title']),
            'review' => wp_kses_post($request['content']),
            'listing_title' => get_the_title($property_id),
            'listing_id' => $property_id,
            'review_post_type' => 'property',
            'permalink' => get_permalink($property_id),
            'is_update' => 0, // New review
            'review-security' => wp_create_nonce('review-nonce')
        );
        
        // Add email if user is not logged in
        if (!$user_id && !empty($request['email'])) {
            $_POST['review_email'] = sanitize_email($request['email']);
        }
        
        // If the user is not logged in but provided name and email
        if (!$user_id) {
            $_POST['review_email'] = sanitize_email($request['email']);
            // Name will be extracted from email in the original function
        }
        
        $result = houzez_process_review_submission( $_POST, false );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return rest_ensure_response( $result );
    
    }
    
    /**
     * REST API callback to toggle a property in the user's favorites.
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response|WP_Error Response or error.
     */
    public static function favorite_property(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Authentication required', 'houzez-api')
            ], 401);
        }

        $property_id = intval($request->get_param('property_id'));
        
        if (!$property_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid property ID', 'houzez-api')
            ], 400);
        }

        $result = houzez_process_favorites($user_id, $property_id);
        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message()
            ], 400);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $result
        ], 200);
    }
    
    /**
     * Update mobile export settings
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function update_mobile_export_settings($request) {
        $params = $request->get_params();
        $settings = isset($params['settings']) ? $params['settings'] : '';
        
        if (empty($settings)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Settings data is required', 'houzez-api')
            ], 400);
        }
        
        // If settings is a JSON string, decode it
        if (is_string($settings) && is_array(json_decode($settings, true))) {
            $settings = json_decode($settings, true);
        }
        
        // Update the option
        update_option('houzez_mobile_export_settings', $settings);
        
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Mobile export settings updated successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Contact property agent
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function contact_property_agent($request) {
        $property_id = $request['id'];
        $params = $request->get_params();
        
        // Validate required fields
        $required_fields = array('name', 'email', 'message');
        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => sprintf(esc_html__('%s is required', 'houzez-api'), ucfirst($field))
                ], 400);
            }
        }
       
        // Verify property exists
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Property not found', 'houzez-api')
            ], 404);
        }
        
        // Set up the global post temporarily to the property
        $original_post = isset($GLOBALS['post']) ? $GLOBALS['post'] : null;
        $GLOBALS['post'] = $property;
        
        // Get agent information using the theme function
        $return_array = houzez20_property_contact_form();
        
        // Restore the original post
        $GLOBALS['post'] = $original_post;
        
        $is_single_agent = $return_array['is_single_agent'];
        if ($is_single_agent == true) {
            $target_email = $return_array['agent_email'];
        } else {
            // For multiple agents, use the first agent's email
            // You might want to modify this based on your requirements
            $target_email = $return_array['agent_email'];
        }
        
        if (empty($target_email)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Agent email not found', 'houzez-api')
            ], 404);
        }
        
        // Prepare data for email
        $name = sanitize_text_field($params['name']);
        $email = sanitize_email($params['email']);
        $message = wp_kses_post($params['message']);
        $phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
        $user_type = isset($params['user_type']) ? sanitize_text_field($params['user_type']) : '';
        $user_type = function_exists('houzez_get_form_user_type') ? houzez_get_form_user_type($user_type) : $user_type;
        
        // Set up arguments for the email function
        $args = array(
            'sender_name' => $name, 
            'sender_email' => $email, 
            'sender_phone' => $phone, 
            'property_title' => get_the_title($property_id), 
            'property_link' => get_permalink($property_id), 
            'property_id' => $property_id, 
            'user_type' => $user_type, 
            'sender_message' => $message, 
        );
        
        // Set up CC email if configured
        $cc_email = '';
        $bcc_email = '';
        if (function_exists('houzez_option')) {
            $send_message_copy = houzez_option('send_agent_message_copy');
            if ($send_message_copy == '1') {
                $cc_email = houzez_option('send_agent_message_email');
            }
        }
        
        // Send email using the theme function
        $email_sent = false;
        if (function_exists('houzez_email_with_reply')) {
            $email_sent = houzez_email_with_reply($target_email, 'property_agent_contact', $args, $name, $email, $cc_email, $bcc_email);
        }
        
        if ($email_sent) {
            // Record activity if the function exists
            if (function_exists('do_action')) {
                $activity_args = array(
                    'type' => 'lead',
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'user_type' => $user_type,
                    'message' => $message,
                );
                do_action('houzez_record_activities', $activity_args);
                do_action('houzez_after_agent_form_submission');
                
                // Webhook if configured
                if (function_exists('houzez_option') && function_exists('houzez_webhook_post')) {
                    if (houzez_option('webhook_property_agent_contact') == 1) {
                        houzez_webhook_post($params, 'houzez_property_agent_contact_form');
                    }
                }
            }
            
            return new WP_REST_Response([
                'success' => true,
                'message' => esc_html__('Your message has been sent successfully', 'houzez-api')
            ], 200);
        } else {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Failed to send email', 'houzez-api')
            ], 500);
        }
    }

    /**
     * Handle review actions like like/dislike
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function review_action($request) {
        $review_id = intval($request['review_id']);
        $action = sanitize_text_field($request['action']);
        
        // Validate review exists
        $review = get_post($review_id);
        if (!$review || $review->post_type !== 'houzez_reviews') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Review not found', 'houzez-api')
            ], 404);
        }
        
        // Make sure review is published
        if ($review->post_status !== 'publish') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Review is not published', 'houzez-api')
            ], 403);
        }
        
        // Set up data for the likes/dislikes function
        $data = array(
            'review_id' => $review_id,
            'type' => $action
        );
        
        // Process the like/dislike
        $result = houzez_process_review_likes_dislikes($data, false);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message()
            ], 400);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $result
        ], 200);
    }


    /**
     * Get property search query
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function save_search( WP_REST_Request $request ) {
        $data   = $request->get_params();
        // For REST calls you might choose to skip nonce validation.
        $result = houzez_process_save_search( $data, false );
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message()
            ], 400);
        }
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Search is saved. You will receive an email notification when new properties matching your search will be published', 'houzez-api')
        ], 200);
    }

    public static function delete_saved_search( WP_REST_Request $request ) {
        $data   = $request->get_params();
        $data['property_id'] = $request['id'];
        $result = houzez_process_delete_search( $data );
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message()
            ], 400);
        }
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Search is deleted', 'houzez-api')
        ], 200);
    }
    
    /**
     * Get the current user's saved searches
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_saved_searches( WP_REST_Request $request ) {
        // Get current user ID
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => esc_html__('User not authenticated', 'houzez-api')
                ),
                401
            );
        }
        
        global $wpdb;
        
        // Query saved searches from database
        $table_name = $wpdb->prefix . 'houzez_search';
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE auther_id = %d ORDER BY id DESC",
            $user_id
        );
        
        $results = $wpdb->get_results($sql);
        
        if (empty($results)) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => esc_html__('No saved searches found', 'houzez-api'),
                    'data' => array()
                )
            );
        }
        
        $saved_searches = array();
        $search_page = houzez_get_template_link('template/template-search.php');
        
        foreach ($results as $search) {
            // Decode search arguments - add error handling
            $search_args = $search->query;
            $search_args_decoded = null;
            
            // Safely decode the search args
            try {
                $decoded = base64_decode($search_args);
                if ($decoded) {
                    $search_args_decoded = unserialize($decoded);
                }
            } catch (Exception $e) {
                // Silently handle error
                $search_args_decoded = array();
            }
            
            // Fallback if decode failed
            if (!$search_args_decoded || !is_array($search_args_decoded)) {
                $search_args_decoded = array();
            }
            
            $search_uri = $search->url;
            $search_link = $search_page . '/?' . $search_uri;
            
            // Parse search URI into parameters
            parse_str($search_uri, $search_params);
            
            // Format the saved search for API response
            $formatted_search = array(
                'id' => intval($search->id),
                'time' => $search->time,
                'search_url' => $search_link,
                'email_notifications' => !empty($search->email),
                'parameters' => array(),
                'display_text' => '' // Will build this below
            );
            
            // Build display parameters array for formatted text
            $display_params = array();
            
            // Add keyword if exists
            if (!empty($search_args_decoded['s'])) {
                $formatted_search['parameters']['keyword'] = $search_args_decoded['s'];
                $display_params[] = sprintf('<strong>%s:</strong> %s', 
                    esc_html__('Keyword', 'houzez-api'),
                    esc_html($search_args_decoded['s'])
                );
            }
            
            // Add location if exists
            if (!empty($search_params['search_location'])) {
                $location = urldecode($search_params['search_location']);
                $formatted_search['parameters']['location'] = $location;
                $display_params[] = sprintf('<strong>%s:</strong> %s', 
                    esc_html__('Location', 'houzez-api'),
                    esc_html($location)
                );
            }
            
            // Direct parsing of URL parameters for display if we can't decode the query
            if (empty($display_params) && !empty($search_params)) {
                // Map common URL parameters to readable labels
                $url_param_labels = array(
                    'keyword' => esc_html__('Keyword', 'houzez-api'),
                    'property_status' => esc_html__('Status', 'houzez-api'),
                    'property_type' => esc_html__('Type', 'houzez-api'),
                    'property_label' => esc_html__('Label', 'houzez-api'),
                    'property_country' => esc_html__('Country', 'houzez-api'),
                    'property_state' => esc_html__('State', 'houzez-api'),
                    'property_city' => esc_html__('City', 'houzez-api'),
                    'property_area' => esc_html__('Area', 'houzez-api'),
                    'bedrooms' => esc_html__('Bedrooms', 'houzez-api'),
                    'bathrooms' => esc_html__('Bathrooms', 'houzez-api'),
                    'min-price' => esc_html__('Min Price', 'houzez-api'),
                    'max-price' => esc_html__('Max Price', 'houzez-api'),
                    'min-area' => esc_html__('Min Area', 'houzez-api'),
                    'max-area' => esc_html__('Max Area', 'houzez-api'),
                );
                
                foreach ($search_params as $param => $value) {
                    if (!empty($value) && isset($url_param_labels[$param])) {
                        $display_params[] = sprintf('<strong>%s:</strong> %s', 
                            $url_param_labels[$param],
                            esc_html(urldecode($value))
                        );
                        $formatted_search['parameters'][$param] = urldecode($value);
                    }
                }
                
                // Handle price range if present in URL params
                if (!empty($search_params['min-price']) && !empty($search_params['max-price'])) {
                    $price_display = sprintf('<strong>%s:</strong> %s - %s', 
                        esc_html__('Price', 'houzez-api'),
                        esc_html(urldecode($search_params['min-price'])),
                        esc_html(urldecode($search_params['max-price']))
                    );
                    $display_params[] = $price_display;
                    $formatted_search['parameters']['price'] = array(
                        'min' => urldecode($search_params['min-price']),
                        'max' => urldecode($search_params['max-price'])
                    );
                }
            }
            
            // Add taxonomy terms
            if (isset($search_args_decoded['tax_query']) && is_array($search_args_decoded['tax_query'])) {
                $taxonomy_map = array(
                    'property_status' => array('key' => 'status', 'label' => esc_html__('Status', 'houzez-api')),
                    'property_type' => array('key' => 'type', 'label' => esc_html__('Type', 'houzez-api')),
                    'property_city' => array('key' => 'city', 'label' => esc_html__('City', 'houzez-api')),
                    'property_country' => array('key' => 'country', 'label' => esc_html__('Country', 'houzez-api')),
                    'property_state' => array('key' => 'state', 'label' => esc_html__('State', 'houzez-api')),
                    'property_area' => array('key' => 'area', 'label' => esc_html__('Area', 'houzez-api')),
                    'property_label' => array('key' => 'label', 'label' => esc_html__('Label', 'houzez-api'))
                );
                
                foreach ($search_args_decoded['tax_query'] as $tax_query) {
                    if (isset($tax_query['taxonomy'], $tax_query['terms']) && isset($taxonomy_map[$tax_query['taxonomy']])) {
                        // Check if the hz_saved_search_term function exists
                        if (function_exists('hz_saved_search_term')) {
                            $term_value = hz_saved_search_term($tax_query['terms'], $tax_query['taxonomy']);
                        } else {
                            // Fallback if function doesn't exist
                            $term_value = is_array($tax_query['terms']) ? implode(', ', $tax_query['terms']) : $tax_query['terms'];
                        }
                        
                        if (!empty($term_value)) {
                            $formatted_search['parameters'][$taxonomy_map[$tax_query['taxonomy']]['key']] = $term_value;
                            $display_params[] = sprintf('<strong>%s:</strong> %s', 
                                $taxonomy_map[$tax_query['taxonomy']]['label'],
                                esc_html($term_value)
                            );
                        }
                    }
                }
            }
            
            // Add meta query values
            if (isset($search_args_decoded['meta_query']) && is_array($search_args_decoded['meta_query'])) {
                $meta_keys_map = array(
                    'fave_property_bedrooms' => array('key' => 'bedrooms', 'label' => esc_html__('Bedrooms', 'houzez-api')),
                    'fave_property_bathrooms' => array('key' => 'bathrooms', 'label' => esc_html__('Bathrooms', 'houzez-api')),
                    'fave_property_rooms' => array('key' => 'rooms', 'label' => esc_html__('Rooms', 'houzez-api')),
                    'fave_property_garage' => array('key' => 'garage', 'label' => esc_html__('Garage', 'houzez-api')),
                    'fave_property_year' => array('key' => 'year_built', 'label' => esc_html__('Year Built', 'houzez-api')),
                    'fave_property_id' => array('key' => 'property_id', 'label' => esc_html__('Property ID', 'houzez-api')),
                    'fave_property_price' => array('key' => 'price', 'label' => esc_html__('Price', 'houzez-api')),
                    'fave_property_size' => array('key' => 'size', 'label' => esc_html__('Size', 'houzez-api')),
                    'fave_property_land' => array('key' => 'land_area', 'label' => esc_html__('Land Area', 'houzez-api')),
                    'fave_property_zip' => array('key' => 'zip', 'label' => esc_html__('Zip Code', 'houzez-api'))
                );
                
                self::extract_meta_values_with_display($search_args_decoded['meta_query'], $meta_keys_map, $formatted_search['parameters'], $display_params);
            }
            
            // Build the display text by joining parameters with ' / '
            $formatted_search['display_text'] = !empty($display_params) ? 
                implode(' / ', $display_params) : 
                esc_html__('Saved search', 'houzez-api'); // Fallback text
            
            // Add raw data for client-side use
            //$formatted_search['raw_query'] = $search_args;
            $formatted_search['raw_uri'] = $search_uri;
            
            $saved_searches[] = $formatted_search;
        }
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => esc_html__('Saved searches retrieved successfully', 'houzez-api'),
                'count' => count($saved_searches),
                'data' => $saved_searches
            )
        );
    }

    /**
     * Helper function to extract metadata values from search arguments
     * and build display parameters for the formatted text
     * 
     * @param array $meta_query The meta query array to search
     * @param array $keys_map Mapping of meta keys to response keys and labels
     * @param array &$output Reference to the output array
     * @param array &$display_params Reference to display parameters array
     */
    private static function extract_meta_values_with_display($meta_query, $keys_map, &$output, &$display_params) {
        foreach ($meta_query as $key => $value) {
            if (is_array($value)) {
                if (isset($value['key']) && array_key_exists($value['key'], $keys_map)) {
                    if (isset($value['value'])) {
                        $output_key = $keys_map[$value['key']]['key'];
                        $label = $keys_map[$value['key']]['label'];
                        
                        // Format range values (e.g., price ranges)
                        if (is_array($value['value']) && isset($value['value'][0], $value['value'][1])) {
                            $output[$output_key] = array(
                                'min' => $value['value'][0],
                                'max' => $value['value'][1]
                            );
                            
                            // Format for display
                            $display_value = $value['value'][0] . ' - ' . $value['value'][1];
                            $display_params[] = sprintf('<strong>%s:</strong> %s', $label, esc_html($display_value));
                        } else {
                            $output[$output_key] = $value['value'];
                            
                            // Add to display parameters
                            $display_params[] = sprintf('<strong>%s:</strong> %s', $label, esc_html($value['value']));
                        }
                    }
                } elseif (!isset($value['key'])) {
                    // Recursively search nested arrays
                    self::extract_meta_values_with_display($value, $keys_map, $output, $display_params);
                }
            }
        }
    }

    public static function get_custom_fields($request) {
        $add_new_fields = fave_option('adp_details_fields');
        $add_new_fields = $add_new_fields['enabled'];
        unset($add_new_fields['placebo']);

        $add_property_fields = [];

        if ($add_new_fields) {
            foreach ($add_new_fields as $key => $value) {
                $add_property_fields[$key] = $value;
            }
        }

        $property_detail_fields = fave_option('hide_detail_prop_fields');

        $property_detail_fields_array = array();
        if ($property_detail_fields) {
            foreach ($property_detail_fields as $key => $value) {

                if( !$value ) {
                    $property_detail_fields_array[] = $key;
                }
                
            }
        }


        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => array(
                    'add_new_property_fields' => $add_property_fields,
                    'property_detail_fields' => $property_detail_fields_array
                )
            )
        );
    }

    /**
     * Toggle email notifications for a saved search
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function toggle_saved_search_notifications( WP_REST_Request $request ) {
        $search_id = $request['id'];
        $enabled = $request['enabled'];
        
        // Convert to boolean if string
        if (is_string($enabled)) {
            $enabled = in_array($enabled, ['true', '1'], true);
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'houzez_search';
        
        // First check if this search belongs to the current user
        $search = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d AND auther_id = %d",
            $search_id, $user_id
        ));
        
        if (!$search) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => esc_html__('Search not found or you do not have permission', 'houzez-api')
                ),
                404
            );
        }
        
        // Get user email
        $user = get_userdata($user_id);
        $user_email = '';
        
        if ($user && !empty($user->user_email)) {
            $user_email = $user->user_email;
        }
        
        // Update the email field
        $update_data = array(
            'email' => $enabled ? $user_email : ''
        );
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $search_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => esc_html__('Failed to update notification settings', 'houzez-api')
                ),
                500
            );
        }
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => $enabled 
                    ? esc_html__('Email notifications enabled', 'houzez-api') 
                    : esc_html__('Email notifications disabled', 'houzez-api'),
                'data' => array(
                    'id' => $search_id,
                    'email_notifications' => $enabled
                )
            )
        );
    }

}

