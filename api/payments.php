<?php
session_start();
header('Content-Type: application/json');

require_once '../config/api_config.php';
require_once '../config/database.php';
require_once '../includes/api_functions.php';

$pdo = $GLOBALS['pdo'] ?? null;

function getCurrentUserScope(PDO $pdo): array
{
    $context = getCurrentUserContext();
    $query = "SELECT i.* FROM invoices i LEFT JOIN shipments s ON i.shipment_id = s.id LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id WHERE 1=1";
    $params = [];

    if ($context['role'] === 'agent') {
        $query .= " AND (i.created_by = ?";
        $params[] = $context['user_id'];

        if ($context['email'] !== '') {
            $query .= " OR LOWER(COALESCE(sp.destination_agent_email, '')) = ?";
            $params[] = $context['email'];
        }

        $query .= ")";
    }

    return [
        'context' => $context,
        'query' => $query,
        'params' => $params,
    ];
}

function getInvoicePaymentDetails(PDO $pdo, int $invoiceId): array
{
    $scope = getCurrentUserScope($pdo);
    $query = $scope['query'] . " AND i.id = ?";
    $params = array_merge($scope['params'], [$invoiceId]);

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        return [];
    }

    $paymentStmt = $pdo->prepare("SELECT SUM(amount) AS paid_amount FROM invoice_payments WHERE invoice_id = ?");
    $paymentStmt->execute([$invoiceId]);
    $paidAmount = (float)($paymentStmt->fetchColumn() ?: 0);

    $invoice['total_amount'] = (float)($invoice['total_amount'] ?? 0);
    $invoice['paid_amount'] = $paidAmount;
    $invoice['balance_amount'] = max(0, $invoice['total_amount'] - $paidAmount);
    $invoice['payment_progress'] = $invoice['total_amount'] > 0 ? round(($paidAmount / $invoice['total_amount']) * 100, 2) : 100;

    if ($invoice['balance_amount'] <= 0) {
        $invoice['payment_status'] = 'paid';
    } elseif ($paidAmount > 0) {
        $invoice['payment_status'] = 'partially_paid';
    } else {
        $invoice['payment_status'] = 'unpaid';
    }

    $invoice['status_label'] = $invoice['payment_status'] === 'paid' ? 'Paid' : ($invoice['payment_status'] === 'partially_paid' ? 'Partially Paid' : 'Unpaid');

    return $invoice;
}

function getInvoicePaymentHistory(PDO $pdo, int $invoiceId): array
{
    $invoice = getInvoicePaymentDetails($pdo, $invoiceId);
    if (!$invoice) {
        return [];
    }

    $stmt = $pdo->prepare("SELECT * FROM invoice_payments WHERE invoice_id = ? ORDER BY payment_date DESC, id DESC");
    $stmt->execute([$invoiceId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPaymentsList(PDO $pdo, array $filters = []): array
{
    $scope = getCurrentUserScope($pdo);
    $query = $scope['query'];
    $params = $scope['params'];

    if (!empty($filters['search'])) {
        $query .= " AND (i.invoice_number LIKE ? OR i.shipment_tracking_number LIKE ? OR i.consignee_name LIKE ?)";
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    if (!empty($filters['payment_status'])) {
        $query .= " AND i.payment_status = ?";
        $params[] = $filters['payment_status'];
    }

    $query .= " ORDER BY i.created_at DESC LIMIT 200";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($invoices as &$invoice) {
        $invoiceId = (int)($invoice['id'] ?? 0);
        $paymentStmt = $pdo->prepare("SELECT SUM(amount) AS paid_amount FROM invoice_payments WHERE invoice_id = ?");
        $paymentStmt->execute([$invoiceId]);
        $paidAmount = (float)($paymentStmt->fetchColumn() ?: 0);

        $invoice['total_amount'] = (float)($invoice['total_amount'] ?? 0);
        $invoice['paid_amount'] = $paidAmount;
        $invoice['balance_amount'] = max(0, $invoice['total_amount'] - $paidAmount);
        $invoice['payment_progress'] = $invoice['total_amount'] > 0 ? round(($paidAmount / $invoice['total_amount']) * 100, 2) : 100;
        $invoice['payment_status'] = $invoice['balance_amount'] <= 0 ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');
        $invoice['status_label'] = $invoice['payment_status'] === 'paid' ? 'Paid' : ($invoice['payment_status'] === 'partially_paid' ? 'Partially Paid' : 'Unpaid');
    }

    return $invoices;
}

function updateInvoicePaymentStatus(PDO $pdo, int $invoiceId): void
{
    $invoice = getInvoicePaymentDetails($pdo, $invoiceId);
    if (!$invoice) {
        return;
    }

    $newStatus = $invoice['balance_amount'] <= 0 ? 'paid' : ($invoice['paid_amount'] > 0 ? 'partially_paid' : 'unpaid');

    $stmt = $pdo->prepare("UPDATE invoices SET payment_status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $invoiceId]);
}

function recordInvoicePayment(PDO $pdo, array $data): array
{
    $invoiceId = (int)($data['invoice_id'] ?? 0);
    $amount = (float)($data['amount'] ?? 0);
    $paymentMethod = trim((string)($data['payment_method'] ?? ''));
    $paymentDate = trim((string)($data['payment_date'] ?? date('Y-m-d')));
    $referenceNumber = trim((string)($data['reference_number'] ?? ''));
    $notes = trim((string)($data['notes'] ?? ''));

    if ($invoiceId <= 0 || $amount <= 0) {
        throw new Exception('A valid invoice and payment amount are required.');
    }

    $invoice = getInvoicePaymentDetails($pdo, $invoiceId);
    if (!$invoice) {
        throw new Exception('Invoice not found or access denied.');
    }

    if ($amount > $invoice['balance_amount']) {
        throw new Exception('Payment amount cannot exceed the outstanding balance.');
    }

    if ($paymentMethod === '') {
        throw new Exception('Please select a payment method.');
    }

    if ($paymentDate === '' || !strtotime($paymentDate)) {
        throw new Exception('A valid payment date is required.');
    }

    $context = getCurrentUserContext();

    $stmt = $pdo->prepare(<<<SQL
        INSERT INTO invoice_payments (
            invoice_id, amount, payment_method, reference_number, payment_date, notes, recorded_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
SQL);
    $stmt->execute([
        $invoiceId,
        $amount,
        $paymentMethod,
        $referenceNumber !== '' ? $referenceNumber : null,
        $paymentDate,
        $notes !== '' ? $notes : null,
        $context['user_id'] ?? null,
    ]);

    updateInvoicePaymentStatus($pdo, $invoiceId);

    return [
        'invoice_id' => $invoiceId,
        'amount' => $amount,
        'payment_status' => getInvoicePaymentDetails($pdo, $invoiceId)['payment_status'] ?? 'unpaid',
        'balance_amount' => getInvoicePaymentDetails($pdo, $invoiceId)['balance_amount'] ?? 0,
    ];
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    if (!$pdo instanceof PDO) {
        throw new Exception('Database connection is not available.');
    }
    if ($method === 'GET') {
        if ($action === 'list') {
            $filters = [
                'search' => $_GET['search'] ?? null,
                'payment_status' => $_GET['payment_status'] ?? null,
            ];
            echo json_encode(['success' => true, 'data' => getPaymentsList($pdo, $filters)]);
            exit;
        }

        if ($action === 'detail' && !empty($_GET['id'])) {
            $invoice = getInvoicePaymentDetails($pdo, (int)$_GET['id']);
            if (!$invoice) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'invoice' => $invoice,
                    'payments' => getInvoicePaymentHistory($pdo, (int)$_GET['id']),
                ],
            ]);
            exit;
        }
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $payload['action'] ?? null;

        if ($action === 'record') {
            $result = recordInvoicePayment($pdo, $payload);
            echo json_encode(['success' => true, 'message' => 'Payment recorded successfully.', 'data' => $result]);
            exit;
        }
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
