(function () {
    let allShipments = [];
    let currentReportData = [];

    function showMessage(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.style.minWidth = '300px';
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => {
            if (alertDiv) alertDiv.remove();
        }, 3000);
    }

    function loadShipments() {
        fetch('/api/reports.php?action=stats')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success && result.data) {
                    allShipments = [];
                    updateStatistics(result.data);
                } else {
                    updateStatistics({ total: 0, delivered: 0, in_transit: 0, pending: 0 });
                }
            })
            .catch(error => {
                console.error('API Error:', error);
                showMessage('Warning: Could not load statistics', 'warning');
                updateStatistics({ total: 0, delivered: 0, in_transit: 0, pending: 0 });
            });
    }

    function updateStatistics(stats = {}) {
        const total = stats.total || 0;
        const delivered = stats.delivered || 0;
        const inTransit = stats.in_transit || 0;
        const pending = stats.pending || 0;
        document.getElementById('total-shipments').textContent = total;
        document.getElementById('delivered-count').textContent = delivered;
        document.getElementById('in-transit-count').textContent = inTransit;
        document.getElementById('pending-count').textContent = pending;
    }

    function generateReport() {
        const fromDate = document.getElementById('report-from-date').value;
        const toDate = document.getElementById('report-to-date').value;
        const status = document.getElementById('report-status').value;
        let url = '/api/reports.php?action=generate';
        if (fromDate) url += '&from_date=' + encodeURIComponent(fromDate);
        if (toDate) url += '&to_date=' + encodeURIComponent(toDate);
        if (status) url += '&status=' + encodeURIComponent(status);
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success && result.data) {
                    currentReportData = result.data;
                    displayReportData(currentReportData, { fromDate, toDate, status });
                    showMessage(`✅ Report generated with ${currentReportData.length} records`, 'success');
                    saveToLocalStorage(currentReportData);
                } else {
                    showMessage('No data found for selected criteria', 'warning');
                    currentReportData = [];
                }
            })
            .catch(error => {
                console.error('API Error:', error);
                showMessage('Error generating report: ' + error.message, 'danger');
                currentReportData = [];
            });
    }

    function displayReportData(filteredData, filters) {
        let html = `<div class="alert alert-success">
                    <strong>Report Generated Successfully!</strong><br>
                    Period: ${filters.fromDate || 'Start'} to ${filters.toDate || 'End'}<br>
                    Status: ${filters.status || 'All'}<br>
                    Total Records: ${filteredData.length}<br>
                    Generated: ${new Date().toLocaleString()}
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Tracking Number</th>
                                <th>Customer Name</th>
                                <th>Receiver Name</th>
                                <th>Weight (kg)</th>
                                <th>Status</th>
                                <th>Created Date</th>
                            </tr>
                        </thead>
                        <tbody>`;

        filteredData.forEach((s, index) => {
            let statusClass = 'secondary';
            if (s.status === 'delivered') statusClass = 'success';
            else if (s.status === 'cancelled') statusClass = 'danger';
            else if (s.status === 'pending') statusClass = 'warning';
            else if (s.status === 'in_transit') statusClass = 'info';

            const statusText = s.status ? s.status.replace(/_/g, ' ').toUpperCase() : 'N/A';

            html += `<tr>
                        <td>${index + 1}</td>
                        <td><strong>${s.tracking_number || 'N/A'}</strong></td>
                        <td>${s.customer_name || 'N/A'}</td>
                        <td>${s.receiver_name || 'N/A'}</td>
                        <td>${s.package_weight || 'N/A'}</td>
                        <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                        <td>${s.created_at ? new Date(s.created_at).toLocaleDateString() : 'N/A'}</td>
                    </tr>`;
        });

        html += `</tbody></table></div>`;

        document.getElementById('reportPreview').innerHTML = html;
        const previewCard = document.getElementById('reportPreviewCard');
        previewCard.classList.remove('d-none');
        previewCard.scrollIntoView({ behavior: 'smooth' });
    }

    function exportToCSV() {
        if (currentReportData.length === 0) {
            showMessage('No report data to export. Generate a report first.', 'warning');
            return;
        }

        const headers = ['Tracking Number', 'Customer Name', 'Customer Email', 'Receiver Name', 'Receiver Phone', 'Weight (kg)', 'Status', 'Created Date'];
        const rows = currentReportData.map(s => [
            s.tracking_number || '',
            s.customer_name || '',
            s.customer_email || '',
            s.receiver_name || '',
            s.receiver_phone || '',
            s.package_weight || '',
            s.status || '',
            s.created_at ? new Date(s.created_at).toLocaleDateString() : ''
        ]);

        const csvContent = [headers, ...rows].map(row => row.join(',')).join('\n');
        const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `shipments_export_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showMessage('✅ CSV file downloaded successfully', 'success');
    }

    function exportToJSON() {
        if (currentReportData.length === 0) {
            showMessage('No report data to export. Generate a report first.', 'warning');
            return;
        }

        const exportData = { exported_at: new Date().toISOString(), total_records: currentReportData.length, data: currentReportData };
        const jsonStr = JSON.stringify(exportData, null, 2);
        const blob = new Blob([jsonStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `shipments_export_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showMessage('✅ JSON file downloaded successfully', 'success');
    }

    function saveToLocalStorage(data) {
        const savedReports = getSavedReports();
        const newReport = { id: Date.now(), name: `Report_${new Date().toLocaleString()}`, date: new Date().toLocaleString(), records: data.length, data };
        savedReports.unshift(newReport);
        while (savedReports.length > 5) savedReports.pop();
        localStorage.setItem('shipment_reports', JSON.stringify(savedReports));
        displaySavedReports();
    }

    function getSavedReports() {
        try {
            const reports = localStorage.getItem('shipment_reports');
            return reports ? JSON.parse(reports) : [];
        } catch (e) {
            return [];
        }
    }

    function displaySavedReports() {
        const container = document.getElementById('savedReportsList');
        const reports = getSavedReports();
        if (!container) return;

        if (reports.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No saved reports yet. Generate a report to save it.</p>';
            return;
        }

        let html = `<div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Report Name</th><th>Date</th><th>Records</th><th>Actions</th></tr></thead><tbody>`;

        reports.forEach(report => {
            html += `<tr><td><strong>${report.name}</strong></td><td>${report.date}</td><td><span class="badge bg-primary">${report.records}</span></td><td><button class="btn btn-sm btn-info view-report" data-id="${report.id}"><i class="fas fa-eye"></i> View</button><button class="btn btn-sm btn-danger delete-report" data-id="${report.id}"><i class="fas fa-trash"></i> Delete</button></td></tr>`;
        });

        html += `</tbody></table></div>`;
        container.innerHTML = html;

        document.querySelectorAll('.view-report').forEach(btn => {
            btn.addEventListener('click', () => viewSavedReport(parseInt(btn.dataset.id)));
        });
        document.querySelectorAll('.delete-report').forEach(btn => {
            btn.addEventListener('click', () => deleteSavedReport(parseInt(btn.dataset.id)));
        });
    }

    function viewSavedReport(id) {
        const reports = getSavedReports();
        const report = reports.find(r => r.id === id);
        if (report && report.data) {
            let html = `<div class="alert alert-info"><strong>📄 ${report.name}</strong><br>Generated: ${report.date}<br>Total Records: ${report.records}</div><div class="table-responsive"><table class="table table-bordered"><thead class="table-dark"><tr><th>Tracking #</th><th>Customer</th><th>Receiver</th><th>Weight</th><th>Status</th></tr></thead><tbody>`;
            report.data.slice(0, 10).forEach(s => {
                html += `<tr><td>${s.tracking_number || 'N/A'}</td><td>${s.customer_name || 'N/A'}</td><td>${s.receiver_name || 'N/A'}</td><td>${s.package_weight || 'N/A'}</td><td>${s.status || 'N/A'}</td></tr>`;
            });
            if (report.data.length > 10) html += `<tr><td colspan="5" class="text-center">... and ${report.data.length - 10} more records</td></tr>`;
            html += `</tbody></table></div>`;
            document.getElementById('reportPreview').innerHTML = html;
            const previewCard = document.getElementById('reportPreviewCard');
            previewCard.classList.remove('d-none');
            showMessage(`Loaded saved report with ${report.records} records`, 'success');
        }
    }

    function deleteSavedReport(id) {
        if (!confirm('Are you sure you want to delete this saved report?')) return;
        let reports = getSavedReports();
        reports = reports.filter(r => r.id !== id);
        localStorage.setItem('shipment_reports', JSON.stringify(reports));
        displaySavedReports();
        showMessage('Report deleted successfully', 'success');
    }

    function clearAllReports() {
        if (!confirm('⚠️ Warning: This will delete ALL saved reports. Are you sure?')) return;
        localStorage.removeItem('shipment_reports');
        displaySavedReports();
        showMessage('All reports cleared', 'success');
    }

    function setDefaultDates() {
        const today = new Date();
        const lastMonth = new Date();
        lastMonth.setMonth(today.getMonth() - 1);
        const fromDateInput = document.getElementById('report-from-date');
        const toDateInput = document.getElementById('report-to-date');
        if (fromDateInput) fromDateInput.value = lastMonth.toISOString().split('T')[0];
        if (toDateInput) toDateInput.value = today.toISOString().split('T')[0];
    }

    function initialize() {
        setDefaultDates();
        loadShipments();
        displaySavedReports();

        const generateBtn = document.getElementById('generateReportBtn');
        const exportBtn = document.getElementById('exportReportBtn');
        const clearBtn = document.getElementById('clearReportsBtn');
        const statusFilter = document.getElementById('report-status');
        const fromDate = document.getElementById('report-from-date');
        const toDate = document.getElementById('report-to-date');

        generateBtn?.addEventListener('click', (e) => { e.preventDefault(); generateReport(); });
        exportBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            const format = document.getElementById('report-format').value;
            if (format === 'csv') exportToCSV();
            else if (format === 'json') exportToJSON();
        });
        clearBtn?.addEventListener('click', (e) => { e.preventDefault(); clearAllReports(); });
        statusFilter?.addEventListener('change', generateReport);
        fromDate?.addEventListener('change', generateReport);
        toDate?.addEventListener('change', generateReport);
        showMessage('Reports page ready!', 'success');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
})();
