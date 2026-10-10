<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';
require_once 'api_functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$endpoint = end($segments);
global $pdo;
try {
    switch($method) {
        case 'GET':
            if (is_numeric($endpoint)) {
                // Get single shipment
                $shipment = getShipmentById($pdo, $endpoint);
                if ($shipment) {
                    apiResponse(true, $shipment, 'Shipment retrieved');
                } else {
                    apiResponse(false, null, 'Shipment not found', 404);
                }
            } else {
                // Get all shipments with filters
                $filters = [
                    'status' => $_GET['status'] ?? null,
                    'search' => $_GET['search'] ?? null,
                    'from_date' => $_GET['from_date'] ?? null,
                    'to_date' => $_GET['to_date'] ?? null,
                    'limit' => $_GET['limit'] ?? 100
                ];
                
                // Remove empty filters
                $filters = array_filter($filters);
                
                $shipments = getShipments($pdo, $filters);
                apiResponse(true, $shipments, 'Shipments retrieved');
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Handle both JSON and form data
            $data = $input ?: $_POST;
            
            // Check if this is an update (has ID)
            if (!empty($data['id']) && is_numeric($data['id'])) {
                $id = $data['id'];
                unset($data['id']);
                
                $success = updateShipment($pdo, $id, $data);
                if ($success) {
                    apiResponse(true, null, 'Shipment updated successfully');
                } else {
                    apiResponse(false, null, 'Failed to update shipment or no changes made', 400);
                }
            } else {
                // Create new shipment
                if (empty($data['customer_name']) || empty($data['receiver_name']) || empty($data['customer_email'])) {
                    apiResponse(false, null, 'Customer name, email and receiver name are required', 400);
                }
                
                $tracking = createShipment($pdo, $data);
                apiResponse(true, ['tracking_number' => $tracking], 'Shipment created successfully');
            }
            break;

        case 'PUT':
            $id = $endpoint;
            if (!$id || !is_numeric($id)) {
                apiResponse(false, null, 'Invalid ID', 400);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                apiResponse(false, null, 'Invalid data', 400);
            }
            
            $success = updateShipment($pdo, $id, $data);
            if ($success) {
                apiResponse(true, null, 'Shipment updated successfully');
            } else {
                apiResponse(false, null, 'Failed to update shipment', 400);
            }
            break;

        case 'DELETE':
            $id = $endpoint;
            if (!$id || !is_numeric($id)) {
                apiResponse(false, null, 'Invalid ID', 400);
            }
            
            $success = deleteShipment($pdo, $id);
            if ($success) {
                apiResponse(true, null, 'Shipment deleted successfully');
            } else {
                apiResponse(false, null, 'Failed to delete shipment', 400);
            }
            break;

        default:
            apiResponse(false, null, 'Method not allowed', 405);
    }
} catch (PDOException $e) {
    apiResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    apiResponse(false, null, $e->getMessage(), 500);
}
?>