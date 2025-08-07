<?php
/**
 * Bulk Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Endpoint_Bulk extends Houzez_API_Base {
    /**
     * Bulk create properties
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function create_properties($request) {
        $params = $request->get_params();
        
        if (empty($params['properties']) || !is_array($params['properties'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No properties data provided', 'houzez-api')
            ], 400);
        }

        $results = array(
            'success' => array(),
            'failed' => array()
        );

        foreach ($params['properties'] as $property_data) {
            $request = new WP_REST_Request('POST', '/houzez-api/v1/properties');
            $request->set_body_params($property_data);
            
            $response = Houzez_API_Endpoint_Properties::create_property($request);
            
            if ($response->get_status() === 201) {
                $results['success'][] = $response->get_data();
            } else {
                $results['failed'][] = array(
                    'data' => $property_data,
                    'error' => $response->get_data()
                );
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Bulk update properties
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function update_properties($request) {
        $params = $request->get_params();
        
        if (empty($params['properties']) || !is_array($params['properties'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No properties data provided', 'houzez-api')
            ], 400);
        }

        $results = array(
            'success' => array(),
            'failed' => array()
        );

        foreach ($params['properties'] as $property_data) {
            if (empty($property_data['id'])) {
                $results['failed'][] = array(
                    'data' => $property_data,
                    'error' => array('message' => 'Property ID is required')
                );
                continue;
            }

            $request = new WP_REST_Request('PUT', '/houzez-api/v1/properties/' . $property_data['id']);
            $request->set_body_params($property_data);
            
            $response = Houzez_API_Endpoint_Properties::update_property($request);
            
            if ($response->get_status() === 200) {
                $results['success'][] = $response->get_data();
            } else {
                $results['failed'][] = array(
                    'data' => $property_data,
                    'error' => $response->get_data()
                );
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Bulk delete properties
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_properties($request) {
        $params = $request->get_params();
        
        if (empty($params['ids']) || !is_array($params['ids'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No property IDs provided', 'houzez-api')
            ], 400);
        }

        $results = array(
            'success' => array(),
            'failed' => array()
        );

        foreach ($params['ids'] as $property_id) {
            $request = new WP_REST_Request('DELETE', '/houzez-api/v1/properties/' . $property_id);
            $response = Houzez_API_Endpoint_Properties::delete_property($request);
            
            if ($response->get_status() === 200) {
                $results['success'][] = $property_id;
            } else {
                $results['failed'][] = array(
                    'id' => $property_id,
                    'error' => $response->get_data()
                );
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Bulk create agents
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function create_agents($request) {
        $params = $request->get_params();
        
        if (empty($params['agents']) || !is_array($params['agents'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No agents data provided', 'houzez-api')
            ], 400);
        }

        $results = array(
            'success' => array(),
            'failed' => array()
        );

        foreach ($params['agents'] as $agent_data) {
            $request = new WP_REST_Request('POST', '/houzez-api/v1/agents');
            $request->set_body_params($agent_data);
            
            $response = Houzez_API_Endpoint_Agents::create_agent($request);
            
            if ($response->get_status() === 201) {
                $results['success'][] = $response->get_data();
            } else {
                $results['failed'][] = array(
                    'data' => $agent_data,
                    'error' => $response->get_data()
                );
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Bulk update agents
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function update_agents($request) {
        $params = $request->get_params();
        
        if (empty($params['agents']) || !is_array($params['agents'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No agents data provided', 'houzez-api')
            ], 400);
        }

        $results = array(
            'success' => array(),
            'failed' => array()
        );

        foreach ($params['agents'] as $agent_data) {
            if (empty($agent_data['id'])) {
                $results['failed'][] = array(
                    'data' => $agent_data,
                    'error' => array('message' => 'Agent ID is required')
                );
                continue;
            }

            $request = new WP_REST_Request('PUT', '/houzez-api/v1/agents/' . $agent_data['id']);
            $request->set_body_params($agent_data);
            
            $response = Houzez_API_Endpoint_Agents::update_agent($request);
            
            if ($response->get_status() === 200) {
                $results['success'][] = $response->get_data();
            } else {
                $results['failed'][] = array(
                    'data' => $agent_data,
                    'error' => $response->get_data()
                );
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Bulk delete agents
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_agents($request) {
        $params = $request->get_params();
        
        if (empty($params['ids']) || !is_array($params['ids'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No agent IDs provided', 'houzez-api')
            ], 400);
        }

        $results = array(
            'success' => array(),
            'failed' => array()
        );

        foreach ($params['ids'] as $agent_id) {
            $request = new WP_REST_Request('DELETE', '/houzez-api/v1/agents/' . $agent_id);
            $response = Houzez_API_Endpoint_Agents::delete_agent($request);
            
            if ($response->get_status() === 200) {
                $results['success'][] = $agent_id;
            } else {
                $results['failed'][] = array(
                    'id' => $agent_id,
                    'error' => $response->get_data()
                );
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Bulk create agencies
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function create_agencies($request) {
        $params = $request->get_params();
        
        if (empty($params['agencies']) || !is_array($params['agencies'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No agencies data provided', 'houzez-api')
            ], 400);
        }

        $results = array(
            'success' => array(),
            'failed' => array()
        );

        foreach ($params['agencies'] as $agency_data) {
            $request = new WP_REST_Request('POST', '/houzez-api/v1/agencies');
            $request->set_body_params($agency_data);
            
            $response = Houzez_API_Endpoint_Agencies::create_agency($request);
            
            if ($response->get_status() === 201) {
                $results['success'][] = $response->get_data();
            } else {
                $results['failed'][] = array(
                    'data' => $agency_data,
                    'error' => $response->get_data()
                );
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Bulk update agencies
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function update_agencies($request) {
        $params = $request->get_params();
        
        if (empty($params['agencies']) || !is_array($params['agencies'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No agencies data provided', 'houzez-api')
            ], 400);
        }

        $results = array(
            'success' => array(),
            'failed' => array()
        );

        foreach ($params['agencies'] as $agency_data) {
            if (empty($agency_data['id'])) {
                $results['failed'][] = array(
                    'data' => $agency_data,
                    'error' => array('message' => 'Agency ID is required')
                );
                continue;
            }

            $request = new WP_REST_Request('PUT', '/houzez-api/v1/agencies/' . $agency_data['id']);
            $request->set_body_params($agency_data);
            
            $response = Houzez_API_Endpoint_Agencies::update_agency($request);
            
            if ($response->get_status() === 200) {
                $results['success'][] = $response->get_data();
            } else {
                $results['failed'][] = array(
                    'data' => $agency_data,
                    'error' => $response->get_data()
                );
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Bulk delete agencies
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_agencies($request) {
        $params = $request->get_params();
        
        if (empty($params['ids']) || !is_array($params['ids'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No agency IDs provided', 'houzez-api')
            ], 400);
        }

        $results = array(
            'success' => array(),
            'failed' => array()
        );

        foreach ($params['ids'] as $agency_id) {
            $request = new WP_REST_Request('DELETE', '/houzez-api/v1/agencies/' . $agency_id);
            $response = Houzez_API_Endpoint_Agencies::delete_agency($request);
            
            if ($response->get_status() === 200) {
                $results['success'][] = $agency_id;
            } else {
                $results['failed'][] = array(
                    'id' => $agency_id,
                    'error' => $response->get_data()
                );
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results
        ], 200);
    }

    /**
     * Initialize the class
     */
    public function init() {
        // No initialization needed for static methods
    }
} 