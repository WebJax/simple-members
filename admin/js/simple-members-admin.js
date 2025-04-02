jQuery(function($) {
    'use strict';

    // Initialiser alle grafer, når siden er indlæst
    $(document).ready(function() {
        initMonthlySalesChart();
        initMembersGrowthChart();
        initMembersFlowChart();
    });

    function initMonthlySalesChart() {
        if (!document.getElementById('monthlySalesChart')) {
            return;
        }

        // Hent data fra backend via AJAX
        $.ajax({
            url: simple_members_params.ajax_url,
            type: 'POST',
            data: {
                action: 'get_monthly_sales'
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderMonthlySalesChart(response.data);
                }
            }
        });
    }

    function renderMonthlySalesChart(data) {
        const ctx = document.getElementById('monthlySalesChart').getContext('2d');
        
        // Formatér månederne for bedre læsbarhed
        const labels = Object.keys(data).map(formatMonthLabel);
        const salesData = Object.values(data);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Antal solgte medlemskaber',
                    data: salesData,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
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

    // Ny funktion til at initialisere den akkumulerede medlemsvækstkurve
    function initMembersGrowthChart() {
        if (!document.getElementById('membersGrowthChart')) {
            return;
        }

        // Hent data fra backend via AJAX
        $.ajax({
            url: simple_members_params.ajax_url,
            type: 'POST',
            data: {
                action: 'get_members_growth'
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderMembersGrowthChart(response.data);
                }
            }
        });
    }

    // Renderingsfunktion for den akkumulerede medlemsvækst
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

    // Funktion til at initialisere medlemsflow-grafen (tilgang vs. afgang)
    function initMembersFlowChart() {
        if (!document.getElementById('membersFlowChart')) {
            return;
        }

        // Hent data fra backend via AJAX
        $.ajax({
            url: simple_members_params.ajax_url,
            type: 'POST',
            data: {
                action: 'get_members_flow'
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderMembersFlowChart(response.data);
                }
            }
        });
    }

    // Renderingsfunktion for medlemsflow
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
                                var value = context.raw;
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

    // Hjælpefunktion til at formatere månedsvisning
    function formatMonthLabel(monthKey) {
        const [year, month] = monthKey.split('-');
        const date = new Date(year, month - 1);
        
        // Returner månedsnavn og år
        return date.toLocaleString('da-DK', { month: 'short', year: 'numeric' });
    }
});