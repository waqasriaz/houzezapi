<?php
/**
 * AJAX Handler Class
 *
 * @package Houzez_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Ajax_Handler extends Houzez_API_Base {
    /**
     * Initialize the class
     */
    public function init() {
        // API Key management hooks
        add_action('wp_ajax_houzez_generate_api_key', array($this, 'generate_api_key'));
        add_action('wp_ajax_houzez_revoke_api_key', array($this, 'revoke_api_key'));
        add_action('wp_ajax_houzez_delete_api_key', array($this, 'delete_api_key'));
        add_action('wp_ajax_houzez_activate_api_key', array($this, 'activate_api_key'));
    }

    /**
     * Generate API key via AJAX
     */
    public function generate_api_key() {
        check_ajax_referer('houzez_api_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('Unauthorized', 'houzez-api')));
        }

        $app_name = sanitize_text_field($_POST['app_name']);
        $description = sanitize_textarea_field($_POST['description']);
        $expiry_days = intval($_POST['expiry_days']);

        $api_key = Houzez_API_Keys::generate_key($app_name, $description, $expiry_days);

        if ($api_key) {
            wp_send_json_success(array('api_key' => $api_key));
        } else {
            wp_send_json_error(array('message' => esc_html__('Failed to generate API key', 'houzez-api')));
        }
    }

    /**
     * Revoke API key via AJAX
     */
    public function revoke_api_key() {
        check_ajax_referer('houzez_api_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('Unauthorized', 'houzez-api')));
        }

        $api_key = sanitize_text_field($_POST['api_key']);
        $result = Houzez_API_Keys::revoke_key($api_key);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => esc_html__('Failed to revoke API key', 'houzez-api')));
        }
    }

    /**
     * Delete API key via AJAX
     */
    public function delete_api_key() {
        check_ajax_referer('houzez_api_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('Unauthorized', 'houzez-api')));
        }

        $api_key = sanitize_text_field($_POST['api_key']);
        $result = Houzez_API_Keys::delete_key($api_key);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => esc_html__('Failed to delete API key', 'houzez-api')));
        }
    }

    /**
     * Activate API key via AJAX
     */
    public function activate_api_key() {
        check_ajax_referer('houzez_api_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('Unauthorized', 'houzez-api')));
        }

        $api_key = sanitize_text_field($_POST['api_key']);
        $result = Houzez_API_Keys::activate_key($api_key);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => esc_html__('Failed to activate API key', 'houzez-api')));
        }
    }
} 