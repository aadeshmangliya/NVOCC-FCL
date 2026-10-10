<?php
require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../includes/api_functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
global $pdo;
try {
    if ($method !== 'GET') {
        throw new Exception('Method not allowed', ERR_BAD_REQUEST);
    }

    function getTrackingNumber()
    {
        $number = $_GET['number'] ?? $_GET['tracking_number'] ?? '';
        if (empty($number)) {
            throw new Exception('Tracking number required', ERR_BAD_REQUEST);
        }

        return $number;
    }

    if (strpos($path, '/search') !== false) {
        $number = getTrackingNumber();

        $shipment = getShipmentByTrackingNumber($pdo, $number);
        if (!$shipment) {
            apiResponse(false, null, 'Shipment not found', ERR_NOT_FOUND);
        }

        apiResponse(true, $shipment, 'Shipment found');

    } elseif (strpos($path, '/history') !== false) {
        $number = getTrackingNumber();

        $history = getTrackingHistory($pdo, $number);
        apiResponse(true, $history, 'Tracking history retrieved');

    } else {
        $number = getTrackingNumber();

        $shipment = getShipmentByTrackingNumber($pdo, $number);
        if (!$shipment) {
            apiResponse(false, null, 'Shipment not found', ERR_NOT_FOUND);
        }

        apiResponse(true, $shipment, 'Shipment found');
    }

} catch (Exception $e) {
    apiResponse(false, null, $e->getMessage(), $e->getCode() ?: ERR_SERVER_ERROR);
}
?>