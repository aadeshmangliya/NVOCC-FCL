<?php
require_once 'config/database.php';
global $pdo;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast & Reliable Shipment Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tracking.css">
    <link rel="stylesheet" href="assets/css/email-tracking.css">
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <!-- <a class="navbar-brand" href="#">
                <img src="assets/images/whitelogo.png" alt="Logo" class="img-fluid mb-2" style="max-width: 150px;">
            </a> -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-4">Fast & Reliable Shipment Tracking</h1>
                    <p class="lead mb-4">Track your packages in real-time with Us. We provide secure, fast, and reliable delivery services worldwide.</p>
                    <div class="d-flex gap-3">
                        <a href="#services" class="btn btn-light btn-lg">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="tracking-card">
                        <h3 class="text-dark mb-3">Quick Tracking</h3>
                        <div id="tracking-alerts"></div>

                        <!-- Email Form -->
                        <div id="email-tracking-section">
                            <form id="email-tracking-form" class="email-tracking-form">
                                <div class="email-form-group">
                                    <label for="email-input">Enter Your Email</label>
                                    <input type="email" id="email-input" class="form-control form-control-lg"
                                        placeholder="your@email.com" required>
                                    <small class="text-muted">We'll send you a verification code</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-envelope"></i> Send Verification Code
                                </button>
                            </form>
                        </div>

                        <!-- OTP Verification Form -->
                        <div id="otp-verification-section">
                            <div class="otp-section">
                                <h3>Verify Your Email</h3>
                                <p>Enter the 6-digit code sent to your email</p>
                                <div class="session-info">
                                    Session expires in: <span class="session-timer" id="session-timer">20:00</span>
                                </div>
                            </div>
                            <form id="otp-verification-form">
                                <div class="otp-inputs-wrapper">
                                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-check"></i> Verify OTP
                                </button>
                            </form>
                        </div>
                        <div id="tracking-results" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    // Support GET for shareable URLs (e.g., ?tracking_number=...)
    $tracking_input = $_REQUEST['tracking_number'] ?? null;
    $show_tracking = false;
    if ($tracking_input && !empty(trim($tracking_input))):
        $tracking_no = trim($tracking_input);
        $stmt = $pdo->prepare("SELECT * FROM shipments WHERE tracking_number = ?");
        $stmt->execute([$tracking_no]);
        $shipment = $stmt->fetch();
        if ($shipment):
            $stmt_history = $pdo->prepare("SELECT * FROM tracking_history WHERE tracking_number = ? ORDER BY created_at DESC");
            $stmt_history->execute([$tracking_no]);
            $history = $stmt_history->fetchAll();
            $show_tracking = true;
        endif;
    endif;
    ?>

    <!-- View Details Section (after home) -->
    <?php if ($show_tracking): ?>
        <section id="tracking-result-cta" class="py-5 bg-light">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 text-center">
                        <div class="card shadow-lg h-100">
                            <div class="card-body p-5">
                                <h3 class="card-title mb-3">
                                    <i class="fas fa-package" style="color: #FF883A;"></i> Shipment Found
                                </h3>
                                <p class="card-text text-muted mb-4">
                                    Tracking Number: <strong><?php echo htmlspecialchars($shipment['tracking_number']); ?></strong>
                                </p>
                                <a href="#mainTrackingCard" class="btn btn-primary btn-lg">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Professional Tracking Page Design -->
    <section class="wpcargo-wrap-details" id="mainTrackingCard" style="display: <?= $show_tracking ? 'block' : 'none' ?>;">
        <div class="track-header">
            <div class="shipment-number">
                <?php echo $show_tracking ? htmlspecialchars($shipment['tracking_number']) : ''; ?>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="status-banner">
            <span class="shipment-status-label">Current Shipment Status:</span>
            <span class="status-value"><?php echo $show_tracking ? htmlspecialchars(ucwords(str_replace('_', ' ', $shipment['status'] ?? 'N/A'))) : ''; ?></span>
        </div>
        <div class="print-footer">
            <button class="print-button" id="printTrackingBtn">Print Shipment Ticket</button>
        </div>

        <div class="main-content-wrapper">
            <!-- LEFT COLUMN -->
            <div class="left-column">
                <!-- Parties Information -->
                <div class="section-card">
                    <div class="section-header">Parties Information</div>
                    <div class="two-columns" style="padding: 0 24px 24px 24px;">
                        <div class="col-half">
                            <div class="header-title">Shipper</div>
                            <div class="address-details">
                                <?php echo $show_tracking ? htmlspecialchars($shipment['shipper_name'] ?? 'N/A') . '<br><small style="color:#5e7e8f;">Origin: ' . htmlspecialchars($shipment['origin'] ?? 'N/A') . '</small>' : ''; ?>
                            </div>
                        </div>
                        <div class="col-half">
                            <div class="header-title">Receiver</div>
                            <div class="address-details">
                                <?php echo $show_tracking ? htmlspecialchars($shipment['consignee_name'] ?? 'N/A') . '<br><small style="color:#5e7e8f;">Destination: ' . htmlspecialchars($shipment['destination'] ?? 'N/A') . '</small>' : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipment Details -->
                <div class="section-card">
                    <div class="section-header">Shipment Details</div>
                    <div class="info-grid">
                        <div class="info-item"><span class="info-label">Origin:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['origin'] ?? 'N/A') : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Destination:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['destination'] ?? 'N/A') : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Packages:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['num_packages'] ?? $shipment['package_description'] ?? 'N/A') : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Weight:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['gross_weight'] ?? 'N/A') . ' KGS' : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Product:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['goods_description'] ?? 'N/A') : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Carrier:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['carrier'] ?? '—') : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Mode:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['mode'] ?? '—') : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Payment:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['payment_mode'] ?? '—') : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Total Freight:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['total_freight'] ?? '—') : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">ETA:</span><span class="info-value"><?php echo $show_tracking && !empty($shipment['estimated_delivery']) ? date('d M Y', strtotime($shipment['estimated_delivery'])) : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Departure:</span><span class="info-value"><?php echo $show_tracking && !empty($shipment['etd']) ? date('d M Y', strtotime($shipment['etd'])) : ''; ?></span></div>
                        <div class="info-item"><span class="info-label">Comments:</span><span class="info-value"><?php echo $show_tracking ? htmlspecialchars($shipment['comments'] ?? '—') : ''; ?></span></div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="right-column">
                <!-- Tracking History Panel -->
                <div class="history-panel">
                    <h6>Tracking History</h6>
                    <?php if ($show_tracking && !empty($history)) {
                        foreach ($history as $event) { ?>
                            <div class="history-item">
                                <div class="history-date"><?php echo date('d M Y, h:i A', strtotime($event['created_at'])); ?></div>
                                <div><span class="history-status"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $event['status']))); ?></span></div>
                                <div class="history-location"><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></div>
                                <?php if ($event['remarks']): ?>
                                    <div class="history-remarks"><strong>Remarks:</strong> <?php echo htmlspecialchars($event['remarks']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="history-item" style="text-align:center;padding:20px;">No tracking history available yet</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
    <?php if ($tracking_input && !$shipment): ?>
        <section class="container mt-5 mb-5">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <strong>Error!</strong> Tracking number not found. Please check and try again.
            </div>
        </section>
    <?php endif; ?>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Our Services</h2>
                <p class="lead text-muted">We offer comprehensive shipment solutions for all your needs</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="service-card card h-100 text-center p-4">
                        <div class="service-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h4>Express Delivery</h4>
                        <p class="text-muted">Fast and secure delivery of your packages within 24-48 hours</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card card h-100 text-center p-4">
                        <div class="service-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h4>International Shipping</h4>
                        <p class="text-muted">Worldwide shipping services with customs clearance support</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card card h-100 text-center p-4">
                        <div class="service-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h4>Real-time Tracking</h4>
                        <p class="text-muted">Track your shipments in real-time from pickup to delivery</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-5 fw-bold mb-4">About</h2>
                    <p class="lead">Since 2020, we've been providing reliable shipment tracking solutions for businesses and individuals.</p>
                    <p>A modern shipment tracking system that helps you monitor your packages in real-time. With our advanced technology and dedicated support team, we ensure your shipments reach their destination safely and on time.</p>
                    <div class="mt-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                            <span>100% Secure Tracking</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                            <span>24/7 Customer Support</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                            <span>Real-time Updates</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-4">
                            <h4 class="mb-3">Why Choose Us?</h4>
                            <ul class="list-unstyled">
                                <li class="mb-3"><i class="fas fa-chart-line  me-2" style="color:#FF883A"></i> <strong>99.9%</strong> Delivery Success Rate</li>
                                <li class="mb-3"><i class="fas fa-clock me-2" style="color:#FF883A"></i> <strong>24/7</strong> Real-time Tracking</li>
                                <li class="mb-3"><i class="fas fa-shield-alt  me-2" style="color:#FF883A"></i> <strong>100%</strong> Insured Shipments</li>
                                <li><i class="fas fa-headset  me-2" style="color:#FF883A"></i> <strong>Dedicated</strong> Support Team</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-4 mb-md-0">
                    <div class="stat-number">10K+</div>
                    <p>Shipments Delivered</p>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <div class="stat-number">98%</div>
                    <p>On-time Delivery</p>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <div class="stat-number">500+</div>
                    <p>Happy Clients</p>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">24/7</div>
                    <p>Customer Support</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <!-- <div class="col-md-4 mb-4 mb-md-0">
                     <img src="assets/images/whitelogo.png" alt="Logo" class="img-fluid mb-2" style="max-width: 150px;">
                    <p class="mt-3">Your trusted partner for fast and reliable shipment tracking solutions worldwide.</p>
                    <div class="mt-3">
                        <a href="https://www.facebook.com/RoyalGulfShipping" class="me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="https://x.com/royalgulfdubai" class="me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="https://www.youtube.com/@storagegulf" class="me-3"><i class="fab fa-youtube fa-lg"></i></a>
                        <a href="https://www.linkedin.com/company/royalgulfshipping/" class="me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="https://www.instagram.com/royal.gulfshipping"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div> -->
                <div class="col-md-2 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled mt-3">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#tracking">Track</a></li>
                    </ul>
                </div>
                <!-- <div class="col-md-3 mb-4 mb-md-0">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled mt-3">
                        <li><i class="fas fa-map-marker-alt me-2"></i> 21 6a st - Ras Al Khor Industrial Area 2 - Dubai - United Arab Emirates</li>
                        <li><i class="fas fa-phone me-2"></i> +971 50 9724214</li>
                        <li><i class="fas fa-envelope me-2"></i> info@royalgulfshipping.com</li>
                    </ul>
                </div> -->
                <div class="col-md-3">
                    <h5>Working Hours</h5>
                    <ul class="list-unstyled mt-3">
                        <li>Monday - Saturday: 8AM - 6PM</li>
                        <li>Sunday: Closed</li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-3" style="background-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Advanced Shipment Tracking System. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/tracking.js"></script>
    <script src="assets/js/email-tracking.js"></script>
    <script src="assets/js/print-tracking.js"></script>
</body>
</html>