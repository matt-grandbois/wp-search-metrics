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

    // Retrieve the WordPress timezone setting.
    $wp_timezone = new DateTimeZone(get_option('timezone_string') ? get_option('timezone_string') : 'UTC');
    
    // Fetch the UTC timezone for conversion.
    $utc_timezone = new DateTimeZone('UTC');

    // Convert start date to DateTime object in WordPress timezone.
    $start_datetime = new DateTime($start_date, $wp_timezone);
    // Adjust end date to include the entire day in WordPress timezone, then convert to DateTime object.
    $end_datetime = new DateTime($end_date . ' 23:59:59', $wp_timezone);

    // Determine the offset in seconds between WordPress timezone and UTC.
    $offset = $wp_timezone->getOffset($start_datetime) - $utc_timezone->getOffset($start_datetime);
    // Convert the offset to hours to adjust the query range.
    $offsetHours = $offset / 3600;

    // Prepare the SQL query, adjusting the date range by the calculated offset.
    // Note the use of DATE_ADD() function for proper timezone adjustment.
    $prepared_sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " 
        WHERE post_id IS NULL AND interaction_type = 'no_conversion' 
        AND interaction_time >= DATE_ADD(%s, INTERVAL %d HOUR) AND interaction_time <= DATE_ADD(%s, INTERVAL %d HOUR)",
        $start_datetime->format('Y-m-d H:i:s'), // Start date and time in WordPress timezone.
        $offsetHours, // Adjust start date by the offset hours.
        $end_datetime->format('Y-m-d H:i:s'),  // End date adjusted to the end of the day in WordPress timezone.
        $offsetHours // Adjust end date by the offset hours.
    );

    // Execute the query.
    $total_no_conversion_searches = $wpdb->get_var($prepared_sql);

    return (int) $total_no_conversion_searches;
}