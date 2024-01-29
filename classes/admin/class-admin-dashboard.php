<?php

require_once( WP_SEARCH_METRICS_DIR_PATH . 'classes/admin/ui/class-admin-header.php' );

// import functions
require_once( WP_SEARCH_METRICS_DIR_PATH . 'functions/admin/function-convert_timestamp_from_utc_to_local.php' );
require_once( WP_SEARCH_METRICS_DIR_PATH . 'functions/admin/function-get_total_searches_within_date_range.php' );
require_once( WP_SEARCH_METRICS_DIR_PATH . 'functions/admin/function-get_total_clicks_within_date_range.php' );
require_once( WP_SEARCH_METRICS_DIR_PATH . 'functions/admin/function-get_total_no_conversion_searches_within_date_range.php' );
require_once( WP_SEARCH_METRICS_DIR_PATH . 'functions/admin/function-get_top_search_terms_within_date_range.php' );
require_once( WP_SEARCH_METRICS_DIR_PATH . 'functions/admin/function-get_top_clicked_pages_within_date_range.php' );
require_once( WP_SEARCH_METRICS_DIR_PATH . 'functions/admin/function-get_top_non_converting_search_terms_within_date_range.php' );
require_once( WP_SEARCH_METRICS_DIR_PATH . 'functions/admin/function-get_search_terms_resulting_in_page_clicks.php' );

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
        
        $date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '28'; // get date range if set, otherwise use default 28 days

        // Figure out date range and assign variables accordingly
        if ($date_range === '1') { // Today
            $recent_start_date = date('Y-m-d', strtotime('0 day'));
            $recent_end_date = date('Y-m-d', strtotime('now'));

            $preceding_start_date = date('Y-m-d', strtotime('-1 day'));
            $preceding_end_date = date('Y-m-d', strtotime('-1 day'));
        }
        else if ($date_range === '7') { // Last 7 days
            $recent_start_date = date('Y-m-d', strtotime('-6 days'));
            $recent_end_date = date('Y-m-d', strtotime('now'));

            $preceding_start_date = date('Y-m-d', strtotime('-13 days'));
            $preceding_end_date = date('Y-m-d', strtotime('-7 days'));
        }
        else if ($date_range === '28') { // Last 28 days
            $recent_start_date = date('Y-m-d', strtotime('-27 days')); // 28 days including today
            $recent_end_date = date('Y-m-d', strtotime('now')); // Today

            $preceding_start_date = date('Y-m-d', strtotime('-55 days')); // Start date for the 28 days before the most recent 28 days
            $preceding_end_date = date('Y-m-d', strtotime('-28 days')); // End date for the 28 days before the most recent 28 days
        } else { // default to 28 days
            $recent_start_date = date('Y-m-d', strtotime('-27 days'));
            $recent_end_date = date('Y-m-d', strtotime('now'));

            $preceding_start_date = date('Y-m-d', strtotime('-55 days'));
            $preceding_end_date = date('Y-m-d', strtotime('-28 days'));
        }

        // Display date range text to alert users as to what data they are viewing
        function display_date_range($date_range) {
            if ($date_range === '1') {
                return 'Today';
            }
            else if ($date_range === '7') {
                return 'Last 7 Days';
            }
            else if ($date_range === '28') {
                return 'Last 28 Days';
            }
            else {
                return 'Last 28 Days';
            }
        }

        function display_date_range_search_terms_chart_text($date_range) {
            if ($date_range === '1') {
                return 'Hourly';
            }
            else if ($date_range === '7') {
                return 'Daily';
            }
            else if ($date_range === '28') {
                return 'Weekly';
            }
            else {
                return 'Weekly';
            }
        }

        $total_searches_recent = wpsm_get_total_searches_within_date_range($recent_start_date, $recent_end_date);
        $total_searches_preceding = wpsm_get_total_searches_within_date_range($preceding_start_date, $preceding_end_date);

        $total_clicks_recent = wpsm_get_total_clicks_within_date_range($recent_start_date, $recent_end_date);
        $total_clicks_preceding = wpsm_get_total_clicks_within_date_range($preceding_start_date, $preceding_end_date);

        $total_no_conversion_searches_recent = wpsm_get_total_no_conversion_searches_within_date_range($recent_start_date, $recent_end_date);
        $total_no_conversion_searches_preceding = wpsm_get_total_no_conversion_searches_within_date_range($preceding_start_date, $preceding_end_date);

        // Calculations
        $clickthrough_rate_recent = $total_searches_recent > 0 ? number_format(($total_clicks_recent / $total_searches_recent) * 100, 2) : '0';
        $clickthrough_rate_preceding = $total_searches_preceding > 0 ? number_format(($total_clicks_preceding / $total_searches_preceding) * 100, 2) : '0';
        
        $bounce_rate_recent = $total_searches_recent > 0 ? number_format(($total_no_conversion_searches_recent / $total_searches_recent) * 100, 2) : '0';
        $bounce_rate_preceding = $total_searches_preceding > 0 ? number_format(($total_no_conversion_searches_preceding / $total_searches_preceding) * 100, 2) : '0';

        // Calculate percentage change
        function calculate_percentage_change($preceding, $recent) {
            $change_percentage = $preceding == 0 ? ($recent > 0 ? 100 : 0) : ((($recent - $preceding) / $preceding) * 100);

            return $change_percentage;
        }

        // Format the percentage with no decimals
        function change_percentage_formatted($change_percentage) {
            $change_percentage_formatted = number_format($change_percentage, 0);
            return $change_percentage_formatted;
        }

        // Determine increase or decrease for screen reader text
        function change_direction($change_percentage) {
            // Check if there's no change
            if ($change_percentage == 0) {
                return 'No change';
            } else {
                // Determine increase or decrease
                return $change_percentage > 0 ? 'Increased by' : 'Decreased by';
            }
        }

        // Output formatted quick stat information
        function output_formatted_quick_stats($preceding_stat, $recent_stat, $is_calc, $high_is_bad) {
            $percentage_change = calculate_percentage_change($preceding_stat, $recent_stat);
        
            // Determine class and icon based on percentage change and whether high values are considered bad
            if ($percentage_change > 0) {
                $text_color_class = $high_is_bad ? 'text-red-600' : 'text-green-600';
                // Icon for increase
                $change_icon = $high_is_bad ? '<svg class="h-5 w-5 flex-shrink-0 self-center text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" /></svg>' : '<svg class="h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" /></svg>';
            } elseif ($percentage_change < 0) {
                $text_color_class = $high_is_bad ? 'text-green-600' : 'text-red-600';
                // Icon for decrease
                $change_icon = $high_is_bad ? '<svg class="h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg>' : '<svg class="h-5 w-5 flex-shrink-0 self-center text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg>';
            } else {
                // No change scenario
                $text_color_class = 'text-gray-400'; // Placeholder class for no change
                $change_icon = ''; // Placeholder text for no change, you can replace this with an actual icon if desired
            }
        
            // Determine if stat should be displayed as a percentage or a whole number
            $percentage = $is_calc ? '%' : '';

            if ($change_icon === '') {
                $change_percentage = '-';
            } else {
                $change_percentage = change_percentage_formatted($percentage_change) . '%';
            }
        
            echo '<span>' . $recent_stat . $percentage . '</span>';
            echo '<span class="ml-2 flex items-baseline text-sm font-semibold ' . $text_color_class . '">';
                echo $change_icon;
                echo '<span class="sr-only"> ' . change_direction($percentage_change) . ' </span>';
                echo $change_percentage;
            echo '</span>';
        }


        /**
         * Fetch the top search queries and set them to $top_searches_queries
         */
        $top_searches_queries_sort_order = isset($_GET['top_searches_sort']) && in_array(strtoupper($_GET['top_searches_sort']), ['ASC', 'DESC']) 
        ? strtoupper($_GET['top_searches_sort']) 
        : 'DESC';
        $top_searches_queries = wpsm_get_top_search_terms_within_date_range($recent_start_date, $recent_end_date, 8, $top_searches_queries_sort_order);

        /**
         * Fetch the top clicked pages and set them to $top_clicked_pages
         */
        $top_pages_clicked_sort_order = isset($_GET['top_pages_clicked_sort']) && in_array(strtoupper($_GET['top_pages_clicked_sort']), ['ASC', 'DESC'])
        ? strtoupper($_GET['top_pages_clicked_sort']) 
        : 'DESC';
        $top_clicked_pages = wpsm_get_top_clicked_pages_within_date_range($recent_start_date, $recent_end_date, 8, $top_pages_clicked_sort_order);
        
        /**
         * Fetch the top non-converting search terms and set them to $top_non_converting_search_terms
         */
        $top_non_converting_search_terms_sort_order = isset($_GET['top_non_converting_search_terms_sort']) && in_array(strtoupper($_GET['top_non_converting_search_terms_sort']), ['ASC', 'DESC'])
        ? strtoupper($_GET['top_non_converting_search_terms_sort']) 
        : 'DESC';
        $top_non_converting_search_terms = wpsm_get_top_non_converting_search_terms_within_date_range($recent_start_date, $recent_end_date, 8, $top_non_converting_search_terms_sort_order);

        /**
         * Fetch the top converting search terms and their associated pages and set them to $search_terms_clicks_results
         */
        $query_sort_order_dynamic = 'ASC'; // Or 'DESC', ensure this is a valid value.
        $clicks_sort_order_dynamic = 'DESC'; // Or 'ASC', ensure this is a valid value.
        $search_terms_clicks_results = wpsm_get_search_terms_resulting_in_page_clicks($recent_start_date, $recent_end_date, 8, $query_sort_order_dynamic, $clicks_sort_order_dynamic);

        ?>
        <div class="h-full bg-gray-100">
            <div class="min-h-full">
                <?php $this->header->display(); ?>

                <main class="py-8 bg-gray-100">
                    <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
                        <div class="flex flex-col gap-6">
                            <!-- START Quick Stats -->
                            <div>
                                <div class="flex flex-row gap-6 items-center justify-between">
                                    <h3 class="text-base font-semibold leading-6 text-gray-900">Showing <?php echo display_date_range($date_range); ?></h3>
                                    <form id="dateRangeForm" method="get" action="">
                                        <input type="hidden" id="page" name="page" value="wp-search-metrics" />
                                        <div class="flex flex-row gap-2 items-center justify-center">
                                            <label for="date_range" class="block text-sm font-medium leading-6 text-gray-900 whitespace-nowrap">Date Range</label>
                                            <select id="date_range" name="date_range" class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                <option value="1" <?php echo ($date_range == '1') ? 'selected' : ''; ?>>Today</option>
                                                <option value="7" <?php echo ($date_range == '7') ? 'selected' : ''; ?>>Last 7 Days</option>
                                                <option value="28" <?php echo ($date_range == '28') ? 'selected' : ''; ?>>Last 28 Days</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <dl class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                        <dt class="truncate text-sm font-medium text-gray-500">Total Searches</dt>
                                        <dd class="mt-1 flex flex-row items-baseline text-3xl font-semibold tracking-tight text-gray-900">
                                            <?php output_formatted_quick_stats($total_searches_preceding, $total_searches_recent, false, false); ?>
                                        </dd>
                                    </div>
                                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">Total Page Clicks</dt>
                                    <dd class="mt-1 flex flex-row items-baseline text-3xl font-semibold tracking-tight text-gray-900">
                                        <?php output_formatted_quick_stats($total_clicks_preceding, $total_clicks_recent, false, false); ?>
                                    </dd>
                                    </div>
                                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">Click-Through Rate</dt>
                                    <dd class="mt-1 flex flex-row items-baseline text-3xl font-semibold tracking-tight text-gray-900">
                                        <?php output_formatted_quick_stats($clickthrough_rate_preceding, $clickthrough_rate_recent, true, false); ?>
                                    </dd>
                                    </div>
                                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">Bounce Rate</dt>
                                    <dd class="mt-1 flex flex-row items-baseline text-3xl font-semibold tracking-tight text-gray-900">
                                        <?php output_formatted_quick_stats($bounce_rate_preceding, $bounce_rate_recent, true, true); ?>
                                    </dd>
                                    </div>
                                </dl>
                            </div>
                            <!-- END Quick Stats -->

                            <!-- START Detailed Search Terms Stats -->  
                            <div class="w-full grid grid-cols-3 gap-6">
                                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">
                                        <div class="sm:flex sm:items-center">
                                        <div class="sm:flex-auto">
                                            <h2 class="text-base font-semibold leading-6 text-gray-900">Top Search Terms</h2>
                                            <p class="mt-2 text-sm text-gray-500">Popular search terms your visitors are using.</p>
                                        </div>
                                        </div>
                                    </dt>
                                    <dd>
                                        <div>
                                            <div class="mt-6 flow-root">
                                                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                                        <table class="min-w-full divide-y divide-gray-300">
                                                            <thead>
                                                                <tr>
                                                                <th scope="col" class="whitespace-nowrap py-2 pl-0 pr-2 text-left text-sm font-semibold text-gray-900">Search Term</th>
                                                                <th scope="col" class="whitespace-nowrap py-2 pl-2 pr-0 text-left text-sm text-right font-semibold text-gray-900">
                                                                    <a href="#" class="group inline-flex">
                                                                    Volume
                                                                    <!-- Active: "bg-gray-200 text-gray-900 group-hover:bg-gray-300", Not Active: "invisible text-gray-400 group-hover:visible group-focus:visible" -->
                                                                    <span class="ml-2 flex-none rounded bg-gray-100 text-gray-900 group-hover:bg-gray-200">
                                                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </span>
                                                                    </a>
                                                                </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                                <?php
                                                                    if (!empty($top_searches_queries)) {
                                                                        foreach ($top_searches_queries as $search_query) { 
                                                                ?>
                                                                            <tr>
                                                                                <td class="whitespace-nowrap py-2 pl-0 pr-2 text-sm text-gray-500"><?php echo esc_html($search_query->query_text); ?></td>
                                                                                <td class="whitespace-nowrap px-2 pl-2 pr-0 text-sm font-medium text-gray-900 text-right"><?php echo intval($search_query->interaction_count); ?></td>
                                                                            </tr>
                                                                <?php }
                                                                    } else {
                                                                ?>
                                                                    <tr>
                                                                        <td colspan="2" class="whitespace-nowrap py-2 pl-0 pr-2 text-sm text-gray-500 text-center">
                                                                            😭 No data available for the specified date range.
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                                    }
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>                            
                                    </dd>
                                    <?php
                                        if (!empty($top_searches_queries)) {
                                    ?>
                                        <button type="button" class="flex flex-row items-center gap-1 rounded transition mt-8 bg-gray-100 px-2 py-1 text-sm font-semibold text-gray-600 shadow-sm hover:bg-gray-200">
                                            <span>View All</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                            </svg>                                                       
                                        </button>
                                    <?php
                                        } 
                                    ?>
                                </div>
                                    <div class="col-span-2 overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">
                                        <div class="sm:flex sm:items-center">
                                            <div class="sm:flex-auto">
                                                <h2 class="text-base font-semibold leading-6 text-gray-900">Top Search Terms <?php echo display_date_range_search_terms_chart_text($date_range); ?> Volumes</h2>
                                                <p class="mt-2 text-sm text-gray-500"><?php echo display_date_range_search_terms_chart_text($date_range); ?> volumes of your top search terms.</p>
                                            </div>
                                        </div>
                                    </dt>
                                    <dd class="mt-8">
                                        <canvas id="weeklyTopSearchesVolume"></canvas>                            
                                    </dd>
                                </div>
                            </div>
                            <!-- END Detailed Search Terms Stats -->

                            <!-- START Detailed Page Clicks Stats -->  
                            <div class="w-full grid grid-cols-3 gap-6">
                                <div class="col-span-2 overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">
                                        <div class="sm:flex sm:items-center">
                                        <div class="sm:flex-auto">
                                            <h2 class="text-base font-semibold leading-6 text-gray-900">Top Pages</h2>
                                            <p class="mt-2 text-sm text-gray-500">Popular pages your users are visiting from searches.</p>
                                        </div>
                                        </div>
                                    </dt>
                                    <dd>
                                        <div>
                                        <div class="mt-6 flow-root">
                                            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                                <table class="min-w-full divide-y divide-gray-300">
                                                    <thead>
                                                        <tr>
                                                        <th scope="col" class="whitespace-nowrap py-2 pl-0 pr-2 text-left text-sm font-semibold text-gray-900">Search Term</th>
                                                        <th scope="col" class="whitespace-nowrap py-2 pl-2 pr-0 text-left text-sm text-right font-semibold text-gray-900">
                                                            <a href="#" class="group inline-flex">
                                                            Volume
                                                            <!-- Active: "bg-gray-200 text-gray-900 group-hover:bg-gray-300", Not Active: "invisible text-gray-400 group-hover:visible group-focus:visible" -->
                                                            <span class="ml-2 flex-none rounded bg-gray-100 text-gray-900 group-hover:bg-gray-200">
                                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                                </svg>
                                                            </span>
                                                            </a>
                                                        </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200 bg-white">
                                                        <?php
                                                            if (!empty($top_clicked_pages)) {
                                                                foreach ($top_clicked_pages as $clicked_page) {
                                                                    // Get the post title and permalink using the post ID.
                                                                    $post_title = get_the_title($clicked_page->post_id);
                                                                    $post_url = get_permalink($clicked_page->post_id);
                                                        ?>
                                                                    <tr>
                                                                        <!-- Display Post Title as a Clickable Link -->
                                                                        <td class="whitespace-nowrap py-2 pl-0 pr-2 text-sm text-gray-500">
                                                                            <?php if ($post_title && $post_url): ?>
                                                                                <a class="transition hover:text-indigo-500" href="<?php echo esc_url($post_url); ?>" target="_blank" rel="noopener noreferrer">
                                                                                    <?php echo esc_html($post_title); ?>
                                                                                </a>
                                                                            <?php else: ?>
                                                                                <?php echo esc_html($clicked_page->post_id); ?> <!-- Fallback to displaying the ID if no title/url -->
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <!-- Display Total Clicks for the Post within the Date Range -->
                                                                        <td class="whitespace-nowrap px-2 pl-2 pr-0 text-sm font-medium text-gray-900 text-right">
                                                                            <?php echo intval($clicked_page->interaction_count); ?>
                                                                        </td>
                                                                    </tr>
                                                        <?php 
                                                                }
                                                            } else { 
                                                        ?>
                                                                <tr>
                                                                    <td colspan="2" class="whitespace-nowrap py-2 pl-0 pr-2 text-sm text-gray-500 text-center">
                                                                        😭 No data available for the specified date range.
                                                                    </td>
                                                                </tr>
                                                        <?php 
                                                            }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            </div>
                                        </div>
                                        </div>                            
                                    </dd>
                                    <?php
                                        if (!empty($top_searches_queries)) {
                                    ?>
                                        <button type="button" class="flex flex-row items-center gap-1 rounded transition mt-8 bg-gray-100 px-2 py-1 text-sm font-semibold text-gray-600 shadow-sm hover:bg-gray-200">
                                            <span>View All</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                            </svg>                                                       
                                        </button>
                                    <?php
                                        }
                                    ?>
                                </div>
                                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">
                                        <div class="sm:flex sm:items-center">
                                        <div class="sm:flex-auto">
                                            <h2 class="text-base font-semibold leading-6 text-gray-900">Top Search Terms Weekly Volume</h2>
                                            <p class="mt-2 text-sm text-gray-500">Weekly volumes of your top search terms.</p>
                                        </div>
                                        </div>
                                    </dt>
                                    <dd class="mt-8">
                                        <canvas id="weeklyTopSearchesVolume"></canvas>                            
                                    </dd>
                                </div>
                            </div>
                            <!-- END Detailed Page Clicks Stats -->

                            <!-- START Detailed Converting and Non-Converting Clicks Stats -->  
                            <div class="w-full grid grid-cols-3 gap-6">
                                <div class="col-span-2 overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">
                                        <div class="sm:flex sm:items-center">
                                        <div class="sm:flex-auto">
                                            <h2 class="text-base font-semibold leading-6 text-gray-900">Top Converting Search Terms & Pages</h2>
                                            <p class="mt-2 text-sm text-gray-500">Search queries that resulted in a click along with their associated pages.</p>
                                        </div>
                                        </div>
                                    </dt>
                                    <dd>
                                        <div>
                                            <div class="mt-6 flow-root">
                                                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                                        <table class="min-w-full divide-y divide-gray-300">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col" class="whitespace-nowrap py-2 pl-0 pr-2 text-left text-sm font-semibold text-gray-900">Search Term</th>
                                                                    <th scope="col" class="whitespace-nowrap py-2 pl-2 pr-2 text-left text-sm font-semibold text-gray-900">Destination Page</th>
                                                                    <th scope="col" class="whitespace-nowrap py-2 pl-2 pr-0 text-left text-sm text-right font-semibold text-gray-900">
                                                                        <a href="#" class="group inline-flex">
                                                                        Total Clicks
                                                                        <!-- Active: "bg-gray-200 text-gray-900 group-hover:bg-gray-300", Not Active: "invisible text-gray-400 group-hover:visible group-focus:visible" -->
                                                                        <span class="ml-2 flex-none rounded bg-gray-100 text-gray-900 group-hover:bg-gray-200">
                                                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                                            </svg>
                                                                        </span>
                                                                        </a>
                                                                    </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                                <?php
                                                                    if (!empty($search_terms_clicks_results)) {
                                                                        // prepare rowspan values
                                                                        $rowspanCounts = [];
                                                                        foreach ($search_terms_clicks_results as $result) {
                                                                            if (!isset($rowspanCounts[$result->query_text])) {
                                                                                $rowspanCounts[$result->query_text] = 1;
                                                                            } else {
                                                                                $rowspanCounts[$result->query_text]++;
                                                                            }
                                                                        }

                                                                        $outputRowspans = [];  // Track already outputted rowspans to skip them later
                                                                        $previous_query_text = null;  // Keep track of the previous query text for comparison
                                                                        foreach ($search_terms_clicks_results as $result) {
                                                                            $post_title = get_the_title($result->post_id);
                                                                            $post_url = get_permalink($result->post_id);
                                                                        
                                                                            echo '<tr>';
                                                                        
                                                                            // Only output query_text td if it has not been outputted before or it's the first occurrence
                                                                            if (!isset($outputRowspans[$result->query_text])) {
                                                                                // Store and mark this query_text as outputted with its rowspan
                                                                                $outputRowspans[$result->query_text] = true;
                                                                                $rowspan = $rowspanCounts[$result->query_text];
                                                                                echo '<td rowspan="' . $rowspan . '" class="whitespace-nowrap pl-0 pr-2 text-sm text-gray-500">'. esc_html($result->query_text) . '</td>';
                                                                            }
                                                                            // Note: No else part needed since we don't render the cell if it's not the first occurrence
                                                                        
                                                                            // Display the clickable post title and click count
                                                                            echo '<td class="whitespace-nowrap py-2 pl-2 pr-2 text-sm text-gray-500"><a class="transition hover:text-indigo-500" href="' . esc_url($post_url) . '" target="_blank">' . esc_html($post_title) . '</a></td>';
                                                                            echo '<td class="whitespace-nowrap px-2 pl-2 pr-0 text-sm font-medium text-gray-900 text-right">' . intval($result->clicks_count) . '</td>';
                                                                        
                                                                            echo '</tr>';
                                                                        
                                                                            $previous_query_text = $result->query_text;
                                                                        }
                                                                    } else { 
                                                                ?>
                                                                        <tr>
                                                                            <td colspan="2" class="whitespace-nowrap py-2 pl-0 pr-2 text-sm text-gray-500 text-center">
                                                                                😭 No data available for the specified date range.
                                                                            </td>
                                                                        </tr>
                                                                <?php 
                                                                    }
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>                            
                                    </dd>
                                    <?php
                                        if (!empty($search_terms_clicks_results)) {
                                    ?>
                                        <button type="button" class="flex flex-row items-center gap-1 rounded transition mt-8 bg-gray-100 px-2 py-1 text-sm font-semibold text-gray-600 shadow-sm hover:bg-gray-200">
                                            <span>View All</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                            </svg>                                                       
                                        </button>
                                    <?php
                                        }
                                    ?>
                                </div>
                                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                                    <dt class="truncate text-sm font-medium text-gray-500">
                                        <div class="sm:flex sm:items-center">
                                        <div class="sm:flex-auto">
                                            <h2 class="text-base font-semibold leading-6 text-gray-900">Non-Converting Search Terms</h2>
                                            <p class="mt-2 text-sm text-gray-500">Search queries that returned no results or did not result in a click.</p>
                                        </div>
                                        </div>
                                    </dt>
                                    <dd>
                                        <div>
                                        <div class="mt-6 flow-root">
                                            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                                <table class="min-w-full divide-y divide-gray-300">
                                                    <thead>
                                                        <tr>
                                                        <th scope="col" class="whitespace-nowrap py-2 pl-0 pr-2 text-left text-sm font-semibold text-gray-900">Search Term</th>
                                                        <th scope="col" class="whitespace-nowrap py-2 pl-2 pr-0 text-left text-sm text-right font-semibold text-gray-900">
                                                            <a href="#" class="group inline-flex">
                                                            Volume
                                                            <!-- Active: "bg-gray-200 text-gray-900 group-hover:bg-gray-300", Not Active: "invisible text-gray-400 group-hover:visible group-focus:visible" -->
                                                            <span class="ml-2 flex-none rounded bg-gray-100 text-gray-900 group-hover:bg-gray-200">
                                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                                </svg>
                                                            </span>
                                                            </a>
                                                        </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200 bg-white">
                                                        <?php
                                                            if (!empty($top_non_converting_search_terms)) {
                                                                foreach ($top_non_converting_search_terms as $non_converting_search_term) {
                                                        ?>
                                                                    <tr>
                                                                        <td class="whitespace-nowrap py-2 pl-0 pr-2 text-sm text-gray-500">
                                                                            <?php echo esc_html($non_converting_search_term->query_text); ?>
                                                                        </td>
                                                                        <td class="whitespace-nowrap px-2 pl-2 pr-0 text-sm font-medium text-gray-900 text-right">
                                                                            <?php echo intval($non_converting_search_term->search_count); ?>
                                                                        </td>
                                                                    </tr>
                                                        <?php 
                                                                }
                                                            } else { 
                                                        ?>
                                                                <tr>
                                                                    <td colspan="2" class="whitespace-nowrap py-2 pl-0 pr-2 text-sm text-gray-500 text-center">
                                                                        😭 No data available for the specified date range.
                                                                    </td>
                                                                </tr>
                                                        <?php 
                                                            }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            </div>
                                        </div>
                                        </div>                            
                                    </dd>
                                    <?php
                                        if (!empty($top_searches_queries)) {
                                    ?>
                                        <button type="button" class="flex flex-row items-center gap-1 rounded transition mt-8 bg-gray-100 px-2 py-1 text-sm font-semibold text-gray-600 shadow-sm hover:bg-gray-200">
                                            <span>View All</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                            </svg>                                                       
                                        </button>
                                    <?php
                                        }
                                    ?>
                                </div>
                            </div>
                            <!-- END Detailed Non-Converting Clicks Stats -->

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
                'wp-search-metrics-dashboard-js',
                plugins_url('/src/js/admin/dashboard.js', dirname(dirname(__FILE__))),
                array(),
                '1.0',
                true // In footer
            );
			
            wp_enqueue_script(
                'wp-search-metrics-chartjs',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
                array(),
                '4.4.1',
                true // In footer
            );

			global $wpdb;

            $date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '28'; // get date range if set, otherwise default to 28 days

            function fetch_search_term_chart_data_within_date_range($date_range) {
                global $wpdb;
                // Use WordPress function to get the current time in the configured timezone
                $now = current_time('mysql', 1); // 'mysql' format is Y-m-d H:i:s
                $today = date('Y-m-d'); // Use just the date part for daily calculations

                // Initialize $date_ranges
                $date_ranges = [];

                if ($date_range === '1') {
                    // Fetches the current time, rounding down to the nearest hour
                    $current_time = current_time('mysql', 1);
                
                    for ($i = 0; $i < 4; $i++) {
                        // Calculate the end time of each interval; it starts with the current time for the first interval
                        $end = date('Y-m-d H:i:s', strtotime("-" . ($i * 6) . " hours", strtotime($current_time)));
                        // Calculate the start time by subtracting 6 hours from the end time of each interval
                        $start = date('Y-m-d H:i:s', strtotime("-6 hours", strtotime($end)));
                
                        // Prepend the times to the beginning of the array to reverse the order (so the most recent times are at the end)
                        array_unshift($date_ranges, ['start' => $start, 'end' => $end]);
                    }
                
                    // The loop reverses the order, but if you want to ensure the most recent is first, you might not need to reverse the order. If needed, reverse again.
                    // $date_ranges = array_reverse($date_ranges); // Uncomment if reversal needed based on loop adjustment
                } else if ($date_range === '7') {
                    // Last 7 days
                    for ($i = 6; $i >= 0; $i--) {
                        $start = date('Y-m-d 00:00:00', strtotime("-" . ($i) . " days", strtotime($today)));
                        // Adjust the 'end' to be the start of the next day
                        $end = date('Y-m-d 00:00:00', strtotime("-" . ($i - 1) . " days", strtotime($today)));
                        if ($i == 0) $end =  $now; // Till now for the current day
                        $date_ranges[] = [
                            'start' => $start,
                            'end'   => $end,
                        ];
                    }
                }
                else if ($date_range === '28') { // Last 28 days
                    $date_ranges = [
                        [
                            'start' => date('Y-m-d', strtotime("-27 days", strtotime($today))),
                            'end' => date('Y-m-d', strtotime("-21 days", strtotime($today))),
                        ],
                        [
                            'start' => date('Y-m-d', strtotime("-20 days", strtotime($today))),
                            'end' => date('Y-m-d', strtotime("-14 days", strtotime($today))),
                        ],
                        [
                            'start' => date('Y-m-d', strtotime("-13 days", strtotime($today))),
                            'end' => date('Y-m-d', strtotime("-7 days", strtotime($today))),
                        ],
                        [
                            'start' => date('Y-m-d', strtotime("-6 days", strtotime($today))),
                            'end' => date('Y-m-d', strtotime("+1 day", strtotime($today))),
                        ],
                    ];
                }
                else { // default to 28 days
                    $date_ranges = [
                        [
                            'start' => date('Y-m-d', strtotime("-27 days", strtotime($today))),
                            'end' => date('Y-m-d', strtotime("-21 days", strtotime($today))),
                        ],
                        [
                            'start' => date('Y-m-d', strtotime("-20 days", strtotime($today))),
                            'end' => date('Y-m-d', strtotime("-14 days", strtotime($today))),
                        ],
                        [
                            'start' => date('Y-m-d', strtotime("-13 days", strtotime($today))),
                            'end' => date('Y-m-d', strtotime("-7 days", strtotime($today))),
                        ],
                        [
                            'start' => date('Y-m-d', strtotime("-6 days", strtotime($today))),
                            'end' => date('Y-m-d', strtotime("+1 day", strtotime($today))),
                        ],
                    ];
                }
            
                // Dynamically construct the SQL SELECT clause parts for date ranges
                $date_range_columns = [];
                foreach ($date_ranges as $range) {
                    // Notice: Adjusted alias generation to handle different formats
                    $start_alias = esc_sql($range['start']);
                    $end_alias = esc_sql($range['end']);
                    $label = ($date_range === '1')
                    ? date('M j ' . get_option('time_format'), strtotime($start_alias)) . '-' . date(get_option('time_format'), strtotime($end_alias))
                    : date('M j', strtotime($start_alias)) . '-' . date('M j', strtotime($end_alias));
                    $date_range_columns[] = "COUNT(CASE WHEN si.interaction_time BETWEEN %s AND %s THEN 1 ELSE NULL END) AS '{$label}'";
                }

                // Join the dynamically constructed parts with the static SQL
                $sql = "SELECT 
                            sq.query_text,
                            " . implode(', ', $date_range_columns) . "
                        FROM 
                            " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " si
                            JOIN " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . " sq ON si.query_id = sq.id
                        WHERE 
                            si.interaction_time >= %s
                        GROUP BY 
                            sq.query_text
                        ORDER BY 
                            COUNT(si.id) DESC
                        LIMIT 10";

                // Populate $placeholders as before, it already considers dynamic date ranges
                $placeholders = [];
                foreach ($date_ranges as $range) {
                    $placeholders[] = $range['start']; // Start date/time for BETWEEN comparison
                    $placeholders[] = $range['end'];   // End date/time for BETWEEN comparison
                }
                $placeholders[] = $date_ranges[0]['start']; // Using the earliest start date for the WHERE clause

                // Prepare and execute SQL with actual values
                $prepared_sql = $wpdb->prepare($sql, ...$placeholders);
                $results = $wpdb->get_results($prepared_sql, ARRAY_A);

                return $results;
            }

            // Localize the script with new data
            $search_term_chart_data = fetch_search_term_chart_data_within_date_range($date_range);

            // Processing $search_term_chart_data for JS
            $processed_data = [];
            $labels = [];
            // Assuming the first row contains all weeks as keys for labels
            if (!empty($search_term_chart_data)) {
                $raw_labels = array_keys($search_term_chart_data[0]);
                array_shift($raw_labels); // remove 'query_text' label

                // Transform date labels to 'M j - M j' format
                $labels = array_map(function($label) {
                    // Split with a more resilient check
                    $parts = explode('-', $label);
                    
                    // Check we have exactly two parts; otherwise, return label as is or handle differently
                    if(count($parts) != 2) {
                        // Fallback behavior or logging if unexpected format
                        // For the purposes of demonstration, returning the unmodified label
                        return $label; 
                    }
                
                    // Safely destructure parts
                    [$start, $end] = $parts;

                    $date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '28';

                    // Get WordPress timezone setting
                    $wp_timezone_string = get_option('timezone_string');
                    if (!$wp_timezone_string) {
                        // If no timezone string is set, fall back to UTC offset
                        $current_offset = get_option('gmt_offset');
                        $timezone_name = timezone_name_from_abbr('', $current_offset * 3600, false);
                        $wp_timezone_string = $timezone_name ?: 'UTC';
                    }

                    // Create timezone objects
                    $wp_timezone = new DateTimeZone($wp_timezone_string);
                    $utc_timezone = new DateTimeZone('UTC');

                    // Convert start and end dates to WordPress timezone
                    $startFormatted = 'Invalid Start Date';
                    $endFormatted = 'Invalid End Date';

                    if ($date_range === '1') {
                        if ($start) {
                            $startDateTime = new DateTime($start, $utc_timezone);
                            $startDateTime->setTimezone($wp_timezone);
                            $startFormatted = $startDateTime->format('M j ' . get_option('time_format'));
                        }

                        if ($end) {
                            $endDateTime = new DateTime($end, $utc_timezone);
                            $endDateTime->setTimezone($wp_timezone);
                            $endFormatted = $endDateTime->format('M j ' . get_option('time_format'));
                        }
                    } else {
                        if ($start) {
                            $startDateTime = new DateTime($start, $utc_timezone);
                            $startDateTime->setTimezone($wp_timezone);
                            $startFormatted = $startDateTime->format('M j');
                        }

                        if ($end) {
                            $endDateTime = new DateTime($end, $utc_timezone);
                            $endDateTime->setTimezone($wp_timezone);
                            $endFormatted = $endDateTime->format('M j');
                        }
                    }
                    
                    return $startFormatted . ' - ' . $endFormatted;
                }, $raw_labels);
            }

            // Define an array of 10 preferred colors
            $preferred_chart_colors = [
                '#ef4444',  // red-500
                '#f97316',  // orange-500
                '#eab308',  // yellow-500
                '#22c55e',  // green-500
                '#0ea5e9',  // sky-500
                '#6366f1',  // indigo-500
                '#d946ef',  // fuchsia-500
                '#9d174d',  // pink-800
                '#6b7280',  // gray-500
                '#000000',  // black
            ];
            $colorIndex = 0;  // To cycle through the preferred colors array

            foreach ($search_term_chart_data as $row) {
                $dataset = [
                    'label' => $row['query_text'],
                    'data' => [],
                    'fill' => false,
                    'borderColor' => $preferred_chart_colors[$colorIndex % count($preferred_chart_colors)],  // Cycle through colors
                    'tension' => 0.1
                ];
                foreach ($labels as $index => $label) {
                    // Ensure you use the pre-transformed keys here
                    $dataset['data'][] = $row[$raw_labels[$index]];
                }
                $processed_data['datasets'][] = $dataset;

                $colorIndex++;  // Increment color index for the next dataset
            }
            $processed_data['labels'] = $labels;
        
            wp_localize_script('wp-search-metrics-chartjs', 'ChartData', ['data' => $processed_data]);

			$inline_script = "
			document.addEventListener('DOMContentLoaded', function() {
                var weeklyTopSearchesVolumeCtx = document.getElementById('weeklyTopSearchesVolume').getContext('2d');
                var weeklyTopSearchesVolume = new Chart(weeklyTopSearchesVolumeCtx, {
                    type: 'line',
                    data: ChartData.data,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                        },
                    },
                });
            });
			";

			wp_add_inline_script('wp-search-metrics-chartjs', $inline_script);

        }
    }
}