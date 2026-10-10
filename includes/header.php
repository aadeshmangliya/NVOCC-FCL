<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">


    <!-- Environment Configuration -->
    <?php require_once __DIR__ . '/../config/env.php'; ?>
    <meta name="base-url" content="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>">
    <meta name="api-base-url" content="<?php echo env('API_BASE_URL', 'https://jqx.pov.mybluehost.me/website_873610e6/api'); ?>">
    <meta name="app-env" content="<?php echo env('APP_ENV', 'production'); ?>">
    <meta name="app-debug" content="<?php echo env('APP_DEBUG', 'false'); ?>">
    <?php $isLoggedInForApi = isset($_SESSION['user_id']) || isset($_SESSION['user']); ?>
    <?php if ($isLoggedInForApi): ?>
        <meta name="api-bearer-token" content="<?php echo env('API_BEARER_TOKEN', ''); ?>">
    <?php endif; ?>

</head>

<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="javascript:void(0)">
                <i class="fas fa-box"></i>Shipping
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0)" data-page="dashboard">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-page="tracking">
                            <i class="fas fa-search"></i> Track
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-page="shipments">
                            <i class="fas fa-boxes"></i> Shipments
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item">
                            <a class="nav-link logout-link" href="admin/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar d-none d-md-block">
                <ul class="sidebar-menu">
                    <li><a href="javascript:void(0)" class="sidebar-link active" data-page="dashboard">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a></li>
                    <li><a href="javascript:void(0)" class="sidebar-link" data-page="tracking">
                            <i class="fas fa-search"></i> Track Shipment
                        </a></li>
                    <li><a href="javascript:void(0)" class="sidebar-link" data-page="shipments">
                            <i class="fas fa-boxes"></i> All Shipments
                        </a></li>
                    <li><a href="javascript:void(0)" class="sidebar-link" data-page="invoice-list">
                            <i class="fas fa-file-invoice-dollar"></i> Invoices
                        </a></li>
                    <li><a href="javascript:void(0)" class="sidebar-link" data-page="payments">
                            <i class="fas fa-money-check-alt"></i> Payments
                        </a></li>
                    <li><a href="javascript:void(0)" class="sidebar-link" data-page="cargo_release">
                            <i class="fas fa-truck-loading"></i> Cargo Release
                        </a></li>
                    <!-- <li><a href="javascript:void(0)" class="sidebar-link" data-page="analytics">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a></li>
                <li><a href="javascript:void(0)" class="sidebar-link" data-page="reports">
                    <i class="fas fa-file-alt"></i> Reports
                </a></li> -->
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                        <li>
                            <hr>
                        </li>
                        <li><a href="javascript:void(0)" class="sidebar-link" data-page="users">
                                <i class="fas fa-users"></i> Users
                            </a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 col-12">
                <div id="page-content">
                    <div class="loading">
                        <div class="spinner"></div> Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>