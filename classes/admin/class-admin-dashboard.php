<?php

class WP_Search_Metrics_Admin_Dashboard {
	
	private $dashboard_page_hook_suffix;

    // Constructor
    public function __construct() {
        add_action('admin_menu', array($this, 'wp_search_metrics_add_menus'));
    }
    
    public function wp_search_metrics_add_menus() {
        $this->dashboard_page_hook_suffix = add_menu_page(
            __('WP Search Metrics', 'wp-search-metrics'),
            __('Search Metrics', 'wp-search-metrics'),
            'manage_options',
            'wp-search-metrics',
            array($this, 'wp_search_metrics_dashboard_page'),
            'dashicons-search',
            5
        );

        add_action('admin_enqueue_scripts', array($this, 'wp_search_metrics_enqueue_admin_scripts'));
    }

    public function wp_search_metrics_dashboard_page() {
        global $wpdb;
        
        // Query to get the Total Searches (the sum of query_count for all queries)
        $total_searches = $wpdb->get_var("SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE );

        // Query to get the total number of unique search queries
        $total_search_queries = $wpdb->get_var("SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE );

        // Query to get the total number of clicks from searches
        $total_search_clicks = $wpdb->get_var("SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " WHERE post_id IS NOT NULL AND interaction_type='conversion'" );
		
		$total_no_results_searches = $wpdb->get_var("SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " WHERE post_id IS NULL AND interaction_type='no_conversion'" );

        // Calculate the Search Conversion Rate
        $search_conversion_rate = $total_searches > 0 ? ($total_search_clicks / $total_searches) * 100 : 0;
        ?>
        <div class="wrap">
            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			
			<div style="width: 20rem;">
				<canvas id="myChart"></canvas>
			</div>
            
            <div class="wp-search-metrics-stats">
                <p><strong>Total Searches: </strong><?php echo absint($total_searches); ?></p>
                <p><strong>Total Number of Search Queries: </strong><?php echo absint($total_search_queries); ?></p>
                <p><strong>Total Number of Clicks from Searches: </strong><?php echo absint($total_search_clicks); ?></p>
                <p><strong>Search Conversion Rate: </strong><?php echo number_format_i18n($search_conversion_rate, 2); ?>%</p>
            </div>

            <!-- Search Queries Table -->
            <h3>Search Queries</h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Search Query</th>
                        <th>Search Count</th>
                        <th>Last Searched</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $search_queries_table = $wpdb->prefix . 'wp_search_metrics_search_queries';
                    $search_queries = $wpdb->get_results( "SELECT query_text, query_count, last_searched FROM {$search_queries_table}" );
                    foreach ( $search_queries as $query ) {
                        echo '<tr>';
                        echo '<td>' . esc_html( $query->query_text ) . '</td>';
                        echo '<td>' . absint( $query->query_count ) . '</td>';
                        echo '<td>' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $query->last_searched ) ) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>

            <!-- Pages/Posts Table -->
            <h3>Page Clicks</h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Click Count</th>
                        <th>Last Clicked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $post_interactions_table = $wpdb->prefix . 'wp_search_metrics_post_interactions';
                    $post_interactions = $wpdb->get_results( "SELECT post_id, click_count, last_clicked FROM {$post_interactions_table}" );
                    foreach ( $post_interactions as $interaction ) {
                        $post_title = get_the_title( $interaction->post_id );
                        echo '<tr>';
                        echo '<td>' . esc_html( $post_title ) . '</td>';
                        echo '<td>' . absint( $interaction->click_count ) . '</td>';
                        echo '<td>' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $interaction->last_clicked ) ) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>

            <!-- Search Queries to Clicks Table -->
            <h3>Search Queries &rarr; Clicks</h3>
            <table class="widefat">
            <thead>
            <tr>
            <th>Search Query</th>
            <th>Page Title</th>
            <th>Click Count</th>
            <th>Last Interaction Time</th>
            </tr>
            </thead>
            <tbody>
                <?php
                    $search_queries_table = $wpdb->prefix . 'wp_search_metrics_search_queries';
                    $search_interactions_table = $wpdb->prefix . 'wp_search_metrics_search_interactions';
                    $post_interactions_table = $wpdb->prefix . 'wp_search_metrics_post_interactions';
        
                    // Initial query to get each search query with how many different pages it is associated with
                    $query_counts = $wpdb->get_results("
                        SELECT sq.query_text, COUNT(DISTINCT si.post_id) as page_count
                        FROM {$search_interactions_table} as si
                        JOIN {$search_queries_table} as sq ON si.query_id = sq.id
                        GROUP BY sq.query_text
                        ORDER BY sq.query_text
                    ");

                    $query_page_counts = array_column($query_counts, 'page_count', 'query_text');

                    // Then query to get the interaction data
                    $search_interactions = $wpdb->get_results("
                        SELECT sq.query_text, p.ID as post_id, p.post_title, COUNT(si.post_id) as clicks_count, MAX(si.interaction_time) as last_interaction_time
                        FROM {$search_interactions_table} as si
                        JOIN {$search_queries_table} as sq ON si.query_id = sq.id
                        JOIN {$wpdb->posts} as p ON si.post_id = p.ID
                        GROUP BY sq.query_text, si.post_id
                        ORDER BY sq.query_text
                    ");

                    // Initialize variables to keep track of the current query and its rowspan
                    $current_query = '';
                    $rowspan = 0;

                    foreach ($search_interactions as $interaction) {
                        if ($interaction->query_text !== $current_query) {
                            // Update current query and rowspan when query changes
                            $current_query = $interaction->query_text;
                            $rowspan = $query_page_counts[$current_query];
                        }

                        echo '<tr>';
                        if ($rowspan === $query_page_counts[$current_query]) {
                            // Add rowspan attribute to query cell on the first row for the query
                            echo '<td rowspan="' . esc_attr($rowspan) . '">' . esc_html($current_query) . '</td>';
                        }

                        // Output page/post data
                        echo '<td>' . esc_html(get_the_title($interaction->post_id)) . '</td>';
                        echo '<td>' . esc_html($interaction->clicks_count) . '</td>';
                        echo '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($interaction->last_interaction_time))) . '</td>';
                        echo '</tr>';

                        // Decrement rowspan after outputting each row
                        $rowspan--;
                    }
                ?>
            </tbody>
            </table>


            <!-- No Result Search Queries Table -->
            <h3>No Result Search Queries</h3>
            <table class="widefat">
            <thead>
            <tr>
            <th>Search Query</th>
            <th>Search Volume</th>
            <th>Last Searched</th>
            </tr>
            </thead>
            <tbody>
                <?php
                    $search_queries_table = $wpdb->prefix . 'wp_search_metrics_search_queries';
                    $search_interactions_table = $wpdb->prefix . 'wp_search_metrics_search_interactions';

                    // SQL query to get the no result search queries
                    $no_result_queries = $wpdb->get_results("
                        SELECT sq.query_text, COUNT(si.query_id) as no_result_hits, MAX(sq.last_searched) as last_searched
                        FROM {$search_interactions_table} as si
                        RIGHT JOIN {$search_queries_table} as sq ON si.query_id = sq.id
                        WHERE si.post_id IS NULL OR si.id IS NULL
                        GROUP BY si.query_id
                        ORDER BY no_result_hits DESC, last_searched DESC
                    ");

                    foreach ($no_result_queries as $query) {
                        echo '<tr>';
                        echo '<td>' . esc_html($query->query_text) . '</td>';
                        echo '<td>' . absint($query->no_result_hits) . '</td>';
                        echo '<td>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($query->last_searched)) . '</td>';
                        echo '</tr>';
                    }
                ?>
            </tbody>
            </table>
            
            <!-- Bounced Queries Table -->
            <h3>Bounced Queries</h3>
            <table class="widefat">
            <thead>
            <tr>
            <th>Search Query</th>
            <th>No Conversion Hits</th>
            <th>Last Interaction</th>
            </tr>
            </thead>
            <tbody>
                <?php
                // SQL query to get the bounced search queries (no_conversion and post_id is NULL)
                $bounced_queries = $wpdb->get_results("
                    SELECT sq.query_text, COUNT(si.query_id) as no_conversion_hits, MAX(si.interaction_time) as last_interaction
                    FROM {$search_interactions_table} as si
                    INNER JOIN {$search_queries_table} as sq ON si.query_id = sq.id
                    WHERE si.interaction_type = 'no_conversion' AND si.post_id IS NULL
                    GROUP BY si.query_id
                    ORDER BY no_conversion_hits DESC, last_interaction DESC
                ");
                foreach ($bounced_queries as $query) {
                    echo '<tr>';
                    echo '<td>' . esc_html($query->query_text) . '</td>';
                    echo '<td>' . absint($query->no_conversion_hits) . '</td>';
                    echo '<td>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($query->last_interaction)) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
            </table>
            
        </div>
        <?php
    }
	
	public function wp_search_metrics_enqueue_admin_scripts($hook_suffix) {
        if ($hook_suffix === $this->dashboard_page_hook_suffix) {
			wp_enqueue_style(
				'wp-search-metrics-css', // Handle for the stylesheet.
				plugins_url('/src/css/admin/dashboard.css', dirname(dirname(__FILE__))),// Path to the stylesheet file.
				array(),                  // Dependencies (if any).
				'1.0',                    // Version number.
				'all'                     // Media for which this stylesheet is defined.
			);
			
            wp_enqueue_script(
                'wp-search-metrics-chartjs',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
                array(), // Dependencies, if any
                '4.4.1', // Version number
                true     // In footer
            );
			
			global $wpdb;
			
			// Query to get the total number of clicks from searches
        	$total_search_clicks = $wpdb->get_var(
				"SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . "
				WHERE post_id IS NOT NULL
				AND interaction_type='conversion'"
			);
		
			$total_no_results_searches = $wpdb->get_var(
				"SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . "
				WHERE post_id IS NULL
				AND interaction_type='no_conversion'"
			);
			
			$inline_script = "
			document.addEventListener('DOMContentLoaded', function() {
			  var ctx = document.getElementById('myChart').getContext('2d');
			  var myChart = new Chart(ctx, {
				type: 'doughnut',
				data: {
				  labels: [
					'Non-Converting',
					'Converting'
				  ],
				  datasets: [{
					label: 'Link Clicks',
					data: [" . $total_no_results_searches . ", " . $total_search_clicks . "],
					backgroundColor: [
					  'rgb(229, 229, 229)',
					  'rgb(65, 143, 222)',
					],
					weight: 1,
					borderWidth: 0,
					hoverOffset: 4
				  }]
				},
				options: {
					plugins: {
						title: {
							display: true,
							color: '#000',
                			text: 'Search Conversion Rate',
							padding: {
								bottom: 20
							},
							font: {
								size: 20
							}
						},
						legend: {
							display: true,
							position: 'bottom'
						}
					}
				}
			  });
			});
			";

			wp_add_inline_script('wp-search-metrics-chartjs', $inline_script);
        }
    }
}