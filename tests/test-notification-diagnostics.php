<?php
/**
 * Notification System Diagnostics
 * 
 * This file helps diagnose issues with the notification custom post type visibility
 * 
 * Usage: Add ?page=houzez-notification-diagnostics to your admin URL
 */

// Menu registration is now handled in class-houzez-api-settings.php

function houzez_notification_diagnostics_page() {
    ?>
    <div class="wrap">
        <h1>Houzez Notification System Diagnostics</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
            <h2>1. Post Type Registration Check</h2>
            <?php
            $post_type_exists = post_type_exists('houzez_notification');
            if ($post_type_exists) {
                echo '<p style="color: green;">✓ Post type "houzez_notification" is registered</p>';
                
                $post_type_object = get_post_type_object('houzez_notification');
                echo '<h3>Post Type Settings:</h3>';
                echo '<ul>';
                echo '<li>Public: ' . ($post_type_object->public ? 'Yes' : 'No') . '</li>';
                echo '<li>Show UI: ' . ($post_type_object->show_ui ? 'Yes' : 'No') . '</li>';
                echo '<li>Show in Menu: ' . ($post_type_object->show_in_menu ?: 'Not set') . '</li>';
                echo '<li>Menu Position: ' . ($post_type_object->menu_position ?: 'Not set') . '</li>';
                echo '<li>Menu Icon: ' . ($post_type_object->menu_icon ?: 'Not set') . '</li>';
                echo '</ul>';
            } else {
                echo '<p style="color: red;">✗ Post type "houzez_notification" is NOT registered</p>';
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
            <h2>2. Endpoint Class Check</h2>
            <?php
            if (class_exists('Houzez_API_Endpoint_Notifications')) {
                echo '<p style="color: green;">✓ Houzez_API_Endpoint_Notifications class exists</p>';
                
                // Check if init method was called
                $endpoint = new Houzez_API_Endpoint_Notifications();
                echo '<p>Creating new instance of the endpoint class...</p>';
            } else {
                echo '<p style="color: red;">✗ Houzez_API_Endpoint_Notifications class NOT found</p>';
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
            <h2>3. Admin Menu Check</h2>
            <?php
            global $menu, $submenu;
            
            $found_parent = false;
            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && $menu_item[2] === 'houzez-api-settings') {
                    $found_parent = true;
                    echo '<p style="color: green;">✓ Found parent menu "' . $menu_item[0] . '" with slug "houzez-api-settings"</p>';
                    
                    if (isset($submenu['houzez-api-settings'])) {
                        echo '<h4>Submenus:</h4>';
                        echo '<ul>';
                        foreach ($submenu['houzez-api-settings'] as $submenu_item) {
                            echo '<li>' . $submenu_item[0] . ' (' . $submenu_item[2] . ')</li>';
                        }
                        echo '</ul>';
                    }
                    break;
                }
            }
            
            if (!$found_parent) {
                echo '<p style="color: red;">✗ Parent menu "houzez-api-settings" not found</p>';
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
            <h2>4. Hook Registration Check</h2>
            <?php
            global $wp_filter;
            $hook_registered = isset($wp_filter['houzez_send_notification']) && !empty($wp_filter['houzez_send_notification']);
            ?>
            <p><strong>Hook:</strong> houzez_send_notification</p>
            <p><strong>Status:</strong> 
                <?php if ($hook_registered): ?>
                    <span style="color: green;">✓ Registered</span>
                <?php else: ?>
                    <span style="color: red;">✗ Not Registered</span>
                <?php endif; ?>
            </p>
            
            <?php if ($hook_registered && isset($wp_filter['houzez_send_notification'])): ?>
                <h3>Registered Callbacks:</h3>
                <ul>
                <?php
                foreach ($wp_filter['houzez_send_notification'] as $priority => $callbacks) {
                    echo '<li><strong>Priority ' . $priority . ':</strong><ul>';
                    foreach ($callbacks as $callback) {
                        if (is_array($callback['function'])) {
                            $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                            echo '<li>' . $class . '::' . $callback['function'][1] . '</li>';
                        } else {
                            echo '<li>' . $callback['function'] . '</li>';
                        }
                    }
                    echo '</ul></li>';
                }
                ?>
                </ul>
            <?php endif; ?>
            
            <h3>Test Hook Trigger:</h3>
            <form method="post" style="margin-top: 10px;">
                <?php wp_nonce_field('test_hook_trigger', 'hook_nonce'); ?>
                <input type="hidden" name="action" value="test_hook_trigger">
                <button type="submit" class="button">Test houzez_send_notification Hook</button>
            </form>
            
            <?php
            if (isset($_POST['action']) && $_POST['action'] === 'test_hook_trigger' && wp_verify_nonce($_POST['hook_nonce'], 'test_hook_trigger')) {
                $test_args = [
                    'to' => get_option('admin_email'),
                    'title' => 'Test Hook Notification',
                    'message' => 'This is a test notification triggered by the diagnostics page.',
                    'type' => 'property_agent_contact',
                    'sender_name' => 'Test User',
                    'sender_email' => 'test@example.com',
                    'property_title' => 'Test Property',
                    'property_id' => '12345'
                ];
                
                echo '<div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border: 1px solid #0073aa;">';
                echo '<strong>Triggering hook with args:</strong><pre>' . print_r($test_args, true) . '</pre>';
                
                // Check debug log before triggering
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $log_file = WP_CONTENT_DIR . '/debug.log';
                    $before_size = file_exists($log_file) ? filesize($log_file) : 0;
                }
                
                do_action('houzez_send_notification', $test_args);
                
                echo '<p style="color: green;">✓ Hook triggered!</p>';
                
                // Check if notification was created
                $recent_notification = get_posts([
                    'post_type' => 'houzez_notification',
                    'posts_per_page' => 1,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'meta_query' => [
                        [
                            'key' => 'user_email',
                            'value' => get_option('admin_email'),
                            'compare' => '='
                        ]
                    ]
                ]);
                
                if (!empty($recent_notification) && strtotime($recent_notification[0]->post_date) > (time() - 10)) {
                    echo '<p style="color: green;">✓ Notification was created successfully!</p>';
                    echo '<p><a href="' . admin_url('post.php?post=' . $recent_notification[0]->ID . '&action=edit') . '" target="_blank">View Created Notification</a></p>';
                } else {
                    echo '<p style="color: orange;">⚠ Notification may not have been created. Check debug logs.</p>';
                }
                
                // Show debug log output if available
                if (defined('WP_DEBUG') && WP_DEBUG && isset($log_file) && file_exists($log_file)) {
                    $after_size = filesize($log_file);
                    if ($after_size > $before_size) {
                        echo '<h4>Debug Log Output:</h4>';
                        echo '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">';
                        $fp = fopen($log_file, 'r');
                        fseek($fp, $before_size);
                        echo htmlspecialchars(fread($fp, $after_size - $before_size));
                        fclose($fp);
                        echo '</pre>';
                    }
                }
                
                echo '</div>';
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
            <h2>5. Current User Capabilities</h2>
            <?php
            $current_user = wp_get_current_user();
            echo '<p>Current user: ' . $current_user->user_login . '</p>';
            echo '<p>Can manage options: ' . (current_user_can('manage_options') ? 'Yes' : 'No') . '</p>';
            echo '<p>Can edit posts: ' . (current_user_can('edit_posts') ? 'Yes' : 'No') . '</p>';
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
            <h2>6. Notification Count</h2>
            <?php
            $count = wp_count_posts('houzez_notification');
            if ($count) {
                echo '<ul>';
                foreach ($count as $status => $num) {
                    if ($num > 0) {
                        echo '<li>' . ucfirst($status) . ': ' . $num . '</li>';
                    }
                }
                echo '</ul>';
            } else {
                echo '<p>Unable to count notifications</p>';
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
            <h2>7. Quick Actions</h2>
            <p><strong>Try these steps to fix visibility issues:</strong></p>
            <ol>
                <li>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('flush_rewrite_rules', 'flush_nonce'); ?>
                        <input type="hidden" name="action" value="flush_rewrite_rules">
                        <button type="submit" class="button">Flush Rewrite Rules</button>
                    </form>
                    <?php
                    if (isset($_POST['action']) && $_POST['action'] === 'flush_rewrite_rules' && wp_verify_nonce($_POST['flush_nonce'], 'flush_rewrite_rules')) {
                        flush_rewrite_rules();
                        echo ' <span style="color: green;">✓ Done!</span>';
                    }
                    ?>
                </li>
                <li>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('re_register_post_type', 're_register_nonce'); ?>
                        <input type="hidden" name="action" value="re_register_post_type">
                        <button type="submit" class="button">Re-register Post Type</button>
                    </form>
                    <?php
                    if (isset($_POST['action']) && $_POST['action'] === 're_register_post_type' && wp_verify_nonce($_POST['re_register_nonce'], 're_register_post_type')) {
                        if (class_exists('Houzez_API_Endpoint_Notifications')) {
                            $endpoint = new Houzez_API_Endpoint_Notifications();
                            $endpoint->register_notification_post_type();
                            echo ' <span style="color: green;">✓ Done!</span>';
                        }
                    }
                    ?>
                </li>
                <li>Direct link to notifications: <a href="<?php echo admin_url('edit.php?post_type=houzez_notification'); ?>" class="button">View Notifications</a></li>
                <li>Direct link to add new: <a href="<?php echo admin_url('post-new.php?post_type=houzez_notification'); ?>" class="button">Add New Notification</a></li>
            </ol>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
            <h2>8. Plugin Status</h2>
            <?php
            $plugin_file = 'houzez-api/houzez-api.php';
            if (is_plugin_active($plugin_file)) {
                echo '<p style="color: green;">✓ Houzez API plugin is active</p>';
            } else {
                echo '<p style="color: red;">✗ Houzez API plugin is NOT active</p>';
            }
            
            echo '<p>Plugin Version: ' . (defined('HOUZEZ_API_VERSION') ? HOUZEZ_API_VERSION : 'Not defined') . '</p>';
            echo '<p>Plugin Directory: ' . (defined('HOUZEZ_API_PLUGIN_DIR') ? HOUZEZ_API_PLUGIN_DIR : 'Not defined') . '</p>';
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
            <h2>9. Run Notification Tests</h2>
            <p>Create test notifications and check API endpoints functionality.</p>
            
            <?php
            // Handle test execution
            if (isset($_POST['action']) && $_POST['action'] === 'run_notification_tests' && wp_verify_nonce($_POST['test_nonce'], 'run_notification_tests')) {
                // Load the test file if it exists
                $test_file = HOUZEZ_API_PLUGIN_DIR . 'tests/test-notifications.php';
                if (file_exists($test_file)) {
                    echo '<div style="background: #f9f9f9; padding: 15px; margin: 10px 0; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;">';
                    
                    // Include test functions
                    require_once $test_file;
                    
                    // Run specific tests based on selection
                    $test_type = isset($_POST['test_type']) ? $_POST['test_type'] : 'all';
                    
                    switch ($test_type) {
                        case 'create_notifications':
                            test_houzez_notification_creation();
                            break;
                        case 'api_endpoints':
                            test_notification_api_endpoints();
                            break;
                        case 'direct_creation':
                            if (current_user_can('manage_options')) {
                                test_direct_notification_creation();
                            } else {
                                echo '<p style="color: red;">Admin privileges required for direct creation test.</p>';
                            }
                            break;
                        case 'stats':
                            display_notification_stats();
                            break;
                        case 'all':
                        default:
                            test_houzez_notification_creation();
                            echo '<hr>';
                            test_notification_api_endpoints();
                            if (current_user_can('manage_options')) {
                                echo '<hr>';
                                test_direct_notification_creation();
                            }
                            echo '<hr>';
                            display_notification_stats();
                            break;
                    }
                    
                    echo '</div>';
                } else {
                    echo '<p style="color: red;">Test file not found at: ' . $test_file . '</p>';
                }
            }
            ?>
            
            <form method="post" style="margin-top: 10px;">
                <?php wp_nonce_field('run_notification_tests', 'test_nonce'); ?>
                <input type="hidden" name="action" value="run_notification_tests">
                
                <label for="test_type"><strong>Select Test Type:</strong></label><br>
                <select name="test_type" id="test_type" style="margin: 5px 0;">
                    <option value="all">All Tests</option>
                    <option value="create_notifications">Create Test Notifications</option>
                    <option value="api_endpoints">Show API Endpoints Info</option>
                    <option value="direct_creation">Direct Notification Creation (Admin Only)</option>
                    <option value="stats">Display Statistics</option>
                </select><br>
                
                <button type="submit" class="button button-primary" style="margin-top: 10px;">Run Selected Tests</button>
            </form>
            
            <div style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">
                <strong>Note:</strong> Running "Create Test Notifications" will create sample notifications for the admin user. These can be viewed in the Notifications list and used to test the API endpoints.
            </div>
            
            <?php
            // Handle clearing test notifications
            if (isset($_POST['action']) && $_POST['action'] === 'clear_test_notifications' && wp_verify_nonce($_POST['clear_nonce'], 'clear_test_notifications')) {
                $test_titles = [
                    'Test Message Notification',
                    'Price Drop Alert',
                    'New Property Inquiry',
                    'Payment Received',
                    'Listing Expired',
                    'Direct Test Notification'
                ];
                
                $deleted_count = 0;
                foreach ($test_titles as $title) {
                    $args = [
                        'post_type' => 'houzez_notification',
                        'post_title' => $title,
                        'posts_per_page' => -1,
                        'fields' => 'ids'
                    ];
                    $notifications = get_posts($args);
                    
                    foreach ($notifications as $notification_id) {
                        if (wp_delete_post($notification_id, true)) {
                            $deleted_count++;
                        }
                    }
                }
                
                if ($deleted_count > 0) {
                    echo '<div style="margin-top: 10px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724;">';
                    echo '<strong>Success:</strong> Deleted ' . $deleted_count . ' test notification(s).';
                    echo '</div>';
                }
            }
            ?>
            
            <form method="post" style="margin-top: 10px;">
                <?php wp_nonce_field('clear_test_notifications', 'clear_nonce'); ?>
                <input type="hidden" name="action" value="clear_test_notifications">
                <button type="submit" class="button" onclick="return confirm('Are you sure you want to delete all test notifications?');">Clear Test Notifications</button>
            </form>
        </div>
        
        <div style="background: #fffbcc; padding: 20px; margin: 20px 0; border: 1px solid #e6db55;">
            <h2>💡 If Notifications Still Don't Show:</h2>
            <ol>
                <li><strong>Deactivate and reactivate</strong> the Houzez API plugin</li>
                <li><strong>Clear any caching plugins</strong> (W3 Total Cache, WP Super Cache, etc.)</li>
                <li><strong>Check for JavaScript errors</strong> in the browser console</li>
                <li><strong>Try in a different browser</strong> or incognito mode</li>
                <li><strong>Check for plugin conflicts</strong> by temporarily disabling other plugins</li>
            </ol>
        </div>
        
        <div style="background: #e8f5e9; padding: 20px; margin: 20px 0; border: 1px solid #4caf50;">
            <h2>🔗 Quick Links</h2>
            <ul>
                <li><strong>Notifications List:</strong> <a href="<?php echo admin_url('edit.php?post_type=houzez_notification'); ?>" target="_blank">View All Notifications</a></li>
                <li><strong>Add New Notification:</strong> <a href="<?php echo admin_url('post-new.php?post_type=houzez_notification'); ?>" target="_blank">Create Manual Notification</a></li>
                <li><strong>API Documentation:</strong> View NOTIFICATION_API_ENDPOINTS.md in plugin directory</li>
                <li><strong>Test Page (Direct):</strong> <a href="<?php echo admin_url('admin.php?page=houzez-api-settings&run_notification_tests=1'); ?>" target="_blank">Run Tests in New Window</a></li>
            </ul>
        </div>
    </div>
    <?php
}

// Add this file to the plugin if running diagnostics
if (isset($_GET['houzez_diagnostics']) && $_GET['houzez_diagnostics'] === '1') {
    add_action('admin_init', function() {
        require_once __FILE__;
    });
}
?> 