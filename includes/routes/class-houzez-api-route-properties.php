<?php
/**
 * Properties Routes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Route_Properties extends Houzez_API_Route {
    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base = 'properties';

    /**
     * Register routes
     */
    public function register_routes() {
        // Get properties list with basic filtering (pagination, ordering)
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_properties'),
            'permission_callback' => '__return_true',
        ));

        // Property Search/Filter
        // Advanced property search with multiple criteria
        register_rest_route($this->namespace, '/' . $this->rest_base . '/search', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'search_properties'),
            'permission_callback' => '__return_true'
        ));

        // Get single property details by ID
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            'methods' => 'GET',
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Create new property
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'create_property'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication')
        ));

        // Update existing property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'update_property'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Delete property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Properties', 'delete_property'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Get custom fields
        register_rest_route($this->namespace, '/custom-fields', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_custom_fields'),
            'permission_callback' => '__return_true'
        ));

        // Get property types taxonomy terms
        register_rest_route($this->namespace, '/property-types', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property_types'),
            'permission_callback' => '__return_true'
        ));

        // Get property status taxonomy terms
        register_rest_route($this->namespace, '/property-status', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property_status'),
            'permission_callback' => '__return_true'
        ));

        // Get property labels taxonomy terms
        register_rest_route($this->namespace, '/property-labels', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property_labels'),
            'permission_callback' => '__return_true'
        ));

        // Get property countries taxonomy terms
        register_rest_route($this->namespace, '/property-countries', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property_countries'),
            'permission_callback' => '__return_true'
        ));

        // Get property states taxonomy terms
        register_rest_route($this->namespace, '/property-states', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property_states'),
            'permission_callback' => '__return_true',
            'args' => array(
                'country_slug' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Filter states by country slug',
                    'validate_callback' => function($param) {
                        return is_string($param) && !empty($param);
                    },
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Get property cities taxonomy terms
        register_rest_route($this->namespace, '/property-cities', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property_cities'),
            'permission_callback' => '__return_true',
            'args' => array(
                'state_slug' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Filter cities by state slug',
                    'validate_callback' => function($param) {
                        return is_string($param) && !empty($param);
                    },
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Get property areas taxonomy terms
        register_rest_route($this->namespace, '/property-areas', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property_areas'),
            'permission_callback' => '__return_true',
            'args' => array(
                'city_slug' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Filter areas by city slug',
                    'validate_callback' => function($param) {
                        return is_string($param) && !empty($param);
                    },
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Get property features taxonomy terms
        register_rest_route($this->namespace, '/property-features', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property_features'),
            'permission_callback' => '__return_true'
        ));

        // Toggle property favorite status
        register_rest_route($this->namespace, '/favorite-property/(?P<property_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Properties', 'favorite_property'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication'),
        ));

        // Property Media Management
        // Upload media files to property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/media', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Properties', 'add_property_media'),
            'permission_callback' => '__return_true'
        ));

        // Delete specific media from property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/media/(?P<media_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Properties', 'remove_property_media'),
            'permission_callback' => '__return_true'
        ));

        // Reorder property media gallery
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/media/reorder', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Properties', 'reorder_property_media'),
            'permission_callback' => '__return_true'
        ));

        // Property Documents
        // Upload documents to property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/documents', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Properties', 'add_property_document'),
            'permission_callback' => '__return_true'
        ));

        // Delete specific document from property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/documents/(?P<document_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Properties', 'remove_property_document'),
            'permission_callback' => '__return_true'
        ));

        // Floor Plans
        // Add new floor plan to property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/floor-plans', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Properties', 'add_floor_plan'),
            'permission_callback' => '__return_true'
        ));

        // Update existing floor plan
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/floor-plans/(?P<plan_id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array('Houzez_API_Endpoint_Properties', 'update_floor_plan'),
            'permission_callback' => '__return_true'
        ));

        // Delete floor plan from property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/floor-plans/(?P<plan_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Properties', 'delete_floor_plan'),
            'permission_callback' => '__return_true'
        ));

        // Additional Features
        // Add custom feature to property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/features', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Properties', 'add_additional_feature'),
            'permission_callback' => '__return_true'
        ));

        // Remove custom feature from property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/features/(?P<feature_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('Houzez_API_Endpoint_Properties', 'remove_additional_feature'),
            'permission_callback' => '__return_true'
        ));

        // Property Status Management
        // Toggle property featured status
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/featured', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Properties', 'toggle_featured'),
            'permission_callback' => '__return_true'
        ));

        // Update property publish status (publish, draft, pending, private)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/status', array(
            'methods' => 'POST',
            'callback' => array('Houzez_API_Endpoint_Properties', 'update_property_status'),
            'permission_callback' => '__return_true'
        ));

        // Get similar properties based on type, status, and location
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/similar', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_similar_properties'),
            'permission_callback' => '__return_true',
            // 'args' => array(
            //     'id' => array(
            //         'required' => true,
            //         'validate_callback' => function($param, $request, $key) {
            //             return is_numeric($param) && $param > 0;
            //         },
            //         'sanitize_callback' => 'absint'
            //     ),
            //     'limit' => array(
            //         'required' => false,
            //         'default' => 3,
            //         'validate_callback' => function($param, $request, $key) {
            //             return is_numeric($param) && $param > 0;
            //         },
            //         'sanitize_callback' => 'absint'
            //     ),
            //     'criteria' => array(
            //         'required' => false,
            //         'default' => array('property_type', 'property_city'),
            //         'validate_callback' => function($param, $request, $key) {
            //             return is_array($param);
            //         }
            //     ),
            //     'sort_by' => array(
            //         'required' => false,
            //         'default' => 'd_date',
            //         'validate_callback' => function($param, $request, $key) {
            //             $valid_sort = array('a_title', 'd_title', 'a_price', 'd_price', 'a_date', 'd_date', 'featured_first', 'featured_first_random', 'random');
            //             return in_array($param, $valid_sort);
            //         }
            //     )
            // )
        ));

        // Get reviews for a specific property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/reviews', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_property_reviews'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'sort_by' => array(
                    'required' => false,
                    'default' => 'd_date',
                    'validate_callback' => function($param) {
                        return in_array($param, array('a_rating', 'd_rating', 'a_date', 'd_date'));
                    }
                ),
                'paged' => array(
                    'required' => false,
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 10,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Add review for a specific property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/reviews', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'add_property_review'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'rating' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 1 && $param <= 5;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'title' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'content' => array(
                    'required' => true,
                    'sanitize_callback' => 'wp_kses_post'
                ),
                'email' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_email($param);
                    },
                    'sanitize_callback' => 'sanitize_email'
                )
            )
        ));
        
        // Handle review actions (like/dislike)
        register_rest_route($this->namespace, '/reviews/(?P<review_id>[\d]+)/action', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'review_action'),
            'permission_callback' => '__return_true',
            'args' => array(
                'review_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'action' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return in_array($param, ['likes', 'dislikes']);
                    },
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Contact property agent
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/contact', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'contact_property_agent'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'email' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_email($param);
                    },
                    'sanitize_callback' => 'sanitize_email'
                ),
                'message' => array(
                    'required' => true,
                    'sanitize_callback' => 'wp_kses_post'
                ),
                'phone' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'user_type' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Save search criteria
        register_rest_route($this->namespace, '/saved-searches', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'save_search'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication')
        ));

        // Get user's saved searches
        register_rest_route($this->namespace, '/saved-searches', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'get_saved_searches'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication')
        ));

        // Delete saved search - fix the route pattern to use just 'saved-searches' as base
        register_rest_route($this->namespace, '/saved-searches/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE, // Use WP_REST_Server::DELETABLE instead of 'DELETE'
            'callback' => array('Houzez_API_Endpoint_Properties', 'delete_saved_search'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Toggle email notifications for saved search
        register_rest_route($this->namespace, '/saved-searches/(?P<id>\d+)/toggle-notifications', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array('Houzez_API_Endpoint_Properties', 'toggle_saved_search_notifications'),
            'permission_callback' => array('Houzez_API_Auth', 'check_authentication'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'enabled' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_bool($param) || in_array($param, ['true', 'false', '0', '1'], true);
                    }
                )
            )
        ));
    }


    /**
     * Initialize the class
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

}