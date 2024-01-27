(function($) {
	var searchQuery = bbmSearchMetricsResultsPage.current_search_query.toLowerCase();
	var searchInteractionLogged = false; // Flag to ensure interaction is logged only once

	// Function to handle interaction logging
	function trackSearchInteraction(searchQuery, postId, eventType, targetUrl) {
		if(searchInteractionLogged) return; // Prevent multiple logging
		searchInteractionLogged = true; // Set flag to avoid duplicate logging

		// If postId is not provided, set it to null (0 is transformed to null by the server)
		postId = postId || null;

		$.ajax({
			url: bbmSearchMetricsResultsPage.ajax_url,
			type: 'POST',
			data: {
				action: 'bbm_search_metrics_log_search_interaction_results_page',
				nonce: bbmSearchMetricsResultsPage.nonce,
				search_query: searchQuery,
				post_id: postId,
				event_type: eventType
			},
			success: function(response) {
				console.log('Interaction logged:', response);
				if (targetUrl) {
					window.location.href = targetUrl; // Redirect only after success
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error('Error logging interaction:', textStatus, errorThrown);
				if (targetUrl) {
					window.location.href = targetUrl; // Fallback redirect in case of error
				}
			}
		});
	}

	// Track clicks on search results
	$('[data-bbm-search-metrics-results-page-post-id]').on('click', function(event) {
		event.preventDefault(); // Prevent default click event

		var clickedElement = $(this);
		var targetUrl = clickedElement.attr('href');
		var postId = clickedElement.data('bbm-search-metrics-results-page-post-id');

		trackSearchInteraction(searchQuery, postId, 'conversion', targetUrl);
	});

	// Track non-converting search when user leaves the page without clicking a result
	$(window).on('beforeunload', function() {
		if (!searchInteractionLogged) {
			trackSearchInteraction(searchQuery, null, 'no_conversion', null);
		}
	});
})(jQuery);