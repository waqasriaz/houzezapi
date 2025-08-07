# Houzez API - Social Login Endpoints

This document describes the social login API endpoints for Facebook and Google OAuth integration in the Houzez API plugin.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Configuration](#configuration)
3. [Authentication](#authentication)
4. [Available Endpoints](#available-endpoints)
5. [OAuth Flow](#oauth-flow)
6. [Error Handling](#error-handling)
7. [Examples](#examples)

## Prerequisites

-   Houzez theme installed and configured
-   Houzez Login Register plugin installed and activated
-   Houzez API plugin installed and activated
-   Facebook and/or Google OAuth applications configured in Houzez theme options

## Configuration

### Facebook Configuration

1. Go to **Houzez Options > Login Register > Facebook Login**
2. Set your Facebook App ID and App Secret
3. Configure redirect URLs in your Facebook App settings

### Google Configuration

1. Go to **Houzez Options > Login Register > Google Login**
2. Set your Google Client ID and Client Secret
3. Configure redirect URLs in your Google Cloud Console

## Authentication

All social login endpoints are public and don't require authentication. However, successful authentication will return a JWT token for subsequent API calls.

## Available Endpoints

### 1. Get Social Login Configuration

**GET** `/wp-json/houzez-api/v1/social/config`

Returns the available social login providers and their configuration.

**Response:**

```json
{
    "success": true,
    "data": {
        "facebook": {
            "enabled": true,
            "app_id": "your_facebook_app_id"
        },
        "google": {
            "enabled": true,
            "client_id": "your_google_client_id"
        },
        "available_providers": ["facebook", "google"]
    }
}
```

### 2. Get Facebook OAuth URL

**GET** `/wp-json/houzez-api/v1/social/facebook/oauth-url`

**Parameters:**

-   `redirect_uri` (optional): Custom redirect URI for OAuth flow

**Response:**

```json
{
    "success": true,
    "data": {
        "oauth_url": "https://www.facebook.com/v3.2/dialog/oauth?...",
        "redirect_uri": "your_redirect_uri",
        "permissions": ["public_profile", "email"],
        "app_id": "your_facebook_app_id"
    }
}
```

### 3. Facebook Authentication with Code

**POST** `/wp-json/houzez-api/v1/social/facebook/auth`

**Parameters:**

-   `code` (required): Authorization code from Facebook OAuth
-   `state` (optional): State parameter from Facebook OAuth

**Response:**

```json
{
    "success": true,
    "action": "login",
    "data": {
        "user": {
            "id": 123,
            "username": "john.doe",
            "email": "john@example.com",
            "display_name": "John Doe",
            "first_name": "John",
            "last_name": "Doe",
            "roles": ["subscriber"],
            "avatar_url": "https://secure.gravatar.com/avatar/..."
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 3600,
        "provider": "facebook",
        "social_id": "facebook_user_id"
    }
}
```

### 4. Facebook Login with Access Token

**POST** `/wp-json/houzez-api/v1/social/facebook/login`

**Parameters:**

-   `access_token` (required): Facebook access token

**Response:** Same as Facebook authentication with code

### 5. Get Google OAuth URL

**GET** `/wp-json/houzez-api/v1/social/google/oauth-url`

**Parameters:**

-   `redirect_uri` (optional): Custom redirect URI for OAuth flow

**Response:**

```json
{
    "success": true,
    "data": {
        "oauth_url": "https://accounts.google.com/o/oauth2/auth?...",
        "redirect_uri": "your_redirect_uri",
        "scopes": ["email", "profile"],
        "client_id": "your_google_client_id"
    }
}
```

### 6. Google Authentication with Code

**POST** `/wp-json/houzez-api/v1/social/google/auth`

**Parameters:**

-   `code` (required): Authorization code from Google OAuth
-   `redirect_uri` (optional): Redirect URI used in OAuth flow

**Response:**

```json
{
    "success": true,
    "action": "register",
    "data": {
        "user": {
            "id": 124,
            "username": "jane.smith",
            "email": "jane@example.com",
            "display_name": "Jane Smith",
            "first_name": "Jane",
            "last_name": "Smith",
            "roles": ["subscriber"],
            "avatar_url": "https://lh3.googleusercontent.com/..."
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 3600,
        "provider": "google",
        "social_id": "google_user_id"
    }
}
```

### 7. Google Login with Access Token

**POST** `/wp-json/houzez-api/v1/social/google/login`

**Parameters:**

-   `access_token` (required): Google access token

**Response:** Same as Google authentication with code

### 8. Link Facebook Account

**POST** `/wp-json/houzez-api/v1/social/facebook/link`

**Authentication:** Required

**Parameters:**

-   `facebook_id` (required): Facebook user ID
-   `email` (required): Email to link Facebook account to

**Response:**

```json
{
    "success": true,
    "data": {
        "message": "Facebook account linked successfully.",
        "user_id": 123,
        "email": "user@example.com",
        "facebook_id": "facebook_user_id"
    }
}
```

## OAuth Flow

### Standard OAuth Flow

1. **Get OAuth URL**: Call the appropriate OAuth URL endpoint
2. **Redirect User**: Direct user to the returned OAuth URL
3. **Handle Callback**: Capture the authorization code from the callback
4. **Authenticate**: Send the code to the authentication endpoint
5. **Receive Token**: Use the returned JWT token for authenticated requests

### Access Token Flow (Alternative)

1. **Get Access Token**: Obtain access token from social provider directly
2. **Login**: Send access token to the login endpoint
3. **Receive Token**: Use the returned JWT token for authenticated requests

## Error Handling

### Common Error Codes

-   `400` - Bad Request (missing parameters, invalid data)
-   `401` - Unauthorized (authentication required)
-   `404` - Not Found (user not found)
-   `500` - Internal Server Error

### Error Response Format

```json
{
    "code": "error_code",
    "message": "Human readable error message",
    "data": {
        "status": 400
    }
}
```

### Specific Error Codes

#### Facebook Errors

-   `facebook_not_configured` - Facebook API keys not configured
-   `facebook_sdk_missing` - Facebook SDK not available
-   `facebook_oauth_error` - Error generating OAuth URL
-   `facebook_graph_error` - Facebook Graph API error
-   `facebook_sdk_error` - Facebook SDK error
-   `facebook_access_token_error` - Invalid or expired access token
-   `facebook_token_expired` - Access token expired
-   `facebook_api_error` - Error fetching user data from Facebook
-   `email_required_for_linking` - Email permission required for linking

#### Google Errors

-   `google_not_configured` - Google OAuth credentials not configured
-   `google_sdk_missing` - Google SDK not available
-   `google_oauth_error` - Error generating OAuth URL
-   `google_auth_failed` - Google authentication failed
-   `google_access_token_error` - Error getting access token
-   `google_process_error` - Error processing Google user data

#### General Errors

-   `registration_failed` - User registration failed
-   `social_auth_error` - General social authentication error
-   `missing_parameters` - Required parameters missing
-   `user_not_found` - User not found for linking

## Examples

### Example 1: Complete Facebook OAuth Flow

```bash
# Step 1: Get Facebook OAuth URL
curl -X GET "https://yoursite.com/wp-json/houzez-api/v1/social/facebook/oauth-url?redirect_uri=https://yourapp.com/callback"

# Response includes oauth_url, redirect user to this URL

# Step 2: After user authorizes, Facebook redirects with code
# Extract code from callback URL: https://yourapp.com/callback?code=AQB...&state=...

# Step 3: Authenticate with code
curl -X POST "https://yoursite.com/wp-json/houzez-api/v1/social/facebook/auth" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "AQB_authorization_code_from_facebook",
    "state": "state_parameter_if_provided"
  }'
```

### Example 2: Google Login with Access Token

```bash
# If you already have Google access token from client-side
curl -X POST "https://yoursite.com/wp-json/houzez-api/v1/social/google/login" \
  -H "Content-Type: application/json" \
  -d '{
    "access_token": "ya29.google_access_token"
  }'
```

### Example 3: Link Facebook Account

```bash
# Link Facebook account to existing user (requires authentication)
curl -X POST "https://yoursite.com/wp-json/houzez-api/v1/social/facebook/link" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your_jwt_token" \
  -d '{
    "facebook_id": "facebook_user_id",
    "email": "user@example.com"
  }'
```

### Example 4: Check Social Login Configuration

```bash
# Check which social providers are available
curl -X GET "https://yoursite.com/wp-json/houzez-api/v1/social/config"
```

## Integration Notes

### Mobile App Integration

For mobile apps, you can:

1. Use the native Facebook/Google SDKs to obtain access tokens
2. Send access tokens to the `/login` endpoints directly
3. Skip the OAuth URL flow for better user experience

### Web App Integration

For web applications:

1. Use the OAuth URL endpoints to initiate the flow
2. Handle the callback with the authorization code
3. Send the code to the `/auth` endpoints

### Email Linking

If Facebook doesn't provide email permission:

1. The API will return an error with `requires_linking: true`
2. Use the `/facebook/link` endpoint to manually link the account
3. Store the `facebook_id` for future reference

### Token Usage

The returned JWT token should be used for authenticated API requests:

```
Authorization: Bearer your_jwt_token
```

### User Registration

-   New users are automatically registered when they don't exist
-   Existing users are logged in automatically
-   User roles default to 'subscriber' but can be modified through WordPress hooks
-   Profile images are automatically set from social provider avatars

## Security Considerations

1. **HTTPS Only**: Always use HTTPS for OAuth flows
2. **State Parameter**: Use state parameter to prevent CSRF attacks
3. **Token Expiration**: JWT tokens expire after 1 hour by default
4. **Redirect URI Validation**: Ensure redirect URIs are properly configured
5. **API Key Security**: Never expose API keys in client-side code
