document.addEventListener("DOMContentLoaded", function() {
    fetch('<?php echo admin_url("admin-ajax.php?action=get_order_stats&start_date={$start_date}&end_date={$end_date}"); ?>')
        .then(response => response.json())
        .then(data => {
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
        });
});