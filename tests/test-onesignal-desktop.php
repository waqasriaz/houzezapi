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
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; border: 1px solid #ffeaa7; }
        .test-section { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; background: #f9f9f9; }
        .test-section h2 { margin-top: 0; color: #333; }
        button { background: #007cba; color: white; padding: 10px 15px; border: none; border-radius: 3px; cursor: pointer; margin: 5px; font-size: 14px; }
        button:hover { background: #005a87; }
        .button-secondary { background: #666; }
        .button-secondary:hover { background: #444; }
        .button-danger { background: #dc3545; }
        .button-danger:hover { background: #c82333; }
        .button-success { background: #28a745; }
        .button-success:hover { background: #218838; }
        #status { margin: 15px 0; padding: 15px; background: #fff; border-radius: 5px; border: 1px solid #ddd; }
        #status h3 { margin-top: 0; }
        .status-item { margin: 8px 0; padding: 5px; }
        input[type="text"] { padding: 8px; border: 1px solid #ccc; border-radius: 3px; font-size: 14px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <h1>🔔 OneSignal Desktop Testing</h1>
    <div class="info">
        <strong>Current User:</strong> <?php echo esc_html($current_user->display_name); ?> (ID: <?php echo $current_user->ID; ?>)
        <?php if ($is_admin): ?>
        <br><strong>Role:</strong> Administrator (Full testing available)
        <?php else: ?>
        <br><strong>Role:</strong> Standard User (Limited testing)
        <?php endif; ?>
    </div>
    
    <div id="status">
        <h3>📊 Real-time Status</h3>
        <div class="status-item" id="onesignal-status">⏳ Checking OneSignal status...</div>
        <div class="status-item" id="subscription-status">⏳ Checking subscription status...</div>
        <div class="status-item" id="user-id-status">⏳ Getting User ID...</div>
        <div class="status-item" id="api-status">⏳ Checking API registration...</div>
    </div>

    <!-- OneSignal Configuration Test -->
    <div class="test-section">
        <h2>1. 🔧 OneSignal Configuration</h2>
        <?php
        $push_enabled = get_option('houzez_api_push_enabled');
        $push_service = get_option('houzez_api_push_service');
        $app_id = get_option('houzez_api_onesignal_app_id');
        $api_key = get_option('houzez_api_onesignal_api_key');
        
        echo "<p><strong>Push Enabled:</strong> " . ($push_enabled ? '<span style="color: green;">✅ Yes</span>' : '<span style="color: red;">❌ No</span>') . "</p>";
        echo "<p><strong>Push Service:</strong> " . ($push_service ?: '<span style="color: red;">Not set</span>') . "</p>";
        echo "<p><strong>OneSignal App ID:</strong> " . ($app_id ? '<span style="color: green;">✅ Set (' . substr($app_id, 0, 8) . '...)</span>' : '<span style="color: red;">❌ Not set</span>') . "</p>";
        echo "<p><strong>OneSignal API Key:</strong> " . ($api_key ? '<span style="color: green;">✅ Set (' . substr($api_key, 0, 8) . '...)</span>' : '<span style="color: red;">❌ Not set</span>') . "</p>";
        
        if (!$push_enabled || $push_service !== 'onesignal' || !$app_id || !$api_key) {
            echo '<div class="error">❌ OneSignal is not properly configured. Please check your Houzez API settings.</div>';
            if ($is_admin) {
                echo '<p><a href="' . admin_url('admin.php?page=houzez-api-settings') . '" target="_blank">→ Go to Houzez API Settings</a></p>';
            }
        } else {
            echo '<div class="success">✅ OneSignal configuration looks good!</div>';
        }
        ?>
    </div>

    <div class="grid">
        <!-- Subscription Management -->
        <div class="test-section">
            <h2>2. 📱 Subscription Management</h2>
            <button onclick="checkSubscription()">Check Subscription Status</button><br>
            <button onclick="requestPermission()" class="button-success">Request Permission</button><br>
            <button onclick="showSubscriptionPrompt()" class="button-success">Show Subscription Prompt</button><br>
            <button onclick="unsubscribe()" class="button-danger">Unsubscribe</button>
            <div id="subscription-info" style="margin-top: 15px;"></div>
        </div>

        <!-- Device Registration Test -->
        <div class="test-section">
            <h2>3. 🔗 Device Registration</h2>
            <button onclick="registerDevice()" class="button-success">Register Device with API</button><br>
            <button onclick="unregisterDevice()" class="button-danger">Unregister Device</button><br>
            <button onclick="checkRegistration()">Check Registration Status</button>
            <div id="registration-info" style="margin-top: 15px;"></div>
        </div>
    </div>

    <!-- Test Notifications -->
    <div class="test-section">
        <h2>4. 🧪 Test Notifications</h2>
        <div class="grid">
            <div>
                <h4>Basic Tests:</h4>
                <button onclick="sendTestNotification('simple')">📝 Send Simple Test</button><br>
                <button onclick="sendTestNotification('property')">🏠 Send Property Inquiry Test</button><br>
                <button onclick="sendTestNotification('message')">💬 Send Message Test</button><br>
                <button onclick="sendTestNotification('urgent')" class="button-danger">🚨 Send Urgent Test</button>
            </div>
            
            <?php if ($is_admin): ?>
            <div>
                <h4>Admin Only Tests:</h4>
                <button onclick="createWordPressNotification()" class="button-success">📝 Create WordPress Notification</button><br>
                <button onclick="triggerHouzezHook()" class="button-success">🔗 Trigger Houzez Hook</button><br>
                <button onclick="sendViaOneSignalAPI()" class="button-success">🎯 Send Via OneSignal API</button>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="test-results" style="margin-top: 15px;"></div>
    </div>

    <!-- Custom Test -->
    <div class="test-section">
        <h2>5. 🎯 Custom Notification Test</h2>
        <div class="grid">
            <div>
                <p><strong>Title:</strong></p>
                <input type="text" id="custom-title" placeholder="Test Notification Title" value="Custom Test Notification" style="width: 100%; margin-bottom: 10px;">
                
                <p><strong>Message:</strong></p>
                <input type="text" id="custom-message" placeholder="Test message content" value="This is a custom test message!" style="width: 100%; margin-bottom: 10px;">
                
                <p><strong>Priority:</strong></p>
                <select id="custom-priority" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div>
                <p><strong>Type:</strong></p>
                <select id="custom-type" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                    <option value="system_update">System Update</option>
                    <option value="inquiry_received">Inquiry Received</option>
                    <option value="message_received">Message Received</option>
                    <option value="payment_confirmation">Payment Confirmation</option>
                    <option value="listing_approved">Listing Approved</option>
                    <option value="property_matched">Property Matched</option>
                </select>
                
                <br><br>
                <button onclick="sendCustomNotification()" class="button-success">📤 Send Custom Notification</button>
            </div>
        </div>
        <div id="custom-test-results" style="margin-top: 15px;"></div>
    </div>

    <!-- Debug Information -->
    <div class="test-section">
        <h2>6. 🔍 Debug Information & Tools</h2>
        <button onclick="showDebugInfo()">📋 Show Debug Info</button>
        <button onclick="clearDebugInfo()" class="button-secondary">🗑️ Clear Debug</button>
        <button onclick="clearAllResults()" class="button-secondary">🧹 Clear All Results</button>
        <button onclick="exportDebugData()" class="button-secondary">📄 Export Debug Data</button>
        
        <div style="margin-top: 15px;">
            <h4>Quick Tests:</h4>
            <button onclick="testBrowserSupport()">🌐 Test Browser Support</button>
            <button onclick="testPermissions()">🔐 Test Permissions</button>
            <button onclick="testAPIConnection()">🔗 Test API Connection</button>
        </div>
        
        <pre id="debug-info" style="margin-top: 15px; max-height: 400px; overflow-y: auto;"></pre>
    </div>

    <!-- Instructions -->
    <div class="test-section">
        <h2>7. 📖 Testing Instructions</h2>
        <div class="info">
            <h4>🔄 Testing Process:</h4>
            <ol>
                <li><strong>Check Configuration:</strong> Ensure OneSignal settings are properly configured</li>
                <li><strong>Request Permission:</strong> Click "Request Permission" and allow notifications in your browser</li>
                <li><strong>Subscribe:</strong> Use "Show Subscription Prompt" to subscribe to notifications</li>
                <li><strong>Register Device:</strong> Click "Register Device with API" to connect with Houzez API</li>
                <li><strong>Test Notifications:</strong> Try different notification types to verify functionality</li>
                <li><strong>Monitor Results:</strong> Check browser notifications and debug information</li>
            </ol>
        </div>
        
        <div class="warning">
            <h4>⚠️ Common Issues:</h4>
            <ul>
                <li><strong>HTTPS Required:</strong> OneSignal requires HTTPS for web push notifications</li>
                <li><strong>Browser Permissions:</strong> User must grant notification permission</li>
                <li><strong>Configuration:</strong> App ID and API Key must be correctly set</li>
                <li><strong>Subscription:</strong> User must be subscribed to OneSignal to receive notifications</li>
            </ul>
        </div>
    </div>

    <script>
        // Global variables
        let onesignalReady = false;
        let currentUserId = null;
        let deviceId = localStorage.getItem('houzez_device_id') || 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        let debugLog = [];
        
        // Save device ID
        localStorage.setItem('houzez_device_id', deviceId);
        
        // Initialize OneSignal
        window.OneSignal = window.OneSignal || [];
        OneSignal.push(function() {
            OneSignal.init({
                appId: "<?php echo esc_js($app_id); ?>",
                allowLocalhostAsSecureOrigin: true,
                notifyButton: {
                    enable: false, // We'll handle subscription ourselves
                },
            });
            
            onesignalReady = true;
            logDebug('OneSignal SDK initialized');
            updateStatus('onesignal-status', '✅ OneSignal SDK loaded successfully');
            
            // Check initial status
            checkInitialStatus();
        });
        
        // Helper function to update status
        function updateStatus(elementId, message) {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = message;
            }
        }
        
        // Debug logging
        function logDebug(message, data = null) {
            const timestamp = new Date().toISOString();
            debugLog.push({
                timestamp: timestamp,
                message: message,
                data: data
            });
            console.log('[OneSignal Test]', message, data);
        }
        
        // Check initial status
        function checkInitialStatus() {
            if (!onesignalReady) return;
            
            OneSignal.isPushNotificationsEnabled(function(isEnabled) {
                if (isEnabled) {
                    updateStatus('subscription-status', '✅ Subscribed to push notifications');
                    logDebug('User is subscribed to push notifications');
                    
                    OneSignal.getUserId(function(userId) {
                        currentUserId = userId;
                        updateStatus('user-id-status', '✅ User ID: ' + userId);
                        logDebug('OneSignal User ID obtained', userId);
                        checkAPIRegistration();
                    });
                } else {
                    updateStatus('subscription-status', '❌ Not subscribed to push notifications');
                    updateStatus('user-id-status', '❌ No User ID (not subscribed)');
                    updateStatus('api-status', '❌ Not registered with API');
                    logDebug('User is not subscribed to push notifications');
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
                        logDebug('Subscription check: subscribed', userId);
                    });
                } else {
                    info.innerHTML = '<div class="error">❌ Not subscribed to push notifications</div>';
                    logDebug('Subscription check: not subscribed');
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
                logDebug('Current permission status', permission);
                
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
            
            logDebug('Showing subscription prompt');
            OneSignal.showNativePrompt().then(function() {
                logDebug('Subscription prompt shown');
                // Wait a moment for the subscription to process
                setTimeout(function() {
                    checkSubscription();
                    checkInitialStatus();
                }, 2000);
            });
        }
        
        // Unsubscribe
        function unsubscribe() {
            if (!onesignalReady) {
                alert('OneSignal is not ready yet. Please try again.');
                return;
            }
            
            if (confirm('Are you sure you want to unsubscribe from push notifications?')) {
                OneSignal.setSubscription(false).then(function() {
                    alert('✅ Unsubscribed successfully');
                    currentUserId = null;
                    checkSubscription();
                    updateStatus('subscription-status', '❌ Unsubscribed from push notifications');
                    updateStatus('user-id-status', '❌ No User ID (unsubscribed)');
                    updateStatus('api-status', '❌ Not registered with API');
                    logDebug('User unsubscribed successfully');
                });
            }
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
            
            logDebug('Registering device with API', registrationData);
            
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
                    logDebug('Device registration successful', data);
                } else {
                    info.innerHTML = '<div class="error">❌ Registration failed: ' + (data.message || 'Unknown error') + '</div>';
                    logDebug('Device registration failed', data);
                }
            })
            .catch(error => {
                document.getElementById('registration-info').innerHTML = '<div class="error">❌ Registration error: ' + error.message + '</div>';
                logDebug('Device registration error', error);
            });
        }
        
        // Unregister device
        function unregisterDevice() {
            if (confirm('Are you sure you want to unregister this device?')) {
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
                        logDebug('Device unregistration successful', data);
                    } else {
                        info.innerHTML = '<div class="error">❌ Unregistration failed: ' + (data.message || 'Unknown error') + '</div>';
                        logDebug('Device unregistration failed', data);
                    }
                })
                .catch(error => {
                    document.getElementById('registration-info').innerHTML = '<div class="error">❌ Unregistration error: ' + error.message + '</div>';
                    logDebug('Device unregistration error', error);
                });
            }
        }
        
        // Check API registration status
        function checkAPIRegistration() {
            updateStatus('api-status', '✅ Ready for API registration (User ID available)');
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
                    logDebug('API connection test successful', data);
                } else {
                    info.innerHTML = '<div class="error">❌ API connection failed</div>';
                    logDebug('API connection test failed', data);
                }
            })
            .catch(error => {
                document.getElementById('registration-info').innerHTML = '<div class="error">❌ API check error: ' + error.message + '</div>';
                logDebug('API connection test error', error);
            });
        }
        
        // Send test notification via WordPress
        function sendTestNotification(type) {
            const testData = getTestNotificationData(type);
            logDebug('Sending test notification', {type: type, data: testData});
            
            fetch('/wp-json/houzez-api/v1/notifications', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify(testData)
            })
            .then(response => response.json())
            .then(data => {
                const results = document.getElementById('test-results');
                
                if (data.success) {
                    results.innerHTML += '<div class="success">✅ ' + type + ' test notification sent! (ID: ' + (data.data?.id || 'N/A') + ')</div>';
                    logDebug('Test notification sent successfully', {type: type, response: data});
                } else {
                    results.innerHTML += '<div class="error">❌ Failed to send ' + type + ' test: ' + (data.message || 'Unknown error') + '</div>';
                    logDebug('Test notification failed', {type: type, error: data});
                }
            })
            .catch(error => {
                document.getElementById('test-results').innerHTML += '<div class="error">❌ ' + type + ' test error: ' + error.message + '</div>';
                logDebug('Test notification request error', {type: type, error: error});
            });
        }
        
        // Send custom notification
        function sendCustomNotification() {
            const title = document.getElementById('custom-title').value;
            const message = document.getElementById('custom-message').value;
            const priority = document.getElementById('custom-priority').value;
            const type = document.getElementById('custom-type').value;
            
            if (!title || !message) {
                alert('Please enter both title and message');
                return;
            }
            
            const customData = {
                user_id: <?php echo $current_user->ID; ?>,
                user_email: '<?php echo esc_js($current_user->user_email); ?>',
                title: title,
                message: message,
                type: type,
                priority: priority,
                data: {
                    source: 'custom_test',
                    timestamp: new Date().toISOString()
                }
            };
            
            logDebug('Sending custom notification', customData);
            
            fetch('/wp-json/houzez-api/v1/notifications', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify(customData)
            })
            .then(response => response.json())
            .then(data => {
                const results = document.getElementById('custom-test-results');
                
                if (data.success) {
                    results.innerHTML = '<div class="success">✅ Custom notification sent successfully! (ID: ' + (data.data?.id || 'N/A') + ')</div>';
                    logDebug('Custom notification sent successfully', data);
                } else {
                    results.innerHTML = '<div class="error">❌ Failed to send custom notification: ' + (data.message || 'Unknown error') + '</div>';
                    logDebug('Custom notification failed', data);
                }
            })
            .catch(error => {
                document.getElementById('custom-test-results').innerHTML = '<div class="error">❌ Custom notification error: ' + error.message + '</div>';
                logDebug('Custom notification request error', error);
            });
        }
        
        // Get test notification data
        function getTestNotificationData(type) {
            const baseData = {
                user_id: <?php echo $current_user->ID; ?>,
                user_email: '<?php echo esc_js($current_user->user_email); ?>'
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
        
        <?php if ($is_admin): ?>
        // Create WordPress notification (Admin only)
        function createWordPressNotification() {
            logDebug('Creating WordPress notification (admin)');
            
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
                logDebug('WordPress notification created successfully');
            })
            .catch(error => {
                document.getElementById('test-results').innerHTML += '<div class="error">❌ WordPress notification error: ' + error.message + '</div>';
                logDebug('WordPress notification creation error', error);
            });
        }
        
        // Trigger Houzez hook (Admin only)
        function triggerHouzezHook() {
            logDebug('Triggering Houzez hook (admin)');
            
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
                logDebug('Houzez hook triggered successfully');
            })
            .catch(error => {
                document.getElementById('test-results').innerHTML += '<div class="error">❌ Houzez hook error: ' + error.message + '</div>';
                logDebug('Houzez hook trigger error', error);
            });
        }
        
        // Send via OneSignal API (Admin only)
        function sendViaOneSignalAPI() {
            if (!currentUserId) {
                alert('❌ No OneSignal User ID. Please subscribe first.');
                return;
            }
            
            logDebug('Sending notification via OneSignal API (admin)');
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=send_onesignal_api&nonce=<?php echo wp_create_nonce('send_onesignal_api'); ?>&player_id=' + encodeURIComponent(currentUserId)
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-results').innerHTML += '<div class="success">✅ OneSignal API notification sent! Check your browser for push notification.</div>';
                logDebug('OneSignal API notification sent successfully');
            })
            .catch(error => {
                document.getElementById('test-results').innerHTML += '<div class="error">❌ OneSignal API error: ' + error.message + '</div>';
                logDebug('OneSignal API notification error', error);
            });
        }
        <?php endif; ?>
        
        // Test browser support
        function testBrowserSupport() {
            const support = {
                notifications: 'Notification' in window,
                serviceWorker: 'serviceWorker' in navigator,
                pushManager: 'PushManager' in window,
                https: location.protocol === 'https:' || location.hostname === 'localhost'
            };
            
            let message = '<h4>Browser Support Check:</h4>';
            message += '<p>Notifications API: ' + (support.notifications ? '✅' : '❌') + '</p>';
            message += '<p>Service Worker: ' + (support.serviceWorker ? '✅' : '❌') + '</p>';
            message += '<p>Push Manager: ' + (support.pushManager ? '✅' : '❌') + '</p>';
            message += '<p>HTTPS/Localhost: ' + (support.https ? '✅' : '❌') + '</p>';
            
            document.getElementById('debug-info').innerHTML = message;
            logDebug('Browser support check', support);
        }
        
        // Test permissions
        function testPermissions() {
            if ('Notification' in window) {
                const permission = Notification.permission;
                let message = '<h4>Permission Status:</h4>';
                message += '<p>Current Permission: <strong>' + permission + '</strong></p>';
                
                if (permission === 'granted') {
                    message += '<p>✅ Notifications are allowed</p>';
                } else if (permission === 'denied') {
                    message += '<p>❌ Notifications are blocked</p>';
                    message += '<p>To fix: Check browser settings → Site Settings → Notifications</p>';
                } else {
                    message += '<p>⚠️ Permission not yet requested</p>';
                }
                
                document.getElementById('debug-info').innerHTML = message;
                logDebug('Permission test', {permission: permission});
            } else {
                document.getElementById('debug-info').innerHTML = '<p>❌ Notifications API not supported</p>';
                logDebug('Permission test failed - API not supported');
            }
        }
        
        // Test API connection
        function testAPIConnection() {
            fetch('/wp-json/houzez-api/v1/notifications/unread-count', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                }
            })
            .then(response => {
                const message = '<h4>API Connection Test:</h4>' +
                    '<p>Status: ' + response.status + ' ' + response.statusText + '</p>' +
                    '<p>' + (response.ok ? '✅ API is accessible' : '❌ API connection failed') + '</p>';
                
                document.getElementById('debug-info').innerHTML = message;
                logDebug('API connection test', {status: response.status, ok: response.ok});
                
                return response.json();
            })
            .then(data => {
                logDebug('API response data', data);
            })
            .catch(error => {
                document.getElementById('debug-info').innerHTML += '<p>❌ API Error: ' + error.message + '</p>';
                logDebug('API connection test error', error);
            });
        }
        
        // Show debug information
        function showDebugInfo() {
            const debugInfo = {
                timestamp: new Date().toISOString(),
                onesignalReady: onesignalReady,
                currentUserId: currentUserId,
                deviceId: deviceId,
                browserInfo: {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    cookieEnabled: navigator.cookieEnabled,
                    onLine: navigator.onLine
                },
                notificationSupport: {
                    api: 'Notification' in window,
                    permission: typeof Notification !== 'undefined' ? Notification.permission : 'N/A',
                    serviceWorker: 'serviceWorker' in navigator,
                    pushManager: 'PushManager' in window
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
                },
                debugLog: debugLog.slice(-10) // Last 10 log entries
            };
            
            document.getElementById('debug-info').innerHTML = '<h4>Debug Information:</h4><pre>' + JSON.stringify(debugInfo, null, 2) + '</pre>';
        }
        
        // Export debug data
        function exportDebugData() {
            const debugData = {
                timestamp: new Date().toISOString(),
                testSession: {
                    onesignalReady: onesignalReady,
                    currentUserId: currentUserId,
                    deviceId: deviceId
                },
                config: {
                    pushEnabled: <?php echo json_encode($push_enabled); ?>,
                    pushService: <?php echo json_encode($push_service); ?>,
                    appIdSet: <?php echo json_encode(!empty($app_id)); ?>,
                    apiKeySet: <?php echo json_encode(!empty($api_key)); ?>
                },
                debugLog: debugLog
            };
            
            const dataStr = JSON.stringify(debugData, null, 2);
            const blob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = 'onesignal-debug-' + new Date().toISOString().split('T')[0] + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            logDebug('Debug data exported');
        }
        
        // Clear debug info
        function clearDebugInfo() {
            document.getElementById('debug-info').innerHTML = '';
            debugLog = [];
        }
        
        // Clear all results
        function clearAllResults() {
            document.getElementById('test-results').innerHTML = '';
            document.getElementById('registration-info').innerHTML = '';
            document.getElementById('subscription-info').innerHTML = '';
            document.getElementById('custom-test-results').innerHTML = '';
            document.getElementById('debug-info').innerHTML = '';
        }
        
        // Auto-refresh status every 30 seconds
        setInterval(function() {
            if (onesignalReady) {
                checkInitialStatus();
            }
        }, 30000);
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
    
    if ($_POST['action'] === 'send_onesignal_api' && wp_verify_nonce($_POST['nonce'], 'send_onesignal_api')) {
        
        $player_id = sanitize_text_field($_POST['player_id']);
        $app_id = get_option('houzez_api_onesignal_app_id');
        $api_key = get_option('houzez_api_onesignal_api_key');
        
        if ($app_id && $api_key && $player_id) {
            $fields = [
                'app_id' => $app_id,
                'include_player_ids' => [$player_id],
                'headings' => ['en' => 'OneSignal API Test'],
                'contents' => ['en' => 'This notification was sent directly via OneSignal API from the testing page.'],
                'data' => [
                    'type' => 'api_test',
                    'source' => 'desktop_testing',
                    'timestamp' => current_time('timestamp')
                ]
            ];
            
            $response = wp_remote_post('https://onesignal.com/api/v1/notifications', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . $api_key,
                ],
                'body' => json_encode($fields),
                'timeout' => 30,
            ]);
            
            if (is_wp_error($response)) {
                error_log('OneSignal API Test Error: ' . $response->get_error_message());
            }
        }
    }
}
?> 