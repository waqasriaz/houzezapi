<?php
/**
 * Realtors Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Endpoint_Realtors extends Houzez_API_Base {

    /**
     * Initialize realtors endpoint
     */
    public function init() {
        // Add initialization code here when needed
    }

    /**
     * Contact realtor (agent or agency)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function contact_realtor($request) {
        $realtor_id = $request['id'];
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
        
        // Determine realtor type
        $realtor_type = isset($params['realtor_type']) ? sanitize_text_field($params['realtor_type']) : 'agent_info';
        
        // Get target email based on realtor type
        if ($realtor_type == 'author_info') {
            $user = get_user_by('ID', $realtor_id);
            if (!$user) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => esc_html__('Author not found', 'houzez-api')
                ], 404);
            }
            $target_email = $user->user_email;
        } else if ($realtor_type == 'agency_info') {
            $agency = get_post($realtor_id);
            if (!$agency || $agency->post_type !== 'houzez_agency') {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => esc_html__('Agency not found', 'houzez-api')
                ], 404);
            }
            $target_email = get_post_meta($realtor_id, 'fave_agency_email', true);
        } else {
            $agent = get_post($realtor_id);
            if (!$agent || $agent->post_type !== 'houzez_agent') {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => esc_html__('Agent not found', 'houzez-api')
                ], 404);
            }
            $target_email = get_post_meta($realtor_id, 'fave_agent_email', true);
        }
        
        // Validate email
        $target_email = is_email($target_email);
        if (!$target_email) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Realtor email not found or invalid', 'houzez-api')
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
                if (houzez_option('webhook_agency_contact') == 1 && $realtor_type == "agency_info") {
                    houzez_webhook_post($params, 'houzez_agency_profile_contact_from');
                } elseif ((houzez_option('webhook_agent_contact') == 1) && ($realtor_type == "agent_info" || $realtor_type == "author_info")) {
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
}
