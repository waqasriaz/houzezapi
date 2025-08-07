<?php
/**
 * Cache Helper
 */

if (!defined('ABSPATH')) {
    exit;
}

class Houzez_API_Cache extends Houzez_API_Base {
    /**
     * Default cache expiration time in seconds (24 hours)
     */
    const DEFAULT_EXPIRATION = 86400;

    /**
     * Cache types for statistics and management
     */
    const CACHE_TYPES = [
        'properties' => 'Properties List',
        'property' => 'Single Property',
        'tax_property_types' => 'Property Types',
        'tax_property_status' => 'Property Status',
        'tax_property_features' => 'Property Features',
        'agents' => 'Agents List',
        'agent' => 'Single Agent',
        'agencies' => 'Agencies List',
        'agency' => 'Single Agency'
    ];

    /**
     * Get cached data or execute callback to get fresh data
     *
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int $expiration Cache expiration in seconds
     * @return mixed Cached or fresh data
     */
    public static function remember($key, $callback, $expiration = self::DEFAULT_EXPIRATION) {
        // Check if caching is enabled
        $options = get_option('houzez_api_settings', array());
        $enable_caching = isset($options['enable_caching']) ? $options['enable_caching'] : true;

        if (!$enable_caching) {
            return $callback();
        }

        // Get cache duration based on key type
        $expiration = self::get_cache_duration($key, $expiration);
        
        // If cache duration is 0, disable caching for this key
        if ($expiration === 0) {
            return $callback();
        }

        $cached = get_transient($key);
        
        if ($cached !== false) {
            // Update cache statistics
            self::update_cache_stats($key, 'hits');
            return $cached;
        }

        $fresh = $callback();
        set_transient($key, $fresh, $expiration);
        
        // Update cache statistics
        self::update_cache_stats($key, 'misses');
        return $fresh;
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public static function get_cache_stats() {
        global $wpdb;
        $stats = array();

        // Get total number of cached items by type
        $list_items = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_list_%'"
        );
        $single_items = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_single_%'"
        );
        $taxonomy_items = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_taxonomy_%'"
        );

        // Get cache size by type
        $list_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_list_%'"
        );
        $single_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_single_%'"
        );
        $taxonomy_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_taxonomy_%'"
        );

        // Get hits and misses
        $hits_misses = get_option('houzez_api_cache_stats', array(
            'hits' => 0,
            'misses' => 0,
            'by_type' => array()
        ));

        // Calculate hit rate
        $total_requests = $hits_misses['hits'] + $hits_misses['misses'];
        $hit_rate = $total_requests > 0 ? ($hits_misses['hits'] / $total_requests) * 100 : 0;

        return array(
            'total_items' => (int) ($list_items + $single_items + $taxonomy_items),
            'items_by_type' => array(
                'list' => (int) $list_items,
                'single' => (int) $single_items,
                'taxonomy' => (int) $taxonomy_items
            ),
            'cache_size' => self::format_size($list_size + $single_size + $taxonomy_size),
            'size_by_type' => array(
                'list' => self::format_size($list_size),
                'single' => self::format_size($single_size),
                'taxonomy' => self::format_size($taxonomy_size)
            ),
            'hits' => $hits_misses['hits'],
            'misses' => $hits_misses['misses'],
            'hit_rate' => round($hit_rate, 2),
            'by_type' => $hits_misses['by_type']
        );
    }

    /**
     * Update cache statistics
     *
     * @param string $key Cache key
     * @param string $type Hit or miss
     */
    private static function update_cache_stats($key, $type) {
        $stats = get_option('houzez_api_cache_stats', array(
            'hits' => 0,
            'misses' => 0,
            'by_type' => array(),
            'detailed_stats' => array()
        ));

        // Increment total hits/misses
        $stats[$type]++;

        // Get cache type from key
        $cache_type = self::determine_cache_type($key);
        
        // Initialize type-specific stats if not exists
        if (!isset($stats['by_type'][$cache_type])) {
            $stats['by_type'][$cache_type] = array('hits' => 0, 'misses' => 0);
        }
        $stats['by_type'][$cache_type][$type]++;

        // Initialize detailed statistics for this cache type if not exists
        if (!isset($stats['detailed_stats'][$cache_type])) {
            $stats['detailed_stats'][$cache_type] = array(
                'hits' => 0,
                'misses' => 0,
                'last_accessed' => '',
                'total_size' => 0,
                'items_count' => 0,
                'avg_response_time' => 0,
                'response_times' => array()
            );
        }

        // Update detailed statistics
        $stats['detailed_stats'][$cache_type][$type]++;
        $stats['detailed_stats'][$cache_type]['last_accessed'] = current_time('mysql');

        // Calculate response time for this operation
        $start_time = microtime(true);
        self::update_cache_type_stats($cache_type, $stats['detailed_stats'][$cache_type]);
        $response_time = (microtime(true) - $start_time) * 1000; // Convert to milliseconds

        // Update response times array
        $stats['detailed_stats'][$cache_type]['response_times'][] = $response_time;

        // Keep only last 100 response times
        if (count($stats['detailed_stats'][$cache_type]['response_times']) > 100) {
            $stats['detailed_stats'][$cache_type]['response_times'] = array_slice(
                $stats['detailed_stats'][$cache_type]['response_times'], 
                -100
            );
        }

        // Calculate average response time
        $stats['detailed_stats'][$cache_type]['avg_response_time'] = array_sum(
            $stats['detailed_stats'][$cache_type]['response_times']
        ) / count($stats['detailed_stats'][$cache_type]['response_times']);

        update_option('houzez_api_cache_stats', $stats);
    }

    /**
     * Determine cache type from cache key
     */
    private static function determine_cache_type($key) {
        // Remove transient prefix if exists
        $key = str_replace(['_transient_', 'houzez_api_'], '', $key);

        // Handle property location caches
        if (strpos($key, 'property_location') === 0) {
            return 'property_location';
        }

        // Handle taxonomy caches
        if (strpos($key, 'taxonomy_') === 0) {
            return substr($key, strpos($key, '_') + 1);
        }

        // Handle list/search caches
        if (strpos($key, 'list_') === 0) {
            $base_type = substr($key, 5); // Remove 'list_' prefix
            if (strpos($base_type, 'search') !== false) {
                // For search types (properties_search, agents_search, etc.)
                return $base_type;
            }
            return strtok($base_type, '_'); // Get base type before any additional parameters
        }

        // Handle single item caches
        if (strpos($key, 'single_') === 0) {
            return substr($key, 7); // Remove 'single_' prefix
        }

        // For direct types (properties, agents, etc.)
        $base_type = strtok($key, '_');
        if (!empty($base_type)) {
            // Check if it's a search type
            if (strpos($key, '_search') !== false) {
                return $base_type . '_search';
            }
            return $base_type;
        }

        return 'other';
    }

    /**
     * Update detailed statistics for a specific cache type
     */
    private static function update_cache_type_stats($cache_type, &$type_stats) {
        global $wpdb;

        // Build the pattern based on cache type
        $pattern = '';
        if (strpos($cache_type, 'tax_') === 0) {
            // For taxonomy types
            $taxonomy_type = substr($cache_type, 4); // Remove 'tax_' prefix
            $pattern = '%_transient_houzez_api_' . $taxonomy_type . '%';
        } else {
            // For other types (properties, agents, etc.)
            $pattern = '%_transient_houzez_api_%' . $cache_type . '%';
        }

        // Get total size and count for this cache type
        $size_query = $wpdb->prepare(
            "SELECT SUM(LENGTH(option_value)) as total_size, COUNT(*) as count 
            FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            AND option_name NOT LIKE '%_transient_timeout_%'",
            $pattern
        );
        
        $result = $wpdb->get_row($size_query);
        
        if ($result) {
            $type_stats['total_size'] = intval($result->total_size);
            $type_stats['items_count'] = intval($result->count);
        }

        // Calculate average response time
        if (!empty($type_stats['response_times'])) {
            $type_stats['avg_response_time'] = array_sum($type_stats['response_times']) / count($type_stats['response_times']);
        }

        // Keep only last 100 response times to avoid growing too large
        if (count($type_stats['response_times']) > 100) {
            $type_stats['response_times'] = array_slice($type_stats['response_times'], -100);
        }
    }

    /**
     * Get detailed cache statistics table data
     */
    public static function get_detailed_cache_stats() {
        $stats = get_option('houzez_api_cache_stats', array());
        $table_data = array();

        // Only process if we have by_type data
        if (isset($stats['by_type'])) {
            foreach ($stats['by_type'] as $type => $type_stats) {
                // Get detailed stats for this type if they exist
                $detailed_stats = isset($stats['detailed_stats'][$type]) ? $stats['detailed_stats'][$type] : array();
                
                // Calculate hit rate
                $total_requests = $type_stats['hits'] + $type_stats['misses'];
                $hit_rate = $total_requests > 0 ? ($type_stats['hits'] / $total_requests) * 100 : 0;

                // Format type name for display - remove hash if exists
                $type = preg_replace('/_[a-f0-9]{32}$/', '', $type); // Remove MD5 hash if present
                $label = ucwords(str_replace('_', ' ', $type));
                
                // Check if this type already exists in table_data
                $type_exists = false;
                foreach ($table_data as &$existing_data) {
                    if ($existing_data['type'] === $label) {
                        // Merge stats
                        $existing_data['hits'] += $type_stats['hits'];
                        $existing_data['misses'] += $type_stats['misses'];
                        $existing_data['items_count'] += isset($detailed_stats['items_count']) ? $detailed_stats['items_count'] : 0;
                        $existing_data['total_size'] += isset($detailed_stats['total_size']) ? $detailed_stats['total_size'] : 0;
                        
                        // Recalculate hit rate
                        $total = $existing_data['hits'] + $existing_data['misses'];
                        $existing_data['hit_rate'] = $total > 0 ? ($existing_data['hits'] / $total) * 100 : 0;
                        
                        // Update last accessed if newer
                        if (isset($detailed_stats['last_accessed']) && $detailed_stats['last_accessed'] > $existing_data['last_accessed']) {
                            $existing_data['last_accessed'] = $detailed_stats['last_accessed'];
                        }
                        
                        $type_exists = true;
                        break;
                    }
                }
                
                if (!$type_exists) {
                    $table_data[] = array(
                        'type' => $label,
                        'items_count' => isset($detailed_stats['items_count']) ? $detailed_stats['items_count'] : 0,
                        'total_size' => isset($detailed_stats['total_size']) ? $detailed_stats['total_size'] : 0,
                        'hits' => $type_stats['hits'],
                        'misses' => $type_stats['misses'],
                        'hit_rate' => round($hit_rate, 2),
                        'avg_response_time' => isset($detailed_stats['avg_response_time']) ? 
                            round($detailed_stats['avg_response_time'], 2) . 'ms' : 'N/A',
                        'last_accessed' => isset($detailed_stats['last_accessed']) ? $detailed_stats['last_accessed'] : 'Never'
                    );
                }
            }

            // Format sizes after merging
            foreach ($table_data as &$data) {
                $data['total_size'] = self::format_size($data['total_size']);
            }
        }

        return $table_data;
    }

    /**
     * Generate HTML for detailed cache statistics table
     */
    public static function get_cache_stats_table_html() {
        $table_data = self::get_detailed_cache_stats();
        
        $html = '<div class="cache-stats-table-wrapper">';
        $html .= '<h3>Cache Statistics by Type</h3>';
        $html .= '<table class="cache-stats-table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Cache Type</th>';
        $html .= '<th>Items</th>';
        $html .= '<th>Size</th>';
        $html .= '<th>Hits</th>';
        $html .= '<th>Misses</th>';
        $html .= '<th>Hit Rate</th>';
        $html .= '<th>Avg Response</th>';
        $html .= '<th>Last Accessed</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($table_data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($row['type']) . '</td>';
            $html .= '<td>' . number_format($row['items_count']) . '</td>';
            $html .= '<td>' . esc_html($row['total_size']) . '</td>';
            $html .= '<td>' . number_format($row['hits']) . '</td>';
            $html .= '<td>' . number_format($row['misses']) . '</td>';
            $html .= '<td>' . number_format($row['hit_rate'], 2) . '%</td>';
            $html .= '<td>' . esc_html($row['avg_response_time']) . '</td>';
            $html .= '<td>' . ($row['last_accessed'] && $row['last_accessed'] !== 'Never' ? 
                human_time_diff(strtotime($row['last_accessed']), current_time('timestamp')) . ' ago' : 
                'Never') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        // Add CSS styles
        $html .= '<style>
            .cache-stats-table-wrapper {
                margin: 20px 0;
                background: #fff;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .cache-stats-table-wrapper h3 {
                margin: 0 0 15px 0;
                color: #333;
                font-size: 16px;
            }
            .cache-stats-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                font-size: 13px;
            }
            .cache-stats-table th,
            .cache-stats-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #eee;
            }
            .cache-stats-table th {
                background: #f8f9fa;
                font-weight: 600;
                color: #333;
            }
            .cache-stats-table tr:hover {
                background-color: #f8f9fa;
            }
            .cache-stats-table td {
                color: #666;
            }
        </style>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Maximum cache size in bytes (default: 50MB)
     */
    public static function get_max_cache_size() {
        $options = get_option('houzez_api_settings', array());
        $size_mb = isset($options['cache_size_limit']) ? intval($options['cache_size_limit']) : 50;
        // Ensure size is between 1 MB and 1 GB
        $size_mb = max(1, min(1000, $size_mb));
        return $size_mb * 1024 * 1024; // Convert MB to bytes
    }

    /**
     * Related cache patterns for different item types
     */
    private static $related_cache_patterns = [
        'property' => [
            'property_reviews_property_id_%d',
            'similar_properties_%d'
        ],
        'property_country' => [
            'states_by_country_%s'  // %s for slug
        ],
        'property_state' => [
            'cities_by_state_%s'    // %s for slug
        ],
        'property_city' => [
            'areas_by_city_%s'      // %s for slug
        ],
        'agent' => [
            'agent_reviews_agent_id_%d',
            'agent_properties_%d',
            'agent_cities_%d'
        ],
        'agency' => [
            'agency_reviews_agency_id_%d',
            'agency_properties_%d',
            'agency_agents_%d'
        ]
    ];

    /**
     * Clear API cache by type
     *
     * @param string|array $types Type(s) of cache to clear (properties, agents, agencies, etc.)
     * @param int|null $id Specific ID to clear (optional)
     * @param array $options Additional options for clearing cache
     */
    public static function clear_api_cache($types, $id = null, $options = []) {
        global $wpdb;
        $patterns = [];

        // Convert single type to array
        if (!is_array($types)) {
            $types = [$types];
        }

        foreach ($types as $type) {
            // Add handling for property location caches
            if ($type === 'property_location') {
                if (isset($options['country_slug'])) {
                    $patterns[] = '%_transient_houzez_api_property_location_type_states_by_country_' . $options['country_slug'] . '%';
                } elseif (isset($options['state_slug'])) {
                    $patterns[] = '%_transient_houzez_api_property_location_type_cities_by_state_' . $options['state_slug'] . '%';
                } elseif (isset($options['city_slug'])) {
                    $patterns[] = '%_transient_houzez_api_property_location_type_areas_by_city_' . $options['city_slug'] . '%';
                } else {
                    // If no specific slug provided, clear all location caches
                    $patterns[] = '%_transient_houzez_api_property_location%';
                }
                continue;
            }

            // Check if this is a taxonomy cache clear request
            if (strpos($type, 'taxonomy_') === 0) {
                $taxonomy_key = str_replace('taxonomy_', '', $type);
                $patterns[] = '%_transient_houzez_api_' . $taxonomy_key . '%';
                continue;
            }

            // Check if this is a list/search cache clear request
            if (strpos($type, 'list_') === 0) {
                $list_key = str_replace('list_', '', $type);
                $patterns[] = '%_transient_houzez_api_' . $list_key . '_%';
                $patterns[] = '%_transient_houzez_api_' . $list_key . '_search_%';
                continue;
            }

            // Check if this is a single item cache clear request
            if (strpos($type, 'single_') === 0) {
                if (!$id) continue;
                $item_key = str_replace('single_', '', $type);
                $patterns[] = '%_transient_houzez_api_' . $item_key . '_' . $id . '%';
                
                // Add related caches for the item using the patterns map
                if (isset(self::$related_cache_patterns[$item_key])) {
                    foreach (self::$related_cache_patterns[$item_key] as $related_pattern) {
                        $patterns[] = '%_transient_houzez_api_' . sprintf($related_pattern, $id) . '%';
                    }
                }
                continue;
            }
        }

        if (!empty($patterns)) {
            // Add timeout patterns
            $timeout_patterns = array_map(function($pattern) {
                return str_replace('_transient_', '_transient_timeout_', $pattern);
            }, $patterns);
            
            $patterns = array_merge($patterns, $timeout_patterns);

            // Build the SQL for multiple patterns
            $sql_parts = array_fill(0, count($patterns), 'option_name LIKE %s');
            $sql = "DELETE FROM {$wpdb->options} WHERE " . implode(' OR ', $sql_parts);
            
            // Execute the query with all patterns
            $wpdb->query($wpdb->prepare($sql, $patterns));
        }
    }

    /**
     * Reset cache statistics
     */
    public static function reset_cache_stats() {
        $stats = array(
            'hits' => 0,
            'misses' => 0,
            'by_type' => array(),
            'detailed_stats' => array()
        );

        // Initialize stats for all cache types
        foreach (self::CACHE_TYPES as $type => $label) {
            $stats['by_type'][$type] = array('hits' => 0, 'misses' => 0);
            $stats['detailed_stats'][$type] = array(
                'hits' => 0,
                'misses' => 0,
                'last_accessed' => 'Never',
                'total_size' => 0,
                'items_count' => 0,
                'avg_response_time' => 0,
                'response_times' => array()
            );
        }

        update_option('houzez_api_cache_stats', $stats);
    }

    /**
     * Format size in bytes to human readable format
     *
     * @param int $size Size in bytes
     * @return string Formatted size
     */
    private static function format_size($size) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $size = max($size, 0);
        $pow = floor(($size ? log($size) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $size /= pow(1024, $pow);
        return round($size, 2) . ' ' . $units[$pow];
    }

    /**
     * Get cache duration based on key type and settings
     *
     * @param string $key Cache key
     * @param int $default_expiration Default expiration time
     * @return int Cache duration in seconds
     */
    private static function get_cache_duration($key, $default_expiration) {
        $options = get_option('houzez_api_settings', array());

        if (strpos($key, 'houzez_api_properties_') === 0) {
            return isset($options['properties_cache_time']) ? 
                   intval($options['properties_cache_time']) : 
                   86400; // 24 hours default
        }

        if (strpos($key, 'houzez_api_property_') === 0) {
            return isset($options['property_cache_time']) ? 
                   intval($options['property_cache_time']) : 
                   86400; // 24 hours default
        }

        if (strpos($key, 'houzez_api_agents_') === 0) {
            return isset($options['agents_cache_time']) ? 
                   intval($options['agents_cache_time']) : 
                   86400; // 24 hours default
        }

        if (strpos($key, 'houzez_api_agent_') === 0) {
            return isset($options['agent_cache_time']) ? 
                   intval($options['agent_cache_time']) : 
                   86400; // 24 hours default
        }

        if (strpos($key, 'houzez_api_agencies_') === 0) {
            return isset($options['agencies_cache_time']) ? 
                   intval($options['agencies_cache_time']) : 
                   86400; // 24 hours default
        }

        if (strpos($key, 'houzez_api_agency_') === 0) {
            return isset($options['agency_cache_time']) ? 
                   intval($options['agency_cache_time']) : 
                   86400; // 24 hours default
        }

        if (strpos($key, 'houzez_api_property_types') === 0 || 
            strpos($key, 'houzez_api_property_status') === 0) {
            return isset($options['taxonomy_cache_time']) ? 
                   intval($options['taxonomy_cache_time']) : 
                   86400; // 24 hours default
        }

        return $default_expiration;
    }

    /**
     * Clear cache by type
     *
     * @param string $type Type of cache to clear (properties, agents, agencies, etc.)
     * @return bool Success status
     */
    public static function clear_cache_by_type($type) {
        global $wpdb;
        
        if (empty($type)) {
            // Clear all caches
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '%_transient_houzez_api_%' 
                OR option_name LIKE '%_transient_timeout_houzez_api_%'"
            );
            
            // Reset all statistics
            self::reset_cache_stats();
            return true;
        }

        if (!array_key_exists($type, self::CACHE_TYPES)) {
            return false;
        }

        // Build patterns based on type
        $patterns = array();

        // Check if this is a taxonomy type
        $is_taxonomy = strpos($type, 'tax_') === 0;
        
        if ($is_taxonomy) {
            // Handle taxonomy caches - remove 'tax_' prefix for the actual cache key
            $taxonomy_type = substr($type, 4); // Remove 'tax_' prefix
            $patterns[] = '%_transient_houzez_api_' . $taxonomy_type . '%';
            $patterns[] = '%_transient_houzez_api_taxonomy_' . $taxonomy_type . '%';
        } else {
            // Handle list and single item caches
            $patterns[] = '%_transient_houzez_api_list_' . $type . '%';
            $patterns[] = '%_transient_houzez_api_single_' . $type . '%';
        }

        // Add timeout patterns
        $timeout_patterns = array_map(function($pattern) {
            return str_replace('_transient_', '_transient_timeout_', $pattern);
        }, $patterns);
        
        $patterns = array_merge($patterns, $timeout_patterns);

        // Build and execute the query
        if (!empty($patterns)) {
            $sql_parts = array_fill(0, count($patterns), 'option_name LIKE %s');
            $sql = "DELETE FROM {$wpdb->options} WHERE " . implode(' OR ', $sql_parts);
            $wpdb->query($wpdb->prepare($sql, $patterns));
        }

        // Reset statistics for this type
        $stats = get_option('houzez_api_cache_stats', array());
        if (isset($stats['by_type'][$type])) {
            $stats['by_type'][$type] = array('hits' => 0, 'misses' => 0);
            update_option('houzez_api_cache_stats', $stats);
        }

        return true;
    }

    /**
     * Generate a cache key based on request parameters
     *
     * @param string $base Base key name
     * @param array $params Request parameters
     * @return string
     */
    public static function generate_key($base, $params = []) {
        if (empty($params)) {
            return 'houzez_api_' . $base;
        }
        
        // Sort params to ensure consistent cache keys
        ksort($params);
        return 'houzez_api_' . $base . '_' . md5(serialize($params));
    }

    /**
     * Schedule automatic cache warming
     */
    public static function schedule_cache_warming() {
        $options = get_option('houzez_api_settings', array());
        $schedule = isset($options['cache_warming_schedule']) ? $options['cache_warming_schedule'] : 'daily';
        $hour = isset($options['cache_warming_hour']) ? intval($options['cache_warming_hour']) : 0;
        $minute = isset($options['cache_warming_minute']) ? intval($options['cache_warming_minute']) : 0;

        // Unschedule existing event if any
        self::unschedule_cache_warming();

        // Calculate next run time in site's timezone
        $timezone = wp_timezone();
        $datetime = new DateTime('now', $timezone);
        $datetime->setTime($hour, $minute, 0);
        
        // If time has passed for today, move to next occurrence
        $current = new DateTime('now', $timezone);
        if ($datetime <= $current) {
            switch ($schedule) {
                case 'hourly':
                    $datetime->modify('+1 hour');
                    break;
                case 'twicedaily':
                    $datetime->modify('+12 hours');
                    break;
                case 'daily':
                    $datetime->modify('+1 day');
                    break;
                case 'weekly':
                    $datetime->modify('+1 week');
                    break;
            }
        }

        // Convert to timestamp in UTC
        $next_run = $datetime->getTimestamp();

        // Schedule the event
        if (!wp_next_scheduled('houzez_api_warm_cache')) {
            wp_schedule_event($next_run, $schedule, 'houzez_api_warm_cache');
            
            // Log scheduling
            error_log(sprintf(
                'Houzez API: Scheduled cache warming for %s at %s (Schedule: %s)',
                $datetime->format('Y-m-d'),
                $datetime->format('H:i'),
                $schedule
            ));
        }
    }

    /**
     * Unschedule automatic cache warming
     */
    public static function unschedule_cache_warming() {
        $timestamp = wp_next_scheduled('houzez_api_warm_cache');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'houzez_api_warm_cache');
        }
    }

    /**
     * Check cache size and prune if necessary
     */
    public static function check_and_prune_cache() {
        global $wpdb;
        
        // Get current cache size
        $cache_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_%'"
        );

        $max_size = self::get_max_cache_size();

        if ($cache_size > $max_size) {
            // Get oldest cache items first
            $items = $wpdb->get_results(
                "SELECT option_name, option_value FROM {$wpdb->options} 
                WHERE option_name LIKE '%_transient_houzez_api_%' 
                ORDER BY option_id ASC"
            );

            $freed_space = 0;
            foreach ($items as $item) {
                // Remove transient (strip _transient_ prefix)
                $transient_name = str_replace('_transient_', '', $item->option_name);
                delete_transient($transient_name);
                
                $freed_space += strlen($item->option_value);
                if (($cache_size - $freed_space) <= $max_size) {
                    break;
                }
            }

            // Log pruning activity
            $pruned_size = self::format_size($freed_space);
            error_log("Houzez API: Pruned {$pruned_size} from cache");
            
            // Update last prune time
            update_option('houzez_api_last_cache_prune', current_time('mysql'));
        }
    }

    /**
     * Export cache statistics
     *
     * @return array Cache statistics data
     */
    public static function export_cache_stats() {
        $stats = get_option('houzez_api_cache_stats', array());
        $stats['export_date'] = current_time('mysql');
        return $stats;
    }

    /**
     * Import cache statistics
     *
     * @param array $stats Statistics data to import
     * @return bool Success status
     */
    public static function import_cache_stats($stats) {
        if (!is_array($stats) || !isset($stats['hits']) || !isset($stats['misses'])) {
            return false;
        }

        return update_option('houzez_api_cache_stats', $stats);
    }

    /**
     * Get cache health status
     *
     * @return array Cache health information
     */
    public static function get_cache_health() {
        global $wpdb;
        
        // Get total size by type
        $list_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_list_%'"
        ) ?: 0;
        $single_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_single_%'"
        ) ?: 0;
        $taxonomy_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_houzez_api_taxonomy_%'"
        ) ?: 0;

        $total_size = $list_size + $single_size + $taxonomy_size;
        $max_size = self::get_max_cache_size();
        $stats = self::get_cache_stats();
        $size_percentage = ($total_size / $max_size) * 100;

        // Get expiring soon count (caches that will expire in next 24 hours)
        $expiring_soon = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE '%_transient_timeout_houzez_api_%' 
            AND option_value BETWEEN %d AND %d",
            time(),
            time() + 86400
        ));

        return array(
            'total_size' => self::format_size($total_size),
            'size_by_type' => array(
                'list' => self::format_size($list_size),
                'single' => self::format_size($single_size),
                'taxonomy' => self::format_size($taxonomy_size)
            ),
            'max_size' => self::format_size($max_size),
            'size_percentage' => round($size_percentage, 2),
            'status' => $size_percentage > 90 ? 'critical' : ($size_percentage > 70 ? 'warning' : 'good'),
            'hit_rate' => $stats['hit_rate'],
            'items_count' => $stats['total_items'],
            'expiring_soon' => (int) $expiring_soon,
            'last_pruned' => get_option('houzez_api_last_cache_prune', 'Never'),
            'last_warmed' => get_option('houzez_api_last_cache_warm', 'Never')
        );
    }

    /**
     * Automatically warm specific cache items
     *
     * @param string $type Type of cache to warm (properties, taxonomies, etc.)
     * @return array Results of warming operation
     */
    public static function auto_warm_cache($type = 'all') {
        // Log the start of cache warming
        error_log('Houzez API: Starting cache warming process for type: ' . $type);
        
        $results = array('success' => array(), 'failed' => array());
        $start_time = microtime(true);

        try {
            switch ($type) {
                case 'properties':
                    // Warm first 3 pages of properties with different page sizes
                    $page_sizes = array(10, 20, 50);
                    foreach ($page_sizes as $per_page) {
                        for ($page = 1; $page <= 3; $page++) {
                            try {
                                $params = array(
                                    'per_page' => $per_page,
                                    'paged' => $page
                                );
                                $key = self::generate_key('properties', $params);
                                self::remember($key, function() use ($params) {
                                    //return Houzez_API_Endpoint_Properties::format_properties_response($params);
                                });
                                $results['success'][] = "Properties Page {$page} (Per Page: {$per_page})";
                            } catch (Exception $e) {
                                $results['failed'][] = "Properties Page {$page} (Per Page: {$per_page}) - Error: {$e->getMessage()}";
                                error_log("Houzez API Cache Warming Error: {$e->getMessage()}");
                            }
                        }
                    }
                    break;

                case 'agents':
                    // Warm first 2 pages of agents with different page sizes
                    $page_sizes = array(10, 20);
                    foreach ($page_sizes as $per_page) {
                        for ($page = 1; $page <= 2; $page++) {
                            try {
                                $params = array(
                                    'per_page' => $per_page,
                                    'paged' => $page,
                                );
                                $key = self::generate_key('agents', $params);
                                self::remember($key, function() use ($params) {

                                    $params['post_type'] = 'houzez_agent';
                                    $params['post_status'] = 'publish';

                                    $query = new WP_Query($params);
                                    $agents = array();
                                    
                                    if ($query->have_posts()) {
                                        while ($query->have_posts()) {
                                            $query->the_post();
                                            $agents[] = Houzez_API_Endpoint_Agents::format_agent(get_the_ID());
                                        }
                                    }
                                    wp_reset_postdata();

                                    return array(
                                        'agents' => $agents,
                                        'total_records' => $query->found_posts,
                                        'pages' => $query->max_num_pages,
                                        'page' => $params['paged'],
                                        'has_pages' => $query->max_num_pages > 0,
                                        'posts_per_page' => $params['per_page']
                                    );
                                });
                                $results['success'][] = "Agents Page {$page} (Per Page: {$per_page})";
                            } catch (Exception $e) {
                                $results['failed'][] = "Agents Page {$page} (Per Page: {$per_page}) - Error: {$e->getMessage()}";
                                error_log("Houzez API Cache Warming Error: {$e->getMessage()}");
                            }
                        }
                    }
                    break;

                case 'taxonomies':
                    // Warm all taxonomy-related caches
                    $taxonomies = array(
                        'property_type' => 'Property Types',
                        'property_status' => 'Property Status',
                        'property_feature' => 'Property Features',
                        'agent_category' => 'Agent Categories',
                        'agent_city' => 'Agent Cities'
                    );
                    
                    foreach ($taxonomies as $taxonomy => $label) {
                        try {
                            $key = self::generate_key($taxonomy);
                            self::remember($key, function() use ($taxonomy) {
                                $terms = get_terms(array(
                                    'taxonomy' => $taxonomy,
                                    'hide_empty' => false,
                                    'number' => 0
                                ));
                                return array_map([Houzez_API_Helper::class, 'format_taxonomy_term'], $terms);
                            });
                            $results['success'][] = $label;
                        } catch (Exception $e) {
                            $results['failed'][] = "{$label} - Error: {$e->getMessage()}";
                            error_log("Houzez API Cache Warming Error: {$e->getMessage()}");
                        }
                    }
                    break;

                case 'all':
                    // Warm everything
                    $taxonomies_result = self::auto_warm_cache('taxonomies');
                    $properties_result = self::auto_warm_cache('properties');
                    $agents_result = self::auto_warm_cache('agents');
                    
                    $results['success'] = array_merge(
                        $taxonomies_result['success'],
                        $properties_result['success'],
                        $agents_result['success']
                    );
                    $results['failed'] = array_merge(
                        $taxonomies_result['failed'],
                        $properties_result['failed'],
                        $agents_result['failed']
                    );
                    break;
            }

            // Calculate execution time
            $execution_time = microtime(true) - $start_time;
            
            // Log completion
            error_log(sprintf(
                'Houzez API: Cache warming completed in %.2f seconds. Success: %d, Failed: %d',
                $execution_time,
                count($results['success']),
                count($results['failed'])
            ));

            // Update last warm time
            update_option('houzez_api_last_cache_warm', current_time('mysql'));
            
            return $results;
        } catch (Exception $e) {
            error_log("Houzez API Cache Warming Fatal Error: {$e->getMessage()}");
            return array(
                'success' => array(),
                'failed' => array('Fatal error: ' . $e->getMessage())
            );
        }
    }

    /**
     * Initialize the class
     */
    public function init() {
        // Add cache warming cron action
        add_action('houzez_api_warm_cache', array(__CLASS__, 'auto_warm_cache'));
        
        // Add cache pruning check on init (runs once per request)
        add_action('init', array(__CLASS__, 'check_and_prune_cache'));

        // Schedule initial cache warming if not already scheduled
        if (!wp_next_scheduled('houzez_api_warm_cache')) {
            self::schedule_cache_warming();
        }

        // Initialize cache statistics if they don't exist
        self::initialize_cache_stats();
    }

    /**
     * Initialize cache statistics
     */
    private static function initialize_cache_stats() {
        $stats = get_option('houzez_api_cache_stats', array());
        
        if (empty($stats) || !isset($stats['detailed_stats'])) {
            $stats = array(
                'hits' => 0,
                'misses' => 0,
                'by_type' => array(),
                'detailed_stats' => array()
            );
            update_option('houzez_api_cache_stats', $stats);
        }
    }
} 