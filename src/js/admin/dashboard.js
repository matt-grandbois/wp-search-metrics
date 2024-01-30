document.getElementById('date_range').addEventListener('change', function() {
    document.getElementById('dateRangeForm').submit();
});

/* Init chartjs line graph for top search terms volumes chart */
document.addEventListener('DOMContentLoaded', function() {
    var searchTermsVolumesCtx = document.querySelector('[data-chart-js-id="searchTermsVolumes"]').getContext('2d');
    var searchTermsVolumes = new Chart(searchTermsVolumesCtx, {
        type: 'line',
        data: wpSearchMetricsDashboard.searchTermsVolumeData.data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
        },
    });
});