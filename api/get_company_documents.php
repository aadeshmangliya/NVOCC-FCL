<?php
require_once __DIR__ . '/../config/api_config.php';
require_once __DIR__ . '/../config/database.php';

global $pdo;

$company_id = $_GET['company_id'] ?? 0;
$company_key = $_GET['company_key'] ?? '';

try {

    if ($company_key) {
        $stmt = $pdo->prepare("SELECT id FROM companies WHERE company_key = ? AND is_active = 1");
        $stmt->execute([$company_key]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        $company_id = $company['id'] ?? 0;
    }

    if (!$company_id) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No company found']);
        exit;
    }

    // Check if tables exist
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute(['document_templates']);
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'Document templates table not found']);
        exit;
    }

    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute(['document_company_assignments']);
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'Document assignments table not found']);
        exit;
    }

    // Get documents assigned to this company via junction table
    // Documents are GLOBAL (company_id IS NULL) and linked via document_company_assignments
    $stmt = $pdo->prepare(
        "SELECT dt.id, dt.document_type, dt.template_name, dt.html_template, dt.is_active, 
                dca.is_default 
         FROM document_templates dt 
         INNER JOIN document_company_assignments dca ON dt.id = dca.document_id 
         WHERE dca.company_id = ? AND dt.is_active = 1 
         ORDER BY dt.document_type, dt.template_name ASC"
    );
    $stmt->execute([$company_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $documents]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
