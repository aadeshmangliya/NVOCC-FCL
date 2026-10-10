<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/admin_security.php';
require_once '../config/env.php';
require_once '../includes/api_functions.php';
require_once '../includes/email_functions.php';

global $pdo;
// Get all invoices with filters
function getInvoices(PDO $pdo, array $filters = []): array
{
    $context = getCurrentUserContext();
    $query = "SELECT i.*, u.email AS creator_email, u.username AS creator_username FROM invoices i LEFT JOIN users u ON i.created_by = u.id LEFT JOIN shipments s ON i.shipment_id = s.id LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id WHERE 1=1";
    $params = [];

    if ($context['role'] === 'agent') {
        $query .= " AND i.created_by = ?";
        $params[] = $context['user_id'];
    }

    if (!empty($filters['status'])) {
        $query .= " AND status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['payment_status'])) {
        $query .= " AND payment_status = ?";
        $params[] = $filters['payment_status'];
    }

    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $query .= " AND (invoice_number LIKE ? OR shipment_tracking_number LIKE ? OR consignee_name LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    $query .= " ORDER BY created_at DESC LIMIT 200";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInvoiceForCurrentUser(PDO $pdo, int $invoiceId): array
{
    $context = getCurrentUserContext();

    if (!in_array($context['role'], ['admin', 'agent'], true)) {
        return [];
    }

    $stmt = $pdo->prepare("SELECT i.*, u.email AS creator_email, u.username AS creator_username, sp.destination_agent_email AS shipment_destination_agent_email FROM invoices i LEFT JOIN users u ON i.created_by = u.id LEFT JOIN shipments s ON i.shipment_id = s.id LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id WHERE i.id = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        return [];
    }

    if ($context['role'] === 'admin') {
        return $invoice;
    }

    if (!empty($context['user_id']) && (int)$invoice['created_by'] === (int)$context['user_id']) {
        return $invoice;
    }

    return [];
}

function syncInvoiceFromShipment(PDO $pdo, array $invoice): bool
{
    if (empty($invoice['id']) || empty($invoice['shipment_id'])) {
        return false;
    }

    $shipmentStmt = $pdo->prepare("SELECT s.tracking_number, COALESCE(sc.consignee_name, '') AS consignee_name, COALESCE(sc.consignee_email, '') AS consignee_email, COALESCE(sc.consignee_tax_id, '') AS consignee_tax_id, COALESCE(sc.consignee_address, '') AS consignee_address, COALESCE(sc.consignee_phone, '') AS consignee_phone, sv.vessel_name, sv.voyage_no FROM shipments s LEFT JOIN shipment_consignees sc ON sc.shipment_id = s.id LEFT JOIN (SELECT sv.shipment_id, sv.vessel_name, sv.voyage_no FROM shipment_vessels sv INNER JOIN (SELECT shipment_id, MAX(id) AS max_id FROM shipment_vessels GROUP BY shipment_id) latest ON sv.shipment_id = latest.shipment_id AND sv.id = latest.max_id) sv ON sv.shipment_id = s.id WHERE s.id = ? ORDER BY sc.id DESC LIMIT 1");
    $shipmentStmt->execute([(int)$invoice['shipment_id']]);
    $shipment = $shipmentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$shipment) {
        return false;
    }

    $vesselVoyage = trim(((string)($shipment['vessel_name'] ?? '')) . (!empty($shipment['voyage_no']) ? ' / ' . $shipment['voyage_no'] : ''));

    $updates = [
        'shipment_tracking_number' => $shipment['tracking_number'] ?? null,
        'consignee_name' => $shipment['consignee_name'] ?? null,
        'consignee_email' => $shipment['consignee_email'] ?? null,
        'consignee_ntn' => $shipment['consignee_tax_id'] ?? null,
        'consignee_address' => $shipment['consignee_address'] ?? null,
        'consignee_contact' => $shipment['consignee_phone'] ?? null,
        'vessel_voyage' => $vesselVoyage,
    ];

    $setParts = [];
    $values = [];

    foreach ($updates as $field => $value) {
        if ((string)($invoice[$field] ?? '') !== (string)$value) {
            $setParts[] = $field . ' = ?';
            $values[] = $value;
        }
    }

    if (empty($setParts)) {
        return false;
    }

    $values[] = (int)$invoice['id'];

    $stmt = $pdo->prepare('UPDATE invoices SET ' . implode(', ', $setParts) . ', updated_at = NOW() WHERE id = ?');
    return $stmt->execute($values);
}

// Get single invoice with line items
function getInvoiceDetail(PDO $pdo, int $invoiceId): array
{
    try {
        $invoice = getInvoiceForCurrentUser($pdo, $invoiceId);

        if (!$invoice) {
            return [];
        }

        syncInvoiceFromShipment($pdo, $invoice);
        $invoice = getInvoiceForCurrentUser($pdo, $invoiceId);

        if (!$invoice) {
            return [];
        }

        $stmt = $pdo->prepare("SELECT * FROM invoice_line_items WHERE invoice_id = ? ORDER BY id ASC");
        $stmt->execute([$invoiceId]);
        $invoice['line_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $invoice;
    } catch (Throwable $e) {
        error_log('Invoice detail error: ' . $e->getMessage());
        return [];
    }
}

// Get available shipments for invoice creation
function getAvailableShipments(PDO $pdo, ?int $currentInvoiceId = null): array
{
    $context = getCurrentUserContext();
    $query = "SELECT DISTINCT 
        s.id,
        s.tracking_number,
        COALESCE(sc.consignee_name, '') AS consignee_name,
        COALESCE(sc.consignee_email, '') AS consignee_email,
        COALESCE(sc.consignee_tax_id, '') AS consignee_ntn,
        COALESCE(sc.consignee_address, '') AS consignee_address,
        COALESCE(sc.consignee_phone, '') AS consignee_contact,
        a.arrival_date,
        a.free_days_allowed,
        DATE_ADD(a.arrival_date, INTERVAL a.free_days_allowed DAY) AS free_days_expiry,
        sp.destination_agent,
        sp.destination_agent_address,
        sp.destination_agent_phone,
        sp.destination_agent_email,
        sv.vessel_name,
        sv.voyage_no,
        containers.container_no AS container_number
    FROM shipments s
    LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id
    LEFT JOIN shipment_consignees sc ON sc.shipment_id = s.id
    LEFT JOIN (
        SELECT an1.shipment_id, an1.arrival_date, an1.free_days_allowed
        FROM arrival_notices an1
        INNER JOIN (
            SELECT shipment_id, MAX(id) AS max_id
            FROM arrival_notices
            GROUP BY shipment_id
        ) latest ON an1.shipment_id = latest.shipment_id AND an1.id = latest.max_id
    ) a ON s.id = a.shipment_id
    LEFT JOIN (
        SELECT shipment_id, GROUP_CONCAT(container_no SEPARATOR ', ') AS container_no
        FROM shipment_containers
        GROUP BY shipment_id
    ) containers ON s.id = containers.shipment_id
    LEFT JOIN (
        SELECT sv.shipment_id, sv.vessel_name, sv.voyage_no
        FROM shipment_vessels sv
        INNER JOIN (
            SELECT shipment_id, MAX(id) AS max_id
            FROM shipment_vessels
            GROUP BY shipment_id
        ) latest_v ON sv.shipment_id = latest_v.shipment_id AND sv.id = latest_v.max_id
    ) sv ON s.id = sv.shipment_id
    LEFT JOIN invoices i ON s.id = i.shipment_id
    WHERE 1=1";
    $params = [];

    if ($currentInvoiceId) {
        $query .= " AND (i.id IS NULL OR i.id = ?)";
        $params[] = $currentInvoiceId;
    }

    if ($context['role'] === 'agent' && $context['email'] !== '') {
        $query .= " AND LOWER(COALESCE(sp.destination_agent_email, '')) = ?";
        $params[] = $context['email'];
    }

    $query .= " ORDER BY s.created_at DESC LIMIT 200";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($context['role'] === 'agent' && $context['email'] !== '' && empty($shipments)) {
        $fallbackQuery = str_replace(" AND LOWER(COALESCE(sp.destination_agent_email, '')) = ?", '', $query);
        $fallbackStmt = $pdo->prepare($fallbackQuery);
        $fallbackStmt->execute($params);
        $shipments = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $shipments;
}

// Get shipment charges
function getShipmentCharges(PDO $pdo, int $shipmentId): array
{
    $context = getCurrentUserContext();
    $sql = "SELECT sc.id, sc.description, sc.charge_category AS category, sc.amount, sc.charge_type
        FROM shipment_charges sc
        INNER JOIN shipments s ON sc.shipment_id = s.id
        LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id
        WHERE sc.shipment_id = ?";
    $params = [$shipmentId];

    if ($context['role'] === 'agent' && $context['email'] !== '') {
        $sql .= " AND LOWER(COALESCE(sp.destination_agent_email, '')) = ?";
        $params[] = $context['email'];
    }

    $sql .= " ORDER BY sc.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Create invoice
function createInvoice(PDO $pdo, array $data): array
{
    try {
        $context = getCurrentUserContext();

        if ($context['role'] === 'agent' && !empty($data['shipment_id'])) {
            if (!getShipmentById($pdo, (int)$data['shipment_id'])) {
                throw new Exception('You are not authorized to create an invoice for this shipment.');
            }
        }

        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                invoice_number, shipment_id, shipment_tracking_number, 
                consignee_name, consignee_email, consignee_ntn, consignee_address, consignee_contact,
                arrival_date, free_days_allowed, free_days_expiry,
                container_number, vessel_voyage,
                subtotal, global_discount, tax_rate, tax_amount, total_amount,
                invoice_notes, status, payment_status, created_by
            ) VALUES (
                :invoice_number, :shipment_id, :shipment_tracking_number,
                :consignee_name, :consignee_email, :consignee_ntn, :consignee_address, :consignee_contact,
                :arrival_date, :free_days_allowed, :free_days_expiry,
                :container_number, :vessel_voyage,
                :subtotal, :global_discount, :tax_rate, :tax_amount, :total_amount,
                :invoice_notes, :status, :payment_status, :created_by
            )
        ");

        $stmt->execute([
            ':invoice_number' => $invoiceNumber,
            ':shipment_id' => $data['shipment_id'] ?? null,
            ':shipment_tracking_number' => $data['shipment_tracking_number'] ?? null,
            ':consignee_name' => $data['consignee_name'] ?? null,
            ':consignee_email' => $data['consignee_email'] ?? null,
            ':consignee_ntn' => $data['consignee_ntn'] ?? null,
            ':consignee_address' => $data['consignee_address'] ?? null,
            ':consignee_contact' => $data['consignee_contact'] ?? null,
            ':arrival_date' => $data['arrival_date'] ?? null,
            ':free_days_allowed' => $data['free_days_allowed'] ?? 0,
            ':free_days_expiry' => $data['free_days_expiry'] ?? null,
            ':container_number' => $data['container_number'] ?? null,
            ':vessel_voyage' => $data['vessel_voyage'] ?? null,
            ':subtotal' => $data['subtotal'] ?? 0,
            ':global_discount' => $data['global_discount'] ?? 0,
            ':tax_rate' => $data['tax_rate'] ?? 0,
            ':tax_amount' => $data['tax_amount'] ?? 0,
            ':total_amount' => $data['total_amount'] ?? 0,
            ':invoice_notes' => $data['invoice_notes'] ?? null,
            ':status' => 'draft',
            ':payment_status' => 'unpaid',
            ':created_by' => $context['user_id'] ?? null
        ]);

        $invoiceId = (int)$pdo->lastInsertId();

        // Add line items
        if (!empty($data['line_items']) && is_array($data['line_items'])) {
            $lineStmt = $pdo->prepare("
                INSERT INTO invoice_line_items (
                    invoice_id, description, category, qty_or_days, rate, amount, item_type, is_adhoc
                ) VALUES (
                    :invoice_id, :description, :category, :qty_or_days, :rate, :amount, :item_type, :is_adhoc
                )
            ");

            foreach ($data['line_items'] as $item) {
                $lineStmt->execute([
                    ':invoice_id' => $invoiceId,
                    ':description' => $item['description'] ?? '',
                    ':category' => $item['category'] ?? '',
                    ':qty_or_days' => $item['qty_or_days'] ?? 1,
                    ':rate' => $item['rate'] ?? 0,
                    ':amount' => $item['amount'] ?? 0,
                    ':item_type' => $item['item_type'] ?? 'local_charge',
                    ':is_adhoc' => $item['is_adhoc'] ?? false
                ]);
            }
        }

        return [
            'id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'created' => true
        ];
    } catch (Exception $e) {
        throw new Exception('Error creating invoice: ' . $e->getMessage());
    }
}

// Update invoice
function updateInvoice(PDO $pdo, int $invoiceId, array $data): bool
{
    if (!getInvoiceForCurrentUser($pdo, $invoiceId)) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE invoices SET
                shipment_id = ?,
                shipment_tracking_number = ?,
                consignee_name = ?,
                consignee_email = ?,
                consignee_ntn = ?,
                consignee_address = ?,
                consignee_contact = ?,
                arrival_date = ?,
                free_days_allowed = ?,
                free_days_expiry = ?,
                container_number = ?,
                vessel_voyage = ?,
                global_discount = ?,
                tax_rate = ?,
                tax_amount = ?,
                subtotal = ?,
                total_amount = ?,
                invoice_notes = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $res = $stmt->execute([
            $data['shipment_id'] ?? null,
            $data['shipment_tracking_number'] ?? null,
            $data['consignee_name'] ?? null,
            $data['consignee_email'] ?? null,
            $data['consignee_ntn'] ?? null,
            $data['consignee_address'] ?? null,
            $data['consignee_contact'] ?? null,
            $data['arrival_date'] ?? null,
            $data['free_days_allowed'] ?? 0,
            $data['free_days_expiry'] ?? null,
            $data['container_number'] ?? null,
            $data['vessel_voyage'] ?? null,
            $data['global_discount'] ?? 0,
            $data['tax_rate'] ?? 0,
            $data['tax_amount'] ?? 0,
            $data['subtotal'] ?? 0,
            $data['total_amount'] ?? 0,
            $data['invoice_notes'] ?? '',
            $invoiceId
        ]);

        // If line items provided, replace existing ones
        if (!empty($data['line_items']) && is_array($data['line_items'])) {
            $del = $pdo->prepare("DELETE FROM invoice_line_items WHERE invoice_id = ?");
            $del->execute([$invoiceId]);

            $lineStmt = $pdo->prepare("
                INSERT INTO invoice_line_items (
                    invoice_id, description, category, qty_or_days, rate, amount, item_type, is_adhoc
                ) VALUES (
                    :invoice_id, :description, :category, :qty_or_days, :rate, :amount, :item_type, :is_adhoc
                )
            ");

            foreach ($data['line_items'] as $item) {
                $lineStmt->execute([
                    ':invoice_id' => $invoiceId,
                    ':description' => $item['description'] ?? '',
                    ':category' => $item['category'] ?? '',
                    ':qty_or_days' => $item['qty_or_days'] ?? 1,
                    ':rate' => $item['rate'] ?? 0,
                    ':amount' => $item['amount'] ?? 0,
                    ':item_type' => $item['item_type'] ?? 'local_charge',
                    ':is_adhoc' => $item['is_adhoc'] ? 1 : 0
                ]);
            }
        }

        $pdo->commit();
        return (bool)$res;
    } catch (Throwable $e) {
        $pdo->rollBack();
        return false;
    }
}

// Delete line item
function deleteLineItem(PDO $pdo, int $lineItemId): bool
{
    $stmt = $pdo->prepare("SELECT invoice_id FROM invoice_line_items WHERE id = ?");
    $stmt->execute([$lineItemId]);
    $invoiceId = $stmt->fetchColumn();

    if (!$invoiceId || !getInvoiceForCurrentUser($pdo, (int)$invoiceId)) {
        return false;
    }

    $stmt = $pdo->prepare("DELETE FROM invoice_line_items WHERE id = ?");
    return $stmt->execute([$lineItemId]);
}

// API Router

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    if ($method === 'GET') {
        if ($action === 'list') {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'payment_status' => $_GET['payment_status'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            echo json_encode(['success' => true, 'data' => getInvoices($pdo, $filters)]);
            exit;
        }

        if ($action === 'shipments') {
            $currentInvoiceId = !empty($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : null;
            try {
                $data = getAvailableShipments($pdo, $currentInvoiceId);
                echo json_encode(['success' => true, 'data' => $data]);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to load shipments: ' . $e->getMessage()]);
            }
            exit;
        }

        if ($action === 'detail' && !empty($_GET['id'])) {
            $invoice = getInvoiceDetail($pdo, (int)$_GET['id']);
            if (!empty($invoice)) {
                echo json_encode(['success' => true, 'data' => $invoice]);
            } else {
                // Log detail lookup failure for debugging
                $logPath = __DIR__ . '/../logs/invoice_preview.log';
                $logMsg = date('c') . " - DETAIL MISS - id=" . ((int)($_GET['id'] ?? 0)) . " user=" . json_encode(getCurrentUserContext()) . "\n";
                @file_put_contents($logPath, $logMsg, FILE_APPEND | LOCK_EX);
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Invoice not found']);
            }
            exit;
        }

        if ($action === 'preview' && !empty($_GET['id'])) {
            $invoice = getInvoiceDetail($pdo, (int)$_GET['id']);
            if (!$invoice) {
                // Log preview lookup failure for debugging
                $logPath = __DIR__ . '/../logs/invoice_preview.log';
                $logMsg = date('c') . " - PREVIEW MISS - id=" . ((int)($_GET['id'] ?? 0)) . " user=" . json_encode(getCurrentUserContext()) . "\n";
                @file_put_contents($logPath, $logMsg, FILE_APPEND | LOCK_EX);
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Invoice not found']);
                exit;
            }

            $html = buildInvoiceHtml($invoice, $invoice['line_items'] ?? [], getInvoiceBaseUrl(), $pdo);
            echo json_encode(['success' => true, 'data' => ['html' => $html]]);
            exit;
        }

        if ($action === 'charges' && !empty($_GET['shipment_id'])) {
            $charges = getShipmentCharges($pdo, (int)$_GET['shipment_id']);
            echo json_encode(['success' => true, 'data' => $charges]);
            exit;
        }

        if ($action === 'print' && !empty($_GET['id'])) {
            $invoice = getInvoiceDetail($pdo, (int)$_GET['id']);
            if (!$invoice) {
                http_response_code(404);
                echo 'Invoice not found';
                exit;
            }

            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo buildInvoiceHtml($invoice, $invoice['line_items'] ?? [], getInvoiceBaseUrl(), $pdo);
            exit;
        }
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $payload['action'] ?? null;
        if ($action === 'create') {
            $context = getCurrentUserContext();
            if ($context['role'] === 'agent' && !empty($payload['shipment_id'])) {
                if (!getShipmentById($pdo, (int)$payload['shipment_id'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'You are not authorized to create an invoice for this shipment.']);
                    exit;
                }
            }
            $result = createInvoice($pdo, $payload);
            echo json_encode(['success' => true, 'data' => $result]);
            exit;
        }

        if ($action === 'update' && !empty($payload['id'])) {
            if (!updateInvoice($pdo, (int)$payload['id'], $payload)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invoice not found or access denied.']);
                exit;
            }
            $invoice = getInvoiceDetail($pdo, (int)$payload['id']);
            echo json_encode(['success' => true, 'data' => $invoice]);
            exit;
        }

        if ($action === 'delete-line-item' && !empty($payload['line_item_id'])) {
            if (!deleteLineItem($pdo, (int)$payload['line_item_id'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Line item not found or access denied.']);
                exit;
            }
            echo json_encode(['success' => true, 'message' => 'Line item deleted']);
            exit;
        }

        if ($action === 'send-email' && !empty($payload['id'])) {
            $invoice = getInvoiceDetail($pdo, (int)$payload['id']);
            if (!$invoice) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Invoice not found']);
                exit;
            }

            $sent = sendInvoiceEmail($pdo, $invoice, $invoice['line_items'] ?? []);
            if ($sent) {
                echo json_encode(['success' => true, 'message' => 'Invoice email sent successfully.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to send invoice email.']);
            }
            exit;
        }
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
