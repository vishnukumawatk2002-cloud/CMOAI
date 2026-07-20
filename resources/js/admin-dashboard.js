import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const chartData = window.dashboardCharts;

    if (!chartData) {
        return;
    }

    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Revenue (₹)',
                        data: chartData.revenue,
                        backgroundColor: 'rgba(108, 99, 255, 0.75)',
                        borderColor: '#6C63FF',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Orders',
                        data: chartData.orders,
                        type: 'line',
                        borderColor: '#0DC9A0',
                        backgroundColor: 'rgba(13, 201, 160, 0.1)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '₹' + Number(value).toLocaleString('en-IN'),
                        },
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: { stepSize: 1 },
                    },
                },
            },
        });
    }

    const usersCtx = document.getElementById('usersChart');
    if (usersCtx) {
        new Chart(usersCtx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'New Users',
                    data: chartData.users,
                    borderColor: '#6C63FF',
                    backgroundColor: 'rgba(108, 99, 255, 0.15)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6C63FF',
                    pointRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    }
});
