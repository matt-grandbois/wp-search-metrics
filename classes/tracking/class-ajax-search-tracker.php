<?php
// Ensure that the file cannot be accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Ajax_Search_Tracker {
	
	private static $instance = null;
    private $plugin_url;

    // Constructor
    public function __construct($plugin_file) {
        $this->plugin_url = plugin_dir_url($plugin_file);
        $this->init_hooks();
    }
	
	// Create and access the single instance of the class.
    public static function get_instance($plugin_file) {
        if (self::$instance === null) {
            self::$instance = new self($plugin_file);
        }
        return self::$instance;
    }
	
	// Initialize WordPress hooks here.
    private function init_hooks() {
        add_action('wp_ajax_wp_search_metrics_log_search_interaction', array($this, 'log_ajax_search_interaction'));
        add_action('wp_ajax_nopriv_wp_search_metrics_log_search_interaction', array($this, 'log_ajax_search_interaction'));

        add_action('wp_ajax_wp_search_metrics_log_no_results', array($this, 'log_ajax_search_no_results'));
        add_action('wp_ajax_nopriv_wp_search_metrics_log_no_results', array($this, 'log_ajax_search_no_results'));
    }
	
	public function log_ajax_search_interaction() {
		global $wpdb;
		// check security of nonce
		check_ajax_referer('wp_search_metrics_nonce', 'nonce');  // Security check

		// Extract and sanitize the data from the AJAX request
		$search_query  = sanitize_text_field($_POST['search_query']);
		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		$event_type = sanitize_text_field($_POST['event_type']);

		// Check if query already exists in the queries table
		$query_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . " WHERE query_text = %s LIMIT 1",
				$search_query
			)
		);

		if(!$query_id) {
			// Insert new search query if it does not exist
			$wpdb->insert(
				WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE,
				array('query_text' => $search_query, 'query_count' => 1),
				array('%s', '%d')
			);
			$query_id = $wpdb->insert_id;
		} else {
			// Increment query count if it already exists
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . " SET query_count = query_count + 1, last_searched = NOW() WHERE id = %d",
					$query_id
				)
			);
		}

		// Check if post interaction already exists in the post_interactions table
		$post_interaction_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM " . WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE . " WHERE post_id = %d LIMIT 1",
				$post_id
			)
		);

		if(!$post_interaction_exists) {
			// Insert new post interaction if it does not exist
			$wpdb->insert(
				WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE,
				array('post_id' => $post_id, 'click_count' => 1),
				array('%d', '%d')
			);
		} else {
			// Increment click count if it already exists
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE " . WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE . " SET click_count = click_count + 1, last_clicked = NOW() WHERE post_id = %d",
					$post_id
				)
			);
		}

		// Log search interactions with post
		$wpdb->insert(
			WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE,
			array(
				'query_id' => $query_id,
				'post_id' => $post_id,
				'interaction_type' => $event_type
			),
			array('%d', '%d', '%s')
		);

		// Send a JSON success response with a message
		wp_send_json_success('Successful interaction logged');
	}

	function log_ajax_search_no_results() {
		global $wpdb;
		// check security of nonce
		check_ajax_referer('wp_search_metrics_nonce', 'nonce');  // Security check

		// Extract and sanitize the data from the AJAX request
		$search_query  = sanitize_text_field($_POST['search_query']);
		$event_type  = sanitize_text_field($_POST['event_type']);

		// Check if query already exists in the queries table
		$query_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . " WHERE query_text = %s LIMIT 1",
				$search_query
			)
		);

		if(!$query_id) {
			// Insert new search query if it does not exist
			$wpdb->insert(
				WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE,
				array('query_text' => $search_query, 'query_count' => 1),
				array('%s', '%d')
			);
			$query_id = $wpdb->insert_id;
		} else {
			// Increment query count if it already exists
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . "
					 SET query_count = query_count + 1, last_searched = NOW()
					 WHERE id = %d",
					$query_id
				)
			);
		}

		// Log the "no results" interaction
		$wpdb->insert(
			WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE,
			array(
				'query_id' => $query_id,
				// Making sure to use `null` to signify no post was clicked
				'post_id' => null,
				'interaction_type' => $event_type
			),
			array('%d', '%d', '%s') // Defining null `post_id` as `%d` will insert it correctly as NULL
		);

		// Send a JSON success response with a message
		wp_send_json_success('No results interaction logged');
	}
	
	
}