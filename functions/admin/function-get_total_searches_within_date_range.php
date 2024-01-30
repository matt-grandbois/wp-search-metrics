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

    // Fetch WordPress's timezone setting.
    $wp_timezone = new DateTimeZone(get_option('timezone_string') ? get_option('timezone_string') : 'UTC');
    
    // Fetch the UTC timezone.
    $utc_timezone = new DateTimeZone('UTC');

    // Convert start date to DateTime object in WP timezone.
    $start_datetime = new DateTime($start_date, $wp_timezone);
    // Convert end date to DateTime object in WP timezone, adjust to end of the day.
    $end_datetime = new DateTime($end_date . ' 23:59:59', $wp_timezone);

    // Determine the offset in seconds between WP timezone and UTC.
    $offset = $wp_timezone->getOffset($start_datetime) - $utc_timezone->getOffset($start_datetime);
    // Convert offset to hours to adjust the query range.
    $offsetHours = $offset / 3600;

    // Prepare the query, adjusting the date range by the calculated offset.
    $prepared_sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . "
        WHERE interaction_time >= DATE_ADD(%s, INTERVAL %d HOUR) AND interaction_time <= DATE_ADD(%s, INTERVAL %d HOUR)",
        $start_datetime->format('Y-m-d H:i:s'), // Start date in WP timezone.
        $offsetHours, // Adjust start date by offset hours.
        $end_datetime->format('Y-m-d H:i:s'), // End date just before midnight, in WP timezone.
        $offsetHours // Adjust end date by offset hours.
    );

    // Execute the query.
    $total_searches = $wpdb->get_var($prepared_sql);

    return (int) $total_searches;
}