<?php

function validateReportFilters($filters)
{
    $errors = [];

    if (!empty($filters['from_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from_date'])) {
        $errors[] = 'Invalid from_date format (use YYYY-MM-DD)';
    }

    if (!empty($filters['to_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to_date'])) {
        $errors[] = 'Invalid to_date format (use YYYY-MM-DD)';
    }

    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
        if (strtotime($filters['from_date']) > strtotime($filters['to_date'])) {
            $errors[] = 'from_date cannot be after to_date';
        }
    }

    $allowedStatuses = ['pending', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled'];
    if (!empty($filters['status']) && !in_array($filters['status'], $allowedStatuses)) {
        $errors[] = 'Invalid status value';
    }

    return $errors;
}

function generateShipmentReport($pdo, $filters = [])
{
    $validation_errors = validateReportFilters($filters);
    if (!empty($validation_errors)) {
        throw new Exception(implode(', ', $validation_errors));
    }

    $query = "SELECT * FROM shipments WHERE 1=1";
    $params = [];

    if (!empty($filters['status'])) {
        $query .= " AND status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['from_date'])) {
        $query .= " AND DATE(created_at) >= ?";
        $params[] = $filters['from_date'];
    }

    if (!empty($filters['to_date'])) {
        $query .= " AND DATE(created_at) <= ?";
        $params[] = $filters['to_date'];
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $results;
}

function getReportStats($pdo, $filters = [])
{
    $shipments = generateShipmentReport($pdo, $filters);

    $stats = [
        'total' => count($shipments),
        'delivered' => 0,
        'in_transit' => 0,
        'pending' => 0,
        'cancelled' => 0,
        'by_status' => []
    ];

    $status_counts = [];
    foreach ($shipments as $shipment) {
        $status = $shipment['status'];
        $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;

        if ($status === 'delivered') $stats['delivered']++;
        elseif (in_array($status, ['in_transit', 'out_for_delivery', 'picked_up'])) $stats['in_transit']++;
        elseif ($status === 'pending') $stats['pending']++;
        elseif ($status === 'cancelled') $stats['cancelled']++;
    }

    $stats['by_status'] = $status_counts;

    return $stats;
}

function formatReportData($shipments)
{
    return array_map(function ($shipment) {
        return [
            'id' => $shipment['id'],
            'tracking_number' => $shipment['tracking_number'],
            'customer_name' => $shipment['customer_name'],
            'customer_email' => $shipment['customer_email'],
            'receiver_name' => $shipment['receiver_name'],
            'receiver_phone' => $shipment['receiver_phone'] ?? 'N/A',
            'package_weight' => $shipment['package_weight'],
            'status' => $shipment['status'],
            'created_at' => $shipment['created_at'],
            'updated_at' => $shipment['updated_at'] ?? null
        ];
    }, $shipments);
}

function exportReportToCSV($shipments, $filename = null)
{
    if (!$filename) {
        $filename = 'shipments_' . date('Y-m-d_H-i-s') . '.csv';
    }

    $headers = ['ID', 'Tracking Number', 'Customer Name', 'Customer Email', 'Receiver Name', 'Receiver Phone', 'Weight (kg)', 'Status', 'Created Date'];

    $handle = fopen('php://temp', 'w');
    fputcsv($handle, $headers);

    foreach ($shipments as $shipment) {
        fputcsv($handle, [
            $shipment['id'],
            $shipment['tracking_number'],
            $shipment['customer_name'],
            $shipment['customer_email'],
            $shipment['receiver_name'],
            $shipment['receiver_phone'] ?? 'N/A',
            $shipment['package_weight'],
            $shipment['status'],
            $shipment['created_at']
        ]);
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return [
        'filename' => $filename,
        'content' => "\xEF\xBB\xBF" . $csv,
        'mime' => 'text/csv;charset=utf-8;'
    ];
}

function exportReportToJSON($shipments, $filters = [], $filename = null)
{
    if (!$filename) {
        $filename = 'shipments_' . date('Y-m-d_H-i-s') . '.json';
    }

    $data = [
        'exported_at' => date('Y-m-d H:i:s'),
        'total_records' => count($shipments),
        'filters' => $filters,
        'data' => $shipments
    ];

    return [
        'filename' => $filename,
        'content' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        'mime' => 'application/json'
    ];
}

?>
