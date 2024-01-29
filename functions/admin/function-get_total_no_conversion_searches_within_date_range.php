<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Retrieves the total number of non-converting searches within a specified date range.
 *
 * This function counts the total interactions classified as 'no_conversion' and lacking an associated post_id,
 * signifying a search did not result in a conversion within a given date range. It ensures that the dates are
 * adjusted to include the entire end date.
 *
 * @param string $start_date The start date in 'Y-m-d' format from which to start counting non-converting searches.
 * @param string $end_date   The end date in 'Y-m-d' format up to which to count non-converting searches.
 *
 * @return int The total number of non-converting searches within the specified date range.
 */
function wpsm_get_total_no_conversion_searches_within_date_range($start_date, $end_date) {
    global $wpdb;

    // Sanitize input dates to enhance security and prevent potential SQL injection.
    $start_date_sanitized = sanitize_text_field($start_date);
    $end_date_sanitized = sanitize_text_field($end_date);

    // Adjust the end date to include results up to the end of the day.
    $end_date_adjusted = date('Y-m-d', strtotime($end_date_sanitized . ' +1 day'));

    // Use $wpdb->prepare for safe SQL statement preparation with sanitized inputs.
    $prepared_sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " WHERE post_id IS NULL AND interaction_type = 'no_conversion' AND interaction_time >= %s AND interaction_time < %s",
        $start_date_sanitized,
        $end_date_adjusted
    );

    // Execute the query and return the result, ensuring the return value is cast to an integer.
    $total_no_conversion_searches = $wpdb->get_var($prepared_sql);

    return (int) $total_no_conversion_searches;
}