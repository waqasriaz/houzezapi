<?php
/**
 * Test User Registration Notifications
 * 
 * This file tests that the notification system correctly captures
 * user registration events from houzez_wp_new_user_notification
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test user registration notification creation
 */
function test_user_registration_notifications() {
    echo "<h2>Testing User Registration Notifications</h2>";
    
    // Check if the notification system is active
    if (!class_exists('Houzez_API_Endpoint_Notifications')) {
        echo "<p style='color: red;'>Error: Notification system is not active!</p>";
        return;
    }
    
    // Check if houzez_register_email_type function exists
    if (!function_exists('houzez_register_email_type')) {
        echo "<p style='color: red;'>Error: houzez_register_email_type function not found!</p>";
        return;
    }
    
    echo "<h3>1. Testing Admin Notification (New User)</h3>";
    
    // Test admin notification for new user
    $admin_args = array(
        'user_login_register' => 'testuser123',
        'user_email_register' => 'testuser@example.com',
        'user_phone_register' => '+1234567890'
    );
    
    echo "<pre>";
    echo "Triggering admin_new_user_register notification...\n";
    echo "Args: " . print_r($admin_args, true);
    echo "</pre>";
    
    // This will trigger the notification hook we just added
    houzez_register_email_type(get_option('admin_email'), 'admin_new_user_register', $admin_args);
    
    echo "<p style='color: green;'>✓ Admin notification triggered</p>";
    
    echo "<h3>2. Testing User Welcome Notification</h3>";
    
    // Test user notification
    $user_args = array(
        'user_login_register' => 'testuser123',
        'user_email_register' => 'testuser@example.com',
        'user_pass_register' => 'TempPass123!',
        'user_phone_register' => '+1234567890'
    );
    
    echo "<pre>";
    echo "Triggering new_user_register notification...\n";
    echo "Args: " . print_r($user_args, true);
    echo "</pre>";
    
    houzez_register_email_type('testuser@example.com', 'new_user_register', $user_args);
    
    echo "<p style='color: green;'>✓ User welcome notification triggered</p>";
    
    echo "<h3>3. Testing Admin Approval Required Notification</h3>";
    
    // Test admin approval notification
    $approval_args = array(
        'user_login_register' => 'pendinguser',
        'user_email_register' => 'pending@example.com',
        'user_phone_register' => '+0987654321',
        'admin_user_link' => '<a href="' . admin_url('users.php?s=pending@example.com') . '">View User</a>'
    );
    
    echo "<pre>";
    echo "Triggering admin_user_register_approval notification...\n";
    echo "Args: " . print_r($approval_args, true);
    echo "</pre>";
    
    houzez_register_email_type(get_option('admin_email'), 'admin_user_register_approval', $approval_args);
    
    echo "<p style='color: green;'>✓ Admin approval notification triggered</p>";
    
    echo "<h3>4. Checking Created Notifications</h3>";
    
    // Query recent notifications
    $args = array(
        'post_type' => 'houzez_notification',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'notification_type',
                'value' => 'admin_new_user_register',
                'compare' => '='
            ),
            array(
                'key' => 'notification_type',
                'value' => 'new_user_register',
                'compare' => '='
            ),
            array(
                'key' => 'notification_type',
                'value' => 'admin_user_register_approval',
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        echo "<p>Found " . $query->found_posts . " user registration notifications:</p>";
        echo "<ul>";
        while ($query->have_posts()) {
            $query->the_post();
            $notification_id = get_the_ID();
            $type = get_post_meta($notification_id, 'notification_type', true);
            $user_email = get_post_meta($notification_id, 'user_email', true);
            $created = get_the_date('Y-m-d H:i:s');
            
            echo "<li>";
            echo "<strong>Type:</strong> " . esc_html($type) . " | ";
            echo "<strong>To:</strong> " . esc_html($user_email) . " | ";
            echo "<strong>Created:</strong> " . esc_html($created);
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No user registration notifications found in the database.</p>";
    }
    
    wp_reset_postdata();
    
    echo "<hr>";
    echo "<p><strong>Note:</strong> The notifications should appear in the admin panel under Houzez API > Notifications</p>";
}

// Add test to admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        'houzez-api-settings',
        'Test User Registration Notifications',
        'Test User Notifications',
        'manage_options',
        'test-user-notifications',
        function() {
            ?>
            <div class="wrap">
                <h1>User Registration Notification Tests</h1>
                <?php test_user_registration_notifications(); ?>
                
                <h2>Integration Instructions</h2>
                <p>The notification hook has been added to <code>houzez_register_email_type()</code> function.</p>
                <p>Now whenever a user registers, the following notifications will be created:</p>
                <ul>
                    <li><strong>admin_new_user_register</strong> - Sent to admin when a new user registers</li>
                    <li><strong>admin_user_register_approval</strong> - Sent to admin when user approval is required</li>
                    <li><strong>new_user_register</strong> - Welcome notification sent to the new user</li>
                </ul>
                
                <h3>Testing Real Registration</h3>
                <p>To test with real user registration:</p>
                <ol>
                    <li>Go to your registration page</li>
                    <li>Register a new user</li>
                    <li>Check the Notifications admin panel to see the created notifications</li>
                    <li>Check the API endpoint: <code>GET /wp-json/houzez-api/v1/notifications</code></li>
                </ol>
            </div>
            <?php
        }
    );
});

// Add admin notice
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'test-user-notifications') {
        ?>
        <div class="notice notice-info">
            <p><strong>User Registration Notifications Active:</strong> The system will now create notifications for all user registrations.</p>
        </div>
        <?php
    }
}); 