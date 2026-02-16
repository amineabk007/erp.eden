import Alpine from 'alpinejs';
import axios from 'axios';

window.Alpine = Alpine;
window.axios = axios;

Alpine.data('sidebar', () => ({
    collapsed: false,
    toggle() {
        this.collapsed = !this.collapsed;
    },
}));

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    if (!window.Chart) {
        return;
    }

    const barCanvas = document.getElementById('revenueBarChart');
    if (barCanvas) {
        new window.Chart(barCanvas, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
                datasets: [{
                    label: 'CA',
                    data: [12000, 9800, 15600, 18700, 13300, 20200],
                    backgroundColor: '#22C55E',
                    borderRadius: 8,
                }],
            },
            options: {
                plugins: { legend: { labels: { color: '#94A3B8' } } },
                scales: {
                    x: { ticks: { color: '#94A3B8' }, grid: { color: '#1E293B' } },
                    y: { ticks: { color: '#94A3B8' }, grid: { color: '#1E293B' } },
                },
            },
        });
    }

    const pieCanvas = document.getElementById('invoicePieChart');
    if (pieCanvas) {
        new window.Chart(pieCanvas, {
            type: 'pie',
            data: {
                labels: ['Payée', 'Partielle', 'Non payée'],
                datasets: [{
                    data: [62, 21, 17],
                    backgroundColor: ['#22C55E', '#F59E0B', '#EF4444'],
                    borderColor: '#020617',
                    borderWidth: 2,
                }],
            },
            options: {
                plugins: {
                    legend: {
                        labels: { color: '#94A3B8' },
                    },
                },
            },
        });
    }
});
