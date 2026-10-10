<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// NEW: Get route from query parameter first
$route = $_GET['route'] ?? null;

if ($route) {
    // Parse route like "dashboard/stats" or "dashboard/activity"
    $segments = explode('/', $route);
    $resource = $segments[0] ?? null;
    $action = $segments[1] ?? null;
    
    // Set for individual API files
    if ($action) {
        $_GET['action'] = $action;
    }
} else {
    // Fallback to PATH_INFO method (for local)
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    
    // Remove 'api' segment if present
    if (!empty($segments) && $segments[0] === 'api') {
        array_shift($segments);
    }
    
    $resource = $segments[0] ?? null;
}

// Route to the appropriate API file
switch ($resource) {
    case 'dashboard':
        require 'dashboard.php';
        break;
    case 'shipments':
        require 'shipments.php';
        break;
    case 'shipments-charges':
        require 'shipments-charges.php';
        break;
    case 'users':
        require 'users.php';
        break;
    case 'reports':
        require 'reports.php';
        break;
    case 'track':
        require 'track.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
        break;
}
?>