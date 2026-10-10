<?php
// Reports Page
?>
<div class="page-content">
    <h2 class="mb-4">Reports & Export</h2>
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Shipments</h5>
                    <h2 class="mb-0" id="total-shipments">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Delivered</h5>
                    <h2 class="mb-0" id="delivered-count">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">In Transit</h5>
                    <h2 class="mb-0" id="in-transit-count">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <h2 class="mb-0" id="pending-count">0</h2>
                </div>
            </div>
        </div>
    </div>
    <!-- Report Generation Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Custom Report Generator</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" id="report-from-date">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" id="report-to-date">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Status Filter</label>
                    <select class="form-control" id="report-status">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="picked_up">Picked Up</option>
                        <option value="in_transit">In Transit</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Export Format</label>
                    <select class="form-control" id="report-format">
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button class="btn btn-primary" id="generateReportBtn">
                        <i class="fas fa-chart-line"></i> Generate Report
                    </button>
                    <button class="btn btn-success" id="exportReportBtn">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-danger" id="clearReportsBtn">
                        <i class="fas fa-trash"></i> Clear All Reports
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Report Preview Section -->
    <div class="card mb-4 report-preview-card d-none" id="reportPreviewCard">
        <div class="card-header">
            <h5 class="mb-0">Report Preview</h5>
        </div>
        <div class="card-body">
            <div id="reportPreview"></div>
        </div>
    </div>
    <!-- Saved Reports Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Saved Reports</h5>
        </div>
        <div class="card-body">
            <div id="savedReportsList"></div>
        </div>
    </div>
</div>

<script src="../assets/js/reports-page.js"></script>


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
                        console.warn('No statistics data received');
                        updateStatistics({
                            total: 0,
                            delivered: 0,
                            in_transit: 0,
                            pending: 0
                        });
                    }
                })
                .catch(error => {
                    console.error('API Error:', error);
                    showMessage('Warning: Could not load statistics', 'warning');
                    updateStatistics({
                        total: 0,
                        delivered: 0,
                        in_transit: 0,
                        pending: 0
                    });
                });
        }
        // Update statistics cards
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

        // Generate Report
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
                        displayReportData(currentReportData, {
                            fromDate,
                            toDate,
                            status
                        });
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

            html += `
                    </tbody>
                </table>
            </div>
        `;

            document.getElementById('reportPreview').innerHTML = html;
            document.getElementById('reportPreviewCard').style.display = 'block';

            document.getElementById('reportPreviewCard').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Export to CSV
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
            const blob = new Blob(["\uFEFF" + csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
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

        // Export to JSON
        function exportToJSON() {

            if (currentReportData.length === 0) {
                showMessage('No report data to export. Generate a report first.', 'warning');
                return;
            }

            const exportData = {
                exported_at: new Date().toISOString(),
                total_records: currentReportData.length,
                data: currentReportData
            };

            const jsonStr = JSON.stringify(exportData, null, 2);
            const blob = new Blob([jsonStr], {
                type: 'application/json'
            });
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

        // Save to localStorage
        function saveToLocalStorage(data) {
            const savedReports = getSavedReports();
            const newReport = {
                id: Date.now(),
                name: `Report_${new Date().toLocaleString()}`,
                date: new Date().toLocaleString(),
                records: data.length,
                data: data
            };

            savedReports.unshift(newReport);
            // Keep only last 5 reports
            while (savedReports.length > 5) savedReports.pop();

            localStorage.setItem('shipment_reports', JSON.stringify(savedReports));
            displaySavedReports();
        }

        // Get saved reports
        function getSavedReports() {
            try {
                const reports = localStorage.getItem('shipment_reports');
                return reports ? JSON.parse(reports) : [];
            } catch (e) {
                return [];
            }
        }

        // Display saved reports
        function displaySavedReports() {
            const container = document.getElementById('savedReportsList');
            const reports = getSavedReports();

            if (!container) return;

            if (reports.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No saved reports yet. Generate a report to save it.</p>';
                return;
            }

            let html = `
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Report Name</th>
                            <th>Date</th>
                            <th>Records</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            reports.forEach(report => {
                html += `
                <tr>
                    <td><strong>${report.name}</strong></td>
                    <td>${report.date}</td>
                    <td><span class="badge bg-primary">${report.records}</span></td>
                    <td>
                        <button class="btn btn-sm btn-info view-report" data-id="${report.id}">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-danger delete-report" data-id="${report.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
            });

            html += `
                    </tbody>
                </table>
            </div>
        `;

            container.innerHTML = html;

            // Add event listeners to buttons
            document.querySelectorAll('.view-report').forEach(btn => {
                btn.addEventListener('click', () => viewSavedReport(parseInt(btn.dataset.id)));
            });

            document.querySelectorAll('.delete-report').forEach(btn => {
                btn.addEventListener('click', () => deleteSavedReport(parseInt(btn.dataset.id)));
            });
        }

        // View saved report
        function viewSavedReport(id) {
            const reports = getSavedReports();
            const report = reports.find(r => r.id === id);

            if (report && report.data) {
                let html = `
                <div class="alert alert-info">
                    <strong>📄 ${report.name}</strong><br>
                    Generated: ${report.date}<br>
                    Total Records: ${report.records}
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Tracking #</th>
                                <th>Customer</th>
                                <th>Receiver</th>
                                <th>Weight</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

                report.data.slice(0, 10).forEach(s => {
                    html += `
                    <tr>
                        <td>${s.tracking_number || 'N/A'}</td>
                        <td>${s.customer_name || 'N/A'}</td>
                        <td>${s.receiver_name || 'N/A'}</td>
                        <td>${s.package_weight || 'N/A'}</td>
                        <td>${s.status || 'N/A'}</td>
                    </tr>
                `;
                });

                if (report.data.length > 10) {
                    html += `<tr><td colspan="5" class="text-center">... and ${report.data.length - 10} more records</td></tr>`;
                }

                html += `
                        </tbody>
                    </table>
                </div>
            `;

                document.getElementById('reportPreview').innerHTML = html;
                document.getElementById('reportPreviewCard').style.display = 'block';
                showMessage(`Loaded saved report with ${report.records} records`, 'success');
            }
        }

        // Delete saved report
        function deleteSavedReport(id) {
            if (!confirm('Are you sure you want to delete this saved report?')) return;

            let reports = getSavedReports();
            reports = reports.filter(r => r.id !== id);
            localStorage.setItem('shipment_reports', JSON.stringify(reports));
            displaySavedReports();
            showMessage('Report deleted successfully', 'success');
        }

        // Clear all reports
        function clearAllReports() {
            if (!confirm('⚠️ Warning: This will delete ALL saved reports. Are you sure?')) return;

            localStorage.removeItem('shipment_reports');
            displaySavedReports();
            showMessage('All reports cleared', 'success');
        }

        // Set default dates
        function setDefaultDates() {
            const today = new Date();
            const lastMonth = new Date();
            lastMonth.setMonth(today.getMonth() - 1);

            const fromDateInput = document.getElementById('report-from-date');
            const toDateInput = document.getElementById('report-to-date');

            if (fromDateInput) {
                fromDateInput.value = lastMonth.toISOString().split('T')[0];
            }
            if (toDateInput) {
                toDateInput.value = today.toISOString().split('T')[0];
            }

        }

        // Initialize everything when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initialize();
            });
        } else {
            initialize();
        }

        function initialize() {
            setDefaultDates();
            loadShipments();
            displaySavedReports();

            // Get button elements
            const generateBtn = document.getElementById('generateReportBtn');
            const exportBtn = document.getElementById('exportReportBtn');
            const clearBtn = document.getElementById('clearReportsBtn');
            const statusFilter = document.getElementById('report-status');
            const fromDate = document.getElementById('report-from-date');
            const toDate = document.getElementById('report-to-date');

            // Attach event listeners
            if (generateBtn) {
                generateBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    generateReport();
                });
            } else {
                console.error('Generate button not found!');
            }

            if (exportBtn) {
                exportBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const format = document.getElementById('report-format').value;
                    if (format === 'csv') {
                        exportToCSV();
                    } else if (format === 'json') {
                        exportToJSON();
                    }
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearAllReports();
                });
            }

            // Reload statistics when filters change
            if (statusFilter) {
                statusFilter.addEventListener('change', generateReport);
            }
            if (fromDate) {
                fromDate.addEventListener('change', generateReport);
            }
            if (toDate) {
                toDate.addEventListener('change', generateReport);
            }

            showMessage('Reports page ready!', 'success');
        }

    })();
</script>