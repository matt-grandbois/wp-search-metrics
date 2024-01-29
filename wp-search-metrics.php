<?php
/**
 * Plugin Name: WP Search Metrics
 * Plugin URI:  https://wpsearchmetrics.com/
 * Description: Delivers highly valuable metrics showcasing how your visitors use your search engine.
 * Version:     0.2.1
 * Author:      Matt Grandbois
 * Author URI:  https://mattgrandbois.com/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants.
define( 'WP_SEARCH_METRICS_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_SEARCH_METRICS_BASE_FILE', __FILE__ );

// Include the main initialization class file.
require_once( WP_SEARCH_METRICS_DIR_PATH . 'classes/class-wp-search-metrics-init.php' );