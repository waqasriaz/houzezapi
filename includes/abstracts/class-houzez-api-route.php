<?php
/**
 * Abstract API Route class
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Houzez_API_Route extends Houzez_API_Base {
    /**
     * The namespace for this route
     *
     * @var string
     */
    protected $namespace = 'houzez-api/v1';

    /**
     * The base for this route
     *
     * @var string
     */
    protected $rest_base;

    /**
     * Register routes
     */
    abstract public function register_routes();

} 