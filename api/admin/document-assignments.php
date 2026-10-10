<?php
// Document-Company Assignment Management API

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
// Fix path - use absolute path
$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/config/database.php';
global $pdo;

// Start session if not started
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

// Check admin auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','officestaff'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login as admin']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            $documentId = $_GET['document_id'] ?? 0;
            $companyId = $_GET['company_id'] ?? 0;

            if ($documentId) {
                // Get all companies assigned to this document
                $stmt = $pdo->prepare(
                    "SELECT dca.id, dca.company_id, c.name, c.display_name, c.logo_path, c.color_primary, c.color_secondary, 
                            dca.is_default, dca.created_at 
                     FROM document_company_assignments dca 
                     JOIN companies c ON dca.company_id = c.id 
                     WHERE dca.document_id = ? 
                     ORDER BY c.name ASC"
                );
                $stmt->execute([$documentId]);
                $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $assignments]);
            } elseif ($companyId) {
                // Get all documents assigned to this company
                $stmt = $pdo->prepare(
                    "SELECT dca.id as assignment_id, dca.document_id, dt.document_type, dt.template_name, dt.html_template, dt.is_active, 
                            dca.is_default, dca.created_at 
                     FROM document_company_assignments dca 
                     JOIN document_templates dt ON dca.document_id = dt.id 
                     WHERE dca.company_id = ? 
                     ORDER BY dt.document_type, dt.template_name ASC"
                );
                $stmt->execute([$companyId]);
                $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $assignments]);
            } else {
                echo json_encode(['success' => false, 'message' => 'document_id or company_id required']);
            }
            break;

        case 'POST':
            $documentId = $input['document_id'] ?? 0;
            $companyIds = $input['company_ids'] ?? [];

            if (!$documentId || empty($companyIds)) {
                echo json_encode(['success' => false, 'message' => 'document_id and company_ids required']);
                break;
            }

            // Verify document exists
            $stmt = $pdo->prepare("SELECT id FROM document_templates WHERE id = ?");
            $stmt->execute([$documentId]);
            if ($stmt->rowCount() == 0) {
                echo json_encode(['success' => false, 'message' => 'Document not found']);
                break;
            }

            // Insert new assignments without deleting existing ones
            // This preserves existing assignments to other companies
            $successCount = 0;
            foreach ($companyIds as $cid) {
                $stmt = $pdo->prepare(
                    "INSERT INTO document_company_assignments (document_id, company_id, is_default) 
                     VALUES (?, ?, 0) 
                     ON DUPLICATE KEY UPDATE updated_at = NOW()"
                );
                if ($stmt->execute([$documentId, $cid])) {
                    $successCount++;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Document assigned to $successCount companies",
                'assigned_count' => $successCount
            ]);
            break;

        case 'DELETE':
            $assignmentId = $input['id'] ?? 0;
            $documentId = $input['document_id'] ?? 0;
            $companyId = $input['company_id'] ?? 0;

            if ($assignmentId) {
                // Delete specific assignment
                $stmt = $pdo->prepare("DELETE FROM document_company_assignments WHERE id = ?");
                $stmt->execute([$assignmentId]);
                echo json_encode(['success' => true, 'message' => 'Assignment removed']);
            } elseif ($documentId && $companyId) {
                // Delete specific doc-company assignment
                $stmt = $pdo->prepare("DELETE FROM document_company_assignments WHERE document_id = ? AND company_id = ?");
                $stmt->execute([$documentId, $companyId]);
                echo json_encode(['success' => true, 'message' => 'Assignment removed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'id or (document_id + company_id) required']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
