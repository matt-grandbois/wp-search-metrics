<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves the top converting search terms and their associated pages within a specified date range.
 *
 * This function fetches search terms leading to conversion clicks along with the associated post/page IDs and 
 * the click counts within the given date range. Results can be ordered based on search term and click counts,
 * with a customizable limit on the number of results.
 *
 * @param string  $start_date     The start date in 'Y-m-d' format from which to start counting conversions.
 * @param string  $end_date       The end date in 'Y-m-d' format up to which to count conversions.
 * @param integer $num_results    (Optional) The number of results to fetch. Defaults to 8 if not specified.
 * @param string  $query_sort_order (Optional) The sort order for query_text, 'ASC' or 'DESC'. Defaults to 'ASC' if not specified.
 * @param string  $clicks_sort_order (Optional) The sort order for clicks_count, 'ASC' or 'DESC'. Defaults to 'DESC' if not specified.
 *
 * @return array An array of objects containing the query_text, post_id, and clicks_count for the top converting search terms and pages.
 */
function wpsm_get_search_terms_resulting_in_page_clicks($start_date, $end_date, $num_results = 8, $query_sort_order = 'ASC', $clicks_sort_order = 'DESC') {
    global $wpdb;

    // Sanitize and validate input parameters.
    $start_date_sanitized = sanitize_text_field($start_date);
    $end_date_sanitized = sanitize_text_field($end_date);
    $num_results_sanitized = intval($num_results);

    // Validate and sanitize sort orders.
    $query_sort_order_sanitized = in_array(strtoupper($query_sort_order), ['ASC', 'DESC']) ? strtoupper($query_sort_order) : 'ASC';
    $clicks_sort_order_sanitized = in_array(strtoupper($clicks_sort_order), ['ASC', 'DESC']) ? strtoupper($clicks_sort_order) : 'DESC';

    // Adjust the end date to include interactions up to the end of the day.
    $end_date_adjusted = date('Y-m-d', strtotime($end_date_sanitized . ' +1 day'));

    // Construct the SQL query with safe placeholders and input values.
    $sql = $wpdb->prepare("
        SELECT
            sq.query_text,
            si.post_id,
            COUNT(si.id) AS clicks_count
        FROM 
            " . WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE . " sq
        JOIN 
            " . WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE . " si 
            ON sq.id = si.query_id
        WHERE 
            si.interaction_time >= %s 
            AND si.interaction_time < %s 
            AND si.interaction_type = 'conversion'
        GROUP BY 
            sq.query_text, si.post_id
        ORDER BY 
            sq.query_text $query_sort_order_sanitized, clicks_count $clicks_sort_order_sanitized
        LIMIT %d",
        $start_date_sanitized, $end_date_adjusted, $num_results_sanitized
    );

    // Execute the query and return the results.
    return $wpdb->get_results($sql);
}