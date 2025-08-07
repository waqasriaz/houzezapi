<?php
/**
 * Agencies Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Endpoint_Agencies extends Houzez_API_Base {

    /**
     * Initialize agencies endpoint
     * 
     * Base initialization method for agency-specific functionality.
     * Override this method to add custom hooks and filters.
     * 
     * @since 1.0.0
     */
    public function init() {
        // Add initialization code here when needed
    }

    /**
     * Get agencies
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_agencies($request) {
        $params = $request->get_params();
        $_GET = $params;

        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;
        
        $args = array(
            'post_type' => 'houzez_agency',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'fave_agency_visible',
                    'compare' => 'NOT EXISTS',
                    'value' => ''
                ),
                array(
                    'key' => 'fave_agency_visible',
                    'value' => 1,
                    'type' => 'NUMERIC',
                    'compare' => '!='
                )
            )
        );

        $query = new WP_Query($args);
        $agencies = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $agencies[] = self::format_agency_data(get_the_ID());
            }
        }

        wp_reset_postdata();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'agencies' => $agencies,
                'pagination' => Houzez_API_Helper::get_pagination_data($query, $params)
            ]
        ], 200);
    }


    /**
     * Get agents
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function search_agencies($request) {
        $params = $request->get_params();
        $_GET = $params;

        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        $args = array(
            'post_type' => 'houzez_agency',
            'meta_query' => array(
            'relation' => 'OR',
                array(
                'key' => 'fave_agency_visible',
                'compare' => 'NOT EXISTS',
                'value' => ''
                ),
                array(
                'key' => 'fave_agency_visible',
                'value' => 1,
                'type' => 'NUMERIC',
                'compare' => '!=',
                )
            ),
            'post_status' => 'publish'
        );

        $args = apply_filters('houzez_agencies_search_filter', $args);

        $args['posts_per_page'] = $per_page;
        $args['paged'] = $paged;

        $query = new WP_Query($args);
        $agencies = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $agencies[] = self::format_agency_data(get_the_ID());
            }
        }

        wp_reset_postdata();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'agencies' => $agencies,
                'pagination' => Houzez_API_Helper::get_pagination_data($query, $params)
            ]
        ], 200);
    }

    /**
     * Get single agency
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_agency($request) {
        $agency_id = intval($request['id']);
        
        // Validate agency before caching
        $agency = get_post($agency_id);
        
        if (!$agency) {
            return new WP_Error('agency_not_found', 'Agency not found', array('status' => 404));
        }

        if ($agency->post_type !== 'houzez_agency') {
            return new WP_Error('invalid_post_type', 'Invalid post type', array('status' => 400));
        }

        if ($agency->post_status !== 'publish') {
            return new WP_Error('agency_not_published', 'Agency is not published', array('status' => 404));
        }

        $response_data = self::format_agency_data($agency_id);

        return new WP_REST_Response([
            'success' => true,
            'data' => $response_data,
        ], 200);
    }

    public static function create_agency($request) {
        $params = $request->get_params();
        
        $agency_data = array(
            'post_title' => sanitize_text_field($params['name']),
            'post_content' => wp_kses_post($params['description'] ?? ''),
            'post_type' => 'houzez_agency',
            'post_status' => 'publish'
        );

        $agency_id = wp_insert_post($agency_data);

        if (is_wp_error($agency_id)) {
            return new WP_Error('agency_creation_failed', 'Failed to create agency', array('status' => 500));
        }

        // Update agency meta
        self::update_agency_meta($agency_id, $params);

        return new WP_REST_Response(self::format_agency_data($agency_id), 201);
    }

    public static function update_agency($request) {
        $agency_id = $request['id'];
        $params = $request->get_params();

        $agency_data = array(
            'ID' => $agency_id,
            'post_title' => sanitize_text_field($params['name']),
            'post_content' => wp_kses_post($params['description'] ?? '')
        );

        $updated = wp_update_post($agency_data);

        if (is_wp_error($updated)) {
            return new WP_Error('agency_update_failed', 'Failed to update agency', array('status' => 500));
        }

        // Update agency meta
        self::update_agency_meta($agency_id, $params);

        return new WP_REST_Response(self::format_agency_data($agency_id), 200);
    }

    public static function delete_agency($request) {
        $agency_id = $request['id'];
        $result = wp_delete_post($agency_id, true);

        if (!$result) {
            return new WP_Error('agency_deletion_failed', 'Failed to delete agency', array('status' => 500));
        }

        return new WP_REST_Response(null, 204);
    }

    private static function format_agency_data($agency_id) {
        $agency = get_post($agency_id);
        
        // Get all meta data at once
        $phone = get_post_meta($agency_id, 'fave_agency_phone', true);
        $mobile = get_post_meta($agency_id, 'fave_agency_mobile', true);
        $whatsapp = get_post_meta($agency_id, 'fave_agency_whatsapp', true);
        
        // Get agency's properties count
        $properties_count = Houzez_Query::agency_properties_count($agency_id);
        $agency_agents = Houzez_Query::loop_agency_agents($agency_id);
        $agents_count = $agency_agents && is_object($agency_agents) ? $agency_agents->found_posts : 0;
        $rating = get_post_meta($agency_id, 'houzez_total_rating', true);

        // Get total reviews count
        $reviews_count = Houzez_API_Helper::get_reviews_count('agency', $agency_id);
        
        return array(
            'id' => $agency_id,
            'title' => $agency->post_title,
            'slug' => $agency->post_name,
            'url' => get_permalink($agency_id),
            'content' => get_the_content(null, false, $agency_id),
            'excerpt' => get_the_excerpt($agency_id),
            'status' => get_post_status($agency_id),
            'date' => get_the_date('c', $agency_id),
            'modified' => get_the_modified_date('c', $agency_id),
            'properties_count' => $properties_count,
            'agents_count' => $agents_count,
            'rating' => !empty($rating) ? round((float)$rating, 1) : 0,
            'reviews_count' => $reviews_count,
            'meta' => array(
                'email' => get_post_meta($agency_id, 'fave_agency_email', true),
                'visible_hidden' => (bool) get_post_meta($agency_id, 'fave_agency_visible', true),
                'service_areas' => get_post_meta($agency_id, 'fave_agency_service_area', true),
                'specialties' => get_post_meta($agency_id, 'fave_agency_specialties', true),
                'phone' => $phone,
                'phone_call' => Houzez_API_Helper::format_phone_number($phone),
                'mobile' => $mobile,
                'mobile_call' => Houzez_API_Helper::format_phone_number($mobile),
                'whatsapp' => $whatsapp,
                'whatsapp_call' => Houzez_API_Helper::format_phone_number($whatsapp),
                'fax' => get_post_meta($agency_id, 'fave_agency_fax', true),
                'language' => get_post_meta($agency_id, 'fave_agency_language', true),
                'address' => get_post_meta($agency_id, 'fave_agency_address', true),
                'experience' => get_post_meta($agency_id, 'fave_agency_experience', true),
                'licenses' => get_post_meta($agency_id, 'fave_agency_licenses', true),
                'tax_number' => get_post_meta($agency_id, 'fave_agency_tax_no', true),
                'website' => get_post_meta($agency_id, 'fave_agency_web', true),
                'location' => array(
                    'latitude' => get_post_meta($agency_id, 'fave_agency_latitude', true),
                    'longitude' => get_post_meta($agency_id, 'fave_agency_longitude', true)
                ),
                'line' => get_post_meta($agency_id, 'fave_agency_line_id', true),
                'telegram' => get_post_meta($agency_id, 'fave_agency_telegram', true),
            ),
            'thumbnail' => get_the_post_thumbnail_url($agency_id, 'full') ?: null,
            'social_media' => array(
                'facebook' => get_post_meta($agency_id, 'fave_agency_facebook', true),
                'twitter' => get_post_meta($agency_id, 'fave_agency_twitter', true),
                'linkedin' => get_post_meta($agency_id, 'fave_agency_linkedin', true),
                'instagram' => get_post_meta($agency_id, 'fave_agency_instagram', true),
                'youtube' => get_post_meta($agency_id, 'fave_agency_youtube', true),
                'pinterest' => get_post_meta($agency_id, 'fave_agency_pinterest', true),
                'vimeo' => get_post_meta($agency_id, 'fave_agency_vimeo', true),
                'google' => get_post_meta($agency_id, 'fave_agency_googleplus', true),
                'tiktok' => get_post_meta($agency_id, 'fave_agency_tiktok', true),
            ),
            'external_links' => array(
                'zillow' => get_post_meta($agency_id, 'fave_agency_zillow', true),
                'realtor' => get_post_meta($agency_id, 'fave_agency_realtor_com', true),
            )
        );
    }

    private static function update_agency_meta($agency_id, $params) {
        $meta_fields = array(
            'email' => 'fave_agency_email',
            'phone' => 'fave_agency_phone',
            'mobile' => 'fave_agency_mobile',
            'whatsapp' => 'fave_agency_whatsapp',
            'address' => 'fave_agency_address',
            'latitude' => 'fave_agency_latitude',
            'longitude' => 'fave_agency_longitude',
            'licenses' => 'fave_agency_licenses',
            'tax_number' => 'fave_agency_tax_no',
            'website' => 'fave_agency_web',
            'facebook' => 'fave_agency_facebook',
            'twitter' => 'fave_agency_twitter',
            'linkedin' => 'fave_agency_linkedin',
            'instagram' => 'fave_agency_instagram',
            'youtube' => 'fave_agency_youtube',
            'pinterest' => 'fave_agency_pinterest',
            'vimeo' => 'fave_agency_vimeo',
            'visible_hidden' => 'fave_agency_visible',
            'service_areas' => 'fave_agency_service_area',
            'specialties' => 'fave_agency_specialties',
            'fax' => 'fave_agency_fax',
            'language' => 'fave_agency_language',
            'line' => 'fave_agency_line_id',
            'telegram' => 'fave_agency_telegram'
        );

        foreach ($meta_fields as $param_key => $meta_key) {
            if (isset($params[$param_key])) {
                update_post_meta($agency_id, $meta_key, sanitize_text_field($params[$param_key]));
            }
        }
    }

    /**
     * Get properties for a specific agency
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or WP_Error
     */
    public static function get_agency_properties($request) {
        $agency_id = intval($request['id']);
        
        // Validate agency before proceeding
        $agency = get_post($agency_id);
        if (!$agency) {
            return new WP_Error('agency_not_found', 'Agency not found', array('status' => 404));
        }

        if ($agency->post_type !== 'houzez_agency') {
            return new WP_Error('invalid_post_type', 'Invalid post type', array('status' => 400));
        }

        if ($agency->post_status !== 'publish') {
            return new WP_Error('agency_not_published', 'Agency is not published', array('status' => 404));
        }

        $params = $request->get_params();
        $_GET = $params;

        $tax_query = array();
        $taxonomy = isset($params['taxonomy']) ? $params['taxonomy'] : 'property_status';
        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        if (isset($params['term']) && !empty($params['term'])) {
            $tax_query[] = array(
                'taxonomy' => esc_attr($taxonomy),
                'field' => 'slug',
                'terms' => esc_attr($params['term'])
            );
        }

        $args = array(
            'post_type' => 'property',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'fave_property_agency',
                    'value' => $agency_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'fave_agent_display_option',
                    'value' => 'agency_info',
                    'compare' => '='
                )
            )
        );

        $args = apply_filters('houzez_sold_status_filter', $args);

        if (count($tax_query) > 0) {
            $args['tax_query'] = $tax_query;
        }

        $args = houzez_prop_sort($args);

        $query = new WP_Query($args);
        $properties = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $properties[] = Houzez_API_Endpoint_Properties::format_property(get_the_ID());
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
     * Get agents for a specific agency
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or WP_Error
     */
    public static function get_agency_agents($request) {
        $agency_id = intval($request['id']);
        
        // Validate agency before proceeding
        $agency = get_post($agency_id);
        if (!$agency) {
            return new WP_Error('agency_not_found', 'Agency not found', array('status' => 404));
        }

        if ($agency->post_type !== 'houzez_agency') {
            return new WP_Error('invalid_post_type', 'Invalid post type', array('status' => 400));
        }

        if ($agency->post_status !== 'publish') {
            return new WP_Error('agency_not_published', 'Agency is not published', array('status' => 404));
        }

        $params = $request->get_params();
        
        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        $args = array(
            'post_type' => 'houzez_agent',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'fave_agent_agencies',
                    'value' => $agency_id,
                    'compare' => 'LIKE'
                )
            )
        );

        $query = new WP_Query($args);
        $agents = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $agents[] = Houzez_API_Endpoint_Agents::format_agent(get_the_ID());
            }
        }

        wp_reset_postdata();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'agents' => $agents,
                'pagination' => Houzez_API_Helper::get_pagination_data($query, $params)
            ]
        ], 200);
    }

    /**
     * Get reviews for a specific agency
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or WP_Error
     */
    public static function get_agency_reviews($request) {
        $agency_id = intval($request['id']);
        
        // Verify agency exists
        $agency = get_post($agency_id);
        if (!$agency || $agency->post_type !== 'houzez_agency') {
            return new WP_Error(
                'agency_not_found',
                'Agency not found',
                array('status' => 404)
            );
        }
        
        if ($agency->post_status !== 'publish') {
            return new WP_Error(
                'agency_not_published',
                'Agency is not published',
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
                    'key' => 'review_agency_id',
                    'value' => $agency_id,
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
     * Add a review for a specific agency
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function add_agency_review($request) {
        $agency_id = intval($request['id']);
        $user_id = get_current_user_id();
        
        // Verify agency exists
        $agency = get_post($agency_id);
        if (!$agency || $agency->post_type !== 'houzez_agency') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Agency not found', 'houzez-api')
            ], 404);
        }
        
        if ($agency->post_status !== 'publish') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Agency is not published', 'houzez-api')
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
            'listing_title' => get_the_title($agency_id),
            'listing_id' => $agency_id,
            'review_post_type' => 'houzez_agency',
            'permalink' => get_permalink($agency_id),
            'is_update' => 0, // New review
            'review-security' => wp_create_nonce('review-nonce')
        );
        
        // Add email if user is not logged in
        if (!$user_id && !empty($request['email'])) {
            $_POST['review_email'] = sanitize_email($request['email']);
        }
        
        $result = houzez_process_review_submission($_POST, false);
        if (is_wp_error($result)) {
            return $result;
        }
        return rest_ensure_response($result);
    }

} 