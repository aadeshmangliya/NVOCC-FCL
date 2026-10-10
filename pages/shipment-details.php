<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/api_functions.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit();
}
$shipmentId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$shipment = $shipmentId ? getShipmentById($pdo, $shipmentId) : null;
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Shipment Details</h2>
            <p class="text-muted mb-0">Detailed shipment information and progress overview.</p>
        </div>
        <button class="btn btn-outline-secondary" type="button" onclick="App.loadPage('shipments')">
            <i class="fas fa-arrow-left"></i> Back to Shipments
        </button>
    </div>
    <?php if (!$shipment): ?><div class="alert alert-danger">Shipment not found.</div><?php else: ?>
        <?php
        $status = str_replace('_', ' ', (string)($shipment['status'] ?? 'pending'));
        $route = (!empty($shipment['pol']) && !empty($shipment['pod'])) ? $shipment['pol'] . ' → ' . $shipment['pod'] : 'N/A';
        $weight = $shipment['gross_weight'] ?? $shipment['net_weight'] ?? 'N/A';
        $notes = $shipment['special_instructions'] ?? $shipment['comments'] ?? 'No additional notes';
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($shipment['tracking_number'] ?? 'N/A'); ?></h4>
                        <div class="text-muted">
                            <?php echo htmlspecialchars($shipment['shipper_name'] ?? 'N/A'); ?> → <?php echo htmlspecialchars($shipment['consignee_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <span class="badge bg-primary text-capitalize"><?php echo htmlspecialchars($status); ?></span>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white"><strong>Basic Details</strong></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Tracking No</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($shipment['tracking_number'] ?? 'N/A'); ?></dd>
                            <dt class="col-sm-5">Status</dt>
                            <dd class="col-sm-7 text-capitalize"><?php echo htmlspecialchars($status); ?></dd>
                            <dt class="col-sm-5">Current Location</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($shipment['current_location'] ?? 'N/A'); ?></dd>
                            <dt class="col-sm-5">Route</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($route); ?></dd>
                            <dt class="col-sm-5">Weight</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars((string)$weight); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white"><strong>Party Information</strong></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Shipper</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($shipment['shipper_name'] ?? 'N/A'); ?></dd>
                            <dt class="col-sm-5">Consignee</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($shipment['consignee_name'] ?? 'N/A'); ?></dd>
                            <dt class="col-sm-5">Carrier</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($shipment['carrier'] ?? 'N/A'); ?></dd>
                            <dt class="col-sm-5">Booking No</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($shipment['booking_no'] ?? 'N/A'); ?></dd>
                            <dt class="col-sm-5">Export Ref</dt>
                            <dd class="col-sm-7"><?php echo htmlspecialchars($shipment['export_reference'] ?? 'N/A'); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white"><strong>Notes</strong></div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($notes)); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>