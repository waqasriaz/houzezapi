<?php
// Include WordPress core
require_once(__DIR__ . '/../../../../wp-load.php');

class Houzez_API_Tester {
    private $base_url;
    private $api_key;
    private $token = null;  // Start with null token
    private $bearer_token = null;

    public function __construct() {
        $this->base_url = get_site_url() . '/wp-json/houzez-api/v1';  // Using houzez-api/v1 namespace
        $this->setup_api_key();
    }

    private function setup_api_key() {
        // Get existing API keys
        $keys = Houzez_API_Keys::get_all_keys();
        
        // Find first active key for testing
        foreach ($keys as $key_data) {
            if ($key_data['status'] === 'active' && 
                (empty($key_data['expires_at']) || strtotime($key_data['expires_at']) > time())) {
                $this->api_key = $key_data['api_key'];
                break;
            }
        }

        // If no active key found, create one for testing
        if (!$this->api_key) {
            $this->api_key = Houzez_API_Keys::generate_key(
                'Test API Key',
                'Generated for API testing',
                30 // expires in 30 days
            );
        }
    }

    public function run_tests() {
        echo "\nStarting Houzez API Tests...\n";
        echo "================================\n";
        echo "Testing URL: {$this->base_url}\n";
        echo "Using API Key: {$this->api_key}\n";

        $this->test_api_key_validation();

        $this->test_registration();
        // First run login to get a fresh token
        $this->test_login();

        // Then test profile if we got a token
        if ($this->token) {
            $this->test_get_profile();
            $this->test_update_profile();
            $this->test_get_properties();
        } else {
            echo "\nSkipping profile test - no valid token available\n";
        }

        echo "\nTests completed!\n";
    }

    private function test_api_key_validation() {
        echo "\nTesting API Key Validation...\n";

        // Test with valid API key
        $test_data = array(
            'username' => 'agent',
            'password' => '123456'
        );

        $response = wp_remote_post($this->base_url . '/auth/login', array(
            'body' => json_encode($test_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ),
            'sslverify' => false
        ));

        $this->print_response('Valid API Key', $response);

        // Test with invalid API key
        $response = wp_remote_post($this->base_url . '/auth/login', array(
            'body' => json_encode($test_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => 'invalid_key_12345'
            ),
            'sslverify' => false
        ));

        $this->print_response('Invalid API Key', $response);
    }

    private function test_registration() {
        echo "\nTesting Registration...\n";
        
        $test_user = array(
            'username' => 'testuser_' . time(),
            'email' => 'testuser_' . time() . '@example.com',
            'password' => 'Test@123'
        );

        $response = wp_remote_post($this->base_url . '/auth/register', array(
            'body' => json_encode($test_user),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ),
            'sslverify' => false
        ));

        $this->print_response('Registration', $response);
    }

    private function test_login() {
        echo "\nTesting Login...\n";
        
        $credentials = array(
            'username' => 'agent', // Use an existing username
            'password' => '123456'  // Use the correct password
        );

        $response = wp_remote_post($this->base_url . '/auth/login', array(
            'body' => json_encode($credentials),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ),
            'sslverify' => false
        ));

        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['data']['token'])) {
                $this->token = $body['data']['token'];
                $this->bearer_token = 'Bearer ' . $this->token;
                echo "Token received successfully!\n";
                echo "Raw token: " . $this->token . "\n";
                echo "Bearer token: " . $this->bearer_token . "\n";
            }
        }

        $this->print_response('Login', $response);
    }

    private function test_get_profile() {
        echo "\nTesting Get Profile...\n";
        
        $headers = array(
            'Authorization' => $this->bearer_token,
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->api_key
        );
        
        echo "\nRequest Headers:\n";
        print_r($headers);
        
        $response = wp_remote_get($this->base_url . '/auth/me', array(
            'headers' => $headers,
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            echo "\nWP Error encountered:\n";
            echo $response->get_error_message() . "\n";
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_headers = wp_remote_retrieve_headers($response);
            
            echo "\nResponse Code: " . $response_code . "\n";
            echo "\nResponse Body:\n";
            echo $response_body . "\n";
            echo "\nResponse Headers:\n";
            print_r($response_headers);
        }

        $this->print_response('Get Profile', $response);
    }

    private function test_update_profile() {
        echo "\nTesting Update Profile...\n";
        
        $update_data = array(
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '1234567890'
        );

        $response = wp_remote_post($this->base_url . '/auth/profile', array(
            'body' => json_encode($update_data),
            'headers' => array(
                'Authorization' => $this->bearer_token,
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ),
            'sslverify' => false,
            'method' => 'POST'
        ));

        $this->print_response('Update Profile', $response);
    }

    private function test_get_properties() {
        echo "\nTesting Get User Properties...\n";
        
        $response = wp_remote_get($this->base_url . '/auth/my-properties?' . http_build_query([
            'per_page' => 10,
            'paged' => 1
        ]), array(
            'headers' => array(
                'Authorization' => $this->bearer_token,
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ),
            'sslverify' => false
        ));

        $this->print_response('User Properties', $response);

        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['data'])) {
                echo "\nPagination Info:\n";
                echo "Total Properties: " . $body['data']['total'] . "\n";
                echo "Total Pages: " . $body['data']['pages'] . "\n";
                echo "Properties in this page: " . count($body['data']['properties']) . "\n";
            }
        }
    }

    private function print_response($test_name, $response) {
        echo "\n$test_name Response:\n";
        echo "------------------------\n";
        
        if (is_wp_error($response)) {
            echo "Error: " . $response->get_error_message() . "\n";
            return;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        echo "Status Code: $status_code\n";
        echo "Response Body:\n";
        echo json_encode($body, JSON_PRETTY_PRINT) . "\n";
    }
}

// Run tests if accessed directly or through admin AJAX
if (php_sapi_name() !== 'cli' && ((!defined('DOING_AJAX') || !DOING_AJAX) || (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action']) && $_REQUEST['action'] === 'houzez_api_run_tests'))) {
    // Set content type to plain text for direct access
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        header('Content-Type: text/plain');
    }
    
    // Run the tests
    $tester = new Houzez_API_Tester();
    $tester->run_tests();
} 