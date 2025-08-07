<?php
/**
 * Houzez API Autoloader
 *
 * @package Houzez_API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Houzez API Autoloader Class
 *
 * Enhanced autoloader with namespace support, better performance, and maintainability.
 *
 * @since 1.0.0
 */
class Houzez_API_Autoloader {

    /**
     * Namespace prefix for the plugin
     *
     * @var string
     */
    private $namespace_prefix = 'Houzez_API_';

    /**
     * Base directory for the plugin's classes
     *
     * @var string
     */
    private $base_dir;

    /**
     * Path mapping cache
     *
     * @var array
     */
    private $path_mapping = [];

    /**
     * Directory mapping for different class types
     *
     * @var array
     */
    private $directory_mapping = [
        'route'      => 'routes',
        'admin'      => 'admin',
        'helper'     => 'helpers',
        'jwt_helper' => 'helpers',  // Added for JWT helper
        'base'       => 'abstracts',
        'trait'      => 'traits',
        'migration'  => 'migrations',
        'endpoint'   => 'endpoints',
        'cache'      => 'helpers',
        'ajax'       => '',
        'auth'       => '',
        'keys'       => '',
        'i18n'       => '',
        'activator'  => '',
        'deactivator' => ''
    ];

    /**
     * The Constructor
     */
    public function __construct() {
        $this->base_dir = untrailingslashit(HOUZEZ_API_PLUGIN_DIR) . '/includes/';
        
        // Register autoloader
        spl_autoload_register([$this, 'autoload']);

        // Initialize path mapping
        $this->initialize_path_mapping();
    }

    /**
     * Initialize path mapping for better performance
     *
     * @return void
     */
    private function initialize_path_mapping() {
        foreach ($this->directory_mapping as $type => $dir) {
            $full_path = $this->base_dir . $dir;
            if (is_dir($full_path)) {
                $this->path_mapping[$type] = $full_path;
            }
        }
    }

    /**
     * Convert class name to file name
     *
     * @param string $class Class name
     * @return string
     */
    private function get_file_name_from_class($class) {
        // Remove namespace prefix
        $class = str_replace($this->namespace_prefix, '', $class);
        
        // Special case for main plugin class
        if ($class === 'Houzez_API') {
            return 'class-houzez-api.php';
        }
        
        // Convert to lowercase and replace underscores
        return 'class-houzez-api-' . str_replace(
            ['_'],
            ['-'],
            strtolower($class)
        ) . '.php';
    }

    /**
     * Get the appropriate directory path for a class
     *
     * @param string $class Class name
     * @return string|null
     */
    private function get_class_path($class) {
        $class = strtolower($class);

        foreach ($this->directory_mapping as $type => $dir) {
            if (strpos($class, 'houzez_api_' . $type) === 0) {
                return isset($this->path_mapping[$type]) 
                    ? $this->path_mapping[$type] 
                    : $this->base_dir . $dir;
            }
        }

        // Special handling for compound helper names (e.g., JWT_Helper)
        if (strpos($class, 'houzez_api_') === 0 && strpos($class, '_helper') !== false) {
            return isset($this->path_mapping['helper']) 
                ? $this->path_mapping['helper'] 
                : $this->base_dir . 'helpers';
        }

        return $this->base_dir;
    }

    /**
     * Load a class file
     *
     * @param string $path File path
     * @return bool
     */
    private function load_file($path) {
        if ($path && is_readable($path)) {
            require_once $path;
            return true;
        }
        return false;
    }

    /**
     * Autoload Houzez API classes
     *
     * @param string $class Class name
     * @return void
     */
    public function autoload($class) {
        // Handle both namespaced and non-namespaced classes
        if (strpos($class, 'Houzez_API_') !== 0 && $class !== 'Houzez_API') {
            return;
        }

        $file_name = $this->get_file_name_from_class($class);
        $class_path = $this->get_class_path($class);

        // Try loading from the determined path
        if ($class_path && $this->load_file($class_path . '/' . $file_name)) {
            return;
        }

        // Fallback to base directory
        if ($this->load_file($this->base_dir . $file_name)) {
            return;
        }

        // Final fallback to abstracts directory
        $abstracts_path = $this->base_dir . 'abstracts/';
        if (is_dir($abstracts_path)) {
            $this->load_file($abstracts_path . $file_name);
        }
    }
}

// Initialize the autoloader
new Houzez_API_Autoloader(); 