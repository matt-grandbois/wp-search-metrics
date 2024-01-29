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

    // Sanitize input dates to enhance security and prevent potential SQL injection.
    $start_date_sanitized = sanitize_text_field($start_date);
    $end_date_sanitized = sanitize_text_field($end_date);

    // Validate and sanitize the requested number of results.
    $num_results_sanitized = filter_var($num_results, FILTER_VALIDATE_INT, ['options' => ['default' => 8, 'min_range' => 1]]);

    // Validate and sanitize the sort order.
    $sort_order_sanitized = in_array(strtoupper($sort_order), ['ASC', 'DESC']) ? strtoupper($sort_order) : 'DESC';

    // Adjust the end date to include results up to the end of the day.
    $end_date_adjusted = date('Y-m-d', strtotime($end_date_sanitized . ' +1 day'));

    // Construct the SQL statement using safe practices.
    $prepared_sql = $wpdb->prepare(
        "SELECT sq.query_text, COUNT(si.id) AS search_count
        FROM " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . " sq
        JOIN " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " si ON sq.id = si.query_id
        WHERE si.interaction_time >= %s AND si.interaction_time < %s 
        AND si.interaction_type = 'no_conversion'
        GROUP BY sq.query_text
        ORDER BY search_count $sort_order_sanitized
        LIMIT %d",
        $start_date_sanitized, $end_date_adjusted, $num_results_sanitized
    );

    // Execute the query and return the results.
    return $wpdb->get_results($prepared_sql);
}