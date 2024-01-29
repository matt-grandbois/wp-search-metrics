<?php

class WP_Search_Metrics_Init {

    protected static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->include_files();
        $this->init_hooks();
    }

    private function define_constants() {
        global $wpdb;
        // Define plugin path and table names.
        if (!defined('WP_SEARCH_METRICS_DIR_PATH')) {
            define('WP_SEARCH_METRICS_DIR_PATH', plugin_dir_path(dirname(__FILE__)));
        }
        if ( ! defined( 'WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE' ) ) {
            define( 'WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE', $wpdb->prefix . 'wp_search_metrics_search_queries' );
        }
        if ( ! defined( 'WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE' ) ) {
            define( 'WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE', $wpdb->prefix . 'wp_search_metrics_search_interactions' );
        }
        if ( ! defined( 'WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE' ) ) {
            define( 'WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE', $wpdb->prefix . 'wp_search_metrics_post_interactions' );
        }
    }

    private function include_files() {
        require_once( WP_SEARCH_METRICS_DIR_PATH . 'classes/class-wp-search-metrics-installer.php' );
        require_once( WP_SEARCH_METRICS_DIR_PATH . 'classes/tracking/class-ajax-search-tracker.php' );
        require_once( WP_SEARCH_METRICS_DIR_PATH . 'classes/tracking/class-search-results-page-tracker.php' );
		require_once( WP_SEARCH_METRICS_DIR_PATH . 'classes/admin/class-admin-dashboard.php' );
		require_once( WP_SEARCH_METRICS_DIR_PATH . 'classes/admin/class-admin-settings.php' );
    }

    private function init_hooks() {
        // Register activation and deactivation hooks.
        register_activation_hook(WP_SEARCH_METRICS_BASE_FILE, array('WP_Search_Metrics_Installer', 'activate'));
        register_deactivation_hook(WP_SEARCH_METRICS_BASE_FILE, array('WP_Search_Metrics_Installer', 'deactivate'));

        // Instantiate classes using their own init methods.
        Ajax_Search_Tracker::get_instance( WP_SEARCH_METRICS_BASE_FILE );
		Search_Results_Page_Tracker::get_instance( WP_SEARCH_METRICS_BASE_FILE );
		
		new WP_Search_Metrics_Admin_Dashboard();
		new WP_Search_Metrics_Admin_Settings();

        // Enqueue scripts and add admin menus with proper hooks
        add_action( 'wp_enqueue_scripts', array($this, 'wp_search_metrics_enqueue_scripts') );
    }

    public function wp_search_metrics_enqueue_scripts() {
        wp_enqueue_script('wp-search-metrics-js', plugins_url('src/js/wp-search-metrics.js', dirname(__FILE__)), array('jquery'), '1.0', true);

        // Localize the script with server-side data
        wp_localize_script('wp-search-metrics-js', 'wpSearchMetrics', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wp_search_metrics_nonce'),
        ));
    }
}

// Initialize the plugin.
WP_Search_Metrics_Init::get_instance();