<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves the top non-converting search terms within a specified date range.
 *
 * Fetches the most frequent non-converting search terms based on search counts within the given date range.
 * Results can be ordered in ascending or descending order based on search count, with customizable limit on the number of results.
 *
 * @param string  $start_date  The start date in 'Y-m-d' format from which to count non-converting searches.
 * @param string  $end_date    The end date in 'Y-m-d' format up to which to count non-converting searches.
 * @param integer $num_results (Optional) The number of results to fetch. Defaults to 8 if not specified.
 * @param string  $sort_order  (Optional) The sort order, 'ASC' for ascending or 'DESC' for descending. Defaults to 'DESC' if not specified.
 *
 * @return array An array of objects containing the query_text and search_count for the top non-converting search terms.
 */
function wpsm_get_top_non_converting_search_terms_within_date_range($start_date, $end_date, $num_results = 8, $sort_order = 'DESC') {
    global $wpdb;

    // Retrieve the WordPress timezone setting.
    $wp_timezone = new DateTimeZone(get_option('timezone_string') ? get_option('timezone_string') : 'UTC');
    
    // Define the UTC timezone for conversion purposes.
    $utc_timezone = new DateTimeZone('UTC');

    // Convert start and end dates to DateTime objects respecting the WordPress timezone.
    $start_datetime = new DateTime($start_date, $wp_timezone);
    $end_datetime = new DateTime($end_date . ' 23:59:59', $wp_timezone); // Including the full day

    // Calculate the offset in seconds from the WordPress timezone to UTC.
    $offset = $wp_timezone->getOffset($start_datetime) - $utc_timezone->getOffset($start_datetime);
    $offsetHours = $offset / 3600; // Convert offset to hours

    // Validate and sanitize the number of results and sort order.
    $num_results_sanitized = filter_var($num_results, FILTER_VALIDATE_INT, ['options' => ['default' => 8, 'min_range' => 1]]);
    $sort_order_sanitized = in_array(strtoupper($sort_order), ['ASC', 'DESC']) ? strtoupper($sort_order) : 'DESC';

    // Prepare the SQL query, factoring in the timezone offset for accurate local-time-based querying.
    $prepared_sql = $wpdb->prepare(
        "SELECT sq.query_text, COUNT(si.id) AS search_count
        FROM " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . " sq
        JOIN " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " si ON sq.id = si.query_id
        WHERE si.interaction_time >= DATE_ADD(%s, INTERVAL %d HOUR) 
        AND si.interaction_time < DATE_ADD(%s, INTERVAL %d HOUR)
        AND si.interaction_type = 'no_conversion'
        GROUP BY sq.query_text
        ORDER BY search_count $sort_order_sanitized
        LIMIT %d",
        $start_datetime->format('Y-m-d H:i:s'), $offsetHours,
        $end_datetime->format('Y-m-d H:i:s'), $offsetHours,
        $num_results_sanitized
    );

    // Execute the query and gather the results.
    return $wpdb->get_results($prepared_sql);
}