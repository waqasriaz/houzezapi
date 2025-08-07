# OneSignal Desktop Notification Testing Guide

## 🎯 Overview

This guide shows you how to set up and test OneSignal push notifications for desktop browsers with the Houzez API notification system.

## 📋 Prerequisites

1. **WordPress Admin Access** - To configure OneSignal settings
2. **OneSignal Account** - [Sign up free](https://onesignal.com)
3. **Web Browser** - Chrome, Firefox, Safari, or Edge
4. **SSL Certificate** - OneSignal requires HTTPS for web push

---

## 🔧 Step 1: OneSignal Account Setup

### **1. Create OneSignal Account**

1. Go to [OneSignal.com](https://onesignal.com) and sign up
2. Click **"New App/Website"**
3. Select **"Web Push"** platform
4. Enter your app name (e.g., "Houzez Real Estate")

### **2. Configure Web Push Settings**

1. **Site Name**: Your website name (e.g., "Houzez Properties")
2. **Site URL**: Your website URL (must be HTTPS)
3. **Default Icon URL**: Your notification icon (512x512px recommended)
4. **Choose Integration**: Select **"Typical Site"**

### **3. Get Your Credentials**

After setup, note these values:

-   **App ID**: Found in Settings > Keys & IDs
-   **REST API Key**: Found in Settings > Keys & IDs

---

## ⚙️ Step 2: WordPress Configuration

### **1. Configure Houzez API Settings**

Go to **WordPress Admin** > **Houzez API** > **Settings** and configure:

```php
// These settings should be available in your admin panel:
Enable Push Notifications: ✅ Checked
Push Service: OneSignal
OneSignal App ID: [YOUR_APP_ID]
OneSignal API Key: [YOUR_REST_API_KEY]
```

### **2. Manual Configuration (if needed)**

Add to your `functions.php` or custom plugin:

```php
// Configure OneSignal settings
add_action('init', function() {
    update_option('houzez_api_push_enabled', '1');
    update_option('houzez_api_push_service', 'onesignal');
    update_option('houzez_api_onesignal_app_id', 'YOUR_APP_ID_HERE');
    update_option('houzez_api_onesignal_api_key', 'YOUR_REST_API_KEY_HERE');
});
```

---

## 🌐 Step 3: Website Integration

### **1. Add OneSignal SDK to Your Site**

Add this to your website's `<head>` section or in `functions.php`:

```php
// Add OneSignal SDK
add_action('wp_head', function() {
    ?>
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
    <script>
      window.OneSignal = window.OneSignal || [];
      OneSignal.push(function() {
        OneSignal.init({
          appId: "<?php echo get_option('houzez_api_onesignal_app_id'); ?>",
          safari_web_id: "web.onesignal.auto.XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX",
          notifyButton: {
            enable: true,
          },
          allowLocalhostAsSecureOrigin: true, // For testing on localhost
        });
      });
    </script>
    <?php
});
```

### **2. Add Subscription Management**

Add this JavaScript to handle user subscription:

```html
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Check if OneSignal is loaded
        if (typeof OneSignal !== 'undefined') {
            OneSignal.push(function () {
                // Check if user is subscribed
                OneSignal.isPushNotificationsEnabled(function (isEnabled) {
                    if (isEnabled) {
                        console.log('✅ Push notifications are enabled!');

                        // Get user ID (Player ID)
                        OneSignal.getUserId(function (userId) {
                            console.log('OneSignal User ID:', userId);

                            // Register with Houzez API
                            if (
                                userId &&
                                typeof houzez_user_token !== 'undefined'
                            ) {
                                registerWithHouzezAPI(userId);
                            }
                        });
                    } else {
                        console.log('❌ Push notifications are NOT enabled');
                    }
                });

                // Listen for subscription changes
                OneSignal.on('subscriptionChange', function (isSubscribed) {
                    console.log('Subscription changed:', isSubscribed);

                    if (isSubscribed) {
                        OneSignal.getUserId(function (userId) {
                            console.log('New OneSignal User ID:', userId);
                            registerWithHouzezAPI(userId);
                        });
                    }
                });
            });
        }
    });

    // Register device with Houzez API
    function registerWithHouzezAPI(playerId) {
        if (!playerId) return;

        // Generate a unique device ID for this browser
        let deviceId = localStorage.getItem('houzez_device_id');
        if (!deviceId) {
            deviceId =
                'web_' +
                Date.now() +
                '_' +
                Math.random().toString(36).substr(2, 9);
            localStorage.setItem('houzez_device_id', deviceId);
        }

        const registrationData = {
            platform: 'onesignal',
            player_id: playerId,
            device_id: deviceId,
        };

        // Make API call to register device
        fetch('/wp-json/houzez-api/v1/notifications/register-device', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Authorization: 'Bearer ' + (window.houzez_user_token || ''),
            },
            body: JSON.stringify(registrationData),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    console.log('✅ Device registered with Houzez API:', data);
                } else {
                    console.error('❌ Failed to register device:', data);
                }
            })
            .catch((error) => {
                console.error('❌ Registration error:', error);
            });
    }

    // Add subscription button
    function showSubscriptionPrompt() {
        if (typeof OneSignal !== 'undefined') {
            OneSignal.push(function () {
                OneSignal.showNativePrompt();
            });
        }
    }
</script>
```

---

## 🧪 Step 4: Testing Setup

### **1. Create Test Page**

Create a new file: `wp-content/plugins/houzez-api/tests/test-onesignal-desktop.php`

```php
<?php
/**
 * OneSignal Desktop Testing Page
 *
 * Access via: /wp-content/plugins/houzez-api/tests/test-onesignal-desktop.php
 */

// Security check
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('../../../wp-load.php');
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_die('You must be logged in to test OneSignal notifications.');
}

// Check admin capabilities for some tests
$is_admin = current_user_can('manage_options');
$current_user = wp_get_current_user();

?>
<!DOCTYPE html>
<html>
<head>
    <title>OneSignal Desktop Testing</title>
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .test-section { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        button { background: #007cba; color: white; padding: 10px 15px; border: none; border-radius: 3px; cursor: pointer; margin: 5px; }
        button:hover { background: #005a87; }
        .button-secondary { background: #666; }
        .button-secondary:hover { background: #444; }
        #status { margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔔 OneSignal Desktop Testing</h1>
    <p><strong>Current User:</strong> <?php echo $current_user->display_name; ?> (ID: <?php echo $current_user->ID; ?>)</p>

    <div id="status">
        <h3>📊 Status</h3>
        <div id="onesignal-status">⏳ Checking OneSignal status...</div>
        <div id="subscription-status">⏳ Checking subscription status...</div>
        <div id="user-id-status">⏳ Getting User ID...</div>
        <div id="api-status">⏳ Checking API registration...</div>
    </div>

    <!-- OneSignal Configuration Test -->
    <div class="test-section">
        <h2>1. 🔧 OneSignal Configuration</h2>
        <?php
        $push_enabled = get_option('houzez_api_push_enabled');
        $push_service = get_option('houzez_api_push_service');
        $app_id = get_option('houzez_api_onesignal_app_id');
        $api_key = get_option('houzez_api_onesignal_api_key');

        echo "<p><strong>Push Enabled:</strong> " . ($push_enabled ? '✅ Yes' : '❌ No') . "</p>";
        echo "<p><strong>Push Service:</strong> " . ($push_service ?: 'Not set') . "</p>";
        echo "<p><strong>OneSignal App ID:</strong> " . ($app_id ? '✅ Set (' . substr($app_id, 0, 8) . '...)' : '❌ Not set') . "</p>";
        echo "<p><strong>OneSignal API Key:</strong> " . ($api_key ? '✅ Set (' . substr($api_key, 0, 8) . '...)' : '❌ Not set') . "</p>";

        if (!$push_enabled || $push_service !== 'onesignal' || !$app_id || !$api_key) {
            echo '<div class="error">❌ OneSignal is not properly configured. Please check your settings.</div>';
        } else {
            echo '<div class="success">✅ OneSignal configuration looks good!</div>';
        }
        ?>
    </div>

    <!-- Subscription Management -->
    <div class="test-section">
        <h2>2. 📱 Subscription Management</h2>
        <button onclick="checkSubscription()">Check Subscription Status</button>
        <button onclick="requestPermission()">Request Permission</button>
        <button onclick="showSubscriptionPrompt()">Show Subscription Prompt</button>
        <button onclick="unsubscribe()" class="button-secondary">Unsubscribe</button>
        <div id="subscription-info" style="margin-top: 10px;"></div>
    </div>

    <!-- Device Registration Test -->
    <div class="test-section">
        <h2>3. 🔗 Device Registration</h2>
        <button onclick="registerDevice()">Register Device with API</button>
        <button onclick="unregisterDevice()" class="button-secondary">Unregister Device</button>
        <button onclick="checkRegistration()">Check Registration Status</button>
        <div id="registration-info" style="margin-top: 10px;"></div>
    </div>

    <!-- Test Notifications -->
    <div class="test-section">
        <h2>4. 🧪 Test Notifications</h2>
        <button onclick="sendTestNotification('simple')">Send Simple Test</button>
        <button onclick="sendTestNotification('property')">Send Property Inquiry Test</button>
        <button onclick="sendTestNotification('message')">Send Message Test</button>
        <button onclick="sendTestNotification('urgent')">Send Urgent Test</button>

        <?php if ($is_admin): ?>
        <br><br>
        <strong>Admin Only Tests:</strong><br>
        <button onclick="createWordPressNotification()">Create WordPress Notification</button>
        <button onclick="triggerHouzezHook()">Trigger Houzez Hook</button>
        <?php endif; ?>

        <div id="test-results" style="margin-top: 10px;"></div>
    </div>

    <!-- OneSignal Direct API Test -->
    <div class="test-section">
        <h2>5. 🎯 OneSignal Direct API Test</h2>
        <p>Test sending notifications directly via OneSignal API (requires valid Player ID)</p>
        <input type="text" id="test-message" placeholder="Test message" value="Hello from OneSignal!" style="width: 300px; padding: 5px;">
        <br><br>
        <button onclick="sendDirectNotification()">Send Direct Notification</button>
        <div id="direct-test-results" style="margin-top: 10px;"></div>
    </div>

    <!-- Debug Information -->
    <div class="test-section">
        <h2>6. 🔍 Debug Information</h2>
        <button onclick="showDebugInfo()">Show Debug Info</button>
        <button onclick="clearDebugInfo()" class="button-secondary">Clear Debug</button>
        <pre id="debug-info" style="background: #f5f5f5; padding: 10px; margin-top: 10px; max-height: 400px; overflow-y: auto;"></pre>
    </div>

    <script>
        // Global variables
        let onesignalReady = false;
        let currentUserId = null;
        let deviceId = localStorage.getItem('houzez_device_id') || 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        // Save device ID
        localStorage.setItem('houzez_device_id', deviceId);

        // Initialize OneSignal
        window.OneSignal = window.OneSignal || [];
        OneSignal.push(function() {
            OneSignal.init({
                appId: "<?php echo $app_id; ?>",
                allowLocalhostAsSecureOrigin: true,
                notifyButton: {
                    enable: false, // We'll handle subscription ourselves
                },
            });

            onesignalReady = true;
            updateStatus('onesignal-status', '✅ OneSignal SDK loaded successfully');

            // Check initial status
            checkInitialStatus();
        });

        // Helper function to update status
        function updateStatus(elementId, message) {
            document.getElementById(elementId).innerHTML = message;
        }

        // Check initial status
        function checkInitialStatus() {
            if (!onesignalReady) return;

            OneSignal.isPushNotificationsEnabled(function(isEnabled) {
                if (isEnabled) {
                    updateStatus('subscription-status', '✅ Subscribed to push notifications');

                    OneSignal.getUserId(function(userId) {
                        currentUserId = userId;
                        updateStatus('user-id-status', '✅ User ID: ' + userId);
                        checkAPIRegistration();
                    });
                } else {
                    updateStatus('subscription-status', '❌ Not subscribed to push notifications');
                    updateStatus('user-id-status', '❌ No User ID (not subscribed)');
                    updateStatus('api-status', '❌ Not registered with API');
                }
            });
        }

        // Check subscription status
        function checkSubscription() {
            if (!onesignalReady) {
                alert('OneSignal is not ready yet. Please try again.');
                return;
            }

            OneSignal.isPushNotificationsEnabled(function(isEnabled) {
                const info = document.getElementById('subscription-info');

                if (isEnabled) {
                    OneSignal.getUserId(function(userId) {
                        currentUserId = userId;
                        info.innerHTML = '<div class="success">✅ Subscribed! User ID: ' + userId + '</div>';
                    });
                } else {
                    info.innerHTML = '<div class="error">❌ Not subscribed to push notifications</div>';
                }
            });
        }

        // Request permission
        function requestPermission() {
            if (!onesignalReady) {
                alert('OneSignal is not ready yet. Please try again.');
                return;
            }

            OneSignal.getNotificationPermission(function(permission) {
                console.log('Current permission:', permission);

                if (permission === 'granted') {
                    alert('✅ Permission already granted!');
                    checkSubscription();
                } else {
                    showSubscriptionPrompt();
                }
            });
        }

        // Show subscription prompt
        function showSubscriptionPrompt() {
            if (!onesignalReady) {
                alert('OneSignal is not ready yet. Please try again.');
                return;
            }

            OneSignal.showNativePrompt().then(function() {
                // Wait a moment for the subscription to process
                setTimeout(checkSubscription, 1000);
            });
        }

        // Unsubscribe
        function unsubscribe() {
            if (!onesignalReady) {
                alert('OneSignal is not ready yet. Please try again.');
                return;
            }

            OneSignal.setSubscription(false).then(function() {
                alert('✅ Unsubscribed successfully');
                currentUserId = null;
                checkSubscription();
                updateStatus('subscription-status', '❌ Unsubscribed from push notifications');
                updateStatus('user-id-status', '❌ No User ID (unsubscribed)');
                updateStatus('api-status', '❌ Not registered with API');
            });
        }

        // Register device with Houzez API
        function registerDevice() {
            if (!currentUserId) {
                alert('❌ No OneSignal User ID. Please subscribe first.');
                return;
            }

            const registrationData = {
                platform: 'onesignal',
                player_id: currentUserId,
                device_id: deviceId
            };

            fetch('/wp-json/houzez-api/v1/notifications/register-device', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify(registrationData)
            })
            .then(response => response.json())
            .then(data => {
                const info = document.getElementById('registration-info');

                if (data.success) {
                    info.innerHTML = '<div class="success">✅ Device registered successfully!</div>';
                    updateStatus('api-status', '✅ Registered with Houzez API');
                } else {
                    info.innerHTML = '<div class="error">❌ Registration failed: ' + (data.message || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('registration-info').innerHTML = '<div class="error">❌ Registration error: ' + error.message + '</div>';
            });
        }

        // Unregister device
        function unregisterDevice() {
            fetch('/wp-json/houzez-api/v1/notifications/unregister-device', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify({
                    device_id: deviceId
                })
            })
            .then(response => response.json())
            .then(data => {
                const info = document.getElementById('registration-info');

                if (data.success) {
                    info.innerHTML = '<div class="success">✅ Device unregistered successfully!</div>';
                    updateStatus('api-status', '❌ Unregistered from Houzez API');
                } else {
                    info.innerHTML = '<div class="error">❌ Unregistration failed: ' + (data.message || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('registration-info').innerHTML = '<div class="error">❌ Unregistration error: ' + error.message + '</div>';
            });
        }

        // Check API registration status
        function checkAPIRegistration() {
            // This would require a custom endpoint to check registration status
            // For now, we'll just indicate if we have a User ID
            if (currentUserId) {
                updateStatus('api-status', '✅ Ready for API registration');
            } else {
                updateStatus('api-status', '❌ Not ready (no User ID)');
            }
        }

        // Check registration status
        function checkRegistration() {
            fetch('/wp-json/houzez-api/v1/notifications/unread-count', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                const info = document.getElementById('registration-info');

                if (data.success) {
                    info.innerHTML = '<div class="success">✅ API connection working! Unread count: ' + data.data.count + '</div>';
                } else {
                    info.innerHTML = '<div class="error">❌ API connection failed</div>';
                }
            })
            .catch(error => {
                document.getElementById('registration-info').innerHTML = '<div class="error">❌ API check error: ' + error.message + '</div>';
            });
        }

        // Send test notification via WordPress
        function sendTestNotification(type) {
            const testData = {
                type: type,
                user_id: <?php echo $current_user->ID; ?>
            };

            fetch('/wp-json/houzez-api/v1/notifications', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify(getTestNotificationData(type))
            })
            .then(response => response.json())
            .then(data => {
                const results = document.getElementById('test-results');

                if (data.success) {
                    results.innerHTML += '<div class="success">✅ ' + type + ' test notification sent!</div>';
                } else {
                    results.innerHTML += '<div class="error">❌ Failed to send ' + type + ' test: ' + (data.message || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('test-results').innerHTML += '<div class="error">❌ ' + type + ' test error: ' + error.message + '</div>';
            });
        }

        // Get test notification data
        function getTestNotificationData(type) {
            const baseData = {
                user_id: <?php echo $current_user->ID; ?>,
                user_email: '<?php echo $current_user->user_email; ?>'
            };

            switch (type) {
                case 'simple':
                    return {
                        ...baseData,
                        title: 'Simple Test Notification',
                        message: 'This is a simple test notification from OneSignal desktop testing.',
                        type: 'system_update',
                        priority: 'medium'
                    };

                case 'property':
                    return {
                        ...baseData,
                        title: 'New Property Inquiry',
                        message: 'John Doe contacted you about Downtown Apartment',
                        type: 'inquiry_received',
                        priority: 'high',
                        data: {
                            sender_name: 'John Doe',
                            sender_email: 'john@example.com',
                            property_id: 123,
                            property_title: 'Downtown Apartment'
                        }
                    };

                case 'message':
                    return {
                        ...baseData,
                        title: 'New Message',
                        message: 'You have received a new message from Sarah Smith',
                        type: 'message_received',
                        priority: 'medium',
                        data: {
                            sender_name: 'Sarah Smith',
                            thread_id: 456
                        }
                    };

                case 'urgent':
                    return {
                        ...baseData,
                        title: 'URGENT: Payment Overdue',
                        message: 'Your payment for Premium Listing is overdue',
                        type: 'payment_confirmation',
                        priority: 'urgent',
                        data: {
                            invoice_no: 'INV-789',
                            total_price: '$99.00'
                        }
                    };

                default:
                    return baseData;
            }
        }

        // Send direct notification via OneSignal API
        function sendDirectNotification() {
            if (!currentUserId) {
                alert('❌ No OneSignal User ID. Please subscribe first.');
                return;
            }

            const message = document.getElementById('test-message').value || 'Hello from OneSignal!';

            // This would typically be done server-side, but for testing we'll show the concept
            const notificationData = {
                app_id: "<?php echo $app_id; ?>",
                include_player_ids: [currentUserId],
                headings: {"en": "Direct OneSignal Test"},
                contents: {"en": message},
                data: {
                    type: "direct_test",
                    source: "desktop_testing"
                }
            };

            // Note: This requires server-side implementation due to CORS restrictions
            document.getElementById('direct-test-results').innerHTML =
                '<div class="info">📝 Direct API test data prepared:<br><pre>' +
                JSON.stringify(notificationData, null, 2) +
                '</pre><br>Note: Direct OneSignal API calls must be made server-side due to CORS restrictions.</div>';
        }

        <?php if ($is_admin): ?>
        // Create WordPress notification (Admin only)
        function createWordPressNotification() {
            // Create notification via WordPress hook
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=create_test_notification&nonce=<?php echo wp_create_nonce('create_test_notification'); ?>'
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-results').innerHTML += '<div class="success">✅ WordPress notification created! Check your browser for push notification.</div>';
            })
            .catch(error => {
                document.getElementById('test-results').innerHTML += '<div class="error">❌ WordPress notification error: ' + error.message + '</div>';
            });
        }

        // Trigger Houzez hook (Admin only)
        function triggerHouzezHook() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=trigger_houzez_hook&nonce=<?php echo wp_create_nonce('trigger_houzez_hook'); ?>'
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-results').innerHTML += '<div class="success">✅ Houzez hook triggered! Check your browser for push notification.</div>';
            })
            .catch(error => {
                document.getElementById('test-results').innerHTML += '<div class="error">❌ Houzez hook error: ' + error.message + '</div>';
            });
        }
        <?php endif; ?>

        // Show debug information
        function showDebugInfo() {
            const debugInfo = {
                onesignalReady: onesignalReady,
                currentUserId: currentUserId,
                deviceId: deviceId,
                browserInfo: {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    cookieEnabled: navigator.cookieEnabled
                },
                wordpressConfig: {
                    pushEnabled: <?php echo json_encode($push_enabled); ?>,
                    pushService: <?php echo json_encode($push_service); ?>,
                    appIdSet: <?php echo json_encode(!empty($app_id)); ?>,
                    apiKeySet: <?php echo json_encode(!empty($api_key)); ?>,
                    currentUser: <?php echo json_encode($current_user->ID); ?>
                },
                localStorage: {
                    deviceId: localStorage.getItem('houzez_device_id')
                }
            };

            document.getElementById('debug-info').textContent = JSON.stringify(debugInfo, null, 2);
        }

        // Clear debug info
        function clearDebugInfo() {
            document.getElementById('debug-info').textContent = '';
        }

        // Clear test results
        function clearResults() {
            document.getElementById('test-results').innerHTML = '';
            document.getElementById('registration-info').innerHTML = '';
            document.getElementById('subscription-info').innerHTML = '';
            document.getElementById('direct-test-results').innerHTML = '';
        }
    </script>
</body>
</html>

<?php
// Handle admin actions
if ($is_admin && isset($_POST['action'])) {

    if ($_POST['action'] === 'create_test_notification' && wp_verify_nonce($_POST['nonce'], 'create_test_notification')) {

        // Create a test notification
        $notification_id = wp_insert_post([
            'post_type' => 'houzez_notification',
            'post_title' => 'Desktop Test Notification',
            'post_content' => 'This is a test notification created from the OneSignal desktop testing page.',
            'post_status' => 'publish',
            'post_author' => $current_user->ID,
        ]);

        if ($notification_id) {
            update_post_meta($notification_id, 'user_id', $current_user->ID);
            update_post_meta($notification_id, 'notification_type', 'system_update');
            update_post_meta($notification_id, 'priority', 'high');
            update_post_meta($notification_id, 'read_status', '0');

            // Trigger push notification
            do_action('houzez_api_notification_created', $notification_id, $current_user->ID, 'system_update', [
                'test' => true,
                'source' => 'desktop_testing',
                'message' => 'Desktop test notification created successfully'
            ]);
        }
    }

    if ($_POST['action'] === 'trigger_houzez_hook' && wp_verify_nonce($_POST['nonce'], 'trigger_houzez_hook')) {

        // Trigger the houzez_send_notification hook directly
        do_action('houzez_send_notification', [
            'email' => $current_user->user_email,
            'type' => 'inquiry_received',
            'title' => 'Houzez Hook Test',
            'message' => 'This notification was triggered via the houzez_send_notification hook from desktop testing.',
            'user_id' => $current_user->ID,
            'extra_data' => [
                'test' => true,
                'source' => 'houzez_hook_testing',
                'sender_name' => 'Test Sender',
                'property_id' => 999,
                'property_title' => 'Test Property'
            ]
        ]);
    }
}
?>
```

---

## 🚀 Step 5: Testing Process

### **1. Basic Setup Test**

1. **Access Test Page**: Go to `/wp-content/plugins/houzez-api/tests/test-onesignal-desktop.php`
2. **Check Configuration**: Verify OneSignal settings are properly configured
3. **Grant Permission**: Click "Request Permission" and allow notifications

### **2. Subscription Test**

1. **Subscribe**: Click "Show Subscription Prompt"
2. **Check Status**: Click "Check Subscription Status"
3. **Get User ID**: Note the OneSignal User ID (Player ID)

### **3. Device Registration Test**

1. **Register**: Click "Register Device with API"
2. **Verify**: Check that registration was successful
3. **Test API**: Click "Check Registration Status"

### **4. Notification Tests**

1. **Simple Test**: Click "Send Simple Test" - basic notification
2. **Property Test**: Click "Send Property Inquiry Test" - with rich data
3. **Message Test**: Click "Send Message Test" - message notification
4. **Urgent Test**: Click "Send Urgent Test" - high priority

### **5. Advanced Tests (Admin Only)**

1. **WordPress Creation**: Click "Create WordPress Notification"
2. **Houzez Hook**: Click "Trigger Houzez Hook"

---

## 🔍 Step 6: Troubleshooting

### **Common Issues & Solutions**

#### **1. "OneSignal is not ready yet"**

-   **Cause**: OneSignal SDK not loaded or configured incorrectly
-   **Solution**: Check App ID and ensure HTTPS

#### **2. Permission Denied**

-   **Cause**: User declined notification permission
-   **Solution**:
    -   Chrome: Settings > Privacy > Site Settings > Notifications
    -   Firefox: Settings > Privacy & Security > Permissions > Notifications

#### **3. No Push Notifications Received**

-   **Cause**: Various issues
-   **Solutions**:
    -   Check browser notification settings
    -   Verify OneSignal configuration
    -   Check if subscribed and registered
    -   Look at browser developer console for errors

#### **4. Registration Failed**

-   **Cause**: API authentication or configuration issue
-   **Solution**:
    -   Ensure user is logged in
    -   Check WordPress API settings
    -   Verify REST API is enabled

### **Debug Checklist**

1. ✅ **HTTPS enabled** (required for web push)
2. ✅ **OneSignal App ID** set correctly
3. ✅ **OneSignal API Key** set correctly
4. ✅ **User subscribed** to notifications
5. ✅ **Player ID obtained** from OneSignal
6. ✅ **Device registered** with Houzez API
7. ✅ **Browser permissions** granted
8. ✅ **No console errors** in browser

---

## 📝 Step 7: Manual Testing Commands

### **Browser Console Commands**

Open Developer Tools (F12) and try these:

```javascript
// Check OneSignal status
OneSignal.isPushNotificationsEnabled().then(console.log);

// Get User ID
OneSignal.getUserId().then(console.log);

// Check permission
OneSignal.getNotificationPermission().then(console.log);

// Test notification (if you have User ID)
OneSignal.sendSelfNotification(
    'Test Title',
    'Test Message',
    'https://yoursite.com',
    'https://yoursite.com/icon.png'
);
```

### **WordPress Console Commands**

In WordPress admin, use these:

```php
// Test OneSignal configuration
var_dump([
    'push_enabled' => get_option('houzez_api_push_enabled'),
    'push_service' => get_option('houzez_api_push_service'),
    'app_id' => get_option('houzez_api_onesignal_app_id'),
    'api_key' => substr(get_option('houzez_api_onesignal_api_key'), 0, 8) . '...'
]);

// Check user's registered devices
$user_id = get_current_user_id();
$devices = get_user_meta($user_id, 'houzez_device_tokens', true);
var_dump($devices);

// Create test notification
do_action('houzez_send_notification', [
    'email' => wp_get_current_user()->user_email,
    'title' => 'Manual Test',
    'message' => 'Manual test notification',
    'type' => 'system_update'
]);
```

---

## 🎯 Expected Results

### **Successful Desktop Test Should Show:**

1. ✅ **OneSignal SDK loaded successfully**
2. ✅ **Subscribed to push notifications**
3. ✅ **User ID obtained** (Player ID)
4. ✅ **Device registered with API**
5. ✅ **Test notifications received** in browser
6. ✅ **Rich notification data** displayed
7. ✅ **Deep links working** (if applicable)

### **Browser Notification Should Include:**

-   **Title**: From notification content
-   **Message**: From notification content
-   **Icon**: Your website/OneSignal icon
-   **Actions**: Click to navigate (if configured)
-   **Rich Data**: Property details, sender info, etc.

---

## 📊 Performance Monitoring

### **Track Success Metrics:**

1. **Subscription Rate**: % of users who allow notifications
2. **Delivery Rate**: % of notifications successfully sent
3. **Click-Through Rate**: % of notifications clicked
4. **Unsubscribe Rate**: % of users who opt out

### **OneSignal Dashboard:**

-   Monitor delivery statistics
-   View user segments
-   Track notification performance
-   Analyze engagement metrics

---

This comprehensive guide will help you successfully test OneSignal desktop notifications with your Houzez API system. The test page provides all the tools needed to verify your setup and troubleshoot any issues.
