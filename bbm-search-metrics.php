<?php
/**
 * Plugin Name: BBM Search Metrics
 * Plugin URI:  https://beyondbluemedia.com/
 * Description: Tracks how users interact with the search feature.
 * Version:     1.0
 * Author:      Matt Grandbois
 * Author URI:  https://mattgrandbois.com/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants.
define( 'BBM_SEARCH_METRICS_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'BBM_SEARCH_METRICS_BASE_FILE', __FILE__ );

// Include the main initialization class file.
require_once( BBM_SEARCH_METRICS_DIR_PATH . 'classes/class-bbm-search-metrics-init.php' );