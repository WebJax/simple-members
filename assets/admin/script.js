document.addEventListener("DOMContentLoaded", function() {
    // Check if we're on the statistics page with the chart element
    if (document.getElementById('orderChart') && typeof smStats !== 'undefined') {
        // Build the AJAX URL with parameters
        const ajaxURL = smStats.ajaxUrl + '?action=get_order_stats&start_date=' + smStats.startDate + '&end_date=' + smStats.endDate;
        
        fetch(ajaxURL)
            .then(response => response.json())
            .then(result => {
                // Check if we have a successful response with data
                if (result.success && result.data) {
                    const data = result.data;
                    let ctx = document.getElementById('orderChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Antal ordrer',
                                data: data.orders,
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }, {
                                label: 'Antal solgte produkter',
                                data: data.products,
                                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                } else {
                    console.error('Error loading chart data:', result);
                }
            })
            .catch(error => {
                console.error('Failed to load chart data:', error);
            });
    }
});