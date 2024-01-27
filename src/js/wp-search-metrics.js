(function($) {    
    // Track when a user clicks on a search result
	$(document).on('click', '[data-wp-search-metrics-post-id]', function(event) {
		// Prevent the default navigation
		event.preventDefault();

		// Capture and store the URL from the link to navigate later
		var targetUrl = $(this).attr('href');

		var postId = $(this).data('wp-search-metrics-post-id');
		var searchQuery = $('input[data-wp-search-metrics-search-field]').val().toLowerCase();

		// Data to be sent
		var data = {
			action: 'wp_search_metrics_log_search_interaction',
			nonce: wpSearchMetrics.nonce,
			search_query: searchQuery,
			post_id: postId,
			event_type: 'conversion'
		};

		// Log data to console for debugging
		// console.log('Logging Search Interaction:', data);

		// Send the interaction to the server
		$.ajax({
			url: wpSearchMetrics.ajax_url,
			type: 'POST',
			data: data,
			success: function(response) {
				// Log response to console for debugging
				// console.log('Server Response:', response);

				// Once the request is completed, redirect to the target URL
				window.location.href = targetUrl;
			},
			error: function(xhr, status, error) {
				// Log error to console
				console.error('AJAX Error:', status, error);

				// In the case of an AJAX error, you can still choose to redirect or handle the error differently
				// Uncomment the line below if you want to navigate regardless of AJAX result
				window.location.href = targetUrl;
			}
		});
	});
    
    // Track when the AJAX search returns no results
    $(document).on('wp_search_metrics_no_results', function() {
        var searchQuery = $('input[data-wp-search-metrics-search-field]').val().toLowerCase();
        
        // Data to be sent
        var data = {
            action: 'wp_search_metrics_log_no_results',
            nonce: wpSearchMetrics.nonce,
            search_query: searchQuery,
			event_type: 'no_conversion'
        };
        
        // Send the no result to the server
        $.ajax({
            url: wpSearchMetrics.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                // Log response to console for debugging
                console.log('Server Response:', response);
            },
            error: function(xhr, status, error) {
                // Log AJAX error to console
                console.error('AJAX Error:', status, error);
            }
        });
    });
})(jQuery);