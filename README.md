# Houzez API WordPress Plugin

A comprehensive REST API plugin for the Houzez Real Estate WordPress Theme, providing mobile app integration and external API access.

## Overview

The Houzez API plugin transforms your Houzez real estate website into a powerful API-driven platform, enabling seamless integration with mobile applications, external services, and custom integrations.

## Features

### Core Functionality
- **Property Management** - Complete CRUD operations for property listings
- **User Authentication** - JWT-based authentication with social login support
- **Agent & Agency Management** - Comprehensive realtor profile management
- **Media Handling** - Image and document upload capabilities
- **Advanced Search** - Multi-criteria property search and filtering

### Payment Integration
- **Stripe & PayPal** - Subscription and one-time payment processing
- **Membership Packages** - Flexible pricing models (free, per-listing, subscription)
- **Automatic Billing** - Recurring subscription management

### Push Notifications
- **OneSignal Integration** - Real-time push notifications
- **Firebase Support** - Alternative push notification service
- **User Preferences** - Customizable notification settings

### Social Authentication
- **Facebook OAuth** - Seamless Facebook login integration
- **Google OAuth** - Google account authentication
- **Account Linking** - Connect existing accounts with social profiles

## API Endpoints

All endpoints are available under `/wp-json/houzez-api/v1/`

### Authentication
- `POST /auth/login` - User authentication
- `POST /auth/register` - User registration
- `POST /auth/forgot-password` - Password reset

### Properties
- `GET /properties` - List properties with filtering
- `POST /properties` - Create new property listing
- `GET /properties/{id}` - Get specific property details
- `PUT /properties/{id}` - Update property listing

### Users & Realtors
- `GET /agents` - List real estate agents
- `GET /agencies` - List real estate agencies
- `GET /users/{id}` - Get user profile
- `PUT /users/{id}` - Update user profile

### Payments
- `GET /payments/packages` - List available packages
- `POST /payments/subscribe` - Create subscription
- `POST /payments/cancel` - Cancel subscription

### Notifications
- `POST /notifications/register` - Register device for push notifications
- `GET /notifications/preferences` - Get notification settings
- `PUT /notifications/preferences` - Update notification preferences

## Installation

1. **Prerequisites**
   - WordPress 5.0 or higher
   - Houzez Theme (required)
   - PHP 7.4 or higher

2. **Plugin Installation**
   - Upload plugin files to `/wp-content/plugins/houzez-api/`
   - Activate the plugin through WordPress admin
   - Configure API settings in **Houzez API** menu

3. **API Key Setup**
   - Generate API keys in WordPress admin
   - Configure authentication settings
   - Set up social login credentials (optional)

## Configuration

### Push Notifications
1. Create OneSignal or Firebase account
2. Add credentials in **Houzez API > Push Notifications**
3. Configure notification templates
4. Test notification delivery

### Payment Gateway
1. Set up Stripe and/or PayPal accounts
2. Add API credentials in **Houzez API > Payments**
3. Configure membership packages
4. Test payment flow

### Social Login
1. Create Facebook/Google OAuth apps
2. Add client IDs and secrets in **Houzez API > Social Login**
3. Configure redirect URLs
4. Test social authentication

## Development

### API Testing
```bash
# Test authentication
curl -X POST https://yoursite.com/wp-json/houzez-api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"user","password":"pass"}'

# Test with JWT token
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://yoursite.com/wp-json/houzez-api/v1/properties
```

### Custom Development
- Follow WordPress coding standards
- Use provided base classes for extensions
- Implement proper permission callbacks
- Sanitize and validate all inputs

## Support

For technical support and documentation:
- Check `/docs/` directory for detailed API documentation
- Use `/tests/` directory files for debugging
- Enable WordPress debug logging for troubleshooting

## License

GPL v2 or later

## Author

Waqas Riaz