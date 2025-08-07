# Houzez API - Notification System Documentation

## Base URL

```
https://yourdomain.com/wp-json/houzez-api/v1
```

## Authentication

All endpoints require JWT token authentication via `Authorization` header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

## Available Endpoints

| Method | Endpoint                         | Description                   | Auth Required |
| ------ | -------------------------------- | ----------------------------- | ------------- |
| GET    | `/notifications`                 | Get user notifications        | ✅ User       |
| GET    | `/notifications/{id}`            | Get single notification       | ✅ User       |
| POST   | `/notifications`                 | Create notification           | ✅ Admin      |
| POST   | `/notifications/{id}/read`       | Mark notification as read     | ✅ User       |
| POST   | `/notifications/mark-read`       | Mark multiple as read         | ✅ User       |
| POST   | `/notifications/mark-all-read`   | Mark all as read              | ✅ User       |
| DELETE | `/notifications/{id}`            | Delete notification           | ✅ User       |
| POST   | `/notifications/delete-multiple` | Delete multiple notifications | ✅ User       |
| GET    | `/notifications/unread-count`    | Get unread count              | ✅ User       |
| GET    | `/notifications/preferences`     | Get user preferences          | ✅ User       |
| POST   | `/notifications/preferences`     | Update preferences            | ✅ User       |
| POST   | `/notifications/subscribe`       | Subscribe to push             | ✅ User       |
| POST   | `/notifications/unsubscribe`     | Unsubscribe from push         | ✅ User       |
| POST   | `/notifications/register-device` | Register device for push      | ✅ User       |

---

## 📋 1. Get Notifications

**GET** `/notifications`

Retrieve paginated list of notifications for the authenticated user.

### Query Parameters

| Parameter   | Type    | Default | Description                                           |
| ----------- | ------- | ------- | ----------------------------------------------------- |
| `page`      | integer | 1       | Page number (min: 1)                                  |
| `per_page`  | integer | 20      | Items per page (min: 1, max: 100)                     |
| `type`      | string  | -       | Filter by notification type                           |
| `status`    | string  | all     | Filter by read status: `all`, `read`, `unread`        |
| `priority`  | string  | -       | Filter by priority: `low`, `medium`, `high`, `urgent` |
| `category`  | string  | -       | Filter by category                                    |
| `date_from` | string  | -       | Filter from date (YYYY-MM-DD)                         |
| `date_to`   | string  | -       | Filter to date (YYYY-MM-DD)                           |

### Example Request

```bash
curl -X GET "https://yourdomain.com/wp-json/houzez-api/v1/notifications?page=1&per_page=10&status=unread" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Example Response

```json
{
    "success": true,
    "data": {
        "notifications": [
            {
                "id": 123,
                "title": "New Property Inquiry",
                "message": "John Doe contacted you about Downtown Apartment",
                "description": "John Doe contacted you about Downtown Apartment",
                "type": "inquiry_received",
                "type_label": "Inquiry Received",
                "priority": "high",
                "is_read": false,
                "read_at": null,
                "date": "2024-01-15 10:30:00",
                "created_at": "2024-01-15 10:30:00",
                "created_at_gmt": "2024-01-15 15:30:00",
                "timestamp": 1705312200,
                "time_ago": "2 hours ago",
                "user_id": 456,
                "user_email": "agent@example.com",
                "categories": ["communication"],
                "category": "communication",
                "data": {
                    "sender_name": "John Doe",
                    "sender_email": "john@example.com",
                    "sender_phone": "+1234567890",
                    "property_id": 789,
                    "property_title": "Downtown Apartment"
                },
                "extra_data": {
                    "type": "inquiry_received",
                    "sender_name": "John Doe",
                    "sender_email": "john@example.com",
                    "sender_phone": "+1234567890",
                    "property_id": 789,
                    "property_title": "Downtown Apartment",
                    "sender_message": "I'm interested in this property"
                },
                "action": {
                    "type": "navigate",
                    "label": "View Details",
                    "screen": "inquiries",
                    "data": {
                        "type": "inquiry_received",
                        "sender_name": "John Doe",
                        "property_id": 789
                    }
                },
                "notification_id": 123,
                "badge_count": 5,
                "image_url": null,
                "deep_link": "realestate://inquiries"
            }
        ],
        "total": 25,
        "pages": 3,
        "current_page": 1,
        "stats": {
            "total": 25,
            "unread": 5,
            "read": 20,
            "by_priority": {
                "low": 5,
                "medium": 15,
                "high": 4,
                "urgent": 1
            },
            "by_category": {
                "communication": 10,
                "properties": 8,
                "user_management": 4,
                "financial": 3
            }
        }
    }
}
```

---

## 📝 2. Get Single Notification

**GET** `/notifications/{id}`

Retrieve a specific notification and mark it as read.

### Path Parameters

-   `id` (integer) - Notification ID

### Example Request

```bash
curl -X GET "https://yourdomain.com/wp-json/houzez-api/v1/notifications/123" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Example Response

```json
{
    "success": true,
    "data": {
        "id": 123,
        "title": "New Property Inquiry",
        "message": "John Doe contacted you about Downtown Apartment",
        "type": "inquiry_received",
        "type_label": "Inquiry Received",
        "priority": "high",
        "is_read": true,
        "read_at": "2024-01-15 12:30:00",
        "date": "2024-01-15 10:30:00",
        "timestamp": 1705312200,
        "user_id": 456,
        "categories": ["communication"],
        "extra_data": {
            "type": "inquiry_received",
            "sender_name": "John Doe",
            "sender_email": "john@example.com",
            "property_id": 789,
            "property_title": "Downtown Apartment"
        },
        "action": {
            "type": "navigate",
            "label": "View Details",
            "screen": "inquiries"
        },
        "deep_link": "realestate://inquiries"
    }
}
```

---

## ➕ 3. Create Notification (Admin Only)

**POST** `/notifications`

Create a new notification. Requires admin privileges.

### Request Body

```json
{
    "user_id": 456,
    "user_email": "user@example.com",
    "title": "System Update",
    "message": "Your listing has been approved",
    "type": "listing_approved",
    "priority": "high",
    "data": {
        "property_id": 789,
        "property_title": "Downtown Apartment"
    }
}
```

### Required Fields

-   `title` (string) - Notification title
-   `message` (string) - Notification message
-   `type` (string) - Notification type (see available types below)

### Optional Fields

-   `user_id` (integer) - Target user ID
-   `user_email` (string) - Target user email (alternative to user_id)
-   `priority` (string) - Priority level: `low`, `medium`, `high`, `urgent`
-   `data` (object) - Additional notification data

### Example Response

```json
{
    "success": true,
    "data": {
        "id": 124,
        "message": "Notification created successfully"
    }
}
```

---

## ✅ 4. Mark as Read

**POST** `/notifications/{id}/read`

Mark a specific notification as read.

### Path Parameters

-   `id` (integer) - Notification ID

### Example Request

```bash
curl -X POST "https://yourdomain.com/wp-json/houzez-api/v1/notifications/123/read" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Example Response

```json
{
    "success": true,
    "message": "Notification marked as read"
}
```

---

## ✅ 5. Mark Multiple as Read

**POST** `/notifications/mark-read`

Mark multiple notifications as read.

### Request Body

```json
{
    "ids": [123, 124, 125]
}
```

### Required Fields

-   `ids` (array) - Array of notification IDs

### Example Response

```json
{
    "success": true,
    "message": "3 notifications marked as read",
    "updated": 3
}
```

---

## ✅ 6. Mark All as Read

**POST** `/notifications/mark-all-read`

Mark all unread notifications as read for the current user.

### Example Request

```bash
curl -X POST "https://yourdomain.com/wp-json/houzez-api/v1/notifications/mark-all-read" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Example Response

```json
{
    "success": true,
    "message": "5 notifications marked as read",
    "updated": 5
}
```

---

## 🗑️ 7. Delete Notification

**DELETE** `/notifications/{id}`

Delete a specific notification.

### Path Parameters

-   `id` (integer) - Notification ID

### Example Request

```bash
curl -X DELETE "https://yourdomain.com/wp-json/houzez-api/v1/notifications/123" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Example Response

```json
{
    "success": true,
    "message": "Notification deleted successfully"
}
```

---

## 🗑️ 8. Delete Multiple Notifications

**POST** `/notifications/delete-multiple`

Delete multiple notifications.

### Request Body

```json
{
    "ids": [123, 124, 125]
}
```

### Required Fields

-   `ids` (array) - Array of notification IDs

### Example Response

```json
{
    "success": true,
    "message": "3 notifications deleted",
    "deleted": 3
}
```

---

## 🔢 9. Get Unread Count

**GET** `/notifications/unread-count`

Get the count of unread notifications for the current user.

### Example Request

```bash
curl -X GET "https://yourdomain.com/wp-json/houzez-api/v1/notifications/unread-count" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Example Response

```json
{
    "success": true,
    "data": {
        "count": 5,
        "by_priority": {
            "low": 1,
            "medium": 2,
            "high": 1,
            "urgent": 1
        },
        "by_category": {
            "communication": 3,
            "properties": 1,
            "financial": 1
        }
    }
}
```

---

## ⚙️ 10. Get User Preferences

**GET** `/notifications/preferences`

Get notification preferences for the current user.

### Example Request

```bash
curl -X GET "https://yourdomain.com/wp-json/houzez-api/v1/notifications/preferences" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Example Response

```json
{
    "success": true,
    "data": {
        "push_enabled": true,
        "email_enabled": true,
        "email_frequency": "instant",
        "disabled_types": ["marketing_promotion"],
        "quiet_hours": {
            "enabled": true,
            "start": "22:00",
            "end": "08:00"
        }
    }
}
```

---

## ⚙️ 11. Update User Preferences

**POST** `/notifications/preferences`

Update notification preferences for the current user.

### Request Body

```json
{
    "push_enabled": true,
    "email_enabled": false,
    "email_frequency": "daily",
    "disabled_types": ["marketing_promotion", "system_update"],
    "quiet_hours": {
        "enabled": true,
        "start": "23:00",
        "end": "07:00"
    }
}
```

### Optional Fields

-   `push_enabled` (boolean) - Enable/disable push notifications
-   `email_enabled` (boolean) - Enable/disable email notifications
-   `email_frequency` (string) - Email frequency: `instant`, `daily`, `weekly`
-   `disabled_types` (array) - Array of disabled notification types
-   `quiet_hours` (object) - Quiet hours configuration

### Example Response

```json
{
    "success": true,
    "message": "Preferences updated successfully"
}
```

---

## 📱 12. Subscribe to Push Notifications

**POST** `/notifications/subscribe`

Subscribe to push notifications with device token.

### Request Body

```json
{
    "device_token": "FCM_DEVICE_TOKEN_HERE",
    "device_type": "android"
}
```

### Required Fields

-   `device_token` (string) - Device token from FCM/OneSignal
-   `device_type` (string) - Device type: `ios`, `android`, `web`

### Example Response

```json
{
    "success": true,
    "message": "Successfully subscribed to push notifications"
}
```

---

## 📱 13. Unsubscribe from Push Notifications

**POST** `/notifications/unsubscribe`

Unsubscribe from push notifications.

### Request Body

```json
{
    "device_type": "android"
}
```

### Required Fields

-   `device_type` (string) - Device type: `ios`, `android`, `web`

### Example Response

```json
{
    "success": true,
    "message": "Successfully unsubscribed from push notifications"
}
```

---

## 📱 14. Register Device for Push

**POST** `/notifications/register-device`

Register a device for push notifications (comprehensive device management).

### Request Body for FCM (Firebase)

```json
{
    "platform": "android",
    "token": "FCM_TOKEN_HERE",
    "device_id": "unique_device_identifier"
}
```

### Request Body for OneSignal

```json
{
    "platform": "onesignal",
    "player_id": "ONESIGNAL_PLAYER_ID",
    "device_id": "unique_device_identifier"
}
```

### Required Fields

-   `platform` (string) - Platform: `ios`, `android`, `onesignal`
-   `device_id` (string) - Unique device identifier
-   `token` (string) - Required for FCM platforms
-   `player_id` (string) - Required for OneSignal platform

### Example Response

```json
{
    "success": true,
    "message": "Device registered successfully"
}
```

---

## 📊 Notification Types

The system supports 50+ notification types organized by category:

### 🏠 Property & Listings

-   `property_saved` - Property saved to favorites
-   `property_matched` - Property matches search criteria
-   `property_price_drop` - Property price decreased
-   `property_status_change` - Property status updated
-   `property_matching` - Matching properties found
-   `price_update` - Price update notification

### 💬 Contact & Communication

-   `inquiry_received` - Contact form submission
-   `messages` - Direct message received
-   `message_received` - Message notification
-   `property_agent_contact` - Agent contact form
-   `property_schedule_tour` - Tour scheduling request
-   `contact_agent` - Agent contact
-   `contact_agency` - Agency contact
-   `contact_owner` - Owner contact
-   `property_report` - Property report
-   `review` - Review received
-   `review_received` - Review notification

### 📅 Scheduling & Tours

-   `showing_scheduled` - Tour scheduled
-   `showing_reminder` - Tour reminder

### 👤 User Management

-   `new_user_register` - New user registration
-   `admin_new_user_register` - Admin new user notification
-   `admin_user_register_approval` - User approval required
-   `user_approved` - User account approved
-   `user_declined` - User account declined
-   `user_suspended` - User account suspended
-   `verification_status` - Verification status update
-   `membership_cancelled` - Membership cancelled
-   `agent_assigned` - Agent assigned

### 💰 Payments & Finance

-   `payment_received` - Payment received
-   `payment_confirmation` - Payment confirmed
-   `new_wire_transfer` - Wire transfer initiated
-   `admin_new_wire_transfer` - Admin wire transfer
-   `recurring_payment` - Recurring payment processed
-   `purchase_activated_pack` - Package activated
-   `purchase_activated` - Purchase activated

### 📋 Listing Management

-   `listing_approved` - Listing approved
-   `listing_expired` - Listing expired
-   `listing_disapproved` - Listing disapproved
-   `paid_submission_listing` - Paid listing submitted
-   `admin_paid_submission_listing` - Admin paid listing
-   `featured_submission_listing` - Featured listing submitted
-   `admin_featured_submission_listing` - Admin featured listing
-   `free_submission_listing` - Free listing submitted
-   `admin_free_submission_listing` - Admin free listing
-   `admin_update_listing` - Admin listing update
-   `free_listing_expired` - Free listing expired
-   `featured_listing_expired` - Featured listing expired
-   `admin_expired_listings` - Expired listing resubmitted

### 🔧 Admin & System

-   `report` - Report received
-   `system_update` - System update
-   `document_uploaded` - Document uploaded

### 📢 Marketing & Matching

-   `marketing_promotion` - Marketing promotion
-   `matching_submissions` - Matching submissions found

---

## 🚨 Error Responses

### Authentication Error (401)

```json
{
    "code": "not_authenticated",
    "message": "Authentication required.",
    "data": {
        "status": 401
    }
}
```

### Permission Error (403)

```json
{
    "code": "forbidden",
    "message": "You do not have permission to perform this action.",
    "data": {
        "status": 403
    }
}
```

### Not Found Error (404)

```json
{
    "code": "not_found",
    "message": "Notification not found",
    "data": {
        "status": 404
    }
}
```

### Validation Error (400)

```json
{
    "code": "rest_invalid_param",
    "message": "Invalid parameter(s): ids",
    "data": {
        "status": 400,
        "params": {
            "ids": "ids is required and must be an array of integers."
        }
    }
}
```

---

## 🔗 Deep Links for Mobile Apps

The notification system provides deep links for direct navigation in mobile apps:

### Format

```
realestate://[screen]/[id]?[parameters]
```

### Examples

-   `realestate://property/123` - Navigate to property details
-   `realestate://messages/456` - Open message thread
-   `realestate://inquiries` - Open inquiries list
-   `realestate://my-properties/789` - View specific listing
-   `realestate://invoices/101` - View invoice details
-   `realestate://search` - Open search/matching
-   `realestate://reviews` - View reviews
-   `realestate://profile` - User profile
-   `realestate://settings` - App settings
-   `realestate://documents` - Document management
-   `realestate://reports` - Reports section
-   `realestate://notifications` - Default notifications screen

---

## 📱 Push Notification Payload

When push notifications are sent, they include comprehensive data:

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
    "property_id": 789,
    "sender_name": "John Doe",
    "sender_email": "john@example.com",
    "deep_link": "realestate://inquiries",
    "data": {
        "sender_name": "John Doe",
        "sender_email": "john@example.com",
        "property_id": 789,
        "property_title": "Downtown Apartment",
        "sender_message": "I'm interested in this property"
    }
}
```

---

## ⚡ Rate Limiting

-   **User endpoints**: 100 requests per minute per user
-   **Admin endpoints**: 200 requests per minute per admin
-   **Unread count**: 300 requests per minute (cached for performance)

---

## 🔧 Integration Examples

### Flutter/Dart Example

```dart
class NotificationService {
  static const String baseUrl = 'https://yourdomain.com/wp-json/houzez-api/v1';
  final String token;

  NotificationService(this.token);

  Future<NotificationResponse> getNotifications({
    int page = 1,
    int perPage = 20,
    String? status,
    String? type,
    String? priority,
  }) async {
    final queryParams = <String, String>{
      'page': page.toString(),
      'per_page': perPage.toString(),
      if (status != null) 'status': status,
      if (type != null) 'type': type,
      if (priority != null) 'priority': priority,
    };

    final uri = Uri.parse('$baseUrl/notifications').replace(
      queryParameters: queryParams,
    );

    final response = await http.get(
      uri,
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      return NotificationResponse.fromJson(jsonDecode(response.body));
    } else {
      throw Exception('Failed to load notifications');
    }
  }

  Future<void> markAsRead(int notificationId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/notifications/$notificationId/read'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to mark notification as read');
    }
  }

  Future<UnreadCountResponse> getUnreadCount() async {
    final response = await http.get(
      Uri.parse('$baseUrl/notifications/unread-count'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return UnreadCountResponse.fromJson(jsonDecode(response.body));
    } else {
      throw Exception('Failed to get unread count');
    }
  }

  Future<void> registerDevice({
    required String platform,
    required String deviceId,
    String? token,
    String? playerId,
  }) async {
    final body = <String, dynamic>{
      'platform': platform,
      'device_id': deviceId,
      if (token != null) 'token': token,
      if (playerId != null) 'player_id': playerId,
    };

    final response = await http.post(
      Uri.parse('$baseUrl/notifications/register-device'),
      headers: {
        'Authorization': 'Bearer ${this.token}',
        'Content-Type': 'application/json',
      },
      body: jsonEncode(body),
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to register device');
    }
  }
}
```

### JavaScript/TypeScript Example

```javascript
class NotificationAPI {
    constructor(token) {
        this.token = token;
        this.baseUrl = 'https://yourdomain.com/wp-json/houzez-api/v1';
    }

    async getNotifications(params = {}) {
        const url = new URL(`${this.baseUrl}/notifications`);

        // Add query parameters
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                url.searchParams.append(key, value.toString());
            }
        });

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Authorization: `Bearer ${this.token}`,
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    async getUnreadCount() {
        const response = await fetch(
            `${this.baseUrl}/notifications/unread-count`,
            {
                method: 'GET',
                headers: {
                    Authorization: `Bearer ${this.token}`,
                },
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    async markAsRead(notificationId) {
        const response = await fetch(
            `${this.baseUrl}/notifications/${notificationId}/read`,
            {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${this.token}`,
                    'Content-Type': 'application/json',
                },
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    async markMultipleAsRead(ids) {
        const response = await fetch(
            `${this.baseUrl}/notifications/mark-read`,
            {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${this.token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ids }),
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    async updatePreferences(preferences) {
        const response = await fetch(
            `${this.baseUrl}/notifications/preferences`,
            {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${this.token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(preferences),
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }
}

// Usage example
const notificationAPI = new NotificationAPI('your-jwt-token');

// Get unread notifications
notificationAPI
    .getNotifications({
        status: 'unread',
        per_page: 10,
    })
    .then((data) => {
        console.log('Unread notifications:', data.data.notifications);
    });

// Get unread count for badge
notificationAPI.getUnreadCount().then((data) => {
    document.getElementById('notification-badge').textContent = data.data.count;
});
```

### PHP Example (for server-side integration)

```php
class HouzezNotificationAPI {
    private $baseUrl;
    private $token;

    public function __construct($baseUrl, $token) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }

    public function getNotifications($params = []) {
        $url = $this->baseUrl . '/wp-json/houzez-api/v1/notifications';

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function createNotification($data) {
        $url = $this->baseUrl . '/wp-json/houzez-api/v1/notifications';

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
        ]);

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
```

---

## 🔄 Webhook Integration

For real-time notifications, you can set up webhooks to be notified when new notifications are created:

### Setting up Webhooks

```php
// Add this to your functions.php or plugin
add_action('houzez_api_notification_created', 'send_notification_webhook', 10, 4);

function send_notification_webhook($notification_id, $user_id, $type, $extra_data) {
    $webhook_url = 'https://your-webhook-endpoint.com/notifications';

    $payload = [
        'notification_id' => $notification_id,
        'user_id' => $user_id,
        'type' => $type,
        'extra_data' => $extra_data,
        'timestamp' => current_time('timestamp'),
    ];

    wp_remote_post($webhook_url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($payload),
    ]);
}
```

---

## 🔧 Advanced Configuration

### Custom Notification Types

```php
// Add custom notification types
add_filter('houzez_api_notification_types', function($types) {
    $types[] = 'custom_type';
    return $types;
});

// Add custom type labels
add_filter('houzez_api_notification_type_labels', function($labels) {
    $labels['custom_type'] = __('Custom Notification', 'your-textdomain');
    return $labels;
});
```

### Custom Categories

```php
// Add custom notification categories
add_action('houzez_api_notification_created', function($notification_id, $user_id, $type, $extra_data) {
    if ($type === 'custom_type') {
        wp_set_object_terms($notification_id, 'custom_category', 'notification_category');
    }
}, 10, 4);
```

---

This comprehensive documentation covers all notification endpoints, examples, and integration patterns for both mobile and web applications using the Houzez API notification system.
