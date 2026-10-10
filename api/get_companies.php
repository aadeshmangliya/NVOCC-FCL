<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/api_config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $companyFilter = trim((string)($_GET['company'] ?? ''));
    $sql = "SELECT id, company_key, name, display_name, color_primary, color_secondary FROM companies WHERE is_active = 1";
    $params = [];

    if ($companyFilter !== '') {
        $sql .= " AND (CAST(id AS CHAR) = ? OR LOWER(company_key) = LOWER(?) OR LOWER(name) = LOWER(?) OR LOWER(display_name) = LOWER(?))";
        $params = [$companyFilter, $companyFilter, $companyFilter, $companyFilter];
    }

    $sql .= " ORDER BY display_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $companies]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
