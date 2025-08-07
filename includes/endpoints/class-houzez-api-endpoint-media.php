<?php

/**
 * Media Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Endpoint_Media extends Houzez_API_Base
{
    /**
     * Upload media
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function upload_media($request)
    {
        $files = $request->get_file_params();

        if (empty($files['file'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('No file was uploaded', 'houzez-api')
            ], 400);
        }

        $file = $files['file'];

        $allowed_types = array(
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip'
        );

        if (!in_array($file['type'], $allowed_types)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid file type. Only JPG, PNG, GIF, PDF, DOCX and ZIP are allowed', 'houzez-api')
            ], 400);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('file', 0);

        if (is_wp_error($attachment_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $attachment_id->get_error_message()
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => self::format_media($attachment_id),
            'message' => esc_html__('File uploaded successfully', 'houzez-api')
        ], 201);
    }

    /**
     * Get media
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_media($request)
    {
        $attachment_id = absint($request['id']);

        if (!$attachment_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Invalid media ID', 'houzez-api')
            ], 400);
        }

        $attachment = get_post($attachment_id);

        if (!$attachment || $attachment->post_type !== 'attachment') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Media not found', 'houzez-api')
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => self::format_media($attachment_id)
        ], 200);
    }

    /**
     * Update media
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function update_media($request)
    {
        $attachment_id = absint($request['id']);
        $params = $request->get_params();

        $attachment = get_post($attachment_id);

        if (!$attachment || $attachment->post_type !== 'attachment') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Media not found', 'houzez-api')
            ], 404);
        }

        $attachment_data = array(
            'ID' => $attachment_id
        );

        if (isset($params['title'])) {
            $attachment_data['post_title'] = sanitize_text_field($params['title']);
        }

        if (isset($params['caption'])) {
            $attachment_data['post_excerpt'] = sanitize_text_field($params['caption']);
        }

        if (isset($params['description'])) {
            $attachment_data['post_content'] = wp_kses_post($params['description']);
        }

        if (isset($params['alt'])) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($params['alt']));
        }

        $updated = wp_update_post($attachment_data);

        if (is_wp_error($updated)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $updated->get_error_message()
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => self::format_media($attachment_id),
            'message' => esc_html__('Media updated successfully', 'houzez-api')
        ], 200);
    }

    /**
     * Delete media
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function delete_media($request)
    {
        $attachment_id = absint($request['id']);

        $attachment = get_post($attachment_id);

        if (!$attachment || $attachment->post_type !== 'attachment') {
            return new WP_REST_Response([
                'success' => false,
                'message' => esc_html__('Media not found', 'houzez-api')
            ], 404);
        }

        $result = wp_delete_attachment($attachment_id, true);

        if (!$result) {
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
     * Format media data for API response
     *
     * @param int $attachment_id
     * @return array
     */
    private static function format_media($attachment_id)
    {
        $attachment = get_post($attachment_id);
        $metadata = wp_get_attachment_metadata($attachment_id);
        $attachment_url = wp_get_attachment_url($attachment_id);

        $data = array(
            'id' => $attachment_id,
            'title' => $attachment->post_title,
            'caption' => $attachment->post_excerpt,
            'description' => $attachment->post_content,
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'mime_type' => $attachment->post_mime_type,
            'date' => $attachment->post_date,
            'modified' => $attachment->post_modified,
            'url' => $attachment_url,
            'sizes' => array()
        );

        // Add image sizes if this is an image
        if (wp_attachment_is_image($attachment_id)) {
            $available_sizes = array(
                'thumbnail',
                'medium',
                'large',
                'full'
            );

            foreach ($available_sizes as $size) {
                $image = wp_get_attachment_image_src($attachment_id, $size);
                if ($image) {
                    $data['sizes'][$size] = array(
                        'url' => $image[0],
                        'width' => $image[1],
                        'height' => $image[2]
                    );
                }
            }

            // Add full size
            $full_image = wp_get_attachment_image_src($attachment_id, 'full');
            if ($full_image) {
                $data['sizes']['full'] = array(
                    'url' => $full_image[0],
                    'width' => $full_image[1],
                    'height' => $full_image[2]
                );
            }
        }

        return $data;
    }

    /**
     * Initialize the class
     */
    public function init()
    {
        // No initialization needed for static methods
    }
}
