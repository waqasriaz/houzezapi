# Push Notification Setup Guide

This guide explains how to set up push notifications for the Houzez API notification system.

## Overview

The push notification system allows agents to receive instant notifications on their mobile devices when:

-   Someone contacts them about a property
-   A showing is scheduled
-   A payment is received
-   Messages are sent
-   And more...

## How It Works

### Notification Flow

```
1. User contacts Agent about Property
   ↓
2. Houzez triggers email notification
   ↓
3. Houzez API captures notification
   ↓
4. Database notification created
   ↓
5. Push notification sent to agent's device
```

### Multi-Agent Scenario

In your scenario with 5 agents, each with 5 properties:

-   ✅ Agent 01 receives push notifications only for their properties
-   ✅ Agent 02 receives push notifications only for their properties
-   ✅ Each agent's notifications are completely isolated
-   ✅ Agents can manage their notification preferences individually

## Setup Instructions

### 1. Enable Push Notifications

1. Go to **WordPress Admin → Houzez API → Settings**
2. Find the **Push Notification Settings** section
3. Check **Enable Push Notifications**
4. Select your push service (OneSignal or Firebase)

### 2. OneSignal Setup (Recommended)

#### A. Create OneSignal Account

1. Go to [OneSignal.com](https://onesignal.com) and create an account
2. Create a new app for your Houzez site
3. Configure for both iOS and Android

#### B. Get OneSignal Credentials

1. In OneSignal Dashboard, go to **Settings → Keys & IDs**
2. Copy your **App ID**
3. Copy your **REST API Key**

#### C. Configure in WordPress

1. Go to **WordPress Admin → Houzez API → Settings**
2. Enter your **OneSignal App ID**
3. Enter your **OneSignal API Key**
4. Save settings

### 3. Firebase Setup (Alternative)

#### A. Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com)
2. Create a new project
3. Add your iOS and Android apps

#### B. Get Firebase Server Key

1. In Firebase Console, go to **Project Settings → Cloud Messaging**
2. Copy your **Server Key**

#### C. Configure in WordPress

1. Go to **WordPress Admin → Houzez API → Settings**
2. Enter your **Firebase Server Key**
3. Save settings

## Mobile App Integration

### 1. Device Registration

Your mobile app needs to register devices for push notifications:

```bash
POST /wp-json/houzez-api/v1/notifications/register-device
Authorization: Bearer YOUR_JWT_TOKEN

# For OneSignal
{
    "platform": "onesignal",
    "player_id": "OneSignal-Player-ID",
    "device_id": "unique-device-id"
}

# For Firebase (iOS)
{
    "platform": "ios",
    "token": "FCM-device-token",
    "device_id": "unique-device-id"
}

# For Firebase (Android)
{
    "platform": "android",
    "token": "FCM-device-token",
    "device_id": "unique-device-id"
}
```

### 2. Handle Push Notifications

When a push notification is received, it contains:

```json
{
    "title": "New Property Inquiry",
    "message": "John Doe is interested in your property...",
    "notification_id": 123,
    "type": "inquiry_received",
    "property_id": "456",
    "property_title": "Beautiful 3BR House",
    "sender_name": "John Doe",
    "timestamp": 1234567890
}
```

### 3. Deep Linking

Use the notification data to navigate users directly to:

-   The notification details (`notification_id`)
-   The property page (`property_id`)
-   The messages section (`type: messages`)

## User Preferences

Users can manage their push notification preferences:

```bash
# Get preferences
GET /wp-json/houzez-api/v1/notifications/preferences

# Update preferences
POST /wp-json/houzez-api/v1/notifications/preferences
{
    "push_enabled": true,
    "disabled_types": ["property_saved", "report"]
}
```

## Testing Push Notifications

### 1. Manual Test

1. Go to **WordPress Admin → Houzez API → Diagnostics**
2. In Section 4, click **Test houzez_send_notification Hook**
3. Check if push notification is received on registered devices

### 2. Real Test

1. Have someone contact an agent through a property listing
2. Agent should receive:
    - Database notification (visible in API)
    - Push notification on mobile device

## Troubleshooting

### Push Notifications Not Working?

1. **Check Device Registration**

    - Ensure device is properly registered
    - Check user meta for `houzez_device_tokens`

2. **Check Push Service Configuration**

    - Verify API keys are correct
    - Test with OneSignal/Firebase dashboard

3. **Check Debug Logs**

    - Enable `WP_DEBUG` and `WP_DEBUG_LOG`
    - Look for errors starting with "Houzez API - OneSignal Error" or "Houzez API - FCM Error"

4. **Common Issues**
    - User has disabled push notifications
    - Invalid API credentials
    - Device token expired
    - Network connectivity issues

### Debug Information

To see registered devices for a user:

```php
$user_id = 123; // Replace with actual user ID
$devices = get_user_meta($user_id, 'houzez_device_tokens', true);
print_r($devices);
```

## Best Practices

1. **Token Management**

    - Refresh device tokens periodically
    - Remove old/invalid tokens
    - Handle token expiration gracefully

2. **User Experience**

    - Request push permission at the right time
    - Provide clear value proposition
    - Allow users to manage preferences

3. **Performance**
    - Batch notifications when possible
    - Implement retry logic for failed sends
    - Monitor delivery rates

## Custom Integration

If you need custom push notification handling:

```php
add_action('houzez_send_custom_push_notification', function($device_tokens, $push_data, $user_id) {
    // Your custom push notification logic
    // Send to your own push service
}, 10, 3);
```

## Support

For issues or questions:

1. Check the diagnostics page
2. Review error logs
3. Test with manual notifications
4. Verify agent email associations
