<?php
/**
 * Houzez API Settings Class
 * Handles the admin settings interface for the Houzez API plugin
 */
class Houzez_API_Settings {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Actions
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * Add plugin page to WordPress admin menu
     */
    public function add_plugin_page() {
        add_menu_page(
            'Houzez API Settings',
            'Houzez API',
            'manage_options',
            'houzez-api-settings',
            array($this, 'create_admin_page'),
            'dashicons-rest-api',
            85
        );
        
        // Settings submenu (same as parent)
        add_submenu_page(
            'houzez-api-settings',
            'Settings',
            'Settings',
            'manage_options',
            'houzez-api-settings',
            array($this, 'create_admin_page')
        );
        
        // Add submenu for notifications
        add_submenu_page(
            'houzez-api-settings',
            'Notifications',
            'Notifications',
            'manage_options',
            'edit.php?post_type=houzez_notification'
        );
        
        // Add submenu for diagnostics
        add_submenu_page(
            'houzez-api-settings',
            'Diagnostics',
            'Diagnostics',
            'manage_options',
            'houzez-notification-diagnostics',
            'houzez_notification_diagnostics_page'
        );
        
        // Add submenu for mobile demo
        add_submenu_page(
            'houzez-api-settings',
            'Mobile Demo',
            'Mobile Demo',
            'manage_options',
            'houzez-mobile-demo',
            array($this, 'create_mobile_demo_page')
        );
    }

    /**
     * Initialize page settings
     */
    public function page_init() {
        // Register settings group
        register_setting('houzez_api_settings', 'houzez_api_push_enabled');
        register_setting('houzez_api_settings', 'houzez_api_push_service');
        register_setting('houzez_api_settings', 'houzez_api_onesignal_app_id');
        register_setting('houzez_api_settings', 'houzez_api_onesignal_api_key');
        register_setting('houzez_api_settings', 'houzez_api_firebase_server_key');
        
        // Make sure push notification settings are loaded
        if (class_exists('Houzez_API_Push_Notifications')) {
            $push = Houzez_API_Push_Notifications::get_instance();
            $push->add_settings_fields();
        }
    }

    /**
     * Create the admin page
     */
    public function create_admin_page() {
        // Verify we're on the correct admin page
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if ($current_page !== 'houzez-api-settings') {
            return;
        }

        ?>
        <div class="wrap">
            <h1>Houzez API Settings</h1>
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                    <?php
                settings_fields('houzez_api_settings');
                do_settings_sections('houzez_api_settings');
                submit_button();
                ?>
            </form>
            
            <hr>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>Quick Links</h2>
                <ul>
                    <li><a href="<?php echo admin_url('edit.php?post_type=houzez_notification'); ?>">View Notifications</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=houzez-notification-diagnostics'); ?>">Notification Diagnostics</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=houzez-mobile-demo'); ?>">📱 Mobile Notification Demo</a></li>
                    <li>View Documentation: <code><?php echo HOUZEZ_API_PLUGIN_DIR; ?>PUSH_NOTIFICATION_SETUP.md</code></li>
                    <li>API Documentation: <code><?php echo HOUZEZ_API_PLUGIN_DIR; ?>NOTIFICATION_API_ENDPOINTS.md</code></li>
                </ul>
            </div>
            
            <div style="background: #f0f8ff; padding: 20px; margin: 20px 0; border: 1px solid #0073aa;">
                <h2>Push Notification Setup Status</h2>
        <?php
                $push_enabled = get_option('houzez_api_push_enabled', '0');
                $push_service = get_option('houzez_api_push_service', 'onesignal');
                
                if ($push_enabled === '1') {
                    echo '<p style="color: green;"><strong>✓ Push Notifications Enabled</strong></p>';
                    echo '<p><strong>Service:</strong> ' . ucfirst($push_service) . '</p>';
                    
                    if ($push_service === 'onesignal') {
                        $app_id = get_option('houzez_api_onesignal_app_id');
                        $api_key = get_option('houzez_api_onesignal_api_key');
                        
                        if ($app_id && $api_key) {
                            echo '<p style="color: green;">✓ OneSignal credentials configured</p>';
                        } else {
                            echo '<p style="color: orange;">⚠ OneSignal credentials not configured</p>';
                        }
                    } elseif ($push_service === 'firebase') {
                        $server_key = get_option('houzez_api_firebase_server_key');
                        
                        if ($server_key) {
                            echo '<p style="color: green;">✓ Firebase credentials configured</p>';
                        } else {
                            echo '<p style="color: orange;">⚠ Firebase credentials not configured</p>';
                        }
                    }
                } else {
                    echo '<p style="color: red;"><strong>✗ Push Notifications Disabled</strong></p>';
                    echo '<p>Enable push notifications above to send instant notifications to mobile devices.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Create the mobile demo page
     */
    public function create_mobile_demo_page() {
        ?>
        <div class="wrap">
            <h1>Mobile Notification Demo</h1>
            <p>This demo shows how notifications will appear on mobile devices using the Houzez API notification system.</p>
            
            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <!-- Mobile Device Mockup -->
                <div style="flex: 0 0 300px;">
                    <div style="background: #333; border-radius: 25px; padding: 20px; width: 300px; height: 600px; position: relative;">
                        <!-- Phone Frame -->
                        <div style="background: #000; border-radius: 20px; width: 100%; height: 100%; position: relative; overflow: hidden;">
                            <!-- Status Bar -->
                            <div style="background: #1a1a1a; color: white; padding: 8px 15px; font-size: 12px; display: flex; justify-content: space-between;">
                                <span>9:41 AM</span>
                                <div style="display: flex; gap: 5px;">
                                    <span>📶</span>
                                    <span>📶</span>
                                    <span>🔋</span>
                                </div>
                            </div>
                            
                            <!-- App Header -->
                            <div style="background: #2c3e50; color: white; padding: 15px; display: flex; align-items: center; gap: 10px;">
                                <div style="font-size: 18px;">🏠</div>
                                <div>
                                    <div style="font-weight: bold; font-size: 16px;">Houzez App</div>
                                    <div style="font-size: 12px; opacity: 0.8;">Notifications</div>
                                </div>
                                <div style="margin-left: auto; background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                    3
                                </div>
                            </div>
                            
                            <!-- Notifications List -->
                            <div id="mobile-notifications" style="background: #f8f9fa; height: calc(100% - 80px); overflow-y: auto; padding: 10px;">
                                <!-- Notifications will be inserted here -->
                            </div>
                        </div>
                    </div>
            </div>

                <!-- Controls -->
                <div style="flex: 1;">
                    <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h3>Demo Controls - All Notification Types</h3>
                        
                        <!-- Property & Listings Category -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px;">🏠 Property & Listings</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0;">
                                <button id="add-property_saved" class="button">Property Saved</button>
                                <button id="add-property_matched" class="button">Property Match</button>
                                <button id="add-property_price_drop" class="button">Price Drop</button>
                                <button id="add-property_status_change" class="button">Status Change</button>
                                <button id="add-listing_approved" class="button button-primary">Listing Approved</button>
                                <button id="add-listing_expired" class="button">Listing Expired</button>
                                <button id="add-listing_disapproved" class="button">Listing Disapproved</button>
                                <button id="add-property_matching" class="button">Property Matching</button>
                                <button id="add-price_update" class="button">Price Update</button>
                        </div>
                        </div>

                        <!-- Communication & Contact Category -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 5px;">💬 Communication & Contact</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0;">
                                <button id="add-inquiry_received" class="button button-primary">Inquiry Received</button>
                                <button id="add-messages" class="button button-primary">Message Received</button>
                                <button id="add-property_agent_contact" class="button button-primary">Agent Contact</button>
                                <button id="add-contact_agent" class="button">Contact Agent</button>
                                <button id="add-contact_agency" class="button">Contact Agency</button>
                                <button id="add-contact_owner" class="button">Contact Owner</button>
                                <button id="add-review" class="button">Review Received</button>
                                <button id="add-message_received" class="button">Message</button>
                                <button id="add-review_received" class="button">Review</button>
                    </div>
                        </div>

                        <!-- User Management & Registration Category -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #2c3e50; border-bottom: 2px solid #f39c12; padding-bottom: 5px;">👤 User Management</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0;">
                                <button id="add-new_user_register" class="button">New User Registration</button>
                                <button id="add-admin_new_user_register" class="button">Admin New User</button>
                                <button id="add-admin_user_register_approval" class="button">User Approval</button>
                                <button id="add-verification_status" class="button">Verification Status</button>
                                <button id="add-membership_cancelled" class="button">Membership Cancelled</button>
                                <button id="add-agent_assigned" class="button">Agent Assigned</button>
                    </div>
                    </div>

                        <!-- Financial & Payments Category -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #2c3e50; border-bottom: 2px solid #27ae60; padding-bottom: 5px;">💰 Financial & Payments</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0;">
                                <button id="add-payment_received" class="button button-secondary">Payment Received</button>
                                <button id="add-payment_confirmation" class="button button-secondary">Payment Confirmation</button>
                                <button id="add-new_wire_transfer" class="button">Wire Transfer</button>
                                <button id="add-admin_new_wire_transfer" class="button">Admin Wire Transfer</button>
                                <button id="add-recurring_payment" class="button">Recurring Payment</button>
                                <button id="add-purchase_activated_pack" class="button">Package Activated</button>
                                <button id="add-purchase_activated" class="button">Purchase Activated</button>
                </div>
            </div>

                        <!-- Scheduling & Tours Category -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #2c3e50; border-bottom: 2px solid #9b59b6; padding-bottom: 5px;">📅 Scheduling & Tours</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0;">
                                <button id="add-showing_scheduled" class="button button-primary">Tour Scheduled</button>
                                <button id="add-showing_reminder" class="button">Tour Reminder</button>
                                <button id="add-property_schedule_tour" class="button button-primary">Schedule Tour</button>
                        </div>
                    </div>

                        <!-- Listing Management Category -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #2c3e50; border-bottom: 2px solid #e67e22; padding-bottom: 5px;">📋 Listing Management</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0;">
                                <button id="add-paid_submission_listing" class="button">Paid Listing</button>
                                <button id="add-admin_paid_submission_listing" class="button">Admin Paid Listing</button>
                                <button id="add-featured_submission_listing" class="button">Featured Listing</button>
                                <button id="add-admin_featured_submission_listing" class="button">Admin Featured</button>
                                <button id="add-free_submission_listing" class="button">Free Listing</button>
                                <button id="add-admin_free_submission_listing" class="button">Admin Free Listing</button>
                                <button id="add-admin_update_listing" class="button">Update Listing</button>
                                <button id="add-free_listing_expired" class="button">Free Expired</button>
                                <button id="add-featured_listing_expired" class="button">Featured Expired</button>
                                <button id="add-admin_expired_listings" class="button">Expired Resubmission</button>
                        </div>
                    </div>

                        <!-- Admin & Reports Category -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #2c3e50; border-bottom: 2px solid #34495e; padding-bottom: 5px;">🔧 Admin & Reports</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0;">
                                <button id="add-report" class="button">Report Received</button>
                                <button id="add-property_report" class="button">Property Report</button>
                                <button id="add-system_update" class="button">System Update</button>
                                <button id="add-document_uploaded" class="button">Document Uploaded</button>
                    </div>
                </div>

                        <!-- Marketing & Matching Category -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #2c3e50; border-bottom: 2px solid #1abc9c; padding-bottom: 5px;">📢 Marketing & Matching</h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0;">
                                <button id="add-marketing_promotion" class="button">Marketing Promotion</button>
                                <button id="add-matching_submissions" class="button">Matching Submissions</button>
                            </div>
            </div>

                        <!-- Quick Actions -->
                        <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #ecf0f1;">
                            <h4 style="color: #2c3e50;">⚡ Quick Actions</h4>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button id="add-random-notification" class="button button-primary">🎲 Add Random</button>
                                <button id="add-urgent-notification" class="button" style="background: #e74c3c; color: white;">🚨 Add Urgent</button>
                                <button id="add-multiple-notifications" class="button button-secondary">📚 Add 5 Random</button>
                                <button id="clear-notifications" class="button">🗑️ Clear All</button>
                        </div>
                </div>

                        <div style="margin-top: 15px; font-size: 12px; color: #666; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                            <strong>Total Types Available:</strong> 50+ notification types covering all Houzez functionality<br>
                            <strong>Categories:</strong> Property, Communication, User Management, Payments, Scheduling, Listing Management, Admin, Marketing<br>
                            <strong>Priorities:</strong> Low, Medium, High, Urgent (color-coded borders)
                        </div>
                </div>

                    <div style="background: #f0f8ff; padding: 20px; border: 1px solid #0073aa; border-radius: 5px; margin-top: 20px;">
                        <h3>API Integration</h3>
                        <p>These notifications are automatically generated when:</p>
                        <ul>
                            <li>Users contact agents through property forms</li>
                            <li>Tour requests are submitted</li>
                            <li>Messages are sent via the messaging system</li>
                            <li>Payments and wire transfers are processed</li>
                            <li>Listings are approved, expired, or disapproved</li>
                            <li>New users register on the website</li>
                            <li>Properties match saved search criteria</li>
                            <li>Packages and memberships are activated or cancelled</li>
                        </ul>
                        <p><strong>Complete API endpoints available:</strong></p>
                        <ul style="font-family: monospace; font-size: 12px;">
                            <li>GET /wp-json/houzez-api/v1/notifications</li>
                            <li>GET /wp-json/houzez-api/v1/notifications/{id}</li>
                            <li>POST /wp-json/houzez-api/v1/notifications/{id}/read</li>
                            <li>GET /wp-json/houzez-api/v1/notifications/unread-count</li>
                        </ul>
                        <p><strong>Supported notification types:</strong> <span style="background: #e8f4fd; padding: 2px 6px; border-radius: 3px; font-size: 11px;">50+ types</span></p>
                </div>
            </div>
        </div>
        </div>
        
        <style>
            .notification-item {
                background: white;
                border-radius: 8px;
                padding: 12px;
                margin-bottom: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                border-left: 4px solid #3498db;
                position: relative;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .notification-item:hover {
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }
            
            .notification-item.unread {
                background: #e8f4fd;
            }
            
            .notification-item.high-priority {
                border-left-color: #f39c12;
            }
            
            .notification-item.urgent-priority {
                border-left-color: #e74c3c;
            }
            
            .notification-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 5px;
            }
            
            .notification-title {
                font-weight: bold;
                font-size: 14px;
                color: #2c3e50;
            }
            
            .notification-time {
                font-size: 11px;
                color: #7f8c8d;
            }
            
            .notification-message {
                font-size: 13px;
                color: #34495e;
                line-height: 1.4;
            }
            
            .notification-type {
                font-size: 11px;
                background: #ecf0f1;
                color: #7f8c8d;
                padding: 2px 6px;
                border-radius: 10px;
                display: inline-block;
                margin-top: 5px;
            }
            
            .unread-badge {
                width: 8px;
                height: 8px;
                background: #e74c3c;
                border-radius: 50%;
                position: absolute;
                top: 8px;
                right: 8px;
            }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationContainer = document.getElementById('mobile-notifications');
            let notificationCount = 0;
            
            // Comprehensive notification templates for all types
            const templates = {
                // Property & Listings
                property_saved: {
                    title: 'Property Saved',
                    message: 'You saved "Modern Downtown Apartment" to your favorites. View it anytime in your saved properties.',
                    type: 'Property Saved',
                    priority: 'low',
                    icon: '❤️'
                },
                property_matched: {
                    title: 'New Property Match',
                    message: 'New property "Suburban Villa" matches your saved search: 3 bed, 2 bath, $400-500k range.',
                    type: 'Property Match',
                    priority: 'medium',
                    icon: '🎯'
                },
                property_price_drop: {
                    title: 'Price Drop Alert',
                    message: 'Great news! "Ocean View Condo" price dropped from $650,000 to $599,000 (-8%)',
                    type: 'Price Drop',
                    priority: 'high',
                    icon: '📉'
                },
                property_status_change: {
                    title: 'Property Status Updated',
                    message: '"Luxury Penthouse" status changed from "For Sale" to "Under Contract"',
                    type: 'Status Change',
                    priority: 'medium',
                    icon: '🔄'
                },
                listing_approved: {
                    title: 'Listing Approved',
                    message: 'Congratulations! Your listing "Downtown Loft" has been approved and is now live',
                    type: 'Listing Approved',
                    priority: 'high',
                    icon: '✅'
                },
                listing_expired: {
                    title: 'Listing Expired',
                    message: 'Your listing "Garden Apartment" has expired. Click to renew and keep it visible.',
                    type: 'Listing Expired',
                    priority: 'high',
                    icon: '⏰'
                },
                listing_disapproved: {
                    title: 'Listing Needs Review',
                    message: 'Your listing "City Apartment" requires additional information before approval.',
                    type: 'Listing Review',
                    priority: 'high',
                    icon: '❌'
                },
                property_matching: {
                    title: 'Property Matching Service',
                    message: 'We found 3 new properties matching your criteria. Check them out now!',
                    type: 'Property Matching',
                    priority: 'low',
                    icon: '🔍'
                },
                price_update: {
                    title: 'Price Update',
                    message: 'Price updated for "Beachfront Villa" - New price: $1,250,000',
                    type: 'Price Update',
                    priority: 'medium',
                    icon: '💰'
                },

                // Communication & Contact
                inquiry_received: {
                    title: 'New Property Inquiry',
                    message: 'Sarah Johnson inquired about "Modern Villa". Contact: sarah@email.com, Phone: +1-555-123-4567',
                    type: 'Property Inquiry',
                    priority: 'high',
                    icon: '📧'
                },
                messages: {
                    title: 'New Message',
                    message: 'Mike Wilson: "Is the property still available? I\'d like to schedule a viewing this weekend."',
                    type: 'Direct Message',
                    priority: 'medium',
                    icon: '💬'
                },
                property_agent_contact: {
                    title: 'Agent Contact Form',
                    message: 'Emma Davis contacted you about "Luxury Condo" via agent contact form',
                    type: 'Agent Contact',
                    priority: 'high',
                    icon: '👨‍💼'
                },
                contact_agent: {
                    title: 'Contact Request',
                    message: 'New contact request from John Smith regarding property consultation',
                    type: 'Contact Request',
                    priority: 'medium',
                    icon: '📞'
                },
                contact_agency: {
                    title: 'Agency Contact',
                    message: 'Jennifer Lee wants to know more about your agency services',
                    type: 'Agency Contact',
                    priority: 'medium',
                    icon: '🏢'
                },
                contact_owner: {
                    title: 'Owner Contact',
                    message: 'David Brown would like to contact the property owner directly',
                    type: 'Owner Contact',
                    priority: 'medium',
                    icon: '🏠'
                },
                review: {
                    title: 'New Review Received',
                    message: 'Lisa Thompson left a 5-star review: "Excellent service and very professional!"',
                    type: 'Review Received',
                    priority: 'medium',
                    icon: '⭐'
                },
                message_received: {
                    title: 'Message Received',
                    message: 'You have a new message from Alex Johnson about the consultation',
                    type: 'Message',
                    priority: 'medium',
                    icon: '📨'
                },
                review_received: {
                    title: 'Customer Review',
                    message: 'New review from Maria Garcia: "Highly recommend! Found my dream home quickly."',
                    type: 'Customer Review',
                    priority: 'medium',
                    icon: '🌟'
                },

                // User Management & Registration
                new_user_register: {
                    title: 'Welcome New User',
                    message: 'Welcome to our platform! Your account has been created successfully.',
                    type: 'New Registration',
                    priority: 'medium',
                    icon: '👋'
                },
                admin_new_user_register: {
                    title: 'New User Registration',
                    message: 'New agent registration: Robert Johnson (robert@realty.com) requires approval',
                    type: 'Admin Registration',
                    priority: 'urgent',
                    icon: '👤'
                },
                admin_user_register_approval: {
                    title: 'User Approval Needed',
                    message: 'Agent approval required for Jessica Miller (jessica.miller@homes.com)',
                    type: 'User Approval',
                    priority: 'urgent',
                    icon: '✋'
                },
                verification_status: {
                    title: 'Verification Update',
                    message: 'Your agent verification has been completed successfully!',
                    type: 'Verification',
                    priority: 'high',
                    icon: '🛡️'
                },
                membership_cancelled: {
                    title: 'Membership Cancelled',
                    message: 'Your premium membership has been cancelled. Upgrade anytime to restore full access.',
                    type: 'Membership',
                    priority: 'urgent',
                    icon: '⚠️'
                },
                agent_assigned: {
                    title: 'Agent Assigned',
                    message: 'Agent Emma Wilson has been assigned to handle your property listing',
                    type: 'Agent Assignment',
                    priority: 'medium',
                    icon: '🤝'
                },

                // Financial & Payments
                payment_received: {
                    title: 'Payment Received',
                    message: 'Payment of $2,500 received from David Chen for "Beachfront Condo" booking',
                    type: 'Payment Confirmation',
                    priority: 'high',
                    icon: '💰'
                },
                payment_confirmation: {
                    title: 'Payment Confirmed',
                    message: 'Your payment of $1,200 for premium listing package has been processed successfully',
                    type: 'Payment Confirmed',
                    priority: 'high',
                    icon: '✅'
                },
                new_wire_transfer: {
                    title: 'Wire Transfer Request',
                    message: 'New wire transfer request received. Amount: $5,000. Awaiting processing.',
                    type: 'Wire Transfer',
                    priority: 'high',
                    icon: '🏦'
                },
                admin_new_wire_transfer: {
                    title: 'Admin: Wire Transfer',
                    message: 'New wire transfer from Maria Lopez requires admin approval ($3,200)',
                    type: 'Admin Wire Transfer',
                    priority: 'urgent',
                    icon: '🔐'
                },
                recurring_payment: {
                    title: 'Recurring Payment',
                    message: 'Monthly premium subscription renewed automatically. Next payment: Dec 15, 2024',
                    type: 'Recurring Payment',
                    priority: 'medium',
                    icon: '🔄'
                },
                purchase_activated_pack: {
                    title: 'Package Activated',
                    message: 'Your "Professional Agent Package" has been activated! Start listing properties now.',
                    type: 'Package Activated',
                    priority: 'high',
                    icon: '📦'
                },
                purchase_activated: {
                    title: 'Purchase Activated',
                    message: 'Your purchase has been activated. You can now access all premium features.',
                    type: 'Purchase Activated',
                    priority: 'high',
                    icon: '🎉'
                },

                // Scheduling & Tours
                showing_scheduled: {
                    title: 'Tour Scheduled',
                    message: 'Property tour scheduled with Lisa Chen for "Mountain View Home" on Dec 15 at 2:00 PM',
                    type: 'Tour Scheduled',
                    priority: 'high',
                    icon: '📅'
                },
                showing_reminder: {
                    title: 'Tour Reminder',
                    message: 'Reminder: Property tour tomorrow at 3:00 PM with John Davis at "City Apartment"',
                    type: 'Tour Reminder',
                    priority: 'high',
                    icon: '⏰'
                },
                property_schedule_tour: {
                    title: 'Schedule Tour Request',
                    message: 'Amanda Taylor wants to schedule a tour for "Suburban House" this weekend',
                    type: 'Tour Request',
                    priority: 'high',
                    icon: '🏠'
                },

                // Listing Management
                paid_submission_listing: {
                    title: 'Paid Listing Submitted',
                    message: 'Your paid listing "Executive Office Space" has been submitted for review',
                    type: 'Paid Listing',
                    priority: 'medium',
                    icon: '💳'
                },
                admin_paid_submission_listing: {
                    title: 'Admin: Paid Listing',
                    message: 'New paid listing submission from Thomas Wilson requires admin review',
                    type: 'Admin Paid Listing',
                    priority: 'urgent',
                    icon: '💼'
                },
                featured_submission_listing: {
                    title: 'Featured Listing Upgrade',
                    message: 'Your property "Luxury Villa" has been upgraded to featured listing!',
                    type: 'Featured Listing',
                    priority: 'medium',
                    icon: '⭐'
                },
                admin_featured_submission_listing: {
                    title: 'Admin: Featured Listing',
                    message: 'Featured listing upgrade request from Rachel Green needs approval',
                    type: 'Admin Featured',
                    priority: 'high',
                    icon: '🌟'
                },
                free_submission_listing: {
                    title: 'Free Listing Submitted',
                    message: 'Your free listing "Cozy Apartment" has been submitted successfully',
                    type: 'Free Listing',
                    priority: 'medium',
                    icon: '🆓'
                },
                admin_free_submission_listing: {
                    title: 'Admin: Free Listing',
                    message: 'New free listing submission from Kevin Martinez awaiting review',
                    type: 'Admin Free Listing',
                    priority: 'medium',
                    icon: '📝'
                },
                admin_update_listing: {
                    title: 'Listing Updated',
                    message: 'Property listing "Downtown Condo" has been updated by the owner',
                    type: 'Listing Update',
                    priority: 'medium',
                    icon: '🔄'
                },
                free_listing_expired: {
                    title: 'Free Listing Expired',
                    message: 'Your free listing "Student Housing" has expired. Upgrade to featured for better visibility.',
                    type: 'Free Expired',
                    priority: 'high',
                    icon: '📅'
                },
                featured_listing_expired: {
                    title: 'Featured Listing Expired',
                    message: 'Your featured listing "Penthouse Suite" has expired. Renew to maintain premium visibility.',
                    type: 'Featured Expired',
                    priority: 'high',
                    icon: '⭐'
                },
                admin_expired_listings: {
                    title: 'Expired Listing Resubmitted',
                    message: 'Previously expired listing "Garden Villa" has been resubmitted for approval',
                    type: 'Resubmission',
                    priority: 'medium',
                    icon: '🔄'
                },

                // Admin & Reports
                report: {
                    title: 'New Report Received',
                    message: 'Property report submitted for "Industrial Complex" - requires admin attention',
                    type: 'Report',
                    priority: 'urgent',
                    icon: '🚨'
                },
                property_report: {
                    title: 'Property Report',
                    message: 'Report filed against listing "Commercial Space" for incorrect information',
                    type: 'Property Report',
                    priority: 'urgent',
                    icon: '⚠️'
                },
                system_update: {
                    title: 'System Update',
                    message: 'New features available! Check out our improved search filters and mobile app updates.',
                    type: 'System Update',
                    priority: 'low',
                    icon: '🔧'
                },
                document_uploaded: {
                    title: 'Document Uploaded',
                    message: 'New document "Property_Certificate.pdf" uploaded for "Riverside Mansion"',
                    type: 'Document Upload',
                    priority: 'medium',
                    icon: '📄'
                },

                // Marketing & Matching
                marketing_promotion: {
                    title: 'Special Promotion',
                    message: 'Limited time: Get 50% off premium listing upgrades this month only!',
                    type: 'Marketing',
                    priority: 'low',
                    icon: '🎁'
                },
                matching_submissions: {
                    title: 'Matching Properties Found',
                    message: 'We found 7 new properties matching your saved searches. View matches now!',
                    type: 'Matching Properties',
                    priority: 'low',
                    icon: '🎯'
                }
            };
            
            function createNotification(template) {
                notificationCount++;
                const timeAgo = ['Just now', '2 min ago', '5 min ago', '10 min ago', '15 min ago'][Math.floor(Math.random() * 5)];
                
                const notification = document.createElement('div');
                notification.className = `notification-item unread ${template.priority}-priority`;
                notification.innerHTML = `
                    <div class="unread-badge"></div>
                    <div class="notification-header">
                        <div class="notification-title">${template.icon} ${template.title}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                    <div class="notification-message">${template.message}</div>
                    <div class="notification-type">${template.type}</div>
                `;
                
                // Add click handler to simulate reading
                notification.addEventListener('click', function() {
                    this.classList.remove('unread');
                    const badge = this.querySelector('.unread-badge');
                    if (badge) badge.remove();
                    updateNotificationBadge();
                });
                
                notificationContainer.insertBefore(notification, notificationContainer.firstChild);
                updateNotificationBadge();
                
                // Add smooth slide-in animation
                notification.style.transform = 'translateX(-100%)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.style.transition = 'all 0.3s ease-out';
                    notification.style.transform = 'translateX(0)';
                    notification.style.opacity = '1';
                }, 10);
            }
            
            function updateNotificationBadge() {
                const unreadCount = document.querySelectorAll('.notification-item.unread').length;
                const badge = document.querySelector('.wrap .button-primary + div div:last-child');
                if (badge) {
                    badge.textContent = unreadCount;
                    badge.style.display = unreadCount > 0 ? 'flex' : 'none';
                }
            }
            
            // Add event listeners for all notification types
            Object.keys(templates).forEach(type => {
                const button = document.getElementById(`add-${type}`);
                if (button) {
                    button.addEventListener('click', () => createNotification(templates[type]));
                }
            });

            // Special action buttons
            document.getElementById('add-random-notification').addEventListener('click', function() {
                const types = Object.keys(templates);
                const randomType = types[Math.floor(Math.random() * types.length)];
                createNotification(templates[randomType]);
            });

            document.getElementById('add-urgent-notification').addEventListener('click', function() {
                const urgentTypes = Object.keys(templates).filter(type => 
                    templates[type].priority === 'urgent'
                );
                const randomUrgent = urgentTypes[Math.floor(Math.random() * urgentTypes.length)];
                createNotification(templates[randomUrgent]);
            });

            document.getElementById('add-multiple-notifications').addEventListener('click', function() {
                const types = Object.keys(templates);
                for (let i = 0; i < 5; i++) {
                    setTimeout(() => {
                        const randomType = types[Math.floor(Math.random() * types.length)];
                        createNotification(templates[randomType]);
                    }, i * 200);
                }
            });
            
            document.getElementById('clear-notifications').addEventListener('click', function() {
                notificationContainer.innerHTML = '';
                notificationCount = 0;
                updateNotificationBadge();
            });
            
            // Add some initial sample notifications
            setTimeout(() => createNotification(templates.inquiry_received), 500);
            setTimeout(() => createNotification(templates.payment_received), 1000);
            setTimeout(() => createNotification(templates.showing_scheduled), 1500);
        });
        </script>
        <?php
    }
} 