<?php
/**
 * Agents Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Endpoint_Agents extends Houzez_API_Base {

    /**
     * Initialize agents endpoint
     * 
     * Base initialization method for agent-specific functionality.
     * Override this method to add custom hooks and filters.
     * 
     * @since 1.0.0
     */
    public function init() {
        // Add initialization code here when needed
    }

    /**
     * Get agents
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_agents($request) {
        $params = $request->get_params();
        $_GET = $params;

        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        $args = array(
            'post_type' => 'houzez_agent',
            'meta_query' => array(
            'relation' => 'OR',
                array(
                'key' => 'fave_agent_visible',
                'compare' => 'NOT EXISTS',
                'value' => ''
                ),
                array(
                'key' => 'fave_agent_visible',
                'value' => 1,
                'type' => 'NUMERIC',
                'compare' => '!=',
                )
            ),
            'post_status' => 'publish'
        );

        $args = apply_filters('houzez_agents_search_filter', $args);

        $args['posts_per_page'] = $per_page;
        $args['paged'] = $paged;

        $query = new WP_Query($args);
        $agents = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $agents[] = self::format_agent(get_the_ID());
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
     * Get agents
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function search_agents($request) {
        $params = $request->get_params();
        $_GET = $params;

        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        $args = array(
            'post_type' => 'houzez_agent',
            'meta_query' => array(
            'relation' => 'OR',
                array(
                'key' => 'fave_agent_visible',
                'compare' => 'NOT EXISTS',
                'value' => ''
                ),
                array(
                'key' => 'fave_agent_visible',
                'value' => 1,
                'type' => 'NUMERIC',
                'compare' => '!=',
                )
            ),
            'post_status' => 'publish'
        );

        $args = apply_filters('houzez_agents_search_filter', $args);

        $args['posts_per_page'] = $per_page;
        $args['paged'] = $paged;

        $query = new WP_Query($args);
        $agents = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $agents[] = self::format_agent(get_the_ID());
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
     * Get single agent
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_agent($request) {
        $agent_id = intval($request['id']);

        // Validate agent before caching
        $agent = get_post($agent_id);
        
        if (!$agent) {
            return new WP_Error('agent_not_found', 'Agent not found', array('status' => 404));
        }

        if ($agent->post_type !== 'houzez_agent') {
            return new WP_Error('invalid_post_type', 'Invalid post type', array('status' => 400));
        }

        if ($agent->post_status !== 'publish') {
            return new WP_Error('agent_not_published', 'Agent is not published', array('status' => 404));
        }

        $agent = self::format_agent($agent_id);

        return new WP_REST_Response([
            'success' => true,
            'data' => $agent,
        ], 200);
    }

    public static function create_agent($request) {
        $params = $request->get_params();
        
        $agent_data = array(
            'post_title' => sanitize_text_field($params['name']),
            'post_type' => 'houzez_agent',
            'post_status' => 'publish'
        );

        $agent_id = wp_insert_post($agent_data);

        if (is_wp_error($agent_id)) {
            return new WP_Error('agent_creation_failed', 'Failed to create agent', array('status' => 500));
        }

        // Update agent meta
        self::update_agent_meta($agent_id, $params);

        return new WP_REST_Response(self::format_agent($agent_id), 201);
    }

    public static function update_agent($request) {
        $agent_id = $request['id'];
        $params = $request->get_params();

        $agent_data = array(
            'ID' => $agent_id,
            'post_title' => sanitize_text_field($params['name'])
        );

        $updated = wp_update_post($agent_data);

        if (is_wp_error($updated)) {
            return new WP_Error('agent_update_failed', 'Failed to update agent', array('status' => 500));
        }

        // Update agent meta
        self::update_agent_meta($agent_id, $params);

        return new WP_REST_Response(self::format_agent($agent_id), 200);
    }

    public static function delete_agent($request) {
        $agent_id = $request['id'];
        $result = wp_delete_post($agent_id, true);

        if (!$result) {
            return new WP_Error('agent_deletion_failed', 'Failed to delete agent', array('status' => 500));
        }

        return new WP_REST_Response(null, 204);
    }

    

    /**
     * Get agent properties
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_agent_properties($request) {
        $agent_id = intval($request['id']);

        // Validate agent before proceeding
        $agent = get_post($agent_id);
        
        if (!$agent) {
            return new WP_Error('agent_not_found', 'Agent not found', array('status' => 404));
        }

        if ($agent->post_type !== 'houzez_agent') {
            return new WP_Error('invalid_post_type', 'Invalid post type', array('status' => 400));
        }

        if ($agent->post_status !== 'publish') {
            return new WP_Error('agent_not_published', 'Agent is not published', array('status' => 404));
        }

        $params = $request->get_params();
        $_GET = $params;

        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        // Add our custom filter
        add_filter('houzez_meta_search_filter', function($meta_query) use ($agent_id) {
            return self::filter_properties_by_agent($meta_query, $agent_id);
        });

        $args = array(
            'post_type' => 'property',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $paged,
        );

        // Apply filters in the correct order
        $args = apply_filters('houzez20_search_filters', $args);  // Apply search filters first
        $args = apply_filters('houzez_sold_status_filter', $args); // Then apply sold status filter
        $args = houzez_prop_sort($args); // Finally apply sorting

        // Remove our custom filter
        remove_filter('houzez_meta_search_filter', function($meta_query) use ($agent_id) {
            return self::filter_properties_by_agent($meta_query, $agent_id);
        });

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
     * Get all agent cities
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function get_agent_cities($request) {
        $params = $request->get_params();
        
        return Houzez_API_Helper::get_taxonomy_terms(
            $params,
            'agent_city'
        );
    }


    /**
     * Get reviews for a specific agent
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or WP_Error
     */
    public static function get_agent_reviews($request) {
        $agent_id = intval($request['id']);
        
        // Verify agent exists
        $agent = get_post($agent_id);
        if (!$agent || $agent->post_type !== 'houzez_agent') {
            return new WP_Error(
                'agent_not_found',
                'Agent not found',
                array('status' => 404)
            );
        }
        
        if ($agent->post_status !== 'publish') {
            return new WP_Error(
                'agent_not_published',
                'Agent is not published',
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
                    'key' => 'review_agent_id',
                    'value' => $agent_id,
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
     * Format agent data
     *
     * @param int $agent_id Agent post ID
     * @return array Formatted agent data
     */
    public static function format_agent($agent_id) {
        $agent = get_post($agent_id);
        
        // Get all meta data at once
        $phone = get_post_meta($agent_id, 'fave_agent_office_num', true);
        $mobile = get_post_meta($agent_id, 'fave_agent_mobile', true);
        $whatsapp = get_post_meta($agent_id, 'fave_agent_whatsapp', true);
        
        // Get company logo URL
        $company_logo_id = get_post_meta($agent_id, 'fave_agent_logo', true);
        $company_logo_url = $company_logo_id ? wp_get_attachment_url($company_logo_id) : '';
        
        // Get agent's properties count
        $properties_count = Houzez_Query::agent_properties_count($agent_id);
        $rating = get_post_meta($agent_id, 'houzez_total_rating', true);

        // Get total reviews count
        $reviews_count = Houzez_API_Helper::get_reviews_count('agent', $agent_id);

        $data = array(
            'id' => $agent_id,
            'title' => $agent->post_title,
            'slug' => $agent->post_name,
            'url' => get_permalink($agent_id),
            'content' => get_the_content(null, false, $agent_id),
            'excerpt' => get_the_excerpt($agent_id),
            'status' => get_post_status($agent_id),
            'date' => get_the_date('c', $agent_id),
            'modified' => get_the_modified_date('c', $agent_id),
            'properties_count' => $properties_count,
            'rating' => !empty($rating) ? round((float)$rating, 1) : 0,
            'reviews_count' => $reviews_count,
            'meta' => array(
                'short_description' => get_post_meta($agent_id, 'fave_agent_des', true),
                'email' => get_post_meta($agent_id, 'fave_agent_email', true),
                'visible_hidden' => (bool) get_post_meta($agent_id, 'fave_agent_visible', true),
                'service_areas' => !empty(get_post_meta($agent_id, 'fave_agent_service_area', true)) ? get_post_meta($agent_id, 'fave_agent_service_area', true) : null,
                'specialties' => get_post_meta($agent_id, 'fave_agent_specialties', true),
                'position' => get_post_meta($agent_id, 'fave_agent_position', true),
                'company' => get_post_meta($agent_id, 'fave_agent_company', true),
                'company_logo' => $company_logo_url,
                'license' => get_post_meta($agent_id, 'fave_agent_license', true),
                'tax_number' => get_post_meta($agent_id, 'fave_agent_tax_no', true),
                'phone' => $phone,
                'phone_call' => Houzez_API_Helper::format_phone_number($phone),
                'mobile' => $mobile,
                'mobile_call' => Houzez_API_Helper::format_phone_number($mobile),
                'whatsapp' => $whatsapp,
                'whatsapp_call' => Houzez_API_Helper::format_phone_number($whatsapp),
                'fax' => get_post_meta($agent_id, 'fave_agent_fax', true),
                'language' => get_post_meta($agent_id, 'fave_agent_language', true),
                'address' => get_post_meta($agent_id, 'fave_agent_address', true),
                'experience' => get_post_meta($agent_id, 'fave_agent_experience', true),
                'skype' => get_post_meta($agent_id, 'fave_agent_skype', true),
                'website' => get_post_meta($agent_id, 'fave_agent_website', true),
                'agency_id' => get_post_meta($agent_id, 'fave_agent_agencies', true),
            ),
            'agent_category' => Houzez_API_Helper::get_formatted_terms('agent_category', $agent_id),
            'agent_city' => Houzez_API_Helper::get_formatted_terms('agent_city', $agent_id),
            'thumbnail' => get_the_post_thumbnail_url($agent_id, 'full') ?: null,
            'social_media' => array(
                'facebook' => get_post_meta($agent_id, 'fave_agent_facebook', true),
                'twitter' => get_post_meta($agent_id, 'fave_agent_twitter', true),
                'linkedin' => get_post_meta($agent_id, 'fave_agent_linkedin', true),
                'instagram' => get_post_meta($agent_id, 'fave_agent_instagram', true),
                'youtube' => get_post_meta($agent_id, 'fave_agent_youtube', true),
                'pinterest' => get_post_meta($agent_id, 'fave_agent_pinterest', true),
                'vimeo' => get_post_meta($agent_id, 'fave_agent_vimeo', true),
                'telegram' => get_post_meta($agent_id, 'fave_agent_telegram', true),
                'line' => get_post_meta($agent_id, 'fave_agent_line_id', true),
                'google' => get_post_meta($agent_id, 'fave_agent_googleplus', true),
                'tiktok' => get_post_meta($agent_id, 'fave_agent_tiktok', true),
            ),
            'external_links' => array(
                'zillow' => get_post_meta($agent_id, 'fave_agent_zillow', true),
                'realtor' => get_post_meta($agent_id, 'fave_agent_realtor_com', true),
            )
        );

        return $data;
    }

    /**
     * Update agent meta fields
     *
     * @param int $agent_id
     * @param array $params
     */
    private static function update_agent_meta($agent_id, $params) {
        $meta_fields = array(
            'email' => 'fave_agent_email',
            'phone' => 'fave_agent_office_num',
            'mobile' => 'fave_agent_mobile',
            'whatsapp' => 'fave_agent_whatsapp',
            'position' => 'fave_agent_position',
            'company' => 'fave_agent_company',
            'address' => 'fave_agent_address',
            'facebook' => 'fave_agent_facebook',
            'twitter' => 'fave_agent_twitter',
            'linkedin' => 'fave_agent_linkedin',
            'instagram' => 'fave_agent_instagram'
        );

        foreach ($meta_fields as $param_key => $meta_key) {
            if (isset($params[$param_key])) {
                update_post_meta($agent_id, $meta_key, sanitize_text_field($params[$param_key]));
            }
        }
    }


    /**
     * Get all agent categories
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function get_agent_categories($request) {
        $params = $request->get_params();
        
        return Houzez_API_Helper::get_taxonomy_terms(
            $params,
            'agent_category'
        );
    }

    /**
     * Filter properties by agent
     * 
     * @param array $meta_query Current meta query
     * @param int $agent_id Agent ID to filter by
     * @return array Modified meta query
     */
    public static function filter_properties_by_agent($meta_query, $agent_id) {
        if (!is_array($meta_query)) {
            $meta_query = array();
        }

        // Add agent-specific meta query
        $meta_query[] = array(
            'relation' => 'AND',
            array(
                'key' => 'fave_agents',
                'value' => $agent_id,
                'compare' => '='
            ),
            array(
                'key' => 'fave_agent_display_option',
                'value' => 'agent_info',
                'compare' => '='
            )
        );

        return $meta_query;
    }

    /**
     * Contact agent
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function contact_agent($request) {
        $agent_id = $request['id'];
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
        
        // Determine agent type and verify agent exists
        $agent_type = isset($params['agent_type']) ? sanitize_text_field($params['agent_type']) : 'agent_info';
        
        if ($agent_type == 'author_info') {
            $user = get_user_by('ID', $agent_id);
            if (!$user) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => esc_html__('Author not found', 'houzez-api')
                ], 404);
            }
            $target_email = $user->user_email;
        } else if ($agent_type == 'agency_info') {
            $agency = get_post($agent_id);
            if (!$agency || $agency->post_type !== 'houzez_agency') {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => esc_html__('Agency not found', 'houzez-api')
                ], 404);
            }
            $target_email = get_post_meta($agent_id, 'fave_agency_email', true);
        } else {
            $agent = get_post($agent_id);
            if (!$agent || $agent->post_type !== 'houzez_agent') {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => esc_html__('Agent not found', 'houzez-api')
                ], 404);
            }
            $target_email = get_post_meta($agent_id, 'fave_agent_email', true);
        }
        
        // Validate email
        $target_email = is_email($target_email);
        if (!$target_email) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Agent email not found or invalid', 'houzez-api')
            ], 404);
        }
        
        // Prepare data for email
        $name = sanitize_text_field($params['name']);
        $email = sanitize_email($params['email']);
        $message = wp_kses_post($params['message']);
        $phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
        $user_type = isset($params['user_type']) ? sanitize_text_field($params['user_type']) : '';
        $user_type = function_exists('houzez_get_form_user_type') ? houzez_get_form_user_type($user_type) : $user_type;
        
        // Prepare email content
        $email_subject = sprintf(esc_html__('New message sent by %s using contact form at %s', 'houzez-api'), $name, get_bloginfo('name'));
        
        $email_body = esc_html__("You have received a message from: ", 'houzez-api') . $name . " <br/>";
        if (!empty($phone)) {
            $email_body .= esc_html__("Phone Number : ", 'houzez-api') . $phone . " <br/>";
        }
        if (!empty($user_type)) {
            $email_body .= esc_html__("User Type : ", 'houzez-api') . $user_type . " <br/>";
        }
        $email_body .= esc_html__("Additional message is as follows.", 'houzez-api') . " <br/>";
        $email_body .= wp_kses_post(wpautop(wptexturize($message))) . " <br/>";
        $email_body .= sprintf(esc_html__('You can contact %s via email %s', 'houzez-api'), $name, $email);
        
        $headers = array();
        $headers[] = "From: $name <$email>";
        $headers[] = "Reply-To: $name <$email>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        
        if (function_exists('apply_filters')) {
            $headers = apply_filters("houzez_realtors_mail_header", $headers);
        }
        
        // Send email
        $email_sent = wp_mail($target_email, $email_subject, $email_body, $headers);
        
        if ($email_sent) {
            // Handle webhooks if configured
            if (function_exists('houzez_option') && function_exists('houzez_webhook_post')) {
                if (houzez_option('webhook_agency_contact') == 1 && $agent_type == "agency_info") {
                    houzez_webhook_post($params, 'houzez_agency_profile_contact_from');
                } elseif ((houzez_option('webhook_agent_contact') == 1) && ($agent_type == "agent_info" || $agent_type == "author_info")) {
                    houzez_webhook_post($params, 'houzez_agent_profile_contact_from');
                }
            }
            
            // Send notification if function exists
            if (function_exists('do_action')) {
                $notificationArgs = array(
                    'title'   => $email_subject,
                    'message' => $message,
                    'type'    => 'contact_realtor',
                    'to'      => $target_email,
                );
                do_action('houzez_send_notification', $notificationArgs);
                
                // Record activity
                $activity_args = array(
                    'type' => 'lead_agent',
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'user_type' => $user_type,
                    'message' => $message,
                );
                do_action('houzez_record_activities', $activity_args);
                do_action('houzez_after_agent_form_submission');
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
     * Add a review for a specific agent
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function add_agent_review($request) {
        $agent_id = intval($request['id']);
        $user_id = get_current_user_id();
        
        // Verify agent exists
        $agent = get_post($agent_id);
        if (!$agent || $agent->post_type !== 'houzez_agent') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Agent not found', 'houzez-api')
            ], 404);
        }
        
        if ($agent->post_status !== 'publish') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Agent is not published', 'houzez-api')
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
            'listing_title' => get_the_title($agent_id),
            'listing_id' => $agent_id,
            'review_post_type' => 'houzez_agent',
            'permalink' => get_permalink($agent_id),
            'is_update' => 0, // New review
            'review-security' => wp_create_nonce('review-nonce')
        );
        
        // Add email if user is not logged in
        if (!$user_id && !empty($request['email'])) {
            $_POST['review_email'] = sanitize_email($request['email']);
        }
        
        $result = houzez_process_review_submission( $_POST, false );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return rest_ensure_response( $result );
    }

} 