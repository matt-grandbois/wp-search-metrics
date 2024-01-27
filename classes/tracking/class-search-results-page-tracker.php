<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Search_Results_Page_Tracker {
	
	private static $instance = null;
    private $plugin_url;

    // Constructor
    private function __construct($plugin_file) {
        $this->plugin_url = plugin_dir_url($plugin_file);
        $this->init_hooks();
    }
	
	// Create or retrieve the single instance of the class
    public static function get_instance($plugin_file) {
        if (self::$instance === null) {
            self::$instance = new self($plugin_file);
        }
        return self::$instance;
    }
	
	// Initialize WordPress hooks
	private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'search_results_tracking_localize'));
        add_action('wp_ajax_wp_search_metrics_log_search_interaction_results_page', array($this, 'log_search_interaction'));
        add_action('wp_ajax_nopriv_wp_search_metrics_log_search_interaction_results_page', array($this, 'log_search_interaction'));
    }
    
    public function search_results_tracking_localize() {
        if (is_search()) {
            $search_query = get_search_query();
            $current_search_query = !empty($search_query) ? esc_html($search_query) : '';

            wp_enqueue_script('wp-search-metrics-results-page-js', $this->plugin_url . 'src/js/wp-search-metrics-results-page.js', array('jquery'), '1.0', true);
            wp_localize_script('wp-search-metrics-results-page-js', 'wpSearchMetricsResultsPage', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'is_search_results_page' => true,
                'current_search_query' => $current_search_query,
                'nonce' => wp_create_nonce('wp_search_metrics_results_page_nonce'),
            ));
        }
    }

    // AJAX handler for logging search interaction
	public function log_search_interaction() {
		global $wpdb;
		// Current UTC time in MySQL datetime format
		$current_datetime = gmdate('Y-m-d H:i:s');

		// Perform the security check
		check_ajax_referer('wp_search_metrics_results_page_nonce', 'nonce');

		// Get POST variables safely
		$search_query = sanitize_text_field($_POST['search_query']);
		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		$event_type = sanitize_text_field($_POST['event_type']);

		// First, check if search query exists and get its ID
		$search_query_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . " WHERE query_text = %s LIMIT 1",
				$search_query
			)
		);

		if (!$search_query_id) {
			// Insert new search query if it does not exist
			$wpdb->insert(
				WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE,
				array('query_text' => $search_query, 'query_count' => 1),
				array('%s', '%d')
			);
			$search_query_id = $wpdb->insert_id;
		} else {
			// Increment query count if it already exists
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . "
					 SET query_count = query_count + 1, last_searched = %s
					 WHERE id = %d",
					$current_datetime,
					$search_query_id
				)
			);
		}

		// If it's a click interaction, ensure the post interactions entry exists first
		if ($event_type === 'conversion') {
			$post_interaction_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM " . WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE . " WHERE post_id = %d",
					$post_id
				)
			);

			if (!$post_interaction_row) {
				// Insert a new row with click count as 1 if not exists
				$wpdb->insert(
					WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE,
					array(
						'post_id'       => $post_id,
						'click_count'   => 1,
						'last_clicked'  => $current_datetime,
					),
					array('%d', '%d', '%s')
				);
			}

			// Now, update the click count and last_clicked timestamp
			$wpdb->update(
				WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE,
				array(
					'click_count'   => $post_interaction_row ? $post_interaction_row->click_count + 1 : 1,
					'last_clicked'  => $current_datetime,
				),
				array('post_id' => $post_id),
				array('%d', '%s')
			);
		}

		// Set post_id to null if it's a non-interaction event
		$post_id = $event_type === 'no_conversion' ? null : $post_id;

		// Insert search interaction data into the interactions table
		$search_interaction_inserted = $wpdb->insert(
			WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE,
			array(
				'query_id' => $search_query_id,
				'post_id' => $post_id,
				'interaction_type' => $event_type,
				'interaction_time' => $current_datetime
			),
			array('%d', '%d', '%s', '%s')
		);

		// Prepare the response array
		$response = array(
			'success' => true,
			'search_query' => $search_query,
			'post_id' => $post_id,
			'message' => 'Logged interaction',
			'event_type' => $event_type,
			'db_insert_id' => $wpdb->insert_id // Optionally return the insert ID
		);

		// Return the JSON response
		wp_send_json($response);
	}
}