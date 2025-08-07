<?php
/**
 * Test Push Notification Settings
 */

// Check if push notification class exists
if (class_exists('Houzez_API_Push_Notifications')) {
    echo "✓ Push Notification class exists\n";
    
    // Get instance
    $push = Houzez_API_Push_Notifications::get_instance();
    echo "✓ Push Notification instance created\n";
    
    // Check if settings are registered
    global $wp_settings_sections, $wp_settings_fields;
    
    echo "\n--- Registered Settings Sections ---\n";
    if (isset($wp_settings_sections['houzez_api_settings'])) {
        foreach ($wp_settings_sections['houzez_api_settings'] as $section) {
            echo "Section ID: " . $section['id'] . "\n";
            echo "Title: " . $section['title'] . "\n";
        }
    } else {
        echo "No sections registered for houzez_api_settings\n";
    }
    
    echo "\n--- Registered Settings Fields ---\n";
    if (isset($wp_settings_fields['houzez_api_settings'])) {
        foreach ($wp_settings_fields['houzez_api_settings'] as $section_id => $fields) {
            echo "\nSection: $section_id\n";
            foreach ($fields as $field_id => $field) {
                echo "  - Field: $field_id\n";
            }
        }
    } else {
        echo "No fields registered for houzez_api_settings\n";
    }
    
    echo "\n--- Registered Options ---\n";
    $options = [
        'houzez_api_push_enabled',
        'houzez_api_push_service',
        'houzez_api_onesignal_app_id',
        'houzez_api_onesignal_api_key',
        'houzez_api_firebase_server_key'
    ];
    
    foreach ($options as $option) {
        $value = get_option($option, 'NOT_SET');
        echo "$option: $value\n";
    }
    
} else {
    echo "✗ Push Notification class not found\n";
}

// Check hooks
global $wp_filter;
echo "\n--- Hooks ---\n";
if (isset($wp_filter['admin_init'])) {
    $found = false;
    foreach ($wp_filter['admin_init'] as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && 
                is_object($callback['function'][0]) && 
                get_class($callback['function'][0]) === 'Houzez_API_Push_Notifications' &&
                $callback['function'][1] === 'add_settings_fields') {
                echo "✓ Push notification settings hook found at priority $priority\n";
                $found = true;
            }
        }
    }
    if (!$found) {
        echo "✗ Push notification settings hook not found in admin_init\n";
    }
}

// Test manual settings registration
echo "\n--- Manual Settings Registration Test ---\n";
if (class_exists('Houzez_API_Push_Notifications')) {
    $push = Houzez_API_Push_Notifications::get_instance();
    $push->add_settings_fields();
    echo "✓ Manually called add_settings_fields()\n";
    
    // Check again
    if (isset($wp_settings_sections['houzez_api_settings']['houzez_api_push_settings'])) {
        echo "✓ Push notification section now registered\n";
    } else {
        echo "✗ Push notification section still not registered\n";
    }
} 