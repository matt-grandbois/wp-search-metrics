<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Retrieves the total number of search interactions within a specified date range.
 *
 * This function calculates the sum of `query_count` for all queries within a given date range,
 * ensuring that dates are adjusted to include the entire end date.
 *
 * @param string $start_date The start date in 'Y-m-d' format to calculate searches from.
 * @param string $end_date   The end date in 'Y-m-d' format to calculate searches to.
 *
 * @return int The total number of search interactions within the specified date range.
 */
function wpsm_get_total_searches_within_date_range($start_date, $end_date) {
    global $wpdb;

    // Sanitize input dates to avoid injection.
    $start_date_sanitized = sanitize_text_field($start_date);
    $end_date_sanitized = sanitize_text_field($end_date);

    // Adjust the end date to include searches up to the end of the day.
    $end_date_adjusted = date('Y-m-d', strtotime($end_date_sanitized . ' +1 day'));

    // Prepare SQL statement to prevent SQL injection. 
    $prepared_sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " WHERE interaction_time >= %s AND interaction_time < %s",
        $start_date_sanitized, 
        $end_date_adjusted
    );

    // Execute the query and return the result.
    $total_searches = $wpdb->get_var($prepared_sql);

    // Ensure the result is an integer before returning.
    return (int) $total_searches;
}