<?php
// Dynamic Manifest Generator - Uses database templates
session_start();
require_once '../config/database.php';
require_once '../includes/api_functions.php';
global $pdo;
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$tracking_number = trim($_GET['tracking_number'] ?? '');
$company_key = trim($_GET['company'] ?? '');
$document_type = trim($_GET['document_type'] ?? 'bill_of_lading');
$template_name = trim($_GET['template_name'] ?? '');

// Local admin check (avoids redeclare conflicts with includes/functions.php,
// which also defines generateTrackingNumber() and would clash with api_functions.php)
// NOTE: admin/login.php sets $_SESSION['role'] directly (not $_SESSION['user']['role']),
// so we check that key here to match how this project actually logs users in.
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'admin';
    }
}

// Delivery Order is restricted to admin users only
if ($document_type === 'delivery_order' && !isAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied. Delivery Order can only be downloaded by an administrator.']);
    exit;
}

if (!$tracking_number) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'tracking_number is required']);
    exit;
}

// Get company by key
$stmt = $pdo->prepare("SELECT * FROM companies WHERE company_key = ? AND is_active = 1");
$stmt->execute([$company_key]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid or inactive company']);
    exit;
}

if (!empty($template_name)) {
    // If template_name is provided, get the specific template
    $stmt = $pdo->prepare("SELECT dt.* FROM document_templates dt
    INNER JOIN document_company_assignments dca ON dt.id = dca.document_id
    WHERE dca.company_id = ? AND dt.document_type = ? AND dt.template_name = ? AND dt.is_active = 1 LIMIT 1");
    $stmt->execute([$company['id'], $document_type, $template_name]);
} else {
    // Otherwise, get the default or first template of this type
    $stmt = $pdo->prepare("SELECT dt.* FROM document_templates dt
    INNER JOIN document_company_assignments dca ON dt.id = dca.document_id
    WHERE dca.company_id = ? AND dt.document_type = ? AND dt.is_active = 1 
    ORDER BY dca.is_default DESC, dt.template_name ASC LIMIT 1");
    $stmt->execute([$company['id'], $document_type]);
}

$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No active template found for this document type']);
    exit;
}

// Get shipment data
$stmt = $pdo->prepare("SELECT * FROM shipments WHERE tracking_number = ?");
$stmt->execute([$tracking_number]);
$shipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shipment) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Shipment not found']);
    exit;
}

$shipment = enrichShipmentWithDetails($pdo, $shipment);

$cargoDetailStmt = $pdo->prepare("SELECT * FROM shipment_cargo_details WHERE shipment_id = ? LIMIT 1");
$cargoDetailStmt->execute([$shipment['id']]);
$cargoDetail = $cargoDetailStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$cargoStmt = $pdo->prepare("SELECT * FROM shipment_cargo WHERE shipment_id = ? LIMIT 1");
$cargoStmt->execute([$shipment['id']]);
$cargo = $cargoStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$shipment['notify_name'] = $shipment['notify_name'] ?? $shipment['consignee_name'] ?? null;
$shipment['notify_address'] = $shipment['notify_address'] ?? $shipment['consignee_address'] ?? null;
$shipment['notify_phone'] = $shipment['notify_phone'] ?? $shipment['consignee_phone'] ?? null;
$shipment['notify_email'] = $shipment['notify_email'] ?? $shipment['consignee_email'] ?? null;
$shipment['notify_contact'] = $shipment['notify_contact'] ?? $shipment['notify_phone'] ?? $shipment['consignee_phone'] ?? null;
$shipment['forwarding_agent_address'] = $shipment['forwarding_agent_address'] ?? $shipment['destination_agent_address'] ?? $shipment['shipper_address'] ?? null;

$shipment['package_type'] = $shipment['package_type'] ?? $cargoDetail['package_type'] ?? $cargo['package_type'] ?? null;
$shipment['num_packages'] = $shipment['num_packages'] ?? $cargoDetail['num_packages'] ?? $cargo['num_packages'] ?? null;
$shipment['total_pkgs_text'] = $shipment['total_pkgs_text'] ?? $cargoDetail['total_pkgs_text'] ?? $cargo['total_pkgs_text'] ?? null;

// Get items
$stmt = $pdo->prepare("SELECT * FROM shipment_items WHERE shipment_id = ?");
$stmt->execute([$shipment['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get containers
$stmt = $pdo->prepare("SELECT * FROM shipment_containers WHERE shipment_id = ? ORDER BY created_at ASC, id ASC");
$stmt->execute([$shipment['id']]);
$containers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cargo rows from shipment (already enriched)
$cargoRows = $shipment['cargo_rows'] ?? [];

// Group cargo rows by container
$containerCargoMap = [];
$containerTotals = [];

foreach ($containers as $index => $container) {
    $containerId = $container['id'];
    $containerNo = $container['container_no'];
    $containerCargoMap[$containerId] = [];
    $containerTotals[$containerId] = [
        'total_packages' => 0,
        'total_weight' => 0,
        'total_cbm' => 0
    ];
}

// Assign cargo rows to containers
foreach ($cargoRows as $row) {
    $containerId = $row['container_id'] ?? null;
    $containerNo = $row['container_no'] ?? null;
    
    if ($containerId && isset($containerCargoMap[$containerId])) {
        $containerCargoMap[$containerId][] = $row;
        $containerTotals[$containerId]['total_packages'] += (int)($row['num_packages'] ?? 0);
        $containerTotals[$containerId]['total_weight'] += (float)($row['gross_weight'] ?? 0);
        $containerTotals[$containerId]['total_cbm'] += (float)($row['volume_cbm'] ?? 0);
    } elseif ($containerNo) {
        // Try to match by container number
        foreach ($containers as $index => $cont) {
            if ($cont['container_no'] == $containerNo) {
                $cid = $cont['id'];
                $containerCargoMap[$cid][] = $row;
                $containerTotals[$cid]['total_packages'] += (int)($row['num_packages'] ?? 0);
                $containerTotals[$cid]['total_weight'] += (float)($row['gross_weight'] ?? 0);
                $containerTotals[$cid]['total_cbm'] += (float)($row['volume_cbm'] ?? 0);
                break;
            }
        }
    }
}

// Generate cargo table HTML
$cargoTableHtml = '';
$grandTotalPackages = 0;
$grandTotalWeight = 0;
$grandTotalCbm = 0;

foreach ($containers as $container) {
    $containerId = $container['id'];
    $rows = $containerCargoMap[$containerId] ?? [];
    $totals = $containerTotals[$containerId] ?? ['total_packages' => 0, 'total_weight' => 0, 'total_cbm' => 0];
    
    $grandTotalPackages += $totals['total_packages'];
    $grandTotalWeight += $totals['total_weight'];
    $grandTotalCbm += $totals['total_cbm'];
    
    if (!empty($rows)) {
        foreach ($rows as $row) {
            $cargoTableHtml .= '<tr>';
            $cargoTableHtml .= '<td>' . htmlspecialchars($container['container_no'] ?? '') . '</td>';
            $cargoTableHtml .= '<td>' . htmlspecialchars($row['marks_numbers'] ?? '') . '</td>';
            $cargoTableHtml .= '<td>' . htmlspecialchars($row['num_packages'] ?? 0) . ' ' . htmlspecialchars($row['package_type'] ?? '') . '</td>';
            $cargoTableHtml .= '<td><div class="goods-desc-small">' . htmlspecialchars($row['goods_description'] ?? '') . '</div><div class="hs-code-text">HS Code: ' . htmlspecialchars($row['hs_code'] ?? '') . '</div></td>';
            $cargoTableHtml .= '<td>' . htmlspecialchars($row['commodity_type'] ?? '') . '</td>';
            $cargoTableHtml .= '<td>' . htmlspecialchars($row['gross_weight'] ?? 0) . ' kg</td>';
            $cargoTableHtml .= '<td>' . htmlspecialchars($row['volume_cbm'] ?? 0) . ' cbm</td>';
            $cargoTableHtml .= '</tr>';
        }
    } else {
        // Empty container - show placeholder
        $cargoTableHtml .= '<tr>';
        $cargoTableHtml .= '<td>' . htmlspecialchars($container['container_no'] ?? '') . '</td>';
        $cargoTableHtml .= '<td>-</td>';
        $cargoTableHtml .= '<td>-</td>';
        $cargoTableHtml .= '<td>-</td>';
        $cargoTableHtml .= '<td>-</td>';
        $cargoTableHtml .= '<td>-</td>';
        $cargoTableHtml .= '<td>-</td>';
        $cargoTableHtml .= '</tr>';
    }
    
}

// Overall total
$cargoTableHtml .= '<tr class="total-row" style="background:#e8f0fe;">';
$cargoTableHtml .= '<td colspan="2"><strong>TOTAL</strong></td>';
$cargoTableHtml .= '<td><strong>' . htmlspecialchars($grandTotalPackages) . ' Packages</strong></td>';
$cargoTableHtml .= '<td><strong>' . htmlspecialchars($shipment['package_type'] ?? '') . '</strong></td>';
$cargoTableHtml .= '<td></td>';
$cargoTableHtml .= '<td><strong>' . number_format($grandTotalWeight, 3) . ' KG</strong></td>';
$cargoTableHtml .= '<td><strong>' . number_format($grandTotalCbm, 3) . ' CBM</strong></td>';
$cargoTableHtml .= '</tr>';

// Now add the generated cargo table HTML to the data array
$containerOrder = [];
foreach ($containers as $index => $container) {
    $label = trim((string)($container['container_no'] ?? ''));
    preg_match('/(\d+)/', $label, $matches);
    $number = isset($matches[1]) ? (int)$matches[1] : PHP_INT_MAX;
    $keyParts = [
        (string)($container['container_no'] ?? ''),
        (string)($container['container_type'] ?? ''),
        (string)($container['seal_no'] ?? ''),
        (string)($container['origin'] ?? ''),
        (string)($container['destination'] ?? '')
    ];
    $key = strtolower(implode('|', array_map('trim', $keyParts)));

    if ($key === '') {
        continue;
    }

    $containerOrder[] = [
        'sort' => $number,
        'index' => $index,
        'key' => $key,
        'container' => $container
    ];
}

usort($containerOrder, function ($a, $b) {
    if ($a['sort'] === $b['sort']) {
        return $a['index'] <=> $b['index'];
    }
    return $a['sort'] <=> $b['sort'];
});

$uniqueContainers = [];
$seenContainerKeys = [];
foreach ($containerOrder as $item) {
    $key = $item['key'];
    if (isset($seenContainerKeys[$key])) {
        continue;
    }
    $seenContainerKeys[$key] = true;
    $uniqueContainers[] = $item['container'];
}
$containers = $uniqueContainers;

$firstContainer = $containers[0] ?? [];

// If shipment has cargo_rows, attach them to the matching container objects for per-container rendering
if (!empty($shipment['cargo_rows']) && is_array($shipment['cargo_rows']) && !empty($containers)) {
    // initialize cargo_rows and totals on each container
    foreach ($containers as $k => $cont) {
        $containers[$k]['cargo_rows'] = [];
        $containers[$k]['total_weight'] = 0.0;
        $containers[$k]['total_cbm'] = 0.0;
        $containers[$k]['total_packages'] = 0;
    }

    // map by id and container_no
    $containerById = [];
    $containerByNo = [];
    foreach ($containers as $k => $cont) {
        if (!empty($cont['id'])) $containerById[(int)$cont['id']] = $k;
        if (!empty($cont['container_no'])) $containerByNo[strval($cont['container_no'])] = $k;
    }

    foreach ($shipment['cargo_rows'] as $cr) {
        $assigned = false;
        if (!empty($cr['container_id']) && isset($containerById[(int)$cr['container_id']])) {
            $idx = $containerById[(int)$cr['container_id']];
            $containers[$idx]['cargo_rows'][] = $cr;
            $containers[$idx]['total_weight'] += isset($cr['gross_weight']) ? (float)$cr['gross_weight'] : 0;
            $containers[$idx]['total_cbm'] += isset($cr['volume_cbm']) ? (float)$cr['volume_cbm'] : 0;
            $containers[$idx]['total_packages'] += isset($cr['num_packages']) ? (int)$cr['num_packages'] : 0;
            $assigned = true;
        } elseif (!empty($cr['container_no']) && isset($containerByNo[strval($cr['container_no'])])) {
            $idx = $containerByNo[strval($cr['container_no'])];
            $containers[$idx]['cargo_rows'][] = $cr;
            $containers[$idx]['total_weight'] += isset($cr['gross_weight']) ? (float)$cr['gross_weight'] : 0;
            $containers[$idx]['total_cbm'] += isset($cr['volume_cbm']) ? (float)$cr['volume_cbm'] : 0;
            $containers[$idx]['total_packages'] += isset($cr['num_packages']) ? (int)$cr['num_packages'] : 0;
            $assigned = true;
        }
    }

    // format totals
    foreach ($containers as $k => $cont) {
        $containers[$k]['total_weight'] = number_format($containers[$k]['total_weight'], 3, '.', '');
        $containers[$k]['total_cbm'] = number_format($containers[$k]['total_cbm'], 3, '.', '');
        $containers[$k]['total_packages'] = (int)$containers[$k]['total_packages'];
    }
}

$containerSummaryFields = ['container_no', 'container_type', 'seal_no', 'origin', 'destination'];
foreach ($containerSummaryFields as $field) {
    if (!isset($firstContainer[$field]) || trim((string)$firstContainer[$field]) === '') {
        $firstContainer[$field] = '';
    }
}

// ============================================================
// Generate Delivery Order container rows HTML (container_rows_html)
// Used by the 'delivery_order' document type template.
// ============================================================
$containerRowsHtml = '';
foreach ($containers as $container) {
    $containerId = $container['id'];
    $rows = $containerCargoMap[$containerId] ?? [];
    $totals = $containerTotals[$containerId] ?? ['total_packages' => 0, 'total_weight' => 0, 'total_cbm' => 0];

    $goodsDescParts = [];
    $hsCodes = [];
    foreach ($rows as $row) {
        if (!empty($row['goods_description'])) {
            $goodsDescParts[] = $row['goods_description'];
        }
        if (!empty($row['hs_code'])) {
            $hsCodes[] = $row['hs_code'];
        }
    }
    $goodsDescText = implode(', ', array_unique($goodsDescParts));
    $hsCodeText = implode(', ', array_unique($hsCodes));

    $containerRowsHtml .= '<tr>';
    $containerRowsHtml .= '<td><div class="meta-line">' . htmlspecialchars($container['container_no'] ?? '') . '</div>'
        . '<div>SIZE/TYPE: ' . htmlspecialchars($container['container_type'] ?? '') . '</div>'
        . '<div>SEAL NO: ' . htmlspecialchars($container['seal_no'] ?? '') . '</div></td>';
    $containerRowsHtml .= '<td>' . htmlspecialchars($container['empty_return_location'] ?? $container['destination'] ?? '') . '<br>'
        . 'RETURN DATE: ' . htmlspecialchars($container['empty_return_date'] ?? $shipment['validity_date'] ?? '') . '</td>';
    $containerRowsHtml .= '<td>' . htmlspecialchars($container['marks_numbers'] ?? ($rows[0]['marks_numbers'] ?? 'N/M')) . '</td>';
    $containerRowsHtml .= '<td>' . htmlspecialchars($totals['total_packages'] ?: ($shipment['num_packages'] ?? '')) . '</td>';
    $containerRowsHtml .= '<td>1X' . htmlspecialchars($container['container_type'] ?? '') . ' FCL/FCL CONTAINER STC '
        . htmlspecialchars($totals['total_packages'] ?: '') . ' PACKAGES OF '
        . htmlspecialchars($goodsDescText) . ($hsCodeText !== '' ? ' H.S. CODE: ' . htmlspecialchars($hsCodeText) : '') . '</td>';
    $containerRowsHtml .= '<td>NET: ' . htmlspecialchars($rows[0]['net_weight'] ?? $shipment['net_weight'] ?? '0') . ' kg<br>'
        . 'GROSS: ' . htmlspecialchars($totals['total_weight'] ?: ($shipment['gross_weight'] ?? '0')) . ' kg</td>';
    $containerRowsHtml .= '</tr>';
}

// ============================================================
// Generate IGM "Item Information" rows HTML (item_rows_html)
// One row per container's cargo (HS Code, Description, Packing Mode, Quantity, No of Packages)
// Used by the 'igm' document type template.
// ============================================================
$igmItemRowsHtml = '';
foreach ($containers as $container) {
    $containerId = $container['id'];
    $rows = $containerCargoMap[$containerId] ?? [];
    $totals = $containerTotals[$containerId] ?? ['total_packages' => 0, 'total_weight' => 0, 'total_cbm' => 0];

    foreach ($rows as $row) {
        $igmItemRowsHtml .= '<tr>';
        $igmItemRowsHtml .= '<td>' . htmlspecialchars($row['hs_code'] ?? '') . '</td>';
        $igmItemRowsHtml .= '<td>1X' . htmlspecialchars($container['container_type'] ?? '') . ' FCL CONTAINER '
            . htmlspecialchars($row['goods_description'] ?? '') . '</td>';
        $igmItemRowsHtml .= '<td>' . htmlspecialchars($row['package_type'] ?? 'PACKAGES') . '</td>';
        $igmItemRowsHtml .= '<td>' . htmlspecialchars($row['num_packages'] ?? 0) . '.00</td>';
        $igmItemRowsHtml .= '<td>' . htmlspecialchars($row['num_packages'] ?? 0) . '.00</td>';
        $igmItemRowsHtml .= '</tr>';
    }

    // Fallback: if a container has no cargo_details rows, still show a summary line
    if (empty($rows) && !empty($container['container_no'])) {
        $igmItemRowsHtml .= '<tr>';
        $igmItemRowsHtml .= '<td>' . htmlspecialchars($shipment['hs_code'] ?? '') . '</td>';
        $igmItemRowsHtml .= '<td>1X' . htmlspecialchars($container['container_type'] ?? '') . ' FCL CONTAINER '
            . htmlspecialchars($shipment['goods_description'] ?? '') . '</td>';
        $igmItemRowsHtml .= '<td>PACKAGES</td>';
        $igmItemRowsHtml .= '<td>' . htmlspecialchars($shipment['num_packages'] ?? 0) . '.00</td>';
        $igmItemRowsHtml .= '<td>' . htmlspecialchars($shipment['num_packages'] ?? 0) . '.00</td>';
        $igmItemRowsHtml .= '</tr>';
    }
}

// ============================================================
// Generate IGM "Container Information" rows HTML (igm_container_rows_html)
// Container Number, Seal Number, ISO Code, Gross Weight, Net Weight, SOC
// Used by the 'igm' document type template.
// ============================================================
$igmContainerRowsHtml = '';
foreach ($containers as $container) {
    $igmContainerRowsHtml .= '<tr>';
    $igmContainerRowsHtml .= '<td>' . htmlspecialchars($container['container_no'] ?? '') . '</td>';
    $igmContainerRowsHtml .= '<td>' . htmlspecialchars($container['seal_no'] ?? '') . '</td>';
    $igmContainerRowsHtml .= '<td>' . htmlspecialchars($container['iso_code'] ?? '') . '</td>';
    $igmContainerRowsHtml .= '<td>' . htmlspecialchars($container['gross_weight'] ?? '0.00') . '</td>';
    $igmContainerRowsHtml .= '<td>' . htmlspecialchars($container['net_weight'] ?? '0.00') . '</td>';
    $igmContainerRowsHtml .= '<td>' . htmlspecialchars($container['soc'] ?? 'N') . '</td>';
    $igmContainerRowsHtml .= '</tr>';
}

// Get base URL for assets
function getBaseUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $scriptPath = dirname($scriptName);
    $basePath = rtrim($scriptPath, '/');
    if ($basePath == '/') {
        return $protocol . $host;
    }
    return $protocol . $host . $basePath;
}
$baseUrl = getBaseUrl();

$companyTagline = trim((string)($company['tagline'] ?? $company['company_tagline'] ?? $company['name'] ?? ''));
$exportCarrier = trim((string)($shipment['export_carrier'] ?? $shipment['carrier'] ?? $shipment['carrier_name'] ?? $company['carrier_name'] ?? $company['display_name'] ?? ''));
$shipmentValue = $shipment['value'] ?? $shipment['total_value'] ?? $shipment['cargo_value'] ?? $shipment['declared_value'] ?? 0;

foreach ($items as &$item) {
    if (!isset($item['value']) || $item['value'] === '') {
        $item['value'] = $item['unit_value'] ?? $item['amount'] ?? $shipmentValue;
    }
}
unset($item);

// ============================================================
// Bill of Lading specific fields (container marks, cargo description, totals, etc.)
// ============================================================
$blMarksHtml = '';
$blCargoDescHtml = '';
$blNetWeight = 0;
$blDescParts = [];
foreach ($containers as $container) {
    $cid = $container['id'] ?? null;
    $rows = $containerCargoMap[$cid] ?? [];
    $blMarksHtml .= '<div style="margin-bottom:8px;">'
        . htmlspecialchars(trim(($container['container_no'] ?? '') . '/' . ($container['container_type'] ?? ''), '/'))
        . '<br>' . htmlspecialchars($container['seal_no'] ?? '') . '</div>';
    foreach ($rows as $row) {
        $blNetWeight += (float)($row['net_weight'] ?? 0);
        $blDescParts[] = [
            'pk' => (int)($row['num_packages'] ?? 0),
            'pt' => $row['package_type'] ?? 'PACKAGES',
            'gd' => $row['goods_description'] ?? '',
            'hs' => $row['hs_code'] ?? '',
        ];
    }
}
foreach ($blDescParts as $d) {
    $blCargoDescHtml .= '<div style="margin-bottom:6px;">'
        . htmlspecialchars($d['pk'] . ' ' . $d['pt'] . ' OF ' . $d['gd'])
        . ($d['hs'] !== '' ? '<br>HS CODE: ' . htmlspecialchars($d['hs']) : '')
        . '</div>';
}
if ($blCargoDescHtml === '') {
    $blCargoDescHtml = htmlspecialchars($shipment['goods_description'] ?? '');
}
$blFreightTerms = strtolower((string)($shipment['freight_terms'] ?? ''));
$blTotalFreight = $shipment['total_freight'] ?? '';
$blPlaceIssue = $shipment['place_of_issue'] ?? '';

// Prepare data for template
$data = array_merge($shipment, $firstContainer, [
    'company_name' => $company['name'],
    'company_display_name' => $company['display_name'],
    'company_tagline' => $companyTagline,
    'company_website' => $company['website'],
    'logo_path' => $company['logo_path'],
    'carrier_name' => $company['carrier_name'],
    'export_carrier' => $exportCarrier,
    'value' => $shipmentValue,
    'color_primary' => $company['color_primary'],
    'color_secondary' => $company['color_secondary'],
    'items' => $items,
    'containers' => $containers,
    'generated_date' => date('Y-m-d H:i:s'),
    'total_weight' => array_sum(array_column($items, 'weight')) ?: ($shipment['gross_weight'] ?? 0),
    'total_packages' => array_sum(array_column($items, 'quantity')) ?: ($shipment['num_packages'] ?? 1),
    'total_value' => array_sum(array_column($items, 'unit_value')) ?: ($shipment['unit_value'] ?? 1),
    'total_cbm' => array_sum(array_column($items, 'cbm')) ?: ($shipment['volume_cbm'] ?? 0),
    'base_url' => $baseUrl,
    // Add the pre-rendered cargo table HTML
    'cargo_table_html' => $cargoTableHtml,

    // ---- Delivery Order specific fields ----
    'container_rows_html' => $containerRowsHtml,
    'company_address' => $company['address'] ?? '',
    'company_phone' => $company['phone'] ?? '',
    'company_email' => $company['email'] ?? '',
    'company_national_id' => $company['national_identity_no'] ?? '',
    'do_number' => $shipment['do_number'] ?? ('DO-' . strtoupper(substr(md5($shipment['tracking_number']), 0, 8))),
    'issuance_date' => $shipment['do_issuance_date'] ?? date('d/m/Y'),
    'validity_date' => $shipment['do_validity_date'] ?? '',
    'do_to' => $shipment['do_to'] ?? ($shipment['pod'] ?? ''),
    'hbl_number' => $shipment['hbl_number'] ?? $shipment['tracking_number'],
    'mode_of_delivery' => $shipment['mode_of_delivery'] ?? ($shipment['mode'] ?? ''),
    'destination' => $shipment['destination'] ?? ($shipment['final_destination'] ?? ''),
    'issued_to' => $shipment['issued_to'] ?? '',
    'line_remarks' => $shipment['line_remarks'] ?? 'N/A',
    'printed_on' => date('d/m/Y H:i'),

    // ---- IGM specific fields ----
    // (vir_number, igm_number, igm_date, vir_confirmation_date, vessel_status, berthed_at,
    //  last_port_of_call, exp_date_of_arrival, exp_time_of_arrival, exp_departure_date,
    //  exp_departure_time, berthing_date, sailing_date, vir_launched_by come from
    //  shipment_vessels via enrichShipmentWithDetails() and are already in $shipment)
    // (bl_type, delivery_mode, stuffing_status, via_port, plot_number come from
    //  shipment_cargo_details via enrichShipmentWithDetails() and are already in $shipment)
    // (consignee_city, consignee_country come from shipment_consignees)
    // (shipper_country, shipper_port come from shipment_shippers)
    'cargo_type' => $shipment['cargo_type'] ?? 'Containerized',
    'item_rows_html' => $igmItemRowsHtml,
    'igm_container_rows_html' => $igmContainerRowsHtml,

    // ---- Bill of Lading specific fields ----
    'container_marks_html' => $blMarksHtml,
    'cargo_description_html' => $blCargoDescHtml,
    'gross_weight' => $grandTotalWeight > 0 ? number_format($grandTotalWeight, 3, '.', '') : ($shipment['gross_weight'] ?? ''),
    'net_weight' => $blNetWeight > 0 ? number_format($blNetWeight, 3, '.', '') : ($shipment['net_weight'] ?? ''),
    'measurement' => $grandTotalCbm > 0 ? number_format($grandTotalCbm, 3, '.', '') . ' CBM' : '',
    'total_containers' => count($containers),
    'charges_rows_html' => '<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>',
    'total_freight_prepaid' => strpos($blFreightTerms, 'prepaid') !== false ? $blTotalFreight : '',
    'total_freight_collect' => strpos($blFreightTerms, 'collect') !== false ? $blTotalFreight : '',
    'governing_law' => $shipment['governing_law'] ?? $blPlaceIssue,
    'jurisdiction_place' => $shipment['jurisdiction_place'] ?? $blPlaceIssue,
    'signatory_location' => $blPlaceIssue
]);

// Fixed renderTemplate function with proper type hints
function renderTemplate(string $html, array $data): string
{
    // Replace {{cargo_table_html}} with the pre-rendered table
    $html = str_replace('{{cargo_table_html}}', $data['cargo_table_html'] ?? '', $html);

    // Replace {{container_rows_html}} with the pre-rendered Delivery Order rows (raw HTML, not escaped)
    $html = str_replace('{{container_rows_html}}', $data['container_rows_html'] ?? '', $html);

    // Replace {{item_rows_html}} and {{igm_container_rows_html}} with the pre-rendered IGM rows (raw HTML, not escaped)
    $html = str_replace('{{item_rows_html}}', $data['item_rows_html'] ?? '', $html);
    $html = str_replace('{{igm_container_rows_html}}', $data['igm_container_rows_html'] ?? '', $html);

    // replaced by the top-level shipment/container values first.
    if (preg_match_all('/{{#items}}(.*?){{\/items}}/s', $html, $allMatches, PREG_SET_ORDER)) {
        $itemsHtml = '';
        $itemTemplate = '';
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                // lazily set itemTemplate from first match
                if ($itemTemplate === '' && isset($allMatches[0][1])) {
                    $itemTemplate = $allMatches[0][1];
                }
                if ($itemTemplate === '') break;
                $itemHtml = $itemTemplate;
                foreach ($item as $ik => $iv) {
                    $itemHtml = str_replace("{{{$ik}}}", htmlspecialchars((string)$iv, ENT_QUOTES, 'UTF-8'), $itemHtml);
                }
                $itemHtml = preg_replace_callback('/\{\{\.\.\/([A-Za-z0-9_]+)\}\}/', function ($m) use ($data) {
                    $varName = $m[1];
                    return htmlspecialchars((string)($data[$varName] ?? ''), ENT_QUOTES, 'UTF-8');
                }, $itemHtml);
                $itemsHtml .= $itemHtml;
            }
        }
        // replace all occurrences of the items block with rendered html
        foreach ($allMatches as $m) {
            $html = str_replace($m[0], $itemsHtml, $html);
        }
    }

    if (preg_match_all('/{{#containers}}(.*?){{\/containers}}/s', $html, $allMatches, PREG_SET_ORDER)) {
        $containersHtml = '';
        $containerTemplate = '';
        if (isset($data['containers']) && is_array($data['containers'])) {
            // use the first match's inner template as the container template
            if (!empty($allMatches[0][1])) {
                $containerTemplate = $allMatches[0][1];
            }
            foreach ($data['containers'] as $container) {
                $containerHtml = $containerTemplate;
                foreach ($container as $ck => $cv) {
                    // skip nested arrays (e.g., cargo_rows) to avoid array-to-string warnings
                    if (is_array($cv)) continue;
                    $containerHtml = str_replace("{{{$ck}}}", htmlspecialchars((string)$cv, ENT_QUOTES, 'UTF-8'), $containerHtml);
                }
                $containerHtml = preg_replace_callback('/\{\{\.\.\/([A-Za-z0-9_]+)\}\}/', function ($m) use ($data) {
                    $varName = $m[1];
                    return htmlspecialchars((string)($data[$varName] ?? ''), ENT_QUOTES, 'UTF-8');
                }, $containerHtml);

                // Render nested cargo_rows inside this container block if present
                if (preg_match('/{{#cargo_rows}}(.*?){{\/cargo_rows}}/s', $containerHtml, $cmatch)) {
                    $cargoTemplate = $cmatch[1];
                    $cargoHtml = '';
                    if (!empty($container['cargo_rows']) && is_array($container['cargo_rows'])) {
                        foreach ($container['cargo_rows'] as $crow) {
                            $rowHtml = $cargoTemplate;
                            foreach ($crow as $rk => $rv) {
                                if (is_array($rv)) continue;
                                $rowHtml = str_replace("{{{$rk}}}", htmlspecialchars((string)$rv, ENT_QUOTES, 'UTF-8'), $rowHtml);
                            }
                            // allow ../ references to parent container fields
                            $rowHtml = preg_replace_callback('/\{\{\.\.\/([A-Za-z0-9_]+)\}\}/', function ($m) use ($container) {
                                $varName = $m[1];
                                return htmlspecialchars((string)($container[$varName] ?? ''), ENT_QUOTES, 'UTF-8');
                            }, $rowHtml);
                            $cargoHtml .= $rowHtml;
                        }
                    }
                    // replace the cargo_rows block with rendered html
                    $containerHtml = str_replace($cmatch[0], $cargoHtml, $containerHtml);
                }
                $containersHtml .= $containerHtml;
            }
        }
        // replace all occurrences of the containers block with rendered html
        foreach ($allMatches as $m) {
            $html = str_replace($m[0], $containersHtml, $html);
        }
    }

    // Fixed conditional blocks - proper closure with type hints
    $html = preg_replace_callback('/{{#if (\w+)}}(.*?){{\/if}}/s', function ($matches) use ($data) {
        $varName = $matches[1];
        $content = $matches[2];

        if (isset($data[$varName]) && !empty($data[$varName])) {
            return $content;
        }
        return '';
    }, $html);

    $html = preg_replace_callback('/{{#unless (\w+)}}(.*?){{\/unless}}/s', function ($matches) use ($data) {
        $varName = $matches[1];
        $content = $matches[2];

        if (!isset($data[$varName]) || empty($data[$varName])) {
            return $content;
        }
        return '';
    }, $html);

    // Replace remaining simple variables after loops to avoid overwriting loop placeholders.
    foreach ($data as $key => $value) {
        if (!is_array($value)) {
            $html = str_replace("{{{$key}}}", htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $html);
        }
    }

    // Safety net: remove any placeholder with no data so raw {{tags}} never print
    $html = preg_replace('/\{\{[^{}]*\}\}/', '', $html);

    return $html;
}

$html = $template['html_template'];
$html = renderTemplate($html, $data);

// Add CSS
$fullHtml = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset='utf-8'>\n<title>" . htmlspecialchars($company['display_name']) . " - " . htmlspecialchars($document_type) . "</title>\n<style>\n" . ($template['css_styles'] ?? '') . "\n</style>\n</head>\n<body>\n{$html}\n</body>\n</html>";

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . $document_type . '_' . $tracking_number . '.html"');
echo $fullHtml;