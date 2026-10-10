<?php
require_once '../config/database.php';

$error = '';
global $pdo;
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'] ?? null;
if(!$id || !is_numeric($id)) {
    header('Location: dashboard.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
$stmt->execute([$id]);
$shipment = $stmt->fetch();

if(!$shipment) {
    header('Location: dashboard.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'] ?? '';
    $location = $_POST['location'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    if(empty($new_status) || empty($location)) {
        $error = 'Status and location are required!';
    } else {
        $stmt = $pdo->prepare("UPDATE shipments SET status = ?, current_location = ? WHERE id = ?");
        $stmt->execute([$new_status, $location, $id]);

        writeSimpleLog('Shipment status updated', [
            'shipment_id' => $id,
            'tracking_number' => $shipment['tracking_number'],
            'status' => $new_status,
            'location' => $location,
            'remarks' => $remarks,
            'updated_by' => $_SESSION['username'] ?? 'unknown'
        ]);

        $stmt_history = $pdo->prepare("INSERT INTO tracking_history (tracking_number, status, location, remarks) VALUES (?, ?, ?, ?)");
        $stmt_history->execute([$shipment['tracking_number'], $new_status, $location, $remarks]);

        writeSimpleLog('Tracking history created', [
            'tracking_number' => $shipment['tracking_number'],
            'status' => $new_status,
            'location' => $location,
            'remarks' => $remarks
        ]);

        if($new_status == 'delivered') {
            $stmt = $pdo->prepare("UPDATE shipments SET actual_delivery = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            writeSimpleLog('Shipment marked delivered', [
                'shipment_id' => $id,
                'tracking_number' => $shipment['tracking_number']
            ]);
        }

        // Send email notification
        $to = $shipment['customer_email'];
        $subject = "Shipment Status Update - " . $shipment['tracking_number'];
        $message = "Dear " . $shipment['customer_name'] . ",\n\n";
        $message .= "Your shipment status has been updated.\n\n";
        $message .= "Tracking Number: " . $shipment['tracking_number'] . "\n";
        $message .= "New Status: " . str_replace('_', ' ', ucfirst($new_status)) . "\n";
        $message .= "Location: $location\n";
        $message .= "Remarks: $remarks\n\n";
        $message .= "Thank you!";

        mail($to, $subject, $message);

        header('Location: dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-warning">
                        <h4 class="mb-0">Update Shipment Status</h4>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        <p><strong>Tracking:</strong> <?php echo htmlspecialchars($shipment['tracking_number']); ?></p>
                        <p><strong>Current Status:</strong> <?php echo str_replace('_', ' ', ucfirst(htmlspecialchars($shipment['status']))); ?></p>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label>New Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="pending" <?php echo $shipment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="picked_up" <?php echo $shipment['status'] == 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                                    <option value="in_transit" <?php echo $shipment['status'] == 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                    <option value="out_for_delivery" <?php echo $shipment['status'] == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                    <option value="delivered" <?php echo $shipment['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $shipment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label>Remarks</label>
                                <textarea name="remarks" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Status</button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>