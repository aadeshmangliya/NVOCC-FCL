<?php
// Analytics Page
?>

<div class="page-content">
    <h2 class="mb-4">📊 Analytics & Reports</h2>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Shipments by Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Shipments Trend (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detailed Statistics</h5>
                    <button class="btn btn-sm btn-primary">Export PDF</button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Value</th>
                                <th>Percentage</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total Shipments</td>
                                <td id="stat-total">-</td>
                                <td>100%</td>
                                <td><i class="fas fa-arrow-up text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Delivered</td>
                                <td id="stat-delivered">-</td>
                                <td id="stat-delivered-pct">-</td>
                                <td><i class="fas fa-arrow-up text-success"></i></td>
                            </tr>
                            <tr>
                                <td>In Transit</td>
                                <td id="stat-transit">-</td>
                                <td id="stat-transit-pct">-</td>
                                <td><i class="fas fa-arrow-right text-warning"></i></td>
                            </tr>
                            <tr>
                                <td>Pending</td>
                                <td id="stat-pending">-</td>
                                <td id="stat-pending-pct">-</td>
                                <td><i class="fas fa-arrow-down text-danger"></i></td>
                            </tr>
                            <tr>
                                <td>Cancelled</td>
                                <td id="stat-cancelled">-</td>
                                <td id="stat-cancelled-pct">-</td>
                                <td><i class="fas fa-arrow-down text-danger"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/analytics.js"></script>
