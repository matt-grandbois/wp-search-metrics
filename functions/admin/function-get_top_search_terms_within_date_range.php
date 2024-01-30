<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Retrieves the top search queries within a specified date range.
 *
 * This function fetches the most frequent search queries based on interaction counts within the given date range.
 * The results can be ordered in ascending or descending order as specified, with a customizable number of results.
 *
 * @param string  $start_date  The start date in 'Y-m-d' format from which to start counting search queries.
 * @param string  $end_date    The end date in 'Y-m-d' format up to which to count search queries.
 * @param integer $num_results (Optional) The number of results to fetch. Defaults to 8 if not specified.
 * @param string  $sort_order  (Optional) The sort order, ASC for ascending or DESC for descending. Defaults to DESC if not specified.
 *
 * @return array An array of objects containing the query_text and interaction_count for the top search queries.
 */
function wpsm_get_top_search_terms_within_date_range($start_date, $end_date, $num_results = 8, $sort_order = 'DESC') {
    global $wpdb;

    // Fetch the WordPress timezone setting.
    $wp_timezone = new DateTimeZone(get_option('timezone_string') ? get_option('timezone_string') : 'UTC');
    
    // Define UTC timezone for conversion.
    $utc_timezone = new DateTimeZone('UTC');

    // Convert start and end dates to DateTime objects in WordPress timezone.
    $start_datetime = new DateTime($start_date, $wp_timezone);
    $end_datetime = new DateTime($end_date . ' 23:59:59', $wp_timezone);

    // Determine the offset in seconds between WordPress timezone and UTC.
    $offset = $wp_timezone->getOffset($start_datetime) - $utc_timezone->getOffset($start_datetime);
    // Convert the offset to hours to adjust the query range.
    $offsetHours = $offset / 3600;

    // Validate and sanitize the number of results.
    $num_results_sanitized = filter_var($num_results, FILTER_VALIDATE_INT, ['options' => ['default' => 8, 'min_range' => 1]]);

    // Validate and sanitize sort order.
    $sort_order_sanitized = in_array(strtoupper($sort_order), ['ASC', 'DESC']) ? strtoupper($sort_order) : 'DESC';

    // Prepare the SQL statement, adjusting the date range by the calculated offset.
    $top_searches_queries_sql = $wpdb->prepare("
        SELECT sq.query_text, COUNT(si.id) AS interaction_count
        FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " si
        JOIN " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . " sq ON si.query_id = sq.id
        WHERE si.interaction_time >= DATE_ADD(%s, INTERVAL %d HOUR)
        AND si.interaction_time < DATE_ADD(%s, INTERVAL %d HOUR)
        GROUP BY si.query_id
        ORDER BY interaction_count {$sort_order_sanitized}
        LIMIT %d",
        $start_datetime->format('Y-m-d H:i:s'), $offsetHours,
        $end_datetime->format('Y-m-d H:i:s'), $offsetHours,
        $num_results_sanitized
    );

    // Execute the query and return the results.
    return $wpdb->get_results($top_searches_queries_sql);
}