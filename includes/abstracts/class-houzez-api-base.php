<?php
/**
 * Base API class
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Houzez_API_Base {
    /**
     * Plugin version.
     *
     * @var string
     */
    protected $version;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->version = HOUZEZ_API_VERSION;
    }

    /**
     * Initialize the class
     * 
     * This method can be implemented as either static or non-static in child classes
     */
    public function init() {
        // Default implementation
        // Child classes can override this with either static or non-static implementation
    }
} 