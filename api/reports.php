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
require_once '../config/api_config.php';
require_once '../includes/report-functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$endpoint = end($segments);

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'generate';

            if ($action === 'stats') {
                $filters = [
                    'status' => $_GET['status'] ?? null,
                    'from_date' => $_GET['from_date'] ?? null,
                    'to_date' => $_GET['to_date'] ?? null
                ];
                $filters = array_filter($filters);

                $stats = getReportStats($pdo, $filters);
                apiResponse(true, $stats, 'Report statistics retrieved');
            } elseif ($action === 'export') {
                $format = $_GET['format'] ?? 'csv';
                $filters = [
                    'status' => $_GET['status'] ?? null,
                    'from_date' => $_GET['from_date'] ?? null,
                    'to_date' => $_GET['to_date'] ?? null
                ];
                $filters = array_filter($filters);

                $shipments = generateShipmentReport($pdo, $filters);

                if (empty($shipments)) {
                    apiResponse(false, null, 'No records found for export', 400);
                }

                if ($format === 'json') {
                    $export = exportReportToJSON($shipments, $filters);
                } else {
                    $export = exportReportToCSV($shipments);
                }

                $data = [
                    'filename' => $export['filename'],
                    'size' => strlen($export['content']),
                    'format' => $format
                ];

                apiResponse(true, $data, 'Export ready');
            } else {
                $filters = [
                    'status' => $_GET['status'] ?? null,
                    'from_date' => $_GET['from_date'] ?? null,
                    'to_date' => $_GET['to_date'] ?? null,
                    'limit' => $_GET['limit'] ?? 10000
                ];
                $filters = array_filter($filters);

                $shipments = generateShipmentReport($pdo, $filters);
                $formatted = formatReportData($shipments);

                apiResponse(true, $formatted, 'Report generated successfully');
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? 'generate';

            if ($action === 'generate') {
                $filters = [
                    'status' => $input['status'] ?? null,
                    'from_date' => $input['from_date'] ?? null,
                    'to_date' => $input['to_date'] ?? null
                ];
                $filters = array_filter($filters);

                $shipments = generateShipmentReport($pdo, $filters);
                $formatted = formatReportData($shipments);

                apiResponse(true, $formatted, 'Report generated successfully');
            } else {
                apiResponse(false, null, 'Invalid action', 400);
            }
            break;

        default:
            apiResponse(false, null, 'Method not allowed', 405);
    }
} catch (Exception $e) {
    apiResponse(false, null, $e->getMessage(), 400);
} catch (PDOException $e) {
    apiResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}

?>
