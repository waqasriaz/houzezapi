<?php
/**
 * Helper Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Helper extends Houzez_API_Base {
    
    /**
     * Initialize the class
     */
    public function init() {
        // No initialization needed for static methods
    }

    
    /**
     * Sanitize a string or array of strings
     *
     * @param string|array $input
     * @return string|array
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map(array(__CLASS__, 'sanitize'), $input);
        }

        return sanitize_text_field($input);
    }

    /**
     * Get a specific post meta value from an array of all post meta
     *
     * This helper function extracts a specific meta value from the array returned by get_post_meta($post_id)
     * It handles the array structure and provides a fallback value if the meta key doesn't exist
     *
     * @since 1.0.0
     * 
     * @param array  $post_meta    The array of all post meta (from get_post_meta($post_id))
     * @param string $key         The meta key to retrieve
     * @param mixed  $default     Optional. Default value to return if meta doesn't exist. Default empty string.
     * @param bool   $as_bool     Optional. Whether to cast the result as boolean. Default false.
     * 
     * @return mixed The meta value, or default if not found
     */
    public static function get_post_meta_value($post_meta, $key, $as_single = false, $as_bool = false, $default = '') {
        $value = isset($post_meta[$key]) && !empty($post_meta[$key]) ? ($as_single ? $post_meta[$key][0] : $post_meta[$key]) : $default;
        
        if ($as_bool) {
            return (bool) $value;
        }
        
        return $value;
    }

    /**
     * Helper function to check if request is for mobile
     *
     * @param WP_REST_Request $request The request object
     * @return boolean True if request is from mobile
     */
    public static function is_mobile_request($request) {
        // Check for explicit mobile parameter
        if (isset($request['is_mobile']) && $request['is_mobile'] === 'true') {
            return true;
        }
        return false;
    }

    /**
     * Get formatted taxonomy terms for a post or general taxonomy
     *
     * This method provides a flexible way to fetch and format taxonomy terms.
     * It can be used in two ways:
     * 1. Get terms for a specific post (by providing post_id)
     * 2. Get all terms for a taxonomy (by leaving post_id as null)
     *
     * Example usage:
     * - Get all property types: get_formatted_terms('property_type')
     * - Get property types for a post: get_formatted_terms('property_type', $post_id)
     * - Get terms with custom args: get_formatted_terms('property_type', null, ['orderby' => 'name'])
     *
     * @since 1.0.0
     * 
     * @param string    $taxonomy   The taxonomy name (e.g., 'property_type', 'property_city')
     * @param int|null  $post_id    Optional. Post ID to get terms for. If null, returns all terms.
     * @param array     $args       Optional. Additional arguments for get_terms or wp_get_post_terms
     * 
     * @return array Array of formatted terms or empty array if none/error
     */
    public static function get_formatted_terms($taxonomy, $post_id = null, $args = array()) {
        // Merge default args with provided args
        $default_args = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        );
        $args = wp_parse_args($args, $default_args);

        // Allow modification of arguments
        $args = apply_filters('houzez_api_get_terms_args', $args, $taxonomy, $post_id);

        // Get terms based on whether post_id is provided
        if ($post_id) {
            $terms = wp_get_post_terms($post_id, $taxonomy, $args);
        } else {
            $terms = get_terms($args);
        }

        // Handle error case
        if (is_wp_error($terms)) {
            return apply_filters('houzez_api_terms_error', array(), $taxonomy, $post_id);
        }

        // Format terms based on context (post terms vs general terms)
        $formatted_terms = array_map(function($term) use ($post_id) {
            if ($post_id) {
                // Use format_post_taxonomy_term for post-specific terms
                return self::format_post_taxonomy_term($term);
            } else {
                // Full format for general terms
                return self::format_taxonomy_term($term);
            }
        }, $terms);

        // Allow modification of final formatted terms
        return apply_filters(
            'houzez_api_formatted_terms', 
            $formatted_terms, 
            $terms, 
            $taxonomy, 
            $post_id
        );
    }

    /**
     * Get taxonomy query arguments from parameters
     * 
     * @param array $params Request parameters
     * @return array Formatted arguments for WP_Term_Query
     */
    public static function get_taxonomy_query_args($params) {
        $args = array(
            // Basic parameters
            'hide_empty' => isset($params['hide_empty']) ? (bool)$params['hide_empty'] : false,
            'orderby' => isset($params['orderby']) ? sanitize_text_field($params['orderby']) : 'name',
            'order' => isset($params['order']) ? sanitize_text_field($params['order']) : 'ASC',
            'number' => isset($params['number']) ? intval($params['number']) : '',
            'offset' => isset($params['offset']) ? intval($params['offset']) : '',
            'search' => isset($params['search']) ? sanitize_text_field($params['search']) : '',
            'name__like' => isset($params['name__like']) ? sanitize_text_field($params['name__like']) : '',
            'description__like' => isset($params['description__like']) ? sanitize_text_field($params['description__like']) : '',
            'parent' => isset($params['parent']) ? intval($params['parent']) : '',
            'childless' => isset($params['childless']) ? (bool)$params['childless'] : false,
            'child_of' => isset($params['child_of']) ? intval($params['child_of']) : 0,

            // Additional WP_Term_Query parameters
            'object_ids' => isset($params['object_ids']) ? array_map('intval', (array)$params['object_ids']) : null,
            'term_taxonomy_id' => isset($params['term_taxonomy_id']) ? array_map('intval', (array)$params['term_taxonomy_id']) : '',
            'name' => isset($params['name']) ? (is_array($params['name']) ? array_map('sanitize_text_field', $params['name']) : sanitize_text_field($params['name'])) : '',
            'slug' => isset($params['slug']) ? (is_array($params['slug']) ? array_map('sanitize_text_field', $params['slug']) : sanitize_text_field($params['slug'])) : '',
            'hierarchical' => isset($params['hierarchical']) ? (bool)$params['hierarchical'] : true,
            'pad_counts' => isset($params['pad_counts']) ? (bool)$params['pad_counts'] : false,
            'get' => isset($params['get']) ? sanitize_text_field($params['get']) : '',
            'cache_domain' => isset($params['cache_domain']) ? sanitize_text_field($params['cache_domain']) : 'core',
            'update_term_meta_cache' => isset($params['update_term_meta_cache']) ? (bool)$params['update_term_meta_cache'] : true,
            'fields' => isset($params['fields']) ? sanitize_text_field($params['fields']) : 'all',
            
            // Meta query parameters
            'meta_key' => isset($params['meta_key']) ? (is_array($params['meta_key']) ? array_map('sanitize_text_field', $params['meta_key']) : sanitize_text_field($params['meta_key'])) : '',
            'meta_value' => isset($params['meta_value']) ? (is_array($params['meta_value']) ? array_map('sanitize_text_field', $params['meta_value']) : sanitize_text_field($params['meta_value'])) : '',
            'meta_compare' => isset($params['meta_compare']) ? sanitize_text_field($params['meta_compare']) : '',
            'meta_compare_key' => isset($params['meta_compare_key']) ? sanitize_text_field($params['meta_compare_key']) : '',
            'meta_type' => isset($params['meta_type']) ? sanitize_text_field($params['meta_type']) : '',
            'meta_type_key' => isset($params['meta_type_key']) ? sanitize_text_field($params['meta_type_key']) : '',
        );

        // Handle include/exclude parameters
        if (!empty($params['include'])) {
            $args['include'] = is_array($params['include']) 
                ? array_map('intval', $params['include'])
                : array_map('intval', explode(',', $params['include']));
        }
        if (!empty($params['exclude'])) {
            $args['exclude'] = is_array($params['exclude'])
                ? array_map('intval', $params['exclude'])
                : array_map('intval', explode(',', $params['exclude']));
        }
        if (!empty($params['exclude_tree'])) {
            $args['exclude_tree'] = is_array($params['exclude_tree'])
                ? array_map('intval', $params['exclude_tree'])
                : array_map('intval', explode(',', $params['exclude_tree']));
        }
        if (!empty($params['slug__in'])) {
            $args['slug__in'] = is_array($params['slug__in'])
                ? array_map('sanitize_text_field', $params['slug__in'])
                : array_map('sanitize_text_field', explode(',', $params['slug__in']));
        }

        // Handle meta_query if provided as array
        if (!empty($params['meta_query']) && is_array($params['meta_query'])) {
            $args['meta_query'] = array_map(function($query) {
                return array_map('sanitize_text_field', $query);
            }, $params['meta_query']);
        }

        // Remove empty parameters to prevent unnecessary query conditions
        $args = array_filter($args, function($value) {
            return $value !== '' && $value !== null;
        });

        // Allow modification of arguments through filter
        return apply_filters('houzez_api_taxonomy_query_args', $args, $params);
    }

    /**
     * Get taxonomy terms with query parameters and formatted response
     * 
     * @param array  $params   Request parameters
     * @param string $taxonomy Taxonomy name (e.g., 'property_type', 'property_city')
     * @return WP_REST_Response Response object with taxonomy terms data
     */
    public static function get_taxonomy_terms($params, $taxonomy) {
        // Get query arguments and add taxonomy
        $args = self::get_taxonomy_query_args($params);
        $args['taxonomy'] = $taxonomy;
        
        // Allow modification of final arguments including taxonomy
        $args = apply_filters('houzez_api_get_terms_args', $args, $taxonomy);
        
        // Get terms with provided arguments
        $terms = get_terms($args);
        
        // Handle error case
        if (is_wp_error($terms)) {
            $error_response = array(
                'success' => false,
                'message' => esc_html__('Error fetching taxonomy terms', 'houzez-api')
            );

            // Allow modification of error response
            $error_response = apply_filters('houzez_api_taxonomy_error_response', 
                $error_response, 
                $terms, 
                $taxonomy
            );

            return new WP_REST_Response($error_response, 404);
        }

        // Handle empty results
        if (empty($terms)) {
            $empty_response = array(
                'success' => true,
                'data' => array()
            );

            // Allow modification of empty response
            $empty_response = apply_filters('houzez_api_taxonomy_empty_response', 
                $empty_response, 
                $taxonomy
            );

            return new WP_REST_Response($empty_response, 200);
        }
        
        // Format the terms and ensure sequential array
        $formatted_terms = array_values(array_map([__CLASS__, 'format_taxonomy_term'], $terms));

        // Allow modification of formatted terms
        $formatted_terms = apply_filters('houzez_api_formatted_taxonomy_terms', 
            $formatted_terms, 
            $terms, 
            $taxonomy
        );

        // Prepare success response
        $success_response = array(
            'success' => true,
            'data' => $formatted_terms
        );

        // Allow modification of success response
        $success_response = apply_filters('houzez_api_taxonomy_success_response', 
            $success_response, 
            $formatted_terms, 
            $taxonomy
        );

        return new WP_REST_Response($success_response, 200);
    }

    /**
     * Format taxonomy term with all its metadata
     *
     * @param object $term The term object
     * @return array Formatted term data
     */
    public static function format_taxonomy_term($term) {
        // Basic term data
        $formatted_term = array(
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'count' => $term->count,
            'parent' => $term->parent
        );

        // Allow modification of basic term data
        $formatted_term = apply_filters('houzez_api_format_term_basic', $formatted_term, $term);

        // Add taxonomy image if it exists
        $term_img_id = get_term_meta($term->term_id, 'fave_taxonomy_img', true);
        if (!empty($term_img_id)) {
            $image_url = wp_get_attachment_url($term_img_id);
            if ($image_url) {
                $formatted_term['image_url'] = apply_filters('houzez_api_term_image_url', $image_url, $term_img_id, $term);
            }
        }

        // Add marker icon if it exists
        $marker_icon_id = get_term_meta($term->term_id, 'fave_marker_icon', true);
        if (!empty($marker_icon_id)) {
            $marker_url = wp_get_attachment_url($marker_icon_id);
            if ($marker_url) {
                $formatted_term['marker_icon_url'] = apply_filters('houzez_api_term_marker_url', $marker_url, $marker_icon_id, $term);
            }
        }

        // Add retina marker icon if it exists
        $marker_retina_icon_id = get_term_meta($term->term_id, 'fave_marker_retina_icon', true);
        if (!empty($marker_retina_icon_id)) {
            $retina_url = wp_get_attachment_url($marker_retina_icon_id);
            if ($retina_url) {
                $formatted_term['marker_retina_icon_url'] = apply_filters('houzez_api_term_retina_marker_url', $retina_url, $marker_retina_icon_id, $term);
            }
        }

        // Add custom link if it exists
        $custom_link = get_term_meta($term->term_id, 'fave_prop_taxonomy_custom_link', true);
        if (!empty($custom_link)) {
            $formatted_term['custom_link'] = apply_filters('houzez_api_term_custom_link', $custom_link, $term);
        }

        // Add status color settings if this is a property status or label term
        if ($term->taxonomy === 'property_status' || $term->taxonomy === 'property_label') {
            $status_meta = get_option('_houzez_property_status_' . $term->term_id);
            $color_settings = array(
                'color_type' => $status_meta['color_type'] ?? 'inherit',
                'color' => $status_meta['color'] ?? '#000000'
            );
            $formatted_term = array_merge($formatted_term, 
                apply_filters('houzez_api_term_color_settings', $color_settings, $term)
            );
        }

        // Add hierarchical location data
        if ($term->taxonomy === 'property_state') {
            $formatted_term = self::add_state_country_data($formatted_term, $term);
        } elseif ($term->taxonomy === 'property_city') {
            $formatted_term = self::add_city_state_data($formatted_term, $term);
        } elseif ($term->taxonomy === 'property_area') {
            $formatted_term = self::add_area_city_data($formatted_term, $term);
        }

        return apply_filters('houzez_api_format_taxonomy_term', $formatted_term, $term);
    }

    /**
     * Format taxonomy term with basic metadata for post terms
     * This is a simplified version of format_taxonomy_term() specifically for post terms
     * that only includes the essential data needed for post term display
     *
     * @param object $term The term object
     * @return array Formatted term data
     */
    public static function format_post_taxonomy_term($term) {
        // Basic term data
        $formatted_term = array(
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        );

        // Allow modification of basic term data
        $formatted_term = apply_filters('houzez_api_format_post_term_basic', $formatted_term, $term);

        // Add marker icon if it exists
        $marker_icon_id = get_term_meta($term->term_id, 'fave_marker_icon', true);
        if (!empty($marker_icon_id)) {
            $marker_url = wp_get_attachment_url($marker_icon_id);
            if ($marker_url) {
                $formatted_term['marker_icon_url'] = apply_filters('houzez_api_post_term_marker_url', $marker_url, $marker_icon_id, $term);
            }
        }

        // Add retina marker icon if it exists
        $marker_retina_icon_id = get_term_meta($term->term_id, 'fave_marker_retina_icon', true);
        if (!empty($marker_retina_icon_id)) {
            $retina_url = wp_get_attachment_url($marker_retina_icon_id);
            if ($retina_url) {
                $formatted_term['marker_retina_icon_url'] = apply_filters('houzez_api_post_term_retina_marker_url', $retina_url, $marker_retina_icon_id, $term);
            }
        }

        // Add status color settings if this is a property status or label term
        if ($term->taxonomy === 'property_status' || $term->taxonomy === 'property_label') {
            $status_meta = get_option('_houzez_property_status_' . $term->term_id);
            $color_settings = array(
                'color' => $status_meta['color'] ?? '#000000'
            );
            $formatted_term = array_merge($formatted_term, 
                apply_filters('houzez_api_post_term_color_settings', $color_settings, $term)
            );
        }

        return apply_filters('houzez_api_format_post_taxonomy_term', $formatted_term, $term);
    }

    /**
     * Add state and country data to term
     */
    private static function add_state_country_data($formatted_term, $term) {
        $term_meta = get_option("_houzez_property_state_" . $term->term_id);
        if (!empty($term_meta) && isset($term_meta['parent_country'])) {
            $parent_country = sanitize_title($term_meta['parent_country']);
            $country = get_term_by('slug', $parent_country, 'property_country');
            
            if ($country && !is_wp_error($country)) {
                $formatted_term['country'] = apply_filters('houzez_api_state_country_data', array(
                    'id' => $country->term_id,
                    'name' => $country->name,
                    'slug' => $country->slug
                ), $country, $term);
            }
        }
        return apply_filters('houzez_api_state_location_data', $formatted_term, $term);
    }

    /**
     * Add city and state data to term
     */
    private static function add_city_state_data($formatted_term, $term) {
        $term_meta = get_option("_houzez_property_city_" . $term->term_id);
        if (!empty($term_meta) && isset($term_meta['parent_state'])) {
            $parent_state = sanitize_title($term_meta['parent_state']);
            $state = get_term_by('slug', $parent_state, 'property_state');
            
            if ($state && !is_wp_error($state)) {
                $formatted_term['state'] = apply_filters('houzez_api_city_state_data', array(
                    'id' => $state->term_id,
                    'name' => $state->name,
                    'slug' => $state->slug
                ), $state, $term);
            }
        }
        return apply_filters('houzez_api_city_location_data', $formatted_term, $term);
    }

    /**
     * Add area and city data to term
     */
    private static function add_area_city_data($formatted_term, $term) {
        $term_meta = get_option("_houzez_property_area_" . $term->term_id);
        if (!empty($term_meta) && isset($term_meta['parent_city'])) {
            $parent_city = sanitize_title($term_meta['parent_city']);
            $city = get_term_by('slug', $parent_city, 'property_city');
            
            if ($city && !is_wp_error($city)) {
                $formatted_term['city'] = apply_filters('houzez_api_area_city_data', array(
                    'id' => $city->term_id,
                    'name' => $city->name,
                    'slug' => $city->slug
                ), $city, $term);
            }
        }
        return apply_filters('houzez_api_area_location_data', $formatted_term, $term);
    }

    /**
     * Format phone number for tel: protocol
     *
     * @param string $phone Phone number to format
     * @return string Formatted phone number with tel: protocol
     */
    public static function format_phone_number($phone) {
        if (empty($phone)) {
            return apply_filters('houzez_api_empty_phone_number', '', $phone);
        }

        // Allow pre-cleaning modifications
        $phone = apply_filters('houzez_api_pre_format_phone', $phone);

        // First, clean the number by removing any unwanted characters
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Allow modification of cleaned number
        $cleaned = apply_filters('houzez_api_cleaned_phone_number', $cleaned, $phone);
        
        // If number doesn't start with +, check if we need to add country code
        if (strpos($cleaned, '+') !== 0) {
            // If number starts with 00, replace with +
            if (strpos($cleaned, '00') === 0) {
                $cleaned = '+' . substr($cleaned, 2);
            }
            // Allow custom country code handling
            $cleaned = apply_filters('houzez_api_phone_country_code', $cleaned, $phone);
        }

        return apply_filters('houzez_api_formatted_phone_number', $cleaned, $phone);
    }

    /**
     * Format price with currency
     * 
     * @param string|int|float $price
     * @param string $currency_symbol
     * @return string
     */
    public static function format_price($price, $currency_symbol = '') {
        if (empty($currency_symbol)) {
            $currency_symbol = houzez_option('currency_symbol', '$');
        }

        $price_format = houzez_option('price_format', '{currency} {price}');
        $decimals = houzez_option('decimals', 0);
        $decimal_point = houzez_option('decimal_point', '.');
        $thousands_separator = houzez_option('thousands_separator', ',');

        $price = number_format(
            (float) $price,
            $decimals,
            $decimal_point,
            $thousands_separator
        );

        return str_replace(
            array('{currency}', '{price}'),
            array($currency_symbol, $price),
            $price_format
        );
    }

    /**
     * Format date to ISO8601
     * 
     * @param string $date Date to format
     * @return string Formatted date
     */
    public static function format_date($date) {
        return mysql2date('c', $date);
    }

    /**
     * Get image URL by ID
     * 
     * @param int $image_id Image ID
     * @param string $size Image size
     * @return string|false Image URL or false if not found
     */
    public static function get_image_url($image_id, $size = 'full') {
        if (empty($image_id)) {
            return false;
        }
        return wp_get_attachment_image_url($image_id, $size);
    }

    /**
     * Get image data including alt text and caption
     *
     * @param int $attachment_id
     * @param string $size
     * @return array|false
     */
    public static function get_image_data($attachment_id, $size = 'full') {
        $image = wp_get_attachment_image_src($attachment_id, $size);
        
        if (!$image) {
            return false;
        }

        return array(
            'url' => $image[0],
            'width' => $image[1],
            'height' => $image[2],
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'caption' => wp_get_attachment_caption($attachment_id)
        );
    }

    /**
     * Get user data for API response
     *
     * @param int $user_id
     * @return array
     */
    public static function get_user_data($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return array();
        }

        return array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'avatar' => get_avatar_url($user->ID)
        );
    }

    /**
     * Check if a user has a specific capability
     *
     * @param int $user_id
     * @param string $capability
     * @return bool
     */
    public static function user_can($user_id, $capability) {
        $user = get_userdata($user_id);
        return $user && $user->has_cap($capability);
    }

    /**
     * Generate a random string
     *
     * @param int $length
     * @return string
     */
    public static function generate_random_string($length = 32) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars_length = strlen($chars);
        $random_string = '';

        for ($i = 0; $i < $length; $i++) {
            $random_string .= $chars[rand(0, $chars_length - 1)];
        }

        return $random_string;
    }

    /**
     * Sanitize and validate coordinates
     * 
     * @param string|float $lat Latitude
     * @param string|float $lng Longitude
     * @return array|false Array of coordinates or false if invalid
     */
    public static function validate_coordinates($lat, $lng) {
        $lat = floatval($lat);
        $lng = floatval($lng);

        if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
            return array(
                'latitude' => $lat,
                'longitude' => $lng
            );
        }
        return false;
    }

    /**
     * Clean and format address components
     * 
     * @param array $components Address components
     * @return string Formatted address
     */
    public static function format_address($components) {
        $components = array_filter($components);
        return implode(', ', $components);
    }

    /**
     * Format file size
     * 
     * @param int $bytes Size in bytes
     * @return string Formatted size
     */
    public static function format_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Validate email address
     * 
     * @param string $email Email address to validate
     * @return bool True if valid, false otherwise
     */
    public static function is_valid_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Clean URL and ensure it has protocol
     * 
     * @param string $url URL to clean
     * @return string Cleaned URL
     */
    public static function clean_url($url) {
        if (empty($url)) {
            return '';
        }

        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        return esc_url_raw($url);
    }

    /**
     * Get pagination data from WP_Query
     * 
     * @param WP_Query $query WP_Query object
     * @param array $params Request parameters
     * @return array Pagination data
     */
    public static function get_pagination_data($query, $params) {
        $paged = isset($params['paged']) ? intval($params['paged']) : 1;
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 10;

        return array(
            'total_records' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'current_page' => (int) $paged,
            'posts_per_page' => (int) $per_page,
            'has_next_page' => $paged < $query->max_num_pages,
            'has_previous_page' => $paged > 1,
            'next_page' => $paged < $query->max_num_pages ? $paged + 1 : null,
            'previous_page' => $paged > 1 ? $paged - 1 : null,
        );
    }

    /**
     * Check if user is admin
     */
    public static function is_admin() {
        $current_user = wp_get_current_user();
        return in_array('administrator', (array)$current_user->roles);
    }

    /**
     * Check if user is editor
     */
    public static function is_editor() {
        $current_user = wp_get_current_user();
        return in_array('houzez_manager', (array)$current_user->roles) || in_array('editor', (array)$current_user->roles);
    }

    /**
     * Check if user is agent
     */
    public static function is_agent($user_id = null) {
        // If a user ID is provided, get the user data for the given user ID; otherwise, get the current user.
        if (!empty($user_id)) {
            $user_data = get_userdata($user_id);
        } else {
            $user_data = wp_get_current_user();
        }

        // Check if the user data was successfully retrieved and the user has the 'houzez_agent' role.
        if ($user_data) {
            return in_array('houzez_agent', (array)$user_data->roles);
        }
        return false;
    }

    /**
     * Check if user is agency
     */
    public static function is_agency($user_id = null) {
        // If a user ID is provided, get the user data for the given user ID; otherwise, get the current user.
        if (!empty($user_id)) {
            $user_data = get_userdata($user_id);
        } else {
            $user_data = wp_get_current_user();
        }

        // Check if the user data was successfully retrieved and the user has the 'houzez_agency' role.
        if ($user_data) {
            return in_array('houzez_agency', (array)$user_data->roles);
        }

        return false;
    }

    /**
     * Check if user is buyer
     */
    public static function is_buyer() {
        $current_user = wp_get_current_user();
        return in_array('houzez_buyer', (array)$current_user->roles) || in_array('subscriber', (array)$current_user->roles);
    }

    /**
     * Get reviews count for a specific entity
     *
     * @param string $entity_type Type of entity (agent, agency, property)
     * @param int $entity_id ID of the entity
     * @return int Number of reviews
     */
    public static function get_reviews_count($entity_type, $entity_id) {
        $meta_key = 'review_' . $entity_type . '_id';
        
        $reviews_query = new WP_Query(array(
            'post_type' => 'houzez_reviews',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => $meta_key,
                    'value' => $entity_id,
                    'compare' => '='
                )
            ),
            'fields' => 'ids' // Only get post IDs to make the query more efficient
        ));
        
        $reviews_count = $reviews_query->found_posts;
        wp_reset_postdata();
        
        return $reviews_count;
    }

} 