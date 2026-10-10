<?php
require_once '../config/api_config.php';
require_once '../config/database.php';
require_once '../includes/api_functions.php';

global $pdo;

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return $stmt->rowCount() > 0;
}



function getShipmentPaymentSummary(PDO $pdo, int $shipmentId): array
{
    if (!tableExists($pdo, 'invoices') || !tableExists($pdo, 'invoice_payments')) {
        return [
            'invoice_count' => 0,
            'total_amount' => 0.0,
            'paid_amount' => 0.0,
            'remaining_amount' => 0.0,
            'payment_status' => 'n/a',
        ];
    }

    $invoiceStmt = $pdo->prepare("SELECT id, total_amount FROM invoices WHERE shipment_id = ? ORDER BY id DESC");
    $invoiceStmt->execute([$shipmentId]);
    $invoices = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

    $totalAmount = 0.0;
    $paidAmount = 0.0;

    foreach ($invoices as $invoice) {
        $invoiceId = (int)($invoice['id'] ?? 0);
        $totalAmount += (float)($invoice['total_amount'] ?? 0);

        $paymentStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_id = ?");
        $paymentStmt->execute([$invoiceId]);
        $paidAmount += (float)($paymentStmt->fetchColumn() ?: 0);
    }

    $remainingAmount = max(0, $totalAmount - $paidAmount);

    if ($totalAmount <= 0) {
        $paymentStatus = 'n/a';
    } elseif ($remainingAmount <= 0) {
        $paymentStatus = 'paid';
    } elseif ($paidAmount > 0) {
        $paymentStatus = 'partially_paid';
    } else {
        $paymentStatus = 'unpaid';
    }

    return [
        'invoice_count' => count($invoices),
        'total_amount' => round($totalAmount, 2),
        'paid_amount' => round($paidAmount, 2),
        'remaining_amount' => round($remainingAmount, 2),
        'payment_status' => $paymentStatus,
    ];
}

function getShipmentReleaseList(PDO $pdo): array
{
    $context = getCurrentUserContext();
    $query = "SELECT s.*, cr.cargo_release_status, cr.cargo_release_reason, cr.cargo_release_date, cr.released_at FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id LEFT JOIN cargo_release_records cr ON cr.shipment_id = s.id WHERE 1=1";
    $params = [];

    if ($context['role'] === 'agent' && $context['email'] !== '') {
        $query .= " AND LOWER(COALESCE(sp.destination_agent_email, '')) = ?";
        $params[] = $context['email'];
    }

    $search = trim((string)($_GET['search'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    if ($status === '') {
        $status = trim((string)($_GET['cargo_release_status'] ?? ''));
    }

    if ($search !== '') {
        $query = str_replace('FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id LEFT JOIN cargo_release_records cr ON cr.shipment_id = s.id', 'FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id LEFT JOIN shipment_consignees sc ON sc.shipment_id = s.id LEFT JOIN cargo_release_records cr ON cr.shipment_id = s.id', $query);
        $searchTerm = '%' . strtolower($search) . '%';
        $query .= " AND (LOWER(COALESCE(s.tracking_number, '')) LIKE ? OR LOWER(COALESCE(sc.consignee_name, '')) LIKE ? OR LOWER(COALESCE(sc.consignee_email, '')) LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $allowedStatuses = ['pending', 'ready', 'locked', 'released'];
    if ($status !== '' && in_array($status, $allowedStatuses, true)) {
        $query .= " AND COALESCE(cr.cargo_release_status, 'pending') = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY s.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($shipments as &$shipment) {
        $summary = getShipmentPaymentSummary($pdo, (int)($shipment['id'] ?? 0));
        $shipment['payment_summary'] = $summary;
        $shipment['cargo_release_status'] = isset($shipment['cargo_release_status']) && $shipment['cargo_release_status'] !== '' ? $shipment['cargo_release_status'] : 'pending';
        $statusKey = $shipment['cargo_release_status'];
        $shipment['cargo_release_status_label'] = $statusKey === 'ready' ? 'Ready' : ($statusKey === 'locked' ? 'Locked' : ($statusKey === 'released' ? 'Released' : 'Pending'));
    }

    return $shipments;
}

function updateCargoRelease(PDO $pdo, array $data): array
{
    $shipmentId = (int)($data['id'] ?? 0);
    $status = trim((string)($data['cargo_release_status'] ?? ''));
    $reason = trim((string)($data['cargo_release_reason'] ?? ''));
    $releaseDate = trim((string)($data['release_date'] ?? ''));

    if ($shipmentId <= 0) {
        throw new Exception('Shipment is required.');
    }

    $allowedStatuses = ['pending', 'ready', 'locked', 'released'];
    if (!in_array($status, $allowedStatuses, true)) {
        throw new Exception('Invalid release status.');
    }

    if ($status === 'locked' && $reason === '') {
        throw new Exception('Please provide a reason for locking cargo release.');
    }

    $releaseDate = $releaseDate !== '' ? $releaseDate : date('Y-m-d');

    $fields = [
        'shipment_id' => $shipmentId,
        'cargo_release_status' => $status,
        'cargo_release_reason' => $reason !== '' ? $reason : null,
        'cargo_release_date' => $releaseDate,
        'released_at' => $status === 'released' ? date('Y-m-d H:i:s') : null,
    ];

    $columns = array_keys($fields);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $values = array_values($fields);

    $stmt = $pdo->prepare("INSERT INTO cargo_release_records (" . implode(', ', $columns) . ") VALUES ({$placeholders}) ON DUPLICATE KEY UPDATE cargo_release_status = VALUES(cargo_release_status), cargo_release_reason = VALUES(cargo_release_reason), cargo_release_date = VALUES(cargo_release_date), released_at = VALUES(released_at), updated_at = NOW()");
    $stmt->execute($values);

    $shipmentStmt = $pdo->prepare("SELECT s.*, cr.cargo_release_status, cr.cargo_release_reason, cr.cargo_release_date, cr.released_at FROM shipments s LEFT JOIN cargo_release_records cr ON cr.shipment_id = s.id WHERE s.id = ?");
    $shipmentStmt->execute([$shipmentId]);
    $shipment = $shipmentStmt->fetch(PDO::FETCH_ASSOC);

    if ($shipment) {
        $shipment['payment_summary'] = getShipmentPaymentSummary($pdo, $shipmentId);
        $statusKey = $shipment['cargo_release_status'] ?? 'pending';
        $shipment['cargo_release_status_label'] = $statusKey === 'ready' ? 'Ready' : ($statusKey === 'locked' ? 'Locked' : ($statusKey === 'released' ? 'Released' : 'Pending'));
    }

    return $shipment ?: [];
}

try {
    if (!($pdo instanceof PDO)) {
        throw new Exception('Database connection is not available.');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to connect to the database: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    if ($method === 'GET') {
        if ($action === 'list') {
            echo json_encode(['success' => true, 'data' => getShipmentReleaseList($pdo)]);
            exit;
        }
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $payload['action'] ?? null;

        if ($action === 'update') {
            $shipment = updateCargoRelease($pdo, $payload);
            echo json_encode(['success' => true, 'message' => 'Cargo release updated successfully.', 'data' => $shipment]);
            exit;
        }
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
