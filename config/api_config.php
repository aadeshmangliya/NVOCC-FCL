<?php
// Load environment configuration
require_once __DIR__ . '/env.php';

$httpScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath = rtrim(dirname($scriptPath), '/');
if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
    $basePath = '';
}

// API Configuration
define('API_VERSION', env('API_VERSION', '1.0'));
define('API_RESPONSE_TYPE', env('API_RESPONSE_TYPE', 'json'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_URL', env('APP_URL', $httpScheme . '://' . $httpHost . $basePath));
define('API_BASE_URL', env('API_BASE_URL', $httpScheme . '://' . $httpHost . $basePath . '/api'));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');

// Error codes
define('ERR_SUCCESS', 200);
define('ERR_BAD_REQUEST', 400);
define('ERR_UNAUTHORIZED', 401);
define('ERR_FORBIDDEN', 403);
define('ERR_NOT_FOUND', 404);
define('ERR_SERVER_ERROR', 500);

// Start session with correct settings for Bluehost and local development
if (session_status() === PHP_SESSION_NONE) {
    $isSecureRequest = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (stripos(APP_URL, 'https://') === 0);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',  // Auto-detect
        'secure' => $isSecureRequest,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Helper function to send JSON response
function apiResponse($success = true, $data = null, $message = '', $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'code' => $code,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function isLocalDevelopmentRequest(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

    $localHostPattern = '/^(localhost|127\.0\.0\.1|::1)$/i';
    $privateNetworkPattern = '/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/';

    return APP_ENV !== 'production'
        || preg_match($localHostPattern, $host)
        || preg_match($localHostPattern, $serverName)
        || preg_match($localHostPattern, $remoteAddr)
        || preg_match($privateNetworkPattern, $remoteAddr);
}

// Allowed public endpoints
function checkApiAuth()
{
    // Allow dashboard endpoint without auth for testing
    $script = basename($_SERVER['SCRIPT_NAME']);
    $allowed_public = ['dashboard.php', 'track.php', 'track-email.php'];

    if (in_array($script, $allowed_public)) {
        return true;  // Allow public access
    }

    if (isLocalDevelopmentRequest()) {
        return true;
    }

    // For admin endpoints, require login
        if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
        if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','officestaff'], true)) {
            apiResponse(false, null, 'Admin access required', ERR_FORBIDDEN);
        }
        return true;
    }

    // Session check for other endpoints
    if (!empty($_SESSION['user_id'])) {
        return true;
    }

    // Token check for API clients
    $expected = env('API_BEARER_TOKEN', '');
    $token = getBearerTokenFromHeader();

    if (empty($expected) && APP_ENV !== 'production') {
        return true;  // Allow in development
    }

    if (empty($token) || $token !== $expected) {
        apiResponse(false, null, 'Unauthorized - Please login first', ERR_UNAUTHORIZED);
    }
}

// Token helper functions
function sanitizeToken(string $t): string
{
    return preg_replace('/[^A-Za-z0-9\-_.]/', '', $t);
}

function getAuthorizationHeader(): ?string
{
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) return trim($_SERVER['HTTP_AUTHORIZATION']);
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (!empty($headers['Authorization'])) return trim($headers['Authorization']);
        if (!empty($headers['authorization'])) return trim($headers['authorization']);
    }

    return null;
}

function getBearerTokenFromHeader(): ?string
{
    $header = getAuthorizationHeader();
    if (!$header) return null;
    if (stripos($header, 'Bearer ') === 0) {
        return trim(substr($header, 7));
    }
    return null;
}

// Run auth check (but don't exit for allowed public endpoints)
$script = basename($_SERVER['SCRIPT_NAME']);
$allowed_public = ['dashboard.php', 'track.php', 'track-email.php'];

if (!in_array($script, $allowed_public)) {
    checkApiAuth();
}
