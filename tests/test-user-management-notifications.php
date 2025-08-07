<?php
/**
 * Test User Management Notifications
 * 
 * This file tests that the notification system correctly captures
 * user management actions (approve, decline, suspend) from the
 * Houzez User Approval system.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test user management notification creation
 */
function test_user_management_notifications() {
    echo "<h2>Testing User Management Notifications</h2>";
    
    // Check if the notification system is active
    if (!class_exists('Houzez_API_Endpoint_Notifications')) {
        echo "<p style='color: red;'>Error: Notification system is not active!</p>";
        return;
    }
    
    // Check if Houzez_User_Approval class exists
    if (!class_exists('Houzez_User_Approval')) {
        echo "<p style='color: red;'>Error: Houzez_User_Approval class not found!</p>";
        echo "<p>Make sure the houzez-login-register plugin is active and user approval is enabled.</p>";
        return;
    }
    
    // Get a test user (non-admin)
    $test_users = get_users([
        'role__not_in' => ['administrator'],
        'number' => 1
    ]);
    
    if (empty($test_users)) {
        echo "<p style='color: red;'>Error: No non-admin users found for testing!</p>";
        echo "<p>Please create a test user first.</p>";
        return;
    }
    
    $test_user = $test_users[0];
    $test_user_id = $test_user->ID;
    
    echo "<h3>Test User Details:</h3>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> " . esc_html($test_user->user_login) . "</li>";
    echo "<li><strong>Email:</strong> " . esc_html($test_user->user_email) . "</li>";
    echo "<li><strong>User ID:</strong> " . esc_html($test_user_id) . "</li>";
    echo "</ul>";
    
    echo "<h3>1. Testing User Approval Notification</h3>";
    
    // Test user approved notification
    $notificationArgs = array(
        'title'   => 'Your account has been approved',
        'message' => sprintf(
            "Hello %s,\n\nGood news! Your account on %s has just been approved. You can now log in here:\n%s\n\nThank you!",
            $test_user->display_name,
            get_bloginfo('name'),
            wp_login_url()
        ),
        'type'    => 'user_approved',
        'to'      => $test_user->user_email,
        'user_id' => $test_user_id,
        'user_login' => $test_user->user_login,
        'user_email' => $test_user->user_email,
        'admin_user' => wp_get_current_user()->user_login,
    );
    
    echo "<pre>";
    echo "Triggering user_approved notification...\n";
    echo "Args: " . print_r($notificationArgs, true);
    echo "</pre>";
    
    do_action('houzez_send_notification', $notificationArgs);
    
    echo "<p style='color: green;'>✓ User approval notification triggered</p>";
    
    echo "<h3>2. Testing User Decline Notification</h3>";
    
    // Test user declined notification
    $notificationArgs = array(
        'title'   => 'Your account registration has been declined',
        'message' => sprintf(
            "Hello %s,\n\nWe're sorry to let you know that your account registration on %s has been declined. If you believe this is an error, please contact us.\n\nRegards,",
            $test_user->display_name,
            get_bloginfo('name')
        ),
        'type'    => 'user_declined',
        'to'      => $test_user->user_email,
        'user_id' => $test_user_id,
        'user_login' => $test_user->user_login,
        'user_email' => $test_user->user_email,
        'admin_user' => wp_get_current_user()->user_login,
    );
    
    echo "<pre>";
    echo "Triggering user_declined notification...\n";
    echo "Args: " . print_r($notificationArgs, true);
    echo "</pre>";
    
    do_action('houzez_send_notification', $notificationArgs);
    
    echo "<p style='color: green;'>✓ User decline notification triggered</p>";
    
    echo "<h3>3. Testing User Suspend Notification</h3>";
    
    // Test user suspended notification
    $notificationArgs = array(
        'title'   => 'Your account has been suspended',
        'message' => sprintf(
            "Hello %s,\n\nWe're sorry to let you know that your account on %s has been suspended. If you believe this is an error, please contact us.\n\nRegards,",
            $test_user->display_name,
            get_bloginfo('name')
        ),
        'type'    => 'user_suspended',
        'to'      => $test_user->user_email,
        'user_id' => $test_user_id,
        'user_login' => $test_user->user_login,
        'user_email' => $test_user->user_email,
        'admin_user' => wp_get_current_user()->user_login,
    );
    
    echo "<pre>";
    echo "Triggering user_suspended notification...\n";
    echo "Args: " . print_r($notificationArgs, true);
    echo "</pre>";
    
    do_action('houzez_send_notification', $notificationArgs);
    
    echo "<p style='color: green;'>✓ User suspend notification triggered</p>";
    
    echo "<h3>4. Checking Created Notifications</h3>";
    
    // Query recent notifications
    $args = array(
        'post_type' => 'houzez_notification',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'user_email',
                'value' => $test_user->user_email,
                'compare' => '='
            ),
            array(
                'key' => 'notification_type',
                'value' => array('user_approved', 'user_declined', 'user_suspended'),
                'compare' => 'IN'
            )
        )
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        echo "<p>Found " . $query->found_posts . " user management notifications for this test user:</p>";
        echo "<ul>";
        while ($query->have_posts()) {
            $query->the_post();
            $notification_id = get_the_ID();
            $type = get_post_meta($notification_id, 'notification_type', true);
            $priority = get_post_meta($notification_id, 'priority', true);
            $created = get_the_date('Y-m-d H:i:s');
            
            echo "<li>";
            echo "<strong>Type:</strong> " . esc_html($type) . " | ";
            echo "<strong>Priority:</strong> " . esc_html($priority) . " | ";
            echo "<strong>Created:</strong> " . esc_html($created);
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No user management notifications found for this test user.</p>";
    }
    
    wp_reset_postdata();
}

// Add test to admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        'houzez-api-settings',
        'Test User Management Notifications',
        'Test User Management',
        'manage_options',
        'test-user-management',
        function() {
            ?>
            <div class="wrap">
                <h1>User Management Notification Tests</h1>
                <?php test_user_management_notifications(); ?>
                
                <hr>
                
                <h2>Live Testing Instructions</h2>
                <p>To test the actual user management notifications:</p>
                
                <h3>1. From Users List Page:</h3>
                <ol>
                    <li>Go to <a href="<?php echo admin_url('users.php'); ?>">Users</a></li>
                    <li>Hover over a non-admin user</li>
                    <li>You should see action links: <strong>Approve</strong>, <strong>Decline</strong>, <strong>Suspend</strong></li>
                    <li>Click any action to trigger the notification</li>
                </ol>
                
                <h3>2. From Bulk Actions:</h3>
                <ol>
                    <li>Go to <a href="<?php echo admin_url('users.php'); ?>">Users</a></li>
                    <li>Select multiple users using checkboxes</li>
                    <li>From "Bulk actions" dropdown, select: <strong>Approve Users</strong>, <strong>Decline Users</strong>, or <strong>Suspend Users</strong></li>
                    <li>Click "Apply" to trigger notifications for all selected users</li>
                </ol>
                
                <h3>3. Check Notifications:</h3>
                <ul>
                    <li>Admin Panel: <a href="<?php echo admin_url('edit.php?post_type=houzez_notification'); ?>">View All Notifications</a></li>
                    <li>API Endpoint: <code>GET /wp-json/houzez-api/v1/notifications</code></li>
                </ul>
                
                <h2>Notification Types Added</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Label</th>
                            <th>Priority</th>
                            <th>Category</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>user_approved</code></td>
                            <td>Account Approved</td>
                            <td><span style="color: #fd7e14;">High</span></td>
                            <td>user_management</td>
                            <td>Sent when admin approves a user account</td>
                        </tr>
                        <tr>
                            <td><code>user_declined</code></td>
                            <td>Account Declined</td>
                            <td><span style="color: #fd7e14;">High</span></td>
                            <td>user_management</td>
                            <td>Sent when admin declines a user registration</td>
                        </tr>
                        <tr>
                            <td><code>user_suspended</code></td>
                            <td>Account Suspended</td>
                            <td><span style="color: #fd7e14;">High</span></td>
                            <td>user_management</td>
                            <td>Sent when admin suspends a user account</td>
                        </tr>
                    </tbody>
                </table>
                
                <h2>Note for Mobile Apps</h2>
                <p>All user management notifications:</p>
                <ul>
                    <li>Navigate to the <strong>profile</strong> screen</li>
                    <li>Include <code>user_id</code> in the action data</li>
                    <li>Have high priority to ensure users are promptly notified of account status changes</li>
                </ul>
            </div>
            <?php
        }
    );
});

// Add admin notice
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'test-user-management') {
        ?>
        <div class="notice notice-info">
            <p><strong>User Management Notifications Active:</strong> The system will now create notifications for user approval, decline, and suspend actions.</p>
        </div>
        <?php
    }
}); 