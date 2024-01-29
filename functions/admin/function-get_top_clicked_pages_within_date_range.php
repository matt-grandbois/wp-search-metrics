<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves the top clicked pages within a specified date range.
 *
 * This function fetches pages with the most conversion clicks based on interaction counts within the given date range.
 * The results can be ordered in ascending or descending order as specified and can limit the number of results returned.
 *
 * @param string  $start_date  The start date in 'Y-m-d' format from which to start counting page clicks.
 * @param string  $end_date    The end date in 'Y-m-d' format up to which to count page clicks.
 * @param integer $num_results (Optional) The number of results to fetch. Defaults to 8 if not specified.
 * @param string  $sort_order  (Optional) The sort order, ASC for ascending or DESC for descending. Defaults to DESC if not specified.
 *
 * @return array An array of objects containing the post_id and interaction_count for the top clicked pages.
 */
function wpsm_get_top_clicked_pages_within_date_range($start_date, $end_date, $num_results = 8, $sort_order = 'DESC') {
    global $wpdb;

    // Sanitize input dates to enhance security and prevent potential SQL injection.
    $start_date_sanitized = sanitize_text_field($start_date);
    $end_date_sanitized = sanitize_text_field($end_date);

    // Validate and sanitize the number of results, ensuring it's an integer and defaulting to 8 if not valid.
    $num_results_sanitized = filter_var($num_results, FILTER_VALIDATE_INT, ['options' => ['default' => 8, 'min_range' => 1]]);

    // Validate and sanitize sort order, defaulting to DESC if not valid.
    $sort_order_sanitized = in_array(strtoupper($sort_order), ['ASC', 'DESC']) ? strtoupper($sort_order) : 'DESC';

    // Adjust the end date to include results up to the end of the day.
    $end_date_adjusted = date('Y-m-d', strtotime($end_date_sanitized . ' +1 day'));

    // Prepare the SQL statement using sanitized inputs and a safe sort order.
    $top_pages_clicked_sql = $wpdb->prepare("
        SELECT si.post_id, COUNT(si.id) AS interaction_count
        FROM " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " si
        WHERE si.interaction_time >= %s
        AND si.interaction_time < %s
        AND si.interaction_type = 'conversion'
        GROUP BY si.post_id
        ORDER BY interaction_count {$sort_order_sanitized}
        LIMIT %d",
        $start_date_sanitized, $end_date_adjusted, $num_results_sanitized
    );

    // Execute the query and return the results.
    return $wpdb->get_results($top_pages_clicked_sql);
}