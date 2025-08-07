<?php
class Houzez_API_Migration_20240321000002MigrateApiKeysFromOptions {
    
    /**
     * Run the migration
     */
    public function up() {
        global $wpdb;
        
        // Get existing API keys from options
        $old_keys = get_option('houzez_api_keys', array());
        
        if (empty($old_keys)) {
            return;
        }

        $table_name = $wpdb->prefix . 'houzez_api_keys';

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            foreach ($old_keys as $api_key => $data) {
                // Check if key already exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE api_key = %s",
                    $api_key
                ));

                if ($exists) {
                    continue;
                }

                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'user_id' => isset($data['user_id']) ? $data['user_id'] : get_current_user_id(),
                        'api_key' => $api_key,
                        'app_name' => isset($data['app_name']) ? $data['app_name'] : 'Migrated App',
                        'description' => isset($data['description']) ? $data['description'] : '',
                        'status' => isset($data['active']) && $data['active'] ? 'active' : 'inactive',
                        'last_used' => isset($data['last_used']) ? $data['last_used'] : null,
                        'usage_count' => isset($data['usage_count']) ? $data['usage_count'] : 0,
                        'expires_at' => isset($data['expires']) ? $data['expires'] : null,
                        'created_at' => isset($data['created']) ? $data['created'] : current_time('mysql')
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%s',
                        '%s'
                    )
                );

                if ($result === false) {
                    throw new Exception('Failed to insert API key: ' . $api_key);
                }
            }

            // Delete old option after successful migration
            delete_option('houzez_api_keys');

            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Houzez API Migration Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse the migration
     */
    public function down() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'houzez_api_keys';
        
        // Get all keys from the table
        $keys = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        
        if (empty($keys)) {
            return;
        }

        $old_format_keys = array();
        
        foreach ($keys as $key) {
            $old_format_keys[$key['api_key']] = array(
                'user_id' => $key['user_id'],
                'app_name' => $key['app_name'],
                'description' => $key['description'],
                'active' => $key['status'] === 'active',
                'last_used' => $key['last_used'],
                'usage_count' => $key['usage_count'],
                'expires' => $key['expires_at'],
                'created' => $key['created_at']
            );
        }

        // Save keys in old format
        update_option('houzez_api_keys', $old_format_keys);
    }
} 