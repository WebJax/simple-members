document.addEventListener("DOMContentLoaded", function() {
    // Check if we're on the statistics page with the chart element

    initMembersGrowthChart();
    initMembersFlowChart();

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
                                label: 'Antal medlemmer',
                                data: data.products_sold,
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

    /**
     * Initialiserer den akkumulerede medlemsvækstkurve
     */
    function initMembersGrowthChart() {
        if (!document.getElementById('membersGrowthChart')) {
            return;
        }

        // Hent data fra backend via AJAX - konverteret til fetch API
        fetch(simple_members_params.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_members_growth'
            })
        })
        .then(response => response.json())
        .then(response => {
            if (response.success && response.data) {
                renderMembersGrowthChart(response.data);
            }
        })
        .catch(error => console.error('Fejl ved hentning af medlemsvækst data:', error));
    }

    /**
     * Renderingsfunktion for den akkumulerede medlemsvækst
     * @param {Object} data - Data til grafen
     */
    function renderMembersGrowthChart(data) {
        const ctx = document.getElementById('membersGrowthChart').getContext('2d');
        
        // Formatér månederne for bedre læsbarhed
        const labels = Object.keys(data).map(formatMonthLabel);
        const memberCounts = Object.values(data);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Samlet antal medlemmer',
                    data: memberCounts,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Kun hele tal
                        }
                    }
                }
            }
        });
    }

    /**
     * Funktion til at initialisere medlemsflow-grafen (tilgang vs. afgang)
     */
    function initMembersFlowChart() {
        if (!document.getElementById('membersFlowChart')) {
            return;
        }

        // Hent data fra backend via AJAX - konverteret til fetch API
        fetch(simple_members_params.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_members_flow'
            })
        })
        .then(response => response.json())
        .then(response => {
            if (response.success && response.data) {
                renderMembersFlowChart(response.data);
            }
        })
        .catch(error => console.error('Fejl ved hentning af medlemsflow data:', error));
    }

    /**
     * Renderingsfunktion for medlemsflow
     * @param {Object} data - Data til grafen
     */
    function renderMembersFlowChart(data) {
        const ctx = document.getElementById('membersFlowChart').getContext('2d');
        
        const labels = Object.keys(data).map(formatMonthLabel);
        const newMembers = [];
        const lostMembers = [];

        // Udpak data
        Object.keys(data).forEach(month => {
            newMembers.push(data[month].new);
            lostMembers.push(-data[month].lost); // Negativ værdi for at vise som nedgang
        });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Nye medlemmer',
                        data: newMembers,
                        backgroundColor: 'rgba(75, 192, 92, 0.6)',
                        borderColor: 'rgba(75, 192, 92, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Mistede medlemmer',
                        data: lostMembers,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return Math.abs(value); // Vis absolutte værdier på y-aksen
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                if (context.datasetIndex === 1) { // For mistede medlemmer
                                    return 'Mistede medlemmer: ' + Math.abs(value);
                                }
                                return context.dataset.label + ': ' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Hjælpefunktion til at formatere månedsvisning
     * @param {string} monthKey - Måned på format 'YYYY-MM'
     * @returns {string} - Formateret måned på dansk format
     */
    function formatMonthLabel(monthKey) {
        const [year, month] = monthKey.split('-');
        const date = new Date(year, month - 1);
        
        // Returner månedsnavn og år
        return date.toLocaleString('da-DK', { month: 'short', year: 'numeric' });
    }
});