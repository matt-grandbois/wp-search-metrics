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

    // Retrieve the WordPress timezone setting.
    $wp_timezone = new DateTimeZone(get_option('timezone_string') ? get_option('timezone_string') : 'UTC');
    
    // Fetch the UTC timezone.
    $utc_timezone = new DateTimeZone('UTC');

    // Convert start date to DateTime object in WordPress timezone.
    $start_datetime = new DateTime($start_date, $wp_timezone);
    // Adjust end date to include the entire day in WordPress timezone, then convert to DateTime object.
    $end_datetime = new DateTime($end_date . ' 23:59:59', $wp_timezone);

    // Determine the offset in seconds between WordPress timezone and UTC.
    $offset = $wp_timezone->getOffset($start_datetime) - $utc_timezone->getOffset($start_datetime);
    // Convert offset to hours to adjust the query range.
    $offsetHours = $offset / 3600;

    // Prepare the query, adjusting the date range by the calculated offset.
    // Note the use of DATE_ADD() function to adjust start and end times by the offset hours.
    $prepared_sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " 
        WHERE interaction_type = 'conversion' AND interaction_time >= DATE_ADD(%s, INTERVAL %d HOUR) AND interaction_time <= DATE_ADD(%s, INTERVAL %d HOUR)",
        $start_datetime->format('Y-m-d H:i:s'), // Start date and time in WP timezone.
        $offsetHours, // Adjust start date by offset hours.
        $end_datetime->format('Y-m-d H:i:s'), // End date and time at the end of the day in WP timezone.
        $offsetHours // Adjust end date by offset hours.
    );

    // Execute the query.
    $total_clicks = $wpdb->get_var($prepared_sql);

    return (int) $total_clicks;
}