<?php
require_once '../config/database.php';
require_once '../includes/admin_security.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <meta name="base-url" content="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>">
    <meta name="api-base-url" content="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>/api">
</head>
<body>
    <div id="sidebar-overlay" class="sidebar-overlay"></div>

    <div class="admin-container">
        <div class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header admin-sidebar-header">
                <!-- <img src="../assets/images/logo.png" alt="Logo" class="img-fluid mb-2 admin-sidebar-logo"> -->
                <small>Welcome, <?php echo $_SESSION['username']; ?></small>
                <button class="sidebar-close-btn d-md-none" id="sidebarCloseBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                <a href="javascript:void(0)" class="sidebar-link active" data-page="dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'agent'): ?>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="shipments">
                        <i class="fas fa-boxes"></i> Shipments
                    </a>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="arrival_notice">
                        <i class="fas fa-bell"></i> Arrival Notices
                    </a>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="shipments-charges">
                        <i class="fas fa-dollar-sign"></i> Shipments Charges
                    </a>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="invoice-list">
                        <i class="fas fa-file-invoice-dollar"></i> Invoices
                    </a>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="payments">
                        <i class="fas fa-credit-card"></i> Payments
                    </a>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="cargo_release">
                        <i class="fas fa-truck-loading"></i> Cargo Release
                    </a>
                <?php else: ?>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="shipments">
                        <i class="fas fa-boxes"></i> Shipments
                    </a>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="invoice-list">
                        <i class="fas fa-file-invoice-dollar"></i> Invoices
                    </a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="javascript:void(0)" class="sidebar-link" data-page="users">
                            <i class="fas fa-users"></i> Users
                        </a>
                    <?php endif; ?>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="companies">
                        <i class="fas fa-building"></i> Companies
                    </a>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="documents">
                        <i class="fas fa-file-alt"></i> Documents
                    </a>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="email_logs">
                        <i class="fas fa-envelope"></i> Email Logs
                    </a>
                    <a href="javascript:void(0)" class="sidebar-link" data-page="payments">
                        <i class="fas fa-credit-card"></i> Payments
                    </a>
                <?php endif; ?>
                <hr>
                <a href="logout.php" class="text-danger sidebar-link logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="admin-main" id="adminMain">
            <div class="navbar-admin">
                <button id="adminHamburger" class="hamburger-btn" type="button" aria-label="Open menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="mb-0"><i class="fas fa-chart-line"></i> Admin Control Panel</h4>
                <a href="../index.php" target="_blank" class="btn btn-light btn-sm">
                    <i class="fas fa-globe"></i> View Website
                </a>
            </div>
            <div id="page-content" class="page-content-wrapper">
                <div class="loading">
                    <div class="spinner"></div> Loading...
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/users.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/shipments.js"></script>
    <script src="../assets/js/shipments-charges.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/arrival-notices.js"></script>
    <script src="../assets/js/email-logs.js"></script>
    <script src="../assets/js/admin_companies.js"></script>
    <script src="../assets/js/admin_documents.js"></script>
    <script src="../assets/js/document-assignment-manager.js"></script>
    <script src="../assets/js/admin-dashboard-init.js"></script>
</body>
</html>