(function($) {
    // Simplified send data method using AJAX for consistent Content-Type handling
    function sendData(url, data, redirectUrl = '') {
        // Convert object to URL-encoded string for WordPress compatibility
        var encodedData = $.param(data);

        // Initiate the AJAX request
        $.ajax({
            url: url,
            type: 'POST',
            data: encodedData,
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8', // Explicit Content-Type
            async: (redirectUrl === ''), // Use asynchronous only when not redirecting after
            success: function(response) {
                console.log('Server Response:', response);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                // If a redirect URL is provided, navigate to it after the request completes
                if (redirectUrl !== '') {
                    window.location.href = redirectUrl;
                }
            }
        });
    }

    // Track clicks on a search result
    $(document).on('click', '[data-wp-search-metrics-post-id]', function(event) {
        event.preventDefault();
        
        var targetUrl = $(this).attr('href');
        var postId = $(this).data('wp-search-metrics-post-id');
        
        var searchQuery = $('input[data-wp-search-metrics-search-field]').val().toLowerCase();
        var data = {
            action: 'wp_search_metrics_log_search_interaction',
            nonce: wpSearchMetrics.nonce,
            search_query: searchQuery,
            post_id: postId,
            event_type: 'conversion'
        };

        // Call sendData with the redirection URL
        sendData(wpSearchMetrics.ajax_url, data, targetUrl);
    });

    // Track no results scenario
    $(document).on('wp_search_metrics_no_results', function() {
        var searchQuery = $('input[data-wp-search-metrics-search-field]').val().toLowerCase();
        
        var data = {
            action: 'wp_search_metrics_log_no_results',
            nonce: wpSearchMetrics.nonce,
            search_query: searchQuery,
            event_type: 'no_conversion'
        };
        
        // Call sendData without redirection 
        sendData(wpSearchMetrics.ajax_url, data);
    });
})(jQuery);