<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Converts UTC time to local time based on WordPress settings.
 *
 * @param string $utc_time    UTC time string.
 * @param string $time_format Format to return local time in.
 *
 * @return string Local time in specified format.
 */
function wpsm_convert_timestamp_from_utc_to_local($utc_time, $time_format) {
    try {
        // Create a DateTime object from the UTC time string.
        $datetime = new DateTime($utc_time, new DateTimeZone('UTC'));
        
        // Get the WordPress timezone setting.
        $wp_timezone_string = get_option('timezone_string');

        // Check if the timezone setting is a valid timezone string.
        if (!empty($wp_timezone_string)) {
            // Convert the DateTime object into the WordPress-configured timezone.
            $datetime->setTimezone(new DateTimeZone($wp_timezone_string));
        } else {
            // If the timezone string is empty, WordPress might be using a manual offset. Handle accordingly.
            // This part is optional and typically required only if the timezone setting isn't a valid PHP timezone string,
            // but rather an offset. In most cases, a timezone string should be set for best practices.
            $offset = get_option('gmt_offset');
            $timezoneName = timezone_name_from_abbr('', $offset * 3600, false);
            if($timezoneName) {
                $datetime->setTimezone(new DateTimeZone($timezoneName));
            }
        }

        // Format the datetime for display.
        $local_time_display = $datetime->format($time_format);

        // Display the converted local time.
        return $local_time_display;

    } catch (Exception $e) {
        // Handle the error, perhaps log it or return a default value.
        return null;
    }
}