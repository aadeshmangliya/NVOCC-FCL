document.addEventListener('DOMContentLoaded', () => {
    if (typeof App === 'undefined' || !App.api) return;

    App.api.get('dashboard/stats').then(response => {
        if (response.success) {
            const stats = response.data;
            const total = stats.total_shipments || 1;

            document.getElementById('stat-total').textContent = total;
            document.getElementById('stat-delivered').textContent = stats.status?.delivered || 0;
            document.getElementById('stat-delivered-pct').textContent = ((stats.status?.delivered || 0) / total * 100).toFixed(1) + '%';
            document.getElementById('stat-transit').textContent = stats.status?.in_transit || 0;
            document.getElementById('stat-transit-pct').textContent = ((stats.status?.in_transit || 0) / total * 100).toFixed(1) + '%';
            document.getElementById('stat-pending').textContent = stats.status?.pending || 0;
            document.getElementById('stat-pending-pct').textContent = ((stats.status?.pending || 0) / total * 100).toFixed(1) + '%';
            document.getElementById('stat-cancelled').textContent = stats.status?.cancelled || 0;
            document.getElementById('stat-cancelled-pct').textContent = ((stats.status?.cancelled || 0) / total * 100).toFixed(1) + '%';
        }
    });
});
