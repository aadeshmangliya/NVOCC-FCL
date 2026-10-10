<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
global $pdo;
$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','officestaff'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $basePath = rtrim(dirname($script), '/');

    if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }

    if (preg_match('#/api(/admin)?$#', $basePath)) {
        $basePath = preg_replace('#/api(/admin)?$#', '', $basePath);
    }

    return $protocol . '://' . $host . $basePath;
}

function handleLogoUpload($file) {
    $uploadDir = dirname(__DIR__, 2) . '/assets/images/logo/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No valid file uploaded'];
    }

    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds 2MB limit'];
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WebP allowed'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $basePath = getBaseUrl();
        $relativeUrl = rtrim($basePath, '/') . '/assets/images/logo/' . $filename;
        return ['success' => true, 'path' => $relativeUrl, 'filename' => $filename];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $sql = "SELECT id, company_key, name, display_name, website, carrier_name, logo_path, color_primary, color_secondary, is_active, created_at, updated_at FROM companies ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $companies]);
            break;

        case 'POST':
            if (empty($_POST['company_key']) || empty($_POST['name']) || empty($_POST['display_name'])) {
                echo json_encode(['success' => false, 'message' => 'Required fields: company_key, name, display_name']);
                exit;
            }

            $id = $_POST['id'] ?? null;
            $logoPath = $_POST['logo_path'] ?? null;

            if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
                $uploadResult = handleLogoUpload($_FILES['logo']);
                if ($uploadResult['success']) {
                    $logoPath = $uploadResult['path'];
                } else {
                    echo json_encode($uploadResult);
                    exit;
                }
            }

            if ($id) {
                $sql = "UPDATE companies SET company_key = ?, name = ?, display_name = ?, website = ?,
                        carrier_name = ?, logo_path = ?, color_primary = ?, color_secondary = ?,
                        is_active = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $_POST['company_key'],
                    $_POST['name'],
                    $_POST['display_name'],
                    $_POST['website'] ?? null,
                    $_POST['carrier_name'] ?? null,
                    $logoPath,
                    $_POST['color_primary'] ?? '#006a6a',
                    $_POST['color_secondary'] ?? '#d45f00',
                    $_POST['is_active'] ?? 1,
                    $id
                ]);
                if (!$result) {
                    echo json_encode(['success' => false, 'message' => 'Failed to update company']);
                    exit;
                }
            } else {
                $sql = "INSERT INTO companies (company_key, name, display_name, website, carrier_name, logo_path, color_primary, color_secondary, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $_POST['company_key'],
                    $_POST['name'],
                    $_POST['display_name'],
                    $_POST['website'] ?? null,
                    $_POST['carrier_name'] ?? null,
                    $logoPath,
                    $_POST['color_primary'] ?? '#006a6a',
                    $_POST['color_secondary'] ?? '#d45f00',
                    $_POST['is_active'] ?? 1
                ]);
                if (!$result) {
                    echo json_encode(['success' => false, 'message' => 'Failed to create company']);
                    exit;
                }
            }
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
                $stmt->execute([$id]);
            }
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

