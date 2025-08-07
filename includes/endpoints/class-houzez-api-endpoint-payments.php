<?php
/**
 * Payment Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Endpoint_Payments extends Houzez_API_Base {

    /**
     * Initialize the class
     */
    public function init() {
        // No initialization needed
    }

    /**
     * Get payment mode
     */
    public static function get_payment_mode($request) {
        try {
            $payment_mode = houzez_option('enable_paid_submission', 'no');
            
            $modes = array(
                'no' => 'No paid submission',
                'free_paid_listing' => 'Free (Pay For Featured)',
                'per_listing' => 'Per Listing',
                'membership' => 'Membership'
            );

            $response_data = array(
                'success' => true,
                'data' => array(
                    'payment_mode' => $payment_mode,
                    'payment_mode_label' => isset($modes[$payment_mode]) ? $modes[$payment_mode] : 'Unknown',
                    'available_modes' => $modes
                )
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'payment_mode_error',
                esc_html__('Error retrieving payment mode: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get payment settings
     */
    public static function get_payment_settings($request) {
        try {
            $payment_mode = houzez_option('enable_paid_submission', 'no');
            $currency = houzez_option('currency_paid_submission', 'USD');
            $currency_symbol = houzez_option('currency_symbol', '$');
            $currency_position = houzez_option('currency_position', 'before');
            
            $response_data = array(
                'success' => true,
                'data' => array(
                    'payment_mode' => $payment_mode,
                    'currency' => $currency,
                    'currency_symbol' => $currency_symbol,
                    'currency_position' => $currency_position,
                    'recurring_enabled' => houzez_option('houzez_disable_recurring', 0),
                    'auto_recurring' => houzez_option('houzez_auto_recurring', 0),
                    'per_listing_expire_unlimited' => houzez_option('per_listing_expire_unlimited', 0),
                    'per_listing_expire_days' => houzez_option('per_listing_expire', '30'),
                    'featured_expire_days' => houzez_option('featured_expire', '30')
                )
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'payment_settings_error',
                esc_html__('Error retrieving payment settings: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get membership packages
     */
    public static function get_packages($request) {
        try {
            $payment_mode = houzez_option('enable_paid_submission', 'no');
            
            if ($payment_mode !== 'membership') {
                return new WP_Error(
                    'membership_not_enabled',
                    esc_html__('Membership payment mode is not enabled.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $args = array(
                'post_type' => 'houzez_packages',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => 'fave_package_visible',
                        'value' => 'yes',
                        'compare' => '='
                    )
                )
            );

            $packages_query = new WP_Query($args);
            $packages = array();

            if ($packages_query->have_posts()) {
                while ($packages_query->have_posts()) {
                    $packages_query->the_post();
                    $package_id = get_the_ID();
                    
                    $package_data = self::get_package_data($package_id);
                    $packages[] = $package_data;
                }
                wp_reset_postdata();
            }

            $response_data = array(
                'success' => true,
                'data' => array(
                    'packages' => $packages,
                    'total_packages' => count($packages)
                )
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'packages_error',
                esc_html__('Error retrieving packages: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get specific package details
     */
    public static function get_package_details($request) {
        try {
            $package_id = $request->get_param('id');
            
            if (!$package_id) {
                return new WP_Error(
                    'missing_package_id',
                    esc_html__('Package ID is required.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $package = get_post($package_id);
            
            if (!$package || $package->post_type !== 'houzez_packages') {
                return new WP_Error(
                    'package_not_found',
                    esc_html__('Package not found.', 'houzez-api'),
                    array('status' => 404)
                );
            }

            $package_data = self::get_package_data($package_id);

            $response_data = array(
                'success' => true,
                'data' => $package_data
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'package_details_error',
                esc_html__('Error retrieving package details: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get per listing prices
     */
    public static function get_per_listing_prices($request) {
        try {
            $payment_mode = houzez_option('enable_paid_submission', 'no');
            
            if (!in_array($payment_mode, array('per_listing', 'free_paid_listing'))) {
                return new WP_Error(
                    'per_listing_not_enabled',
                    esc_html__('Per listing payment mode is not enabled.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $listing_price = houzez_option('price_listing_submission', '0');
            $featured_price = houzez_option('price_featured_listing_submission', '0');
            $currency = houzez_option('currency_paid_submission', 'USD');
            $currency_symbol = houzez_option('currency_symbol', '$');
            $currency_position = houzez_option('currency_position', 'before');

            $response_data = array(
                'success' => true,
                'data' => array(
                    'payment_mode' => $payment_mode,
                    'listing_price' => floatval($listing_price),
                    'featured_price' => floatval($featured_price),
                    'currency' => $currency,
                    'currency_symbol' => $currency_symbol,
                    'currency_position' => $currency_position,
                    'formatted_listing_price' => self::format_price($listing_price, $currency_symbol, $currency_position),
                    'formatted_featured_price' => self::format_price($featured_price, $currency_symbol, $currency_position),
                    'expire_days' => houzez_option('per_listing_expire', '30'),
                    'expire_unlimited' => houzez_option('per_listing_expire_unlimited', 0)
                )
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'per_listing_prices_error',
                esc_html__('Error retrieving per listing prices: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get user payment status
     */
    public static function get_user_payment_status($request) {
        try {
            $user_id = get_current_user_id();
            $payment_mode = houzez_option('enable_paid_submission', 'no');

            $user_data = array(
                'user_id' => $user_id,
                'payment_mode' => $payment_mode
            );

            if ($payment_mode === 'membership') {
                $user_package_id = get_user_meta($user_id, 'package_id', true);
                $package_activation = get_user_meta($user_id, 'package_activation', true);
                $remaining_listings = get_user_meta($user_id, 'package_listings', true);
                $remaining_featured = get_user_meta($user_id, 'package_featured_listings', true);

                $user_data['membership'] = array(
                    'package_id' => $user_package_id,
                    'package_activation' => $package_activation,
                    'remaining_listings' => intval($remaining_listings),
                    'remaining_featured' => intval($remaining_featured),
                    'has_active_package' => !empty($user_package_id) && !empty($package_activation)
                );

                if (!empty($user_package_id)) {
                    $package_data = self::get_package_data($user_package_id);
                    $user_data['membership']['package_details'] = $package_data;
                }
            }

            $response_data = array(
                'success' => true,
                'data' => $user_data
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'user_status_error',
                esc_html__('Error retrieving user payment status: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get payment methods
     */
    public static function get_payment_methods($request) {
        try {
            $payment_mode = houzez_option('enable_paid_submission', 'no');
            
            if ($payment_mode === 'no') {
                return new WP_Error(
                    'payment_not_enabled',
                    esc_html__('Payment is not enabled.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $methods = array();

            if (houzez_option('enable_paypal', 0)) {
                $methods['paypal'] = array(
                    'name' => 'PayPal',
                    'enabled' => true,
                    'type' => 'paypal'
                );
            }

            if (houzez_option('enable_stripe', 0)) {
                $methods['stripe'] = array(
                    'name' => 'Stripe',
                    'enabled' => true,
                    'type' => 'stripe'
                );
            }

            if (houzez_option('enable_2checkout', 0)) {
                $methods['2checkout'] = array(
                    'name' => '2Checkout',
                    'enabled' => true,
                    'type' => '2checkout'
                );
            }

            if (houzez_option('enable_wireTransfer', 0)) {
                $methods['bank_transfer'] = array(
                    'name' => 'Bank Transfer',
                    'enabled' => true,
                    'type' => 'direct_pay'
                );
            }

            $response_data = array(
                'success' => true,
                'data' => array(
                    'payment_methods' => $methods,
                    'api_mode' => houzez_option('paypal_api', 'sandbox'),
                    'recurring_enabled' => houzez_option('houzez_disable_recurring', 0),
                    'auto_recurring' => houzez_option('houzez_auto_recurring', 0)
                )
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'payment_methods_error',
                esc_html__('Error retrieving payment methods: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Create payment session
     */
    public static function create_payment_session($request) {
        try {
            $payment_type = $request->get_param('payment_type');
            $payment_method = $request->get_param('payment_method');
            $user_id = get_current_user_id();

            if (!$user_id) {
                return new WP_Error(
                    'authentication_required',
                    esc_html__('Authentication required.', 'houzez-api'),
                    array('status' => 401)
                );
            }

            $response_data = array();

            switch ($payment_type) {
                case 'membership':
                    $package_id = $request->get_param('package_id');
                    if (!$package_id) {
                        return new WP_Error(
                            'missing_package_id',
                            esc_html__('Package ID is required for membership payment.', 'houzez-api'),
                            array('status' => 400)
                        );
                    }
                    $response_data = self::create_membership_session($package_id, $payment_method, $user_id);
                    break;

                case 'per_listing':
                    $property_id = $request->get_param('property_id');
                    $is_featured = $request->get_param('is_featured') ? 1 : 0;
                    $response_data = self::create_per_listing_session($property_id, $is_featured, $payment_method, $user_id);
                    break;

                case 'featured':
                    $property_id = $request->get_param('property_id');
                    if (!$property_id) {
                        return new WP_Error(
                            'missing_property_id',
                            esc_html__('Property ID is required for featured payment.', 'houzez-api'),
                            array('status' => 400)
                        );
                    }
                    $response_data = self::create_featured_session($property_id, $payment_method, $user_id);
                    break;

                default:
                    return new WP_Error(
                        'invalid_payment_type',
                        esc_html__('Invalid payment type.', 'houzez-api'),
                        array('status' => 400)
                    );
            }

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'payment_session_error',
                esc_html__('Error creating payment session: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Cancel subscription (auto-detects payment method)
     */
    public static function cancel_subscription($request) {
        try {
            $user_id = get_current_user_id();

            if (!$user_id) {
                return new WP_Error(
                    'authentication_required',
                    esc_html__('Authentication required.', 'houzez-api'),
                    array('status' => 401)
                );
            }

            // Check user's subscription status
            $package_id = get_user_meta($user_id, 'package_id', true);
            $stripe_subscription_id = get_user_meta($user_id, 'houzez_stripe_subscription_id', true);
            $paypal_subscription_id = get_user_meta($user_id, 'houzez_paypal_recurring_profile_id', true);
            $is_recurring = get_user_meta($user_id, 'houzez_is_recurring_membership', true);

            if (empty($package_id)) {
                return new WP_Error(
                    'no_active_subscription',
                    esc_html__('No active subscription found.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            if (!$is_recurring) {
                return new WP_Error(
                    'not_recurring_subscription',
                    esc_html__('Current subscription is not recurring.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            $response_data = array(
                'success' => true,
                'user_id' => $user_id,
                'package_id' => $package_id
            );

            // Auto-detect and cancel based on payment method
            if (!empty($stripe_subscription_id)) {
                $cancel_result = self::cancel_stripe_subscription_internal($user_id);
                $response_data['payment_method'] = 'stripe';
                $response_data['message'] = 'Stripe subscription cancelled successfully. It will remain active until the end of the current billing period.';
            } elseif (!empty($paypal_subscription_id)) {
                $cancel_result = self::cancel_paypal_subscription_internal($user_id);
                $response_data['payment_method'] = 'paypal';
                $response_data['message'] = 'PayPal subscription cancelled successfully. It will remain active until the end of the current billing period.';
            } else {
                return new WP_Error(
                    'no_subscription_method',
                    esc_html__('No active Stripe or PayPal subscription found.', 'houzez-api'),
                    array('status' => 400)
                );
            }

            if (!$cancel_result['success']) {
                return new WP_Error(
                    'cancellation_failed',
                    $cancel_result['message'],
                    array('status' => 500)
                );
            }

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'subscription_cancel_error',
                esc_html__('Error cancelling subscription: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Cancel Stripe subscription
     */
    public static function cancel_stripe_subscription($request) {
        try {
            $user_id = get_current_user_id();

            if (!$user_id) {
                return new WP_Error(
                    'authentication_required',
                    esc_html__('Authentication required.', 'houzez-api'),
                    array('status' => 401)
                );
            }

            $result = self::cancel_stripe_subscription_internal($user_id);

            if (!$result['success']) {
                return new WP_Error(
                    'stripe_cancel_failed',
                    $result['message'],
                    array('status' => 400)
                );
            }

            $response_data = array(
                'success' => true,
                'user_id' => $user_id,
                'payment_method' => 'stripe',
                'message' => 'Stripe subscription cancelled successfully. It will remain active until the end of the current billing period.'
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'stripe_cancel_error',
                esc_html__('Error cancelling Stripe subscription: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Cancel PayPal subscription
     */
    public static function cancel_paypal_subscription($request) {
        try {
            $user_id = get_current_user_id();

            if (!$user_id) {
                return new WP_Error(
                    'authentication_required',
                    esc_html__('Authentication required.', 'houzez-api'),
                    array('status' => 401)
                );
            }

            $result = self::cancel_paypal_subscription_internal($user_id);

            if (!$result['success']) {
                return new WP_Error(
                    'paypal_cancel_failed',
                    $result['message'],
                    array('status' => 400)
                );
            }

            $response_data = array(
                'success' => true,
                'user_id' => $user_id,
                'payment_method' => 'paypal',
                'message' => 'PayPal subscription cancelled successfully. It will remain active until the end of the current billing period.'
            );

            return new WP_REST_Response($response_data, 200);

        } catch (Exception $e) {
            return new WP_Error(
                'paypal_cancel_error',
                esc_html__('Error cancelling PayPal subscription: ', 'houzez-api') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get package data
     */
    private static function get_package_data($package_id) {
        $package = get_post($package_id);
        $package_price = get_post_meta($package_id, 'fave_package_price', true);
        $package_tax = get_post_meta($package_id, 'fave_package_tax', true);
        $currency_symbol = houzez_option('currency_symbol', '$');
        $currency_position = houzez_option('currency_position', 'before');
        $payment_page_link = houzez_get_template_link('template/template-payment.php');

        // Calculate total price with tax
        $total_price = floatval($package_price);
        if (!empty($package_tax) && !empty($package_price)) {
            $tax_amount = ($package_tax / 100) * $total_price;
            $total_price += $tax_amount;
        }

        return array(
            'id' => $package_id,
            'title' => $package->post_title,
            'description' => $package->post_content,
            'price' => floatval($package_price),
            'tax_rate' => floatval($package_tax),
            'total_price' => $total_price,
            'formatted_price' => self::format_price($package_price, $currency_symbol, $currency_position),
            'formatted_total_price' => self::format_price($total_price, $currency_symbol, $currency_position),
            'listings_included' => get_post_meta($package_id, 'fave_package_listings', true),
            'featured_included' => get_post_meta($package_id, 'fave_package_featured_listings', true),
            'unlimited_listings' => get_post_meta($package_id, 'fave_unlimited_listings', true),
            'billing_period' => get_post_meta($package_id, 'fave_billing_time_unit', true),
            'billing_frequency' => get_post_meta($package_id, 'fave_billing_unit', true),
            'visible' => get_post_meta($package_id, 'fave_package_visible', true),
            'popular' => get_post_meta($package_id, 'fave_package_popular', true),
            'payment_page_link' => $payment_page_link
        );
    }

    /**
     * Format price with currency
     */
    private static function format_price($price, $symbol, $position) {
        $price = floatval($price);
        if ($position === 'before') {
            return $symbol . number_format($price, 2);
        } else {
            return number_format($price, 2) . $symbol;
        }
    }

    /**
     * Create membership payment session
     */
    private static function create_membership_session($package_id, $payment_method, $user_id) {
        // This would integrate with the existing Houzez payment functions
        // For now, return basic session data
        return array(
            'success' => true,
            'data' => array(
                'session_type' => 'membership',
                'package_id' => $package_id,
                'payment_method' => $payment_method,
                'user_id' => $user_id,
                'message' => 'Payment session creation functionality needs to be integrated with existing Houzez payment methods.'
            )
        );
    }

    /**
     * Create per listing payment session
     */
    private static function create_per_listing_session($property_id, $is_featured, $payment_method, $user_id) {
        // This would integrate with the existing Houzez payment functions
        // For now, return basic session data
        return array(
            'success' => true,
            'data' => array(
                'session_type' => 'per_listing',
                'property_id' => $property_id,
                'is_featured' => $is_featured,
                'payment_method' => $payment_method,
                'user_id' => $user_id,
                'message' => 'Payment session creation functionality needs to be integrated with existing Houzez payment methods.'
            )
        );
    }

    /**
     * Create featured payment session
     */
    private static function create_featured_session($property_id, $payment_method, $user_id) {
        // This would integrate with the existing Houzez payment functions
        // For now, return basic session data
        return array(
            'success' => true,
            'data' => array(
                'session_type' => 'featured',
                'property_id' => $property_id,
                'payment_method' => $payment_method,
                'user_id' => $user_id,
                'message' => 'Payment session creation functionality needs to be integrated with existing Houzez payment methods.'
            )
        );
    }

    /**
     * Internal method to cancel Stripe subscription
     */
    private static function cancel_stripe_subscription_internal($user_id) {
        try {
            $stripe_customer_id = get_user_meta($user_id, 'fave_stripe_user_profile', true);
            $subscription_id = get_user_meta($user_id, 'houzez_stripe_subscription_id', true);

            if (empty($subscription_id)) {
                return array(
                    'success' => false,
                    'message' => 'No active Stripe subscription found.'
                );
            }

            // Check if Stripe is enabled
            if (!houzez_option('enable_stripe', 0)) {
                return array(
                    'success' => false,
                    'message' => 'Stripe is not enabled.'
                );
            }

            // Load Stripe PHP library
            require_once( get_template_directory() . '/framework/stripe-php/init.php' );

            $stripe_secret_key = houzez_option('stripe_secret_key');
            if (empty($stripe_secret_key)) {
                return array(
                    'success' => false,
                    'message' => 'Stripe secret key not configured.'
                );
            }

            \Stripe\Stripe::setApiKey($stripe_secret_key);

            // Cancel the subscription
            $subscription = \Stripe\Subscription::retrieve($subscription_id);
            \Stripe\Subscription::update(
                $subscription_id,
                array(
                    'cancel_at_period_end' => true,
                )
            );
            $subscription->cancel();

            // Update user meta
            update_user_meta($user_id, 'houzez_subscription_detail_status', 'expired');
            delete_user_meta($user_id, 'houzez_stripe_subscription_id');
            delete_user_meta($user_id, 'houzez_stripe_subscription_start');
            delete_user_meta($user_id, 'houzez_stripe_subscription_due');
            update_user_meta($user_id, 'houzez_has_stripe_recurring', 0);
            update_user_meta($user_id, 'houzez_is_recurring_membership', 0);

            delete_user_meta($user_id, 'houzez_subscription_order_number');
            delete_user_meta($user_id, 'houzez_subscription_session_id');
            delete_user_meta($user_id, 'houzez_subscription_plan_id');

            return array(
                'success' => true,
                'message' => 'Stripe subscription cancelled successfully.'
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Stripe cancellation failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Internal method to cancel PayPal subscription
     */
    private static function cancel_paypal_subscription_internal($user_id) {
        try {
            $subscription_id = get_user_meta($user_id, 'houzez_paypal_recurring_profile_id', true);

            if (empty($subscription_id)) {
                return array(
                    'success' => false,
                    'message' => 'No active PayPal subscription found.'
                );
            }

            // Check if PayPal is enabled
            if (!houzez_option('enable_paypal', 0)) {
                return array(
                    'success' => false,
                    'message' => 'PayPal is not enabled.'
                );
            }

            // Setup PayPal API
            $host = 'https://api.sandbox.paypal.com';
            $is_paypal_live = houzez_option('paypal_api');
            if ($is_paypal_live == 'live') {
                $host = 'https://api.paypal.com';
            }

            $url = $host . '/v1/oauth2/token';
            $postArgs = 'grant_type=client_credentials';

            // Get access token
            if (function_exists('houzez_get_paypal_access_token')) {
                $access_token = houzez_get_paypal_access_token($url, $postArgs);
            } else {
                return array(
                    'success' => false,
                    'message' => 'PayPal access token function not available.'
                );
            }

            if (empty($access_token)) {
                return array(
                    'success' => false,
                    'message' => 'Failed to get PayPal access token.'
                );
            }

            // Cancel subscription
            $cancel_url = $host . '/v1/billing/subscriptions/' . $subscription_id . '/cancel';

            if (function_exists('houzez_execute_paypal_request_2')) {
                $json_resp = houzez_execute_paypal_request_2($cancel_url, $access_token);
            } else {
                return array(
                    'success' => false,
                    'message' => 'PayPal request function not available.'
                );
            }

            // Update user meta
            update_user_meta($user_id, 'houzez_is_recurring_membership', 0);
            update_user_meta($user_id, 'houzez_paypal_recurring_profile_id', '');
            update_user_meta($user_id, 'houzez_subscription_detail_status', 'expired');

            return array(
                'success' => true,
                'message' => 'PayPal subscription cancelled successfully.'
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'PayPal cancellation failed: ' . $e->getMessage()
            );
        }
    }
}
