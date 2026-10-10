<?php
require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../includes/api_functions.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
global $pdo;
try {
    if ($method !== 'GET') {
        throw new Exception('Method not allowed', ERR_BAD_REQUEST);
    }

    // CHECK FOR QUERY PARAMETER ACTION FIRST
    $action = $_GET['action'] ?? null;
    
    if ($action) {
        // If action came from query parameter
        if ($action === 'stats') {
            $stats = getDashboardStats($pdo);
            apiResponse(true, $stats, 'Dashboard stats retrieved');
        } elseif ($action === 'activity') {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = max(1, min((int)($_GET['per_page'] ?? 10), 100));
            $activity = getRecentActivity($pdo, $perPage, $page);
            apiResponse(true, $activity, 'Recent activity retrieved');
        } else {
            throw new Exception('Endpoint not found', ERR_NOT_FOUND);
        }
    } else {
        // Original logic - works for PATH_INFO
        $segments = explode('/', trim($path, '/'));
        $last = strtolower(end($segments));
        
        if ($last === 'stats') {
            $stats = getDashboardStats($pdo);
            apiResponse(true, $stats, 'Dashboard stats retrieved');
        } elseif ($last === 'activity') {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = max(1, min((int)($_GET['per_page'] ?? 10), 100));
            $activity = getRecentActivity($pdo, $perPage, $page);
            apiResponse(true, $activity, 'Recent activity retrieved');
        } else {
            throw new Exception('Endpoint not found', ERR_NOT_FOUND);
        }
    }
} catch (Exception $e) {
    $errorCode = $e->getCode();
    if (is_string($errorCode) && !is_numeric($errorCode)) {
        $errorCode = ERR_SERVER_ERROR;
    } else {
        $errorCode = (int)$errorCode ?: ERR_SERVER_ERROR;
    }
    apiResponse(false, null, $e->getMessage(), $errorCode);
}
?>