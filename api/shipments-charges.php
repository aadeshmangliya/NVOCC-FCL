<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';
require_once '../includes/admin_security.php';
require_once '../includes/api_functions.php';

$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo) {
    throw new RuntimeException('Database connection is not available.');
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $context = getCurrentUserContext();

        $shipmentQuery = "SELECT s.id, s.tracking_number, sc.consignee_name, sc.consignee_email, a.arrival_date, a.free_days_allowed, sr.eta
            FROM shipments s
            LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id
            LEFT JOIN shipment_consignees sc ON sc.shipment_id = s.id
            LEFT JOIN shipment_routes sr ON sr.shipment_id = s.id
            LEFT JOIN arrival_notices a ON s.tracking_number = a.tracking_number";
        $params = [];

        if ($context['role'] === 'agent' && $context['email'] !== '') {
            $shipmentQuery .= " WHERE LOWER(COALESCE(sp.destination_agent_email, '')) = ?";
            $params[] = $context['email'];
        }

        $shipmentQuery .= " ORDER BY s.id DESC LIMIT 100";
        $shipmentStmt = $pdo->prepare($shipmentQuery);
        $shipmentStmt->execute($params);
        $shipments = $shipmentStmt->fetchAll();

        $chargeQuery = "SELECT sc.* FROM shipment_charges sc INNER JOIN shipments s ON sc.shipment_id = s.id LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id";
        $chargeParams = [];
        if ($context['role'] === 'stafagentf' && $context['email'] !== '') {
            $chargeQuery .= " WHERE LOWER(COALESCE(sp.destination_agent_email, '')) = ?";
            $chargeParams[] = $context['email'];
        }
        $chargeQuery .= " ORDER BY sc.id DESC";

        $chargeStmt = $pdo->prepare($chargeQuery);
        $chargeStmt->execute($chargeParams);
        $charges = $chargeStmt->fetchAll();

        echo json_encode([
            'success' => true,
            'shipments' => $shipments,
            'charges' => $charges,
            'message' => 'Shipment charges loaded successfully.'
        ]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $data = $input ?: $_POST;

        if (($data['action'] ?? '') !== 'create-charge') {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            exit;
        }

        if (empty($data['tracking_number']) || empty($data['shipment_id'])) {
            echo json_encode(['success' => false, 'message' => 'Shipment selection is required.']);
            exit;
        }

        $context = getCurrentUserContext();
        if ($context['role'] === 'agent') {
            $shipment = getShipmentById($pdo, (int)$data['shipment_id']);
            if (!$shipment) {
                echo json_encode(['success' => false, 'message' => 'You are not authorized to add charges for this shipment.']);
                exit;
            }
        }

        $chargeType = $data['charge_type'] ?? 'detention';
        $category = $data['charge_category'] ?? 'Detention / Demurrage';
        $description = trim($data['description'] ?? '');
        $amount = (float)($data['amount'] ?? 0);
        $status = ($data['status'] ?? 'unpaid') === 'paid' ? 'paid' : 'unpaid';
        $expiryDate = $data['expiry_date'] ?? null;
        $delayDays = (int)($data['delay_days'] ?? 0);
        $remarks = trim($data['remarks'] ?? '');

        if ($amount <= 0 || $description === '') {
            echo json_encode(['success' => false, 'message' => 'Description and amount are required.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO shipment_charges (shipment_id, tracking_number, charge_type, charge_category, description, amount, status, expiry_date, delay_days, remarks, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            (int)$data['shipment_id'],
            $data['tracking_number'],
            $chargeType,
            $category,
            $description,
            $amount,
            $status,
            $expiryDate,
            $delayDays,
            $remarks
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Charge created successfully.'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
