# Houzez Payment API Endpoints

This document describes the payment-related API endpoints for the Houzez API plugin.

## Base URL

All endpoints are accessible under: `/wp-json/houzez-api/v1/payments/`

## Authentication

Some endpoints require authentication. Include the JWT token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

## Available Endpoints

### 1. Get Payment Mode

**Endpoint:** `GET /payments/mode`  
**Authentication:** Not required  
**Description:** Returns the current payment mode configuration

**Response:**

```json
{
    "success": true,
    "data": {
        "payment_mode": "membership",
        "payment_mode_label": "Membership",
        "available_modes": {
            "no": "No paid submission",
            "free_paid_listing": "Free (Pay For Featured)",
            "per_listing": "Per Listing",
            "membership": "Membership"
        }
    }
}
```

### 2. Get Payment Settings

**Endpoint:** `GET /payments/settings`  
**Authentication:** Not required  
**Description:** Returns comprehensive payment settings

**Response:**

```json
{
    "success": true,
    "data": {
        "payment_mode": "membership",
        "currency": "USD",
        "currency_symbol": "$",
        "currency_position": "before",
        "recurring_enabled": 1,
        "auto_recurring": 0,
        "per_listing_expire_unlimited": 0,
        "per_listing_expire_days": "30",
        "featured_expire_days": "30"
    }
}
```

### 3. Get Membership Packages

**Endpoint:** `GET /payments/packages`  
**Authentication:** Not required  
**Description:** Returns all available membership packages (only when membership mode is enabled)

**Response:**

```json
{
    "success": true,
    "data": {
        "packages": [
            {
                "id": 123,
                "title": "Basic Package",
                "description": "Package description",
                "price": 29.99,
                "tax_rate": 10,
                "total_price": 32.99,
                "formatted_price": "$29.99",
                "formatted_total_price": "$32.99",
                "listings_included": "5",
                "featured_included": "2",
                "unlimited_listings": "no",
                "billing_period": "month",
                "billing_frequency": "1",
                "visible": "yes",
                "popular": "no",
                "payment_page_link": "https://paymentpage-link"
            }
        ],
        "total_packages": 1
    }
}
```

### 4. Get Package Details

**Endpoint:** `GET /payments/packages/{id}`  
**Authentication:** Not required  
**Description:** Returns details for a specific package

**Parameters:**

-   `id` (required): Package ID

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 123,
        "title": "Basic Package",
        "description": "Package description",
        "price": 29.99,
        "tax_rate": 10,
        "total_price": 32.99,
        "formatted_price": "$29.99",
        "formatted_total_price": "$32.99",
        "listings_included": "5",
        "featured_included": "2",
        "unlimited_listings": "no",
        "billing_period": "month",
        "billing_frequency": "1",
        "visible": "yes",
        "popular": "no"
    }
}
```

### 5. Get Per Listing Prices

**Endpoint:** `GET /payments/per-listing`  
**Authentication:** Not required  
**Description:** Returns per listing pricing information (only when per listing mode is enabled)

**Response:**

```json
{
    "success": true,
    "data": {
        "payment_mode": "per_listing",
        "listing_price": 10.0,
        "featured_price": 5.0,
        "currency": "USD",
        "currency_symbol": "$",
        "currency_position": "before",
        "formatted_listing_price": "$10.00",
        "formatted_featured_price": "$5.00",
        "expire_days": "30",
        "expire_unlimited": 0
    }
}
```

### 6. Get User Payment Status

**Endpoint:** `GET /payments/user-status`  
**Authentication:** Required  
**Description:** Returns the current user's payment status and membership information

**Response (Membership Mode):**

```json
{
    "success": true,
    "data": {
        "user_id": 5,
        "payment_mode": "membership",
        "membership": {
            "package_id": "123",
            "package_activation": "2024-01-01 00:00:00",
            "remaining_listings": 3,
            "remaining_featured": 1,
            "has_active_package": true,
            "package_details": {
                "id": 123,
                "title": "Basic Package",
                "price": 29.99
                // ... other package details
            }
        }
    }
}
```

### 7. Get Payment Methods

**Endpoint:** `GET /payments/methods`  
**Authentication:** Not required  
**Description:** Returns available payment methods

**Response:**

```json
{
    "success": true,
    "data": {
        "payment_methods": {
            "paypal": {
                "name": "PayPal",
                "enabled": true,
                "type": "paypal"
            },
            "stripe": {
                "name": "Stripe",
                "enabled": true,
                "type": "stripe"
            },
            "bank_transfer": {
                "name": "Bank Transfer",
                "enabled": true,
                "type": "direct_pay"
            }
        },
        "api_mode": "sandbox",
        "recurring_enabled": 1,
        "auto_recurring": 0
    }
}
```

### 8. Create Payment Session

**Endpoint:** `POST /payments/create-session`  
**Authentication:** Required  
**Description:** Creates a payment session for processing payments

**Parameters:**

-   `payment_type` (required): Type of payment ("membership", "per_listing", "featured")
-   `payment_method` (optional): Payment method to use
-   `package_id` (required for membership): Package ID for membership payments
-   `property_id` (optional): Property ID for per listing/featured payments
-   `is_featured` (optional): Whether to include featured option for per listing

**Request Example:**

```json
{
    "payment_type": "membership",
    "payment_method": "stripe",
    "package_id": 123
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "session_type": "membership",
        "package_id": 123,
        "payment_method": "stripe",
        "user_id": 5,
        "message": "Payment session creation functionality needs to be integrated with existing Houzez payment methods."
    }
}
```

### 9. Cancel Subscription (Auto-detect)

**Endpoint:** `POST /payments/cancel-subscription`  
**Authentication:** Required  
**Description:** Cancels the user's active subscription. Auto-detects whether it's Stripe or PayPal

**Response:**

```json
{
    "success": true,
    "user_id": 5,
    "package_id": 123,
    "payment_method": "stripe",
    "message": "Stripe subscription cancelled successfully. It will remain active until the end of the current billing period."
}
```

### 10. Cancel Stripe Subscription

**Endpoint:** `POST /payments/cancel-stripe`  
**Authentication:** Required  
**Description:** Specifically cancels a Stripe subscription

**Response:**

```json
{
    "success": true,
    "user_id": 5,
    "payment_method": "stripe",
    "message": "Stripe subscription cancelled successfully. It will remain active until the end of the current billing period."
}
```

### 11. Cancel PayPal Subscription

**Endpoint:** `POST /payments/cancel-paypal`  
**Authentication:** Required  
**Description:** Specifically cancels a PayPal subscription

**Response:**

```json
{
    "success": true,
    "user_id": 5,
    "payment_method": "paypal",
    "message": "PayPal subscription cancelled successfully. It will remain active until the end of the current billing period."
}
```

## Error Responses

All endpoints return errors in the following format:

```json
{
    "code": "error_code",
    "message": "Error description",
    "data": {
        "status": 400
    }
}
```

### Common Error Codes:

-   `payment_not_enabled`: Payment is not enabled
-   `membership_not_enabled`: Membership mode is not enabled
-   `per_listing_not_enabled`: Per listing mode is not enabled
-   `authentication_required`: Authentication is required for this endpoint
-   `package_not_found`: Requested package was not found
-   `missing_package_id`: Package ID is required but not provided
-   `no_active_subscription`: No active subscription found for the user
-   `not_recurring_subscription`: Current subscription is not recurring
-   `no_subscription_method`: No active Stripe or PayPal subscription found
-   `cancellation_failed`: Subscription cancellation failed
-   `stripe_cancel_failed`: Stripe subscription cancellation failed
-   `paypal_cancel_failed`: PayPal subscription cancellation failed

## Usage Examples

### Check Payment Mode

```bash
curl -X GET "https://yourdomain.com/wp-json/houzez-api/v1/payments/mode"
```

### Get Available Packages

```bash
curl -X GET "https://yourdomain.com/wp-json/houzez-api/v1/payments/packages"
```

### Get User Status (Authenticated)

```bash
curl -X GET "https://yourdomain.com/wp-json/houzez-api/v1/payments/user-status" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Create Payment Session

```bash
curl -X POST "https://yourdomain.com/wp-json/houzez-api/v1/payments/create-session" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"payment_type":"membership","package_id":123,"payment_method":"stripe"}'
```

### Cancel Subscription (Auto-detect)

```bash
curl -X POST "https://yourdomain.com/wp-json/houzez-api/v1/payments/cancel-subscription" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

### Cancel Stripe Subscription

```bash
curl -X POST "https://yourdomain.com/wp-json/houzez-api/v1/payments/cancel-stripe" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

### Cancel PayPal Subscription

```bash
curl -X POST "https://yourdomain.com/wp-json/houzez-api/v1/payments/cancel-paypal" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

## Notes

1. The `create-session` endpoint currently returns placeholder data. You'll need to integrate it with actual payment processors (PayPal, Stripe, etc.) based on your requirements.

2. All endpoints respect the Houzez theme's payment configuration settings.

3. Package visibility is controlled by the `fave_package_visible` meta field.

4. Currency formatting follows the site's configured currency symbol and position settings.

5. For membership mode, user package information is stored in user meta fields.

6. Subscription cancellation endpoints are based on the existing Houzez theme functionality and work with both Stripe and PayPal recurring subscriptions.

7. When a subscription is cancelled, it remains active until the end of the current billing period. The subscription will not auto-renew after that period.

8. The cancellation endpoints require proper Stripe/PayPal API credentials to be configured in the Houzez theme settings.
