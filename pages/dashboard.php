<?php
// Dashboard Page
?>

<div class="page-content" data-user-role="<?php echo htmlspecialchars((string)($_SESSION['role'] ?? 'admin')); ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📊 Dashboard</h2>
        <button class="btn btn-primary" onclick="location.reload()">Refresh Data</button>
    </div>

    <!-- Stats Cards -->
    <div id="stats-container">
        <div class="loading">
            <div class="spinner"></div> Loading statistics...
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Recent Activity -->
        <div class="col-md-8">
            <div id="activity-container">
                <div class="loading">
                    <div class="spinner"></div> Loading activity...
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary" onclick="App.loadPage('shipments')">
                        <i class="fas fa-plus"></i> New Shipment
                    </button>
                    <!-- <button class="btn btn-secondary w-100 mb-2" onclick="App.loadPage('tracking')">
                        <i class="fas fa-search"></i> Track Shipment
                    </button> -->
                    <button class="btn btn-info" onclick="App.loadPage('shipments')">
                        <i class="fas fa-list"></i> View All Shipments
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/js/dashboard.js"></script>