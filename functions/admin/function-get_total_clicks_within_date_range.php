<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Retrieves the total number of conversion clicks within a specified date range.
 *
 * This function counts the total interactions classified as 'conversion' within a given date range,
 * ensuring that the dates are adjusted to include the entire end date.
 *
 * @param string $start_date The start date in 'Y-m-d' format to start counting clicks from.
 * @param string $end_date   The end date in 'Y-m-d' format to count clicks up to.
 *
 * @return int The total number of conversion clicks within the specified date range.
 */
function wpsm_get_total_clicks_within_date_range($start_date, $end_date) {
    global $wpdb;

    // Sanitize input dates to enhance security and prevent SQL injection.
    $start_date_sanitized = sanitize_text_field($start_date);
    $end_date_sanitized = sanitize_text_field($end_date);

    // Adjust the end date to ensure it includes searches up to the end of the day.
    $end_date_adjusted = date('Y-m-d', strtotime($end_date_sanitized . ' +1 day'));

    // Use $wpdb->prepare to prepare SQL statement safely with sanitized inputs.
    $prepared_sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " WHERE interaction_type='conversion' AND interaction_time >= %s AND interaction_time < %s",
        $start_date_sanitized,
        $end_date_adjusted
    );

    // Execute the query and return the result as an integer.
    $total_clicks = $wpdb->get_var($prepared_sql);

    return (int) $total_clicks;
}