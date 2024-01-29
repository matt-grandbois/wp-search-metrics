(function($) {
    var searchQuery = wpSearchMetricsResultsPage.current_search_query.toLowerCase();
    var searchInteractionLogged = false;

    function trackSearchInteractionFallback(searchQuery, postId, eventType, targetUrl) {
        if (searchInteractionLogged && eventType !== 'no_conversion') return;
        searchInteractionLogged = true;

        // Prepare data directly for sending
        var formData = new FormData();
        formData.append('action', 'wp_search_metrics_log_search_interaction_results_page');
        formData.append('nonce', wpSearchMetricsResultsPage.nonce);
        formData.append('search_query', searchQuery);
        formData.append('post_id', postId || '0');
        formData.append('event_type', eventType);

        var beaconUrl = wpSearchMetricsResultsPage.ajax_url;

        // Determine whether to use the Beacon API or AJAX
        if (eventType === 'no_conversion' && navigator.sendBeacon) {

            // Correctly convert FormData to URLSearchParams for the beacon data
            var beaconData = new URLSearchParams();
            // Append data from formData
            for (var pair of formData.entries()) {
                beaconData.append(pair[0], pair[1]);
            }

            // Send the data using beacon
            navigator.sendBeacon(beaconUrl, beaconData);
        }
        else {
            // Convert FormData to an object for $.ajax to use. This is necessary because $.ajax doesn't support FormData directly when contentType is 'application/x-www-form-urlencoded'
            var ajaxData = {};
            formData.forEach(function(value, key){
                ajaxData[key] = value;
            });

            // AJAX fallback
            $.ajax({
                url: beaconUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    console.log('Interaction logged:', response);
                    if (targetUrl && eventType === "conversion") {
                        window.location.href = targetUrl;
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error logging interaction:', textStatus, errorThrown);
                    if(targetUrl && eventType === "conversion"){
                        // Redirect in case of error, but only if eventType is conversion
                        window.location.href = targetUrl;
                    }
                }
            });
        }
    }

    // Attach click event listener
    $('[data-wp-search-metrics-results-page-post-id]').on('click', function(event) {
        event.preventDefault();
        var clickedElement = $(this);
        var targetUrl = clickedElement.attr('href');
        var postId = clickedElement.data('wp-search-metrics-results-page-post-id');

        trackSearchInteractionFallback(searchQuery, postId, 'conversion', targetUrl);
    });

    // Track non-converting search when a user leaves the page
    $(window).on('beforeunload', function() {
        if (!searchInteractionLogged) {
            trackSearchInteractionFallback(searchQuery, null, 'no_conversion');
        }
    });
})(jQuery);