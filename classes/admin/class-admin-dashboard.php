<?php

require_once( WP_SEARCH_METRICS_DIR_PATH . 'classes/admin/ui/class-admin-header.php' );

class WP_Search_Metrics_Admin_Dashboard {

    protected static $instance = null;
    
    private $dashboard_page_hook_suffix;
    private $header;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'wp_search_metrics_add_menus'));
        $this->header = new WP_Search_Metrics_Admin_Header();
    }
    
    public function wp_search_metrics_add_menus() {
        $this->dashboard_page_hook_suffix = add_menu_page(
            __('Dashboard', 'wp-search-metrics'),
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
        <div class="h-full bg-gray-100">
            <div class="min-h-full">
                <?php $this->header->display(); ?>

                <main class="py-8 bg-gray-100">
                    <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
                        <div class="flex flex-col gap-6">
                            <!-- START Quick Stats -->
                            <div>
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Last 28 days</h3>
                                <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">Total Searches</dt>
                                    <dd class="mt-1 flex flex-row items-baseline text-3xl font-semibold tracking-tight text-gray-900">
                                        <span><?php echo $total_searches; ?></span>
                                        <span class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                            <svg class="h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="sr-only"> Increased by </span>
                                            122%
                                        </span>
                                    </dd>
                                    </div>
                                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">Total Page Clicks</dt>
                                    <dd class="mt-1 flex flex-row items-baseline text-3xl font-semibold tracking-tight text-gray-900">
                                        <span>1.5k</span>
                                        <span class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                            <svg class="h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="sr-only"> Increased by </span>
                                            80%
                                        </span>
                                    </dd>
                                    </div>
                                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">Clickthrough Rate</dt>
                                    <dd class="mt-1 flex flex-row items-baseline text-3xl font-semibold tracking-tight text-gray-900">
                                        <span>82.3%</span>
                                        <span class="ml-2 flex items-baseline text-sm font-semibold text-red-600">
                                            <svg class="h-5 w-5 flex-shrink-0 self-center text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="sr-only"> Decreased by </span>
                                            3.2%
                                        </span>
                                    </dd>
                                    </div>
                                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">Bounce Rate</dt>
                                    <dd class="mt-1 flex flex-row items-baseline text-3xl font-semibold tracking-tight text-gray-900">
                                        <span>8.3%</span>
                                        <span class="ml-2 flex items-baseline text-sm font-semibold text-green-500">
                                            <svg class="h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="sr-only"> Decreased by </span>
                                            6.7%
                                        </span>
                                    </dd>
                                    </div>
                                </dl>
                            </div>
                            <!-- END Quick Stats -->
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }
	
	public function wp_search_metrics_enqueue_admin_scripts($hook_suffix) {
        if ($hook_suffix === $this->dashboard_page_hook_suffix) {

            wp_enqueue_script(
                'wp-search-metrics-tailwind-css',
                'https://cdn.tailwindcss.com',
                array(),
                '4.4.1',
                false // in header
            );

			wp_enqueue_style(
				'wp-search-metrics-css',
				plugins_url('/src/css/admin/dashboard.css', dirname(dirname(__FILE__))),
				array(),
				'1.0',
				'all'
			);
			
            wp_enqueue_script(
                'wp-search-metrics-chartjs',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
                array(),
                '4.4.1',
                true // In footer
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