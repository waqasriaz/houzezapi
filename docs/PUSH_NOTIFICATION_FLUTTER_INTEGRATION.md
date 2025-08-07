# Houzez API - Push Notification & Flutter Integration Guide

## 🎯 Overview

The Houzez API push notification system provides **automatic push notifications** to mobile apps with minimal Flutter integration required. Flutter apps only need to **register their device tokens** - all notification sending is handled automatically by the web API.

## 📱 Simple Architecture

### **Flutter App Responsibilities:**

-   ✅ Register device token (one-time setup)
-   ✅ Handle incoming push notifications
-   ✅ Navigate based on deep links

### **Web API Responsibilities:**

-   ✅ Detect when notifications are created (automatic)
-   ✅ Find user's registered devices
-   ✅ Format push notification payload
-   ✅ Send via Firebase/OneSignal
-   ✅ Handle errors and retries
-   ✅ Manage user preferences
-   ✅ Generate deep links

---

## 🚀 Quick Start for Flutter

### **1. Add Firebase to Your Flutter Project**

#### Add dependencies to `pubspec.yaml`:

```yaml
dependencies:
    firebase_core: ^2.24.2
    firebase_messaging: ^14.7.10
    http: ^1.1.0
    device_info_plus: ^9.1.0

dev_dependencies:
    flutter_launcher_icons: ^0.13.1
```

#### Initialize Firebase in `main.dart`:

```dart
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();

  // Initialize push notifications
  await PushNotificationService.initialize();

  runApp(MyApp());
}
```

### **2. Create Push Notification Service**

Create `lib/services/push_notification_service.dart`:

```dart
import 'dart:convert';
import 'dart:io';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:http/http.dart' as http;
import 'package:device_info_plus/device_info_plus.dart';

class PushNotificationService {
  static const String API_BASE = 'https://yourdomain.com/wp-json/houzez-api/v1';
  static FirebaseMessaging _firebaseMessaging = FirebaseMessaging.instance;

  /// Initialize push notifications (call once at app startup)
  static Future<void> initialize() async {
    print('🔔 Initializing push notifications...');

    // Request permission
    NotificationSettings settings = await _firebaseMessaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );

    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      print('✅ Push notification permission granted');

      // Get FCM token
      String? token = await _firebaseMessaging.getToken();
      print('📱 FCM Token: $token');

      // Register with Houzez API (when user logs in)
      // Call this after successful login: await registerDeviceWithAPI(token);

      // Set up message handlers
      _setupMessageHandlers();

      // Handle token refresh
      _firebaseMessaging.onTokenRefresh.listen((String token) {
        print('🔄 FCM Token refreshed: $token');
        registerDeviceWithAPI(token); // Re-register with new token
      });

    } else {
      print('❌ Push notification permission denied');
    }
  }

  /// Register device with Houzez API (call after user login)
  static Future<bool> registerDeviceWithAPI(String? fcmToken) async {
    if (fcmToken == null) {
      print('❌ No FCM token available');
      return false;
    }

    // Get user token (replace with your auth system)
    String? userToken = await AuthService.getToken();
    if (userToken == null) {
      print('❌ User not authenticated');
      return false;
    }

    try {
      String deviceId = await _getDeviceId();

      final response = await http.post(
        Uri.parse('$API_BASE/notifications/register-device'),
        headers: {
          'Authorization': 'Bearer $userToken',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'platform': Platform.isIOS ? 'ios' : 'android',
          'token': fcmToken,
          'device_id': deviceId,
        }),
      );

      if (response.statusCode == 200) {
        print('✅ Device registered for push notifications');
        return true;
      } else {
        print('❌ Failed to register device: ${response.body}');
        return false;
      }

    } catch (e) {
      print('❌ Error registering device: $e');
      return false;
    }
  }

  /// Unregister device (call on logout)
  static Future<bool> unregisterDevice() async {
    String? userToken = await AuthService.getToken();
    if (userToken == null) return false;

    try {
      String deviceId = await _getDeviceId();

      final response = await http.post(
        Uri.parse('$API_BASE/notifications/unregister-device'),
        headers: {
          'Authorization': 'Bearer $userToken',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'device_id': deviceId,
        }),
      );

      if (response.statusCode == 200) {
        print('✅ Device unregistered');
        return true;
      } else {
        print('❌ Failed to unregister device');
        return false;
      }

    } catch (e) {
      print('❌ Error unregistering device: $e');
      return false;
    }
  }

  /// Set up message handlers
  static void _setupMessageHandlers() {
    // Handle messages when app is in foreground
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      print('📨 Foreground message received');
      _handleMessage(message, isBackground: false);
    });

    // Handle messages when app is opened from notification
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      print('📨 App opened from notification');
      _handleMessage(message, isBackground: true);
    });

    // Handle messages when app is terminated
    FirebaseMessaging.getInitialMessage().then((RemoteMessage? message) {
      if (message != null) {
        print('📨 App opened from terminated state');
        _handleMessage(message, isBackground: true);
      }
    });
  }

  /// Handle incoming messages
  static void _handleMessage(RemoteMessage message, {required bool isBackground}) {
    print('📨 Message data: ${message.data}');

    // Extract notification data
    String? deepLink = message.data['deep_link'];
    String? notificationId = message.data['notification_id'];
    String? type = message.data['type'];
    String? priority = message.data['priority'];

    // Show in-app notification if foreground
    if (!isBackground) {
      _showInAppNotification(message);
    }

    // Handle navigation if app was opened from notification
    if (isBackground && deepLink != null) {
      _handleDeepLink(deepLink, message.data);
    }

    // Mark notification as received (optional analytics)
    if (notificationId != null) {
      _markNotificationReceived(notificationId);
    }
  }

  /// Show in-app notification (when app is active)
  static void _showInAppNotification(RemoteMessage message) {
    // Use your preferred in-app notification package
    // Example with flutter_local_notifications or overlay

    String title = message.notification?.title ?? message.data['title'] ?? 'New Notification';
    String body = message.notification?.body ?? message.data['message'] ?? '';

    // Show snackbar, dialog, or overlay notification
    // Implementation depends on your UI framework

    print('🔔 Showing in-app notification: $title - $body');
  }

  /// Handle deep link navigation
  static void _handleDeepLink(String deepLink, Map<String, dynamic> data) {
    print('🔗 Handling deep link: $deepLink');

    // Parse deep link and navigate
    if (deepLink.startsWith('realestate://')) {
      String path = deepLink.replaceFirst('realestate://', '');
      _navigateToScreen(path, data);
    }
  }

  /// Navigate to specific screen based on deep link
  static void _navigateToScreen(String path, Map<String, dynamic> data) {
    // Get your navigation service/context
    // NavigationService navigationService = GetIt.instance<NavigationService>();

    List<String> pathSegments = path.split('/');
    String screen = pathSegments[0];

    switch (screen) {
      case 'property':
        if (pathSegments.length > 1) {
          String propertyId = pathSegments[1];
          // navigationService.navigateToPropertyDetails(propertyId);
          print('📱 Navigate to property: $propertyId');
        }
        break;

      case 'messages':
        if (pathSegments.length > 1) {
          String threadId = pathSegments[1];
          // navigationService.navigateToMessageThread(threadId);
          print('📱 Navigate to message thread: $threadId');
        } else {
          // navigationService.navigateToMessages();
          print('📱 Navigate to messages');
        }
        break;

      case 'inquiries':
        // navigationService.navigateToInquiries();
        print('📱 Navigate to inquiries');
        break;

      case 'my-properties':
        if (pathSegments.length > 1) {
          String propertyId = pathSegments[1];
          // navigationService.navigateToMyProperty(propertyId);
          print('📱 Navigate to my property: $propertyId');
        } else {
          // navigationService.navigateToMyProperties();
          print('📱 Navigate to my properties');
        }
        break;

      case 'invoices':
        if (pathSegments.length > 1) {
          String invoiceId = pathSegments[1];
          // navigationService.navigateToInvoice(invoiceId);
          print('📱 Navigate to invoice: $invoiceId');
        } else {
          // navigationService.navigateToInvoices();
          print('📱 Navigate to invoices');
        }
        break;

      case 'reviews':
        // navigationService.navigateToReviews();
        print('📱 Navigate to reviews');
        break;

      case 'search':
        // navigationService.navigateToSearch();
        print('📱 Navigate to search');
        break;

      case 'notifications':
      default:
        // navigationService.navigateToNotifications();
        print('📱 Navigate to notifications');
        break;
    }
  }

  /// Mark notification as received (optional analytics)
  static Future<void> _markNotificationReceived(String notificationId) async {
    try {
      String? userToken = await AuthService.getToken();
      if (userToken == null) return;

      await http.post(
        Uri.parse('$API_BASE/notifications/$notificationId/read'),
        headers: {
          'Authorization': 'Bearer $userToken',
          'Content-Type': 'application/json',
        },
      );

      print('✅ Notification marked as received: $notificationId');
    } catch (e) {
      print('❌ Error marking notification as received: $e');
    }
  }

  /// Get unique device identifier
  static Future<String> _getDeviceId() async {
    DeviceInfoPlugin deviceInfo = DeviceInfoPlugin();

    if (Platform.isAndroid) {
      AndroidDeviceInfo androidInfo = await deviceInfo.androidInfo;
      return androidInfo.id; // Unique Android ID
    } else if (Platform.isIOS) {
      IosDeviceInfo iosInfo = await deviceInfo.iosInfo;
      return iosInfo.identifierForVendor ?? 'ios_device'; // iOS Vendor ID
    }

    return 'unknown_device';
  }

  /// Get current FCM token
  static Future<String?> getCurrentToken() async {
    return await _firebaseMessaging.getToken();
  }

  /// Subscribe to topic (optional)
  static Future<void> subscribeToTopic(String topic) async {
    await _firebaseMessaging.subscribeToTopic(topic);
    print('✅ Subscribed to topic: $topic');
  }

  /// Unsubscribe from topic (optional)
  static Future<void> unsubscribeFromTopic(String topic) async {
    await _firebaseMessaging.unsubscribeFromTopic(topic);
    print('✅ Unsubscribed from topic: $topic');
  }
}
```

### **3. Integration in Your App**

#### In your login flow:

```dart
class AuthService {
  static Future<bool> login(String email, String password) async {
    // Your login logic here
    bool loginSuccess = await performLogin(email, password);

    if (loginSuccess) {
      // Register device for push notifications
      String? fcmToken = await PushNotificationService.getCurrentToken();
      await PushNotificationService.registerDeviceWithAPI(fcmToken);
    }

    return loginSuccess;
  }

  static Future<void> logout() async {
    // Unregister device
    await PushNotificationService.unregisterDevice();

    // Your logout logic here
    await performLogout();
  }
}
```

#### In your main app widget:

```dart
class MyApp extends StatefulWidget {
  @override
  _MyAppState createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);

    // Check if user is already logged in and register device
    _checkLoginStatus();
  }

  Future<void> _checkLoginStatus() async {
    bool isLoggedIn = await AuthService.isLoggedIn();
    if (isLoggedIn) {
      String? fcmToken = await PushNotificationService.getCurrentToken();
      await PushNotificationService.registerDeviceWithAPI(fcmToken);
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);

    if (state == AppLifecycleState.resumed) {
      // App became active - check for notifications
      _checkPendingNotifications();
    }
  }

  Future<void> _checkPendingNotifications() async {
    // Check if app was opened from notification when terminated
    RemoteMessage? initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      // Handle the notification
      PushNotificationService._handleMessage(initialMessage, isBackground: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      // Your app configuration
    );
  }
}
```

---

## 🔧 Backend Setup (WordPress/Houzez)

### **1. Configure Push Notification Settings**

Add these settings to your WordPress admin (Houzez API Settings):

```php
// Push notification settings
add_option('houzez_api_push_enabled', '1');
add_option('houzez_api_push_service', 'firebase'); // or 'onesignal'
add_option('houzez_api_firebase_server_key', 'YOUR_FIREBASE_SERVER_KEY');
```

### **2. Firebase Server Key Setup**

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project
3. Go to **Project Settings** > **Cloud Messaging**
4. Copy the **Server key**
5. Add it to WordPress: **Houzez API Settings** > **Push Notifications**

### **3. Test Push Notification Setup**

Create a test script in your theme's `functions.php`:

```php
// Test push notification (remove after testing)
add_action('wp_ajax_test_push_notification', 'test_push_notification');
function test_push_notification() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $user_id = get_current_user_id();

    // Create a test notification
    $notification_id = wp_insert_post([
        'post_type' => 'houzez_notification',
        'post_title' => 'Test Notification',
        'post_content' => 'This is a test push notification',
        'post_status' => 'publish',
        'post_author' => $user_id,
    ]);

    if ($notification_id) {
        // Add metadata
        update_post_meta($notification_id, 'user_id', $user_id);
        update_post_meta($notification_id, 'notification_type', 'system_update');
        update_post_meta($notification_id, 'priority', 'high');
        update_post_meta($notification_id, 'read_status', '0');

        // Trigger push notification
        do_action('houzez_api_notification_created', $notification_id, $user_id, 'system_update', [
            'test' => true,
            'message' => 'Test push notification from WordPress'
        ]);

        echo 'Test notification created and pushed!';
    } else {
        echo 'Failed to create test notification';
    }

    wp_die();
}

// Add test button to admin bar
add_action('admin_bar_menu', 'add_test_push_button', 100);
function add_test_push_button($wp_admin_bar) {
    if (!current_user_can('manage_options')) return;

    $wp_admin_bar->add_node([
        'id' => 'test_push',
        'title' => 'Test Push',
        'href' => admin_url('admin-ajax.php?action=test_push_notification'),
        'meta' => ['target' => '_blank']
    ]);
}
```

---

## 📱 Automatic Notification Triggers

The system automatically sends push notifications for these events:

### **🏠 Property & Listings**

-   Property saved to favorites
-   Property matches search criteria
-   Property price decreased
-   Property status updated
-   Listing approved/disapproved/expired

### **💬 Communication**

-   New inquiry received
-   Direct message received
-   Agent contact form submitted
-   Tour scheduling request
-   Review received

### **💰 Payments & Finance**

-   Payment received/confirmed
-   Wire transfer initiated
-   Recurring payment processed
-   Package activated/purchased

### **👤 User Management**

-   User account approved/declined
-   Verification status updated
-   Agent assigned
-   Membership cancelled

### **📋 System & Admin**

-   Document uploaded
-   System updates
-   Reports received

---

## 🔗 Deep Link Navigation

The system provides intelligent deep links for direct navigation:

### **Deep Link Format**

```
realestate://[screen]/[id]?[parameters]
```

### **Supported Deep Links**

| Deep Link                        | Action                | Flutter Navigation                          |
| -------------------------------- | --------------------- | ------------------------------------------- |
| `realestate://property/123`      | View property details | `Navigator.pushNamed('/property/123')`      |
| `realestate://messages/456`      | Open message thread   | `Navigator.pushNamed('/messages/456')`      |
| `realestate://messages`          | Open messages list    | `Navigator.pushNamed('/messages')`          |
| `realestate://inquiries`         | View inquiries        | `Navigator.pushNamed('/inquiries')`         |
| `realestate://my-properties/789` | View my property      | `Navigator.pushNamed('/my-properties/789')` |
| `realestate://my-properties`     | My properties list    | `Navigator.pushNamed('/my-properties')`     |
| `realestate://invoices/101`      | View invoice          | `Navigator.pushNamed('/invoices/101')`      |
| `realestate://invoices`          | Invoices list         | `Navigator.pushNamed('/invoices')`          |
| `realestate://reviews`           | View reviews          | `Navigator.pushNamed('/reviews')`           |
| `realestate://search`            | Open search           | `Navigator.pushNamed('/search')`            |
| `realestate://notifications`     | Notifications         | `Navigator.pushNamed('/notifications')`     |

---

## 🎨 Rich Push Notification Payload

### **Complete Payload Structure**

```json
{
    "title": "New Property Inquiry",
    "message": "John Doe contacted you about Downtown Apartment",
    "notification_id": 123,
    "type": "inquiry_received",
    "priority": "high",
    "timestamp": 1705312200,
    "created_at": "2024-01-15 10:30:00",
    "screen": "inquiries",
    "deep_link": "realestate://inquiries",

    // Rich data for UI
    "property_id": 789,
    "property_title": "Downtown Apartment",
    "sender_name": "John Doe",
    "sender_email": "john@example.com",
    "sender_phone": "+1234567890",

    // Complete data object
    "data": {
        "type": "inquiry_received",
        "sender_name": "John Doe",
        "sender_email": "john@example.com",
        "sender_phone": "+1234567890",
        "property_id": 789,
        "property_title": "Downtown Apartment",
        "sender_message": "I'm interested in this property"
    }
}
```

### **Flutter Usage of Payload**

```dart
void _handleMessage(RemoteMessage message, {required bool isBackground}) {
  Map<String, dynamic> data = message.data;

  // Extract common fields
  String? title = data['title'];
  String? messageText = data['message'];
  String? type = data['type'];
  String? priority = data['priority'];

  // Extract specific data
  String? propertyId = data['property_id'];
  String? senderName = data['sender_name'];
  String? deepLink = data['deep_link'];

  // Build rich notification UI
  if (!isBackground) {
    _showRichNotification(
      title: title,
      message: messageText,
      senderName: senderName,
      propertyId: propertyId,
      priority: priority,
      onTap: () => _handleDeepLink(deepLink, data),
    );
  }
}
```

---

## ⚙️ User Preferences & Settings

### **Flutter Preferences Management**

```dart
class NotificationPreferences {
  static const String API_BASE = 'https://yourdomain.com/wp-json/houzez-api/v1';

  /// Get user notification preferences
  static Future<Map<String, dynamic>?> getPreferences() async {
    String? token = await AuthService.getToken();
    if (token == null) return null;

    try {
      final response = await http.get(
        Uri.parse('$API_BASE/notifications/preferences'),
        headers: {
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['data'];
      }
    } catch (e) {
      print('Error getting preferences: $e');
    }

    return null;
  }

  /// Update notification preferences
  static Future<bool> updatePreferences({
    bool? pushEnabled,
    bool? emailEnabled,
    String? emailFrequency,
    List<String>? disabledTypes,
    Map<String, dynamic>? quietHours,
  }) async {
    String? token = await AuthService.getToken();
    if (token == null) return false;

    Map<String, dynamic> preferences = {};
    if (pushEnabled != null) preferences['push_enabled'] = pushEnabled;
    if (emailEnabled != null) preferences['email_enabled'] = emailEnabled;
    if (emailFrequency != null) preferences['email_frequency'] = emailFrequency;
    if (disabledTypes != null) preferences['disabled_types'] = disabledTypes;
    if (quietHours != null) preferences['quiet_hours'] = quietHours;

    try {
      final response = await http.post(
        Uri.parse('$API_BASE/notifications/preferences'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode(preferences),
      );

      return response.statusCode == 200;
    } catch (e) {
      print('Error updating preferences: $e');
      return false;
    }
  }
}
```

### **Settings UI Example**

```dart
class NotificationSettingsPage extends StatefulWidget {
  @override
  _NotificationSettingsPageState createState() => _NotificationSettingsPageState();
}

class _NotificationSettingsPageState extends State<NotificationSettingsPage> {
  bool pushEnabled = true;
  bool emailEnabled = true;
  String emailFrequency = 'instant';
  List<String> disabledTypes = [];

  @override
  void initState() {
    super.initState();
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    Map<String, dynamic>? prefs = await NotificationPreferences.getPreferences();
    if (prefs != null) {
      setState(() {
        pushEnabled = prefs['push_enabled'] ?? true;
        emailEnabled = prefs['email_enabled'] ?? true;
        emailFrequency = prefs['email_frequency'] ?? 'instant';
        disabledTypes = List<String>.from(prefs['disabled_types'] ?? []);
      });
    }
  }

  Future<void> _savePreferences() async {
    bool success = await NotificationPreferences.updatePreferences(
      pushEnabled: pushEnabled,
      emailEnabled: emailEnabled,
      emailFrequency: emailFrequency,
      disabledTypes: disabledTypes,
    );

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Preferences saved successfully')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Notification Settings')),
      body: ListView(
        children: [
          SwitchListTile(
            title: Text('Push Notifications'),
            subtitle: Text('Receive push notifications on this device'),
            value: pushEnabled,
            onChanged: (value) {
              setState(() => pushEnabled = value);
              _savePreferences();
            },
          ),

          SwitchListTile(
            title: Text('Email Notifications'),
            subtitle: Text('Receive notifications via email'),
            value: emailEnabled,
            onChanged: (value) {
              setState(() => emailEnabled = value);
              _savePreferences();
            },
          ),

          ListTile(
            title: Text('Email Frequency'),
            subtitle: Text(emailFrequency.toUpperCase()),
            trailing: DropdownButton<String>(
              value: emailFrequency,
              items: [
                DropdownMenuItem(value: 'instant', child: Text('Instant')),
                DropdownMenuItem(value: 'daily', child: Text('Daily Digest')),
                DropdownMenuItem(value: 'weekly', child: Text('Weekly Digest')),
              ],
              onChanged: (value) {
                if (value != null) {
                  setState(() => emailFrequency = value);
                  _savePreferences();
                }
              },
            ),
          ),

          // Add more settings as needed
        ],
      ),
    );
  }
}
```

---

## 🔍 Troubleshooting

### **Common Issues & Solutions**

#### **1. Push Notifications Not Received**

**Check Flutter App:**

```dart
// Debug FCM token
String? token = await FirebaseMessaging.instance.getToken();
print('FCM Token: $token');

// Check if device is registered
bool registered = await PushNotificationService.registerDeviceWithAPI(token);
print('Device registered: $registered');
```

**Check WordPress Backend:**

```php
// Debug user's registered devices
$user_id = get_current_user_id();
$devices = get_user_meta($user_id, 'houzez_device_tokens', true);
error_log('User devices: ' . print_r($devices, true));

// Check push notification settings
$push_enabled = get_option('houzez_api_push_enabled');
$firebase_key = get_option('houzez_api_firebase_server_key');
error_log("Push enabled: $push_enabled, Firebase key set: " . (!empty($firebase_key) ? 'Yes' : 'No'));
```

#### **2. Deep Links Not Working**

```dart
// Test deep link handling
void _testDeepLinks() {
  List<String> testLinks = [
    'realestate://property/123',
    'realestate://messages/456',
    'realestate://inquiries',
  ];

  for (String link in testLinks) {
    print('Testing deep link: $link');
    PushNotificationService._handleDeepLink(link, {});
  }
}
```

#### **3. Device Not Registering**

```dart
// Check all registration requirements
Future<void> _debugRegistration() async {
  // Check authentication
  String? userToken = await AuthService.getToken();
  print('User token: ${userToken != null ? "Available" : "Missing"}');

  // Check FCM token
  String? fcmToken = await FirebaseMessaging.instance.getToken();
  print('FCM token: ${fcmToken != null ? "Available" : "Missing"}');

  // Check device ID
  String deviceId = await PushNotificationService._getDeviceId();
  print('Device ID: $deviceId');

  // Check permissions
  NotificationSettings settings = await FirebaseMessaging.instance.getNotificationSettings();
  print('Permission status: ${settings.authorizationStatus}');
}
```

#### **4. Background Message Handling**

Add to `android/app/src/main/kotlin/MainActivity.kt`:

```kotlin
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugins.GeneratedPluginRegistrant

class MainActivity: FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        GeneratedPluginRegistrant.registerWith(flutterEngine)
    }
}
```

Add background handler in `main.dart`:

```dart
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  print('Background message: ${message.messageId}');
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();

  // Set background message handler
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

  await PushNotificationService.initialize();
  runApp(MyApp());
}
```

---

## 📊 Analytics & Monitoring

### **Track Notification Performance**

```dart
class NotificationAnalytics {
  static Future<void> trackNotificationReceived(String notificationId, String type) async {
    // Track with your analytics service
    // FirebaseAnalytics.instance.logEvent(name: 'notification_received', parameters: {
    //   'notification_id': notificationId,
    //   'notification_type': type,
    // });
  }

  static Future<void> trackNotificationOpened(String notificationId, String type) async {
    // Track notification opens
    // FirebaseAnalytics.instance.logEvent(name: 'notification_opened', parameters: {
    //   'notification_id': notificationId,
    //   'notification_type': type,
    // });
  }

  static Future<void> trackDeepLinkNavigation(String deepLink, String source) async {
    // Track deep link usage
    // FirebaseAnalytics.instance.logEvent(name: 'deep_link_opened', parameters: {
    //   'deep_link': deepLink,
    //   'source': source,
    // });
  }
}
```

### **WordPress Analytics**

```php
// Track notification delivery
add_action('houzez_api_notification_created', 'track_notification_sent', 20, 4);
function track_notification_sent($notification_id, $user_id, $type, $extra_data) {
    // Log to custom table or analytics service
    global $wpdb;

    $wpdb->insert(
        $wpdb->prefix . 'notification_analytics',
        [
            'notification_id' => $notification_id,
            'user_id' => $user_id,
            'type' => $type,
            'sent_at' => current_time('mysql'),
            'status' => 'sent'
        ]
    );
}
```

---

## 🚀 Advanced Features

### **1. Custom Notification Actions**

```dart
// Handle custom actions in notifications
void _handleNotificationAction(String action, Map<String, dynamic> data) {
  switch (action) {
    case 'reply':
      _openQuickReply(data);
      break;
    case 'call':
      _makePhoneCall(data['phone']);
      break;
    case 'view_property':
      _navigateToProperty(data['property_id']);
      break;
    case 'accept_booking':
      _acceptBooking(data['booking_id']);
      break;
  }
}
```

### **2. Notification Grouping**

```dart
// Group notifications by type
class NotificationGrouper {
  static Map<String, List<RemoteMessage>> groupedNotifications = {};

  static void addToGroup(RemoteMessage message) {
    String type = message.data['type'] ?? 'general';

    if (!groupedNotifications.containsKey(type)) {
      groupedNotifications[type] = [];
    }

    groupedNotifications[type]!.add(message);

    // Show grouped notification
    _showGroupedNotification(type);
  }

  static void _showGroupedNotification(String type) {
    List<RemoteMessage> messages = groupedNotifications[type] ?? [];

    if (messages.length > 1) {
      // Show grouped notification
      String title = '${messages.length} ${type} notifications';
      String body = 'Tap to view all ${type} notifications';

      // Use your notification service to show grouped notification
    }
  }
}
```

### **3. Offline Support**

```dart
// Cache notifications for offline viewing
class NotificationCache {
  static const String _cacheKey = 'cached_notifications';

  static Future<void> cacheNotification(RemoteMessage message) async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    List<String> cached = prefs.getStringList(_cacheKey) ?? [];

    cached.add(jsonEncode(message.data));

    // Keep only last 50 notifications
    if (cached.length > 50) {
      cached = cached.sublist(cached.length - 50);
    }

    await prefs.setStringList(_cacheKey, cached);
  }

  static Future<List<Map<String, dynamic>>> getCachedNotifications() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    List<String> cached = prefs.getStringList(_cacheKey) ?? [];

    return cached.map((e) => jsonDecode(e) as Map<String, dynamic>).toList();
  }
}
```
