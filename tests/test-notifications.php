<?php
/**
 * Test file for Houzez API Notifications
 * 
 * This file demonstrates how to test the notification system
 * and trigger test notifications.
 * 
 * Usage: Include this file in your WordPress environment and call the test functions.
 */

// Ensure this is only run in a test environment
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    die('This test file should only be run in debug mode.');
}

/**
 * Test creating notifications using the houzez_send_notification action
 */
function test_houzez_notification_creation() {
    echo "<h2>Testing Notification Creation</h2>";
    
    // Get a test user (you can change this to any user email)
    $test_user_email = get_option('admin_email');
    
    // Test 1: Simple message notification
    do_action('houzez_send_notification', [
        'to' => $test_user_email,
        'title' => 'Test Message Notification',
        'message' => 'This is a test message notification from the Houzez API test suite.',
        'type' => 'messages',
        'thread_id' => '123',
        'property_id' => '456',
        'sender_id' => '1'
    ]);
    echo "✓ Created message notification<br>";
    
    // Test 2: Property price drop notification
    do_action('houzez_send_notification', [
        'to' => $test_user_email,
        'title' => 'Price Drop Alert',
        'message' => 'Great news! The price for Villa Marina has been reduced by 15%',
        'type' => 'property_price_drop',
        'listing_id' => '789',
        'listing_title' => 'Villa Marina',
        'old_price' => '500000',
        'new_price' => '425000',
        'price_reduction' => '15'
    ]);
    echo "✓ Created price drop notification<br>";
    
    // Test 3: Inquiry notification
    do_action('houzez_send_notification', [
        'to' => $test_user_email,
        'title' => 'New Property Inquiry',
        'message' => 'You have received a new inquiry for Modern Apartment',
        'type' => 'inquiry_received',
        'listing_id' => '101',
        'listing_title' => 'Modern Apartment',
        'inquirer_name' => 'John Doe',
        'inquirer_email' => 'john@example.com'
    ]);
    echo "✓ Created inquiry notification<br>";
    
    // Test 4: Payment received notification (high priority)
    do_action('houzez_send_notification', [
        'to' => $test_user_email,
        'title' => 'Payment Received',
        'message' => 'Payment of $99 has been received for your featured listing',
        'type' => 'payment_received',
        'amount' => '99',
        'currency' => 'USD',
        'invoice_id' => '12345'
    ]);
    echo "✓ Created payment notification<br>";
    
    // Test 5: Listing expired notification (urgent)
    do_action('houzez_send_notification', [
        'to' => $test_user_email,
        'title' => 'Listing Expired',
        'message' => 'Your listing "Luxury Condo" has expired and is no longer visible',
        'type' => 'listing_expired',
        'listing_id' => '202',
        'listing_title' => 'Luxury Condo'
    ]);
    echo "✓ Created listing expired notification<br>";
    
    echo "<p><strong>All test notifications created successfully!</strong></p>";
}

/**
 * Test the notification API endpoints
 */
function test_notification_api_endpoints() {
    echo "<h2>Testing Notification API Endpoints</h2>";
    
    // Get current user
    $current_user = wp_get_current_user();
    if (!$current_user->ID) {
        echo "Error: You must be logged in to test API endpoints<br>";
        return;
    }
    
    // Display test information
    echo "<h3>Test Information:</h3>";
    echo "Current User: " . $current_user->display_name . " (" . $current_user->user_email . ")<br>";
    echo "API Base URL: " . rest_url('houzez-api/v1/notifications') . "<br>";
    
    // Test endpoints
    $endpoints = [
        'GET /notifications' => 'Get all notifications',
        'GET /notifications/unread-count' => 'Get unread count',
        'GET /notifications/preferences' => 'Get notification preferences',
        'GET /notifications/123' => 'Get single notification (replace 123 with actual ID)',
        'POST /notifications/123/read' => 'Mark notification as read',
        'POST /notifications/mark-read' => 'Mark multiple as read',
        'POST /notifications/mark-all-read' => 'Mark all as read',
        'DELETE /notifications/123' => 'Delete notification',
        'POST /notifications/preferences' => 'Update preferences',
        'POST /notifications/subscribe' => 'Subscribe to push notifications',
        'POST /notifications/unsubscribe' => 'Unsubscribe from push notifications'
    ];
    
    echo "<h3>Available Endpoints:</h3>";
    echo "<ul>";
    foreach ($endpoints as $endpoint => $description) {
        echo "<li><code>$endpoint</code> - $description</li>";
    }
    echo "</ul>";
    
    // Example cURL commands
    echo "<h3>Example cURL Commands:</h3>";
    echo "<pre>";
    echo "# Get all notifications
curl -X GET '" . rest_url('houzez-api/v1/notifications') . "' \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

# Get unread count
curl -X GET '" . rest_url('houzez-api/v1/notifications/unread-count') . "' \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

# Mark notification as read
curl -X POST '" . rest_url('houzez-api/v1/notifications/123/read') . "' \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

# Update preferences
curl -X POST '" . rest_url('houzez-api/v1/notifications/preferences') . "' \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\
  -H 'Content-Type: application/json' \\
  -d '{
    \"push_enabled\": true,
    \"email_enabled\": false,
    \"disabled_types\": [\"marketing_promotion\"]
  }'
";
    echo "</pre>";
}

/**
 * Test direct notification creation (admin only)
 */
function test_direct_notification_creation() {
    if (!current_user_can('manage_options')) {
        echo "Error: You must be an admin to test direct notification creation<br>";
        return;
    }
    
    echo "<h2>Testing Direct Notification Creation</h2>";
    
    $endpoint = new Houzez_API_Endpoint_Notifications();
    
    // Create a test notification directly
    $user = get_user_by('email', get_option('admin_email'));
    if ($user) {
        $notification_id = $endpoint->create_notification_post(
            $user->ID,
            'Direct Test Notification',
            'This notification was created directly using the endpoint class',
            'system_update',
            'medium',
            ['test_data' => 'test_value']
        );
        
        if ($notification_id) {
            echo "✓ Successfully created notification with ID: $notification_id<br>";
            echo "View in admin: " . admin_url('edit.php?post_type=houzez_notification') . "<br>";
        } else {
            echo "✗ Failed to create notification<br>";
        }
    }
}

/**
 * Display notification statistics
 */
function display_notification_stats() {
    echo "<h2>Notification Statistics</h2>";
    
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        echo "Error: You must be logged in to view statistics<br>";
        return;
    }
    
    // Total notifications
    $total_args = [
        'post_type' => 'houzez_notification',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'user_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ]
    ];
    $total_query = new WP_Query($total_args);
    
    // Unread notifications
    $unread_args = $total_args;
    $unread_args['meta_query'][] = [
        'key' => 'is_read',
        'value' => '0',
        'compare' => '='
    ];
    $unread_query = new WP_Query($unread_args);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th style='padding: 5px;'>Metric</th><th style='padding: 5px;'>Count</th></tr>";
    echo "<tr><td style='padding: 5px;'>Total Notifications</td><td style='padding: 5px;'>{$total_query->found_posts}</td></tr>";
    echo "<tr><td style='padding: 5px;'>Unread Notifications</td><td style='padding: 5px;'>{$unread_query->found_posts}</td></tr>";
    echo "<tr><td style='padding: 5px;'>Read Notifications</td><td style='padding: 5px;'>" . ($total_query->found_posts - $unread_query->found_posts) . "</td></tr>";
    echo "</table>";
    
    // Recent notifications
    $recent_args = [
        'post_type' => 'houzez_notification',
        'posts_per_page' => 5,
        'meta_query' => [
            [
                'key' => 'user_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ],
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    $recent_query = new WP_Query($recent_args);
    
    if ($recent_query->have_posts()) {
        echo "<h3>Recent Notifications:</h3>";
        echo "<ul>";
        while ($recent_query->have_posts()) {
            $recent_query->the_post();
            $is_read = get_post_meta(get_the_ID(), 'is_read', true);
            $type = get_post_meta(get_the_ID(), 'notification_type', true);
            $priority = get_post_meta(get_the_ID(), 'priority', true);
            
            echo "<li>";
            echo "<strong>" . get_the_title() . "</strong><br>";
            echo "Type: $type | Priority: $priority | Status: " . ($is_read ? 'Read' : '<strong>Unread</strong>') . "<br>";
            echo "Created: " . get_the_date('Y-m-d H:i:s') . "<br>";
            echo "</li>";
        }
        echo "</ul>";
        wp_reset_postdata();
    }
}

/**
 * Run all tests
 */
function run_all_notification_tests() {
    ?>
    <div style="padding: 20px; font-family: Arial, sans-serif;">
        <h1>Houzez API Notification System Tests</h1>
        
        <?php
        // Run tests
        test_houzez_notification_creation();
        test_notification_api_endpoints();
        
        if (current_user_can('manage_options')) {
            test_direct_notification_creation();
        }
        
        display_notification_stats();
        ?>
        
        <div style="margin-top: 20px; padding: 10px; background: #f0f0f0; border: 1px solid #ddd;">
            <h3>Additional Information:</h3>
            <ul>
                <li>View all notifications in admin: <a href="<?php echo admin_url('edit.php?post_type=houzez_notification'); ?>">Notifications Admin</a></li>
                <li>API Documentation: Check NOTIFICATION_API_ENDPOINTS.md in the plugin directory</li>
                <li>Post Type: houzez_notification</li>
                <li>Action Hook: houzez_send_notification</li>
            </ul>
        </div>
    </div>
    <?php
}

// Run tests if this file is accessed directly
if (isset($_GET['run_notification_tests']) && $_GET['run_notification_tests'] === '1') {
    run_all_notification_tests();
}
?> 