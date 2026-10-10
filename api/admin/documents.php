<?php
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
            $docId = $_GET['id'] ?? 0;
            $companyId = $_GET['company_id'] ?? 0;
            $getAll = $_GET['all'] ?? 0;

            // Check if table exists
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute(['document_templates']);
            if ($stmt->rowCount() == 0) {
                echo json_encode(['success' => true, 'data' => [], 'message' => 'Document templates table not found']);
                break;
            }

            if ($docId) {
                // Get single document by ID (for preview)
                $stmt = $pdo->prepare("SELECT * FROM document_templates WHERE id = ?");
                $stmt->execute([$docId]);
            } else if ($getAll) {
                // Get all global documents (no longer filtering by company_id since documents are now global)
                $stmt = $pdo->prepare(
                    "SELECT dt.*, COUNT(dca.id) as company_count 
                     FROM document_templates dt 
                     LEFT JOIN document_company_assignments dca ON dt.id = dca.document_id 
                     WHERE dt.is_active = 1
                     GROUP BY dt.id 
                     ORDER BY dt.document_type, dt.created_at DESC"
                );
                $stmt->execute();
            } else {
                // Get documents assigned to a specific company
                if (!$companyId) {
                    echo json_encode(['success' => true, 'data' => [], 'message' => 'No company ID provided']);
                    break;
                }

                $stmt = $pdo->prepare(
                    "SELECT dt.*, 1 as is_assigned 
                     FROM document_templates dt 
                     INNER JOIN document_company_assignments dca ON dt.id = dca.document_id 
                     WHERE dca.company_id = ? 
                     ORDER BY dt.document_type, dt.template_name ASC"
                );
                $stmt->execute([$companyId]);
            }

            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $templates]);
            break;

        case 'POST':
            $id = $input['id'] ?? null;
            $companyIds = $input['company_ids'] ?? [];
            $documentType = $input['document_type'] ?? '';
            $templateName = $input['template_name'] ?? '';
            $htmlTemplate = $input['html_template'] ?? '';
            $isActive = $input['is_active'] ?? 1;

            if (!$documentType || !$templateName) {
                echo json_encode(['success' => false, 'message' => 'Document type and template name required']);
                break;
            }

            if ($id) {
                // Update existing document
                $sql = "UPDATE document_templates SET document_type = ?, template_name = ?, html_template = ?, 
                        is_active = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $documentType,
                    $templateName,
                    $htmlTemplate,
                    $isActive,
                    $id
                ]);

                // Update company assignments if provided
                if (!empty($companyIds)) {
                    // Insert new assignments without deleting existing ones
                    // This preserves existing assignments to other companies
                    foreach ($companyIds as $cid) {
                        $stmt = $pdo->prepare(
                            "INSERT INTO document_company_assignments (document_id, company_id) VALUES (?, ?) 
                             ON DUPLICATE KEY UPDATE updated_at = NOW()"
                        );
                        $stmt->execute([$id, $cid]);
                    }
                }
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Document updated']);
            } else {
                // Insert new document (global, no company_id)
                $sql = "INSERT INTO document_templates (document_type, template_name, html_template, is_active) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $documentType,
                    $templateName,
                    $htmlTemplate,
                    $isActive
                ]);

                $documentId = $pdo->lastInsertId();

                // Assign to companies if provided
                if (!empty($companyIds)) {
                    foreach ($companyIds as $cid) {
                        $stmt = $pdo->prepare(
                            "INSERT INTO document_company_assignments (document_id, company_id) VALUES (?, ?) 
                             ON DUPLICATE KEY UPDATE updated_at = NOW()"
                        );
                        $stmt->execute([$documentId, $cid]);
                    }
                }

                echo json_encode(['success' => true, 'id' => $documentId, 'message' => 'Document created']);
            }
            break;

        case 'DELETE':
            $id = $input['id'] ?? null;
            if ($id) {
                // Delete assignments first
                $stmt = $pdo->prepare("DELETE FROM document_company_assignments WHERE document_id = ?");
                $stmt->execute([$id]);

                // Then delete document
                $stmt = $pdo->prepare("DELETE FROM document_templates WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Document deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Document ID required']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
