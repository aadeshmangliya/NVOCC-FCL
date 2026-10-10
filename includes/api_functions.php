<?php
// API helper functions
function generateTrackingNumber(): string
{
    return 'RGSL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function getCurrentUserContext(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $role = strtolower(trim((string)($_SESSION['role'] ?? 'admin')));
    $email = strtolower(trim((string)($_SESSION['email'] ?? $_SESSION['user_email'] ?? '')));

    return [
        'role' => $role,
        'email' => $email,
        'user_id' => $_SESSION['user_id'] ?? null,
    ];
}

function normalizeStatus(?string $status): ?string
{
    if ($status === null) return null;
    $map = [
        'Booking Confirmed' => 'booking_confirmed',
        'Cargo Received' => 'cargo_received',
        'Customs Clearance' => 'customs_clearance',
        'Vessel Loaded' => 'vessel_loaded',
        'In Transit' => 'in_transit',
        'Port of Discharge' => 'port_of_discharge',
        'Out for Delivery' => 'out_for_delivery',
        'Delivered' => 'delivered',
        'On Hold' => 'on_hold',
        'Exception' => 'exception',
        'Pending' => 'pending',
        'Picked Up' => 'picked_up',
        'Cancelled' => 'cancelled'
    ];
    $status = trim($status);
    if (isset($map[$status])) return $map[$status];
    $valid = ['pending', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled', 'booking_confirmed', 'cargo_received', 'customs_clearance', 'vessel_loaded', 'port_of_discharge', 'on_hold', 'exception'];
    if (in_array($status, $valid, true)) return $status;
    return null;
}

function schemaColumnExists(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    $key = "$table.$column";
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        $cache[$key] = (bool)$stmt->fetch();
    } catch (PDOException $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function getStaffDestinationAgentEmailCondition(PDO $pdo, string $alias = 'sp'): array
{
    $userContext = getCurrentUserContext();
    if ($userContext['role'] !== 'agent' || $userContext['email'] === '') {
        return ['', []];
    }

    if (!schemaColumnExists($pdo, 'shipment_parties', 'destination_agent_email')) {
        return ['', []];
    }

    return ["LOWER(COALESCE($alias.destination_agent_email, '')) = ?", [$userContext['email']]];
}


function getShipments(PDO $pdo, array $filters = []): array
{
    $userContext = getCurrentUserContext();
    $query = "SELECT s.* FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id WHERE 1=1";
    $params = [];

    [$staffCondition, $staffParams] = getStaffDestinationAgentEmailCondition($pdo);
    if ($staffCondition !== '') {
        $query .= " AND $staffCondition";
        $params = array_merge($params, $staffParams);
    }

    if (!empty($filters['status'])) {
        $query .= " AND status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $query = str_replace('FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id', 'FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id LEFT JOIN shipment_shippers ss ON ss.shipment_id = s.id LEFT JOIN shipment_consignees sc ON sc.shipment_id = s.id', $query);
        $query .= " AND (tracking_number LIKE ? OR LOWER(COALESCE(ss.shipper_name, '')) LIKE ? OR LOWER(COALESCE(sc.consignee_name, '')) LIKE ?)";
        $search = '%' . strtolower($filters['search']) . '%';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
    }

    if (!empty($filters['from_date'])) {
        $query .= " AND DATE(created_at) >= ?";
        $params[] = $filters['from_date'];
    }

    if (!empty($filters['to_date'])) {
        $query .= " AND DATE(created_at) <= ?";
        $params[] = $filters['to_date'];
    }

    $query .= " ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as &$shipment) {
        $shipment = enrichShipmentWithDetails($pdo, $shipment);
    }

    if (!empty($filters['limit']) && is_numeric($filters['limit'])) {
        $results = array_slice($results, 0, (int)$filters['limit']);
    }

    return $results;
}

function getShipmentById(PDO $pdo, int $id): ?array
{
    $userContext = getCurrentUserContext();
    $query = "SELECT s.* FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id WHERE s.id = ?";
    $params = [$id];

    [$staffCondition, $staffParams] = getStaffDestinationAgentEmailCondition($pdo);
    if ($staffCondition !== '') {
        $query .= " AND $staffCondition";
        $params = array_merge($params, $staffParams);
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($shipment) {
        $shipment = enrichShipmentWithDetails($pdo, $shipment);

        $itemStmt = $pdo->prepare("SELECT * FROM shipment_items WHERE shipment_id = ?");
        $itemStmt->execute([$id]);
        $shipment['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $containerStmt = $pdo->prepare("SELECT * FROM shipment_containers WHERE shipment_id = ? ORDER BY id");
        $containerStmt->execute([$id]);
        $shipment['containers'] = $containerStmt->fetchAll(PDO::FETCH_ASSOC);
        // Group cargo_rows by container if cargo_rows exist
        if (!empty($shipment['containers']) && !empty($shipment['cargo_rows']) && is_array($shipment['cargo_rows'])) {
            // initialize cargo_rows array on each container
            foreach ($shipment['containers'] as &$cont) {
                $cont['cargo_rows'] = [];
                $cont['total_weight'] = 0.0;
                $cont['total_cbm'] = 0.0;
                $cont['total_packages'] = 0;
            }

            // map by id and container_no for quick lookup
            $containerById = [];
            $containerByNo = [];
            foreach ($shipment['containers'] as $k => $cont) {
                if (!empty($cont['id'])) $containerById[(int)$cont['id']] = &$shipment['containers'][$k];
                if (!empty($cont['container_no'])) $containerByNo[strval($cont['container_no'])] = &$shipment['containers'][$k];
            }

            // assign cargo rows to their containers when possible
            foreach ($shipment['cargo_rows'] as $cr) {
                $assigned = false;
                if (!empty($cr['container_id']) && isset($containerById[(int)$cr['container_id']])) {
                    $container = &$containerById[(int)$cr['container_id']];
                    $container['cargo_rows'][] = $cr;
                    $container['total_weight'] += isset($cr['gross_weight']) ? (float)$cr['gross_weight'] : 0;
                    $container['total_cbm'] += isset($cr['volume_cbm']) ? (float)$cr['volume_cbm'] : 0;
                    $container['total_packages'] += isset($cr['num_packages']) ? (int)$cr['num_packages'] : 0;
                    $assigned = true;
                } elseif (!empty($cr['container_no']) && isset($containerByNo[strval($cr['container_no'])])) {
                    $container = &$containerByNo[strval($cr['container_no'])];
                    $container['cargo_rows'][] = $cr;
                    $container['total_weight'] += isset($cr['gross_weight']) ? (float)$cr['gross_weight'] : 0;
                    $container['total_cbm'] += isset($cr['volume_cbm']) ? (float)$cr['volume_cbm'] : 0;
                    $container['total_packages'] += isset($cr['num_packages']) ? (int)$cr['num_packages'] : 0;
                    $assigned = true;
                }
                // unassigned cargo rows remain in top-level cargo_rows
            }
            // round totals and ensure strings for template
            foreach ($shipment['containers'] as &$cont) {
                $cont['total_weight'] = number_format($cont['total_weight'], 3, '.', '');
                $cont['total_cbm'] = number_format($cont['total_cbm'], 3, '.', '');
                $cont['total_packages'] = (int)$cont['total_packages'];
            }
            unset($cont);
        }
    }

    return $shipment ?: null;
}

function getShipmentByTrackingNumber(PDO $pdo, string $tracking_number): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM shipments WHERE tracking_number = ?");
    $stmt->execute([$tracking_number]);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shipment) {
        return null;
    }

    return enrichShipmentWithDetails($pdo, $shipment);
}

function getTrackingHistory(PDO $pdo, string $tracking_number): array
{
    $stmt = $pdo->prepare("SELECT * FROM tracking_history WHERE tracking_number = ? ORDER BY created_at DESC");
    $stmt->execute([$tracking_number]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function extractContainerEntries(array $data): array
{
    // $field => $formInputName (when the POST field name differs from the entry/DB key)
    $containerFieldMap = [
        'container_no' => 'container_no',
        'container_type' => 'container_type',
        'seal_no' => 'seal_no',
        'origin' => 'origin',
        'destination' => 'destination',
        'iso_code' => 'iso_code',
        'soc' => 'soc',
        'gross_weight' => 'container_gross_weight',
        'net_weight' => 'container_net_weight',
    ];
    $containerValues = [];

    foreach ($containerFieldMap as $entryKey => $formField) {
        if (!array_key_exists($formField, $data)) {
            continue;
        }

        $values = $data[$formField];
        if (!is_array($values)) {
            $values = [$values];
        }

        $containerValues[$entryKey] = $values;
    }

    if (empty($containerValues)) {
        return [];
    }

    $maxCount = 0;
    foreach ($containerValues as $values) {
        $maxCount = max($maxCount, count($values));
    }

    $entries = [];
    for ($i = 0; $i < $maxCount; $i++) {
        $entry = [];
        foreach (array_keys($containerFieldMap) as $entryKey) {
            $values = $containerValues[$entryKey] ?? [];
            $entry[$entryKey] = $values[$i] ?? null;
        }

        if (!empty(array_filter($entry, fn($value) => $value !== null && $value !== ''))) {
            $entries[] = $entry;
        }
    }

    return $entries;
}

function upsertShipmentDetail(PDO $pdo, int $shipmentId, string $table, array $fields, array $data): bool
{
    $payload = [];
    foreach ($fields as $field) {
        if (!array_key_exists($field, $data)) {
            continue;
        }

        $value = $data[$field];
        if ($value === '' || $value === null) {
            continue;
        }

        $payload[$field] = $value;
    }

    if (empty($payload)) {
        return false;
    }

    $columns = ['shipment_id'];
    $placeholders = ['?'];
    $values = [$shipmentId];

    foreach (array_keys($payload) as $field) {
        $columns[] = $field;
        $placeholders[] = '?';
        $values[] = $payload[$field];
    }

    $updates = [];
    foreach (array_keys($payload) as $field) {
        $updates[] = "`$field` = VALUES(`$field`)";
    }

    $sql = 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($values);
}

function saveShipmentDetailGroups(PDO $pdo, int $shipmentId, array $data, array $containerIds = []): bool
{
    $saved = false;

    $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_shippers', [
        'shipper_name',
        'shipper_email',
        'shipper_phone',
        'shipper_address'
    ], $data) || $saved;

    $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_consignees', [
        'consignee_name',
        'consignee_email',
        'consignee_phone',
        'consignee_address',
        'consignee_tax_id',
        'notify_name',
        'notify_phone',
        'notify_address',
        'notify_same_as_consignee'
    ], $data) || $saved;

    $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_vessels', [
        'vessel_name',
        'voyage_no',
        'vessel_flag',
        'imo_number',
        'carrier',
        'carrier_reference_no',
        'pre_carriage',
        'place_receipt',
        'place_of_delivery',
        'seal_no',
        'mode_load_area',
        'mode_discharge_area',
        'departure_time',
        'pickup_date',
        'pickup_time',
        'final_destination'
    ], $data) || $saved;

    $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_transport', [
        'vessel_flag',
        'imo_number',
        'carrier',
        'carrier_reference_no',
        'pre_carriage',
        'place_receipt',
        'final_destination',
        'departure_time',
        'pickup_date',
        'pickup_time',
        'seal_no',
        'place_of_delivery',
        'mode_load_area',
        'mode_discharge_area'
    ], $data) || $saved;

    $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_cargo', [
        'package_description',
        'un_number',
        'dg_class',
        'packing_group',
        'total_pkgs_text',
        'declared_value'
    ], $data) || $saved;

    $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_routes', [
        'pol',
        'pod',
        'origin',
        'destination',
        'etd',
        'eta',
        'pickup_date',
        'pickup_time',
        'mode',
        'type_of_shipment',
        'freight_terms',
        'payment_mode',
        'currency',
        'incoterms'
    ], $data) || $saved;

    // Handle multiple cargo rows if provided as `cargo` array (from form inputs named cargo[0][...])
    if (!empty($data['cargo']) && is_array($data['cargo'])) {
        try {
            // remove existing cargo detail rows for this shipment
            $del = $pdo->prepare("DELETE FROM shipment_cargo_details WHERE shipment_id = ?");
            $del->execute([$shipmentId]);

            $insertCols = [
                'shipment_id','container_id','container_no','goods_description','hs_code','commodity_type','num_packages','package_type','gross_weight','net_weight','volume_cbm','package_dimensions','marks_numbers','country_of_origin','cargo_value','package_description','un_number','dg_class','packing_group','total_pkgs_text','declared_value','created_at','updated_at'
            ];
            $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
            $sql = 'INSERT INTO shipment_cargo_details (' . implode(', ', $insertCols) . ') VALUES (' . $placeholders . ')';
            $insStmt = $pdo->prepare($sql);

            foreach ($data['cargo'] as $i => $c) {
                if (!is_array($c)) continue;
                // normalize keys
                $row = [];
                $row[] = $shipmentId;
                // container_id from provided mapping if available
                $row[] = isset($containerIds[$i]) ? $containerIds[$i] : null;
                $row[] = $c['container_no_display'] ?? $c['container_no'] ?? null;
                $row[] = $c['goods_description'] ?? null;
                $row[] = $c['hs_code'] ?? null;
                $row[] = $c['commodity_type'] ?? null;
                $row[] = $c['num_packages'] ?? null;
                $row[] = $c['package_type'] ?? null;
                $row[] = $c['gross_weight'] ?? null;
                $row[] = $c['net_weight'] ?? null;
                $row[] = $c['volume_cbm'] ?? null;
                $row[] = $c['package_dimensions'] ?? null;
                $row[] = $c['marks_numbers'] ?? null;
                $row[] = $c['country_of_origin'] ?? null;
                $row[] = $c['cargo_value'] ?? null;
                $row[] = $c['package_description'] ?? null;
                $row[] = $c['un_number'] ?? null;
                $row[] = $c['dg_class'] ?? null;
                $row[] = $c['packing_group'] ?? null;
                $row[] = $c['total_pkgs_text'] ?? null;
                $row[] = $c['declared_value'] ?? null;
                $row[] = date('Y-m-d H:i:s');
                $row[] = date('Y-m-d H:i:s');

                $insStmt->execute($row);
            }

            $saved = true || $saved;
        } catch (Throwable $e) {
            // ignore and continue (caller will handle transaction)
        }
    } else {
        $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_cargo_details', [
            'goods_description',
            'hs_code',
            'commodity_type',
            'num_packages',
            'package_type',
            'gross_weight',
            'net_weight',
            'volume_cbm',
            'package_dimensions',
            'marks_numbers',
            'country_of_origin',
            'cargo_value',
            'package_description',
            'un_number',
            'dg_class',
            'packing_group',
            'total_pkgs_text',
            'declared_value'
        ], $data) || $saved;
    }

    $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_parties', [
        'forwarding_agent',
        'destination_agent',
        'destination_agent_address',
        'destination_agent_phone',
        'destination_agent_email',
        'shipper_ref'
    ], $data) || $saved;

    $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_financials', [
        'ocean_freight',
        'local_charges',
        'doc_fee',
        'other_charges',
        'freight_in_words',
        'destination_charges',
        'tariff_item_no',
        'lc_number',
        'invoice_no',
        'packing_list_no',
        'co_no',
        'customs_no',
        'insured_by',
        'insurance_policy',
        'insurance_value',
        'total_freight'
    ], $data) || $saved;

    $saved = upsertShipmentDetail($pdo, $shipmentId, 'shipment_documents', [
        'bl_type',
        'num_originals',
        'place_of_issue',
        'date_of_issue',
        'declaration',
        'signatory',
        'designation',
        'special_instructions',
        'comments',
        'originals_release_at',
        'freight_payable_at',
        'no_of_original_bl',
        'issue_place',
        'issue_date'
    ], $data) || $saved;

    return $saved;
}

function enrichShipmentWithDetails(PDO $pdo, array $shipment): array
{
    $shipmentId = $shipment['id'] ?? null;
    if (!$shipmentId) {
        return $shipment;
    }

    $detailTables = [
        'shipment_shippers' => ['shipper_name', 'shipper_email', 'shipper_phone', 'shipper_address', 'shipper_country', 'shipper_port'],
        'shipment_consignees' => ['consignee_name', 'consignee_email', 'consignee_phone', 'consignee_address', 'consignee_tax_id', 'notify_name', 'notify_phone', 'notify_address', 'notify_same_as_consignee', 'consignee_city', 'consignee_country'],
        'shipment_vessels' => ['vessel_name', 'voyage_no', 'vessel_flag', 'imo_number', 'carrier', 'carrier_reference_no', 'pre_carriage', 'place_receipt', 'place_of_delivery', 'seal_no', 'mode_load_area', 'mode_discharge_area', 'departure_time', 'pickup_date', 'pickup_time', 'final_destination', 'vir_number', 'igm_number', 'igm_date', 'vir_confirmation_date', 'vessel_status', 'berthed_at', 'last_port_of_call', 'exp_date_of_arrival', 'exp_time_of_arrival', 'exp_departure_date', 'exp_departure_time', 'berthing_date', 'sailing_date', 'vir_launched_by'],
        'shipment_transport' => ['vessel_flag', 'imo_number', 'carrier', 'carrier_reference_no', 'pre_carriage', 'place_receipt', 'final_destination', 'departure_time', 'pickup_date', 'pickup_time', 'seal_no', 'place_of_delivery', 'mode_load_area', 'mode_discharge_area'],
        'shipment_routes' => ['pol', 'pod', 'origin', 'destination', 'etd', 'eta', 'pickup_date', 'pickup_time', 'mode', 'type_of_shipment', 'freight_terms', 'payment_mode', 'currency', 'incoterms'],
        'shipment_cargo' => ['package_description', 'un_number', 'dg_class', 'packing_group', 'total_pkgs_text', 'declared_value'],
        'shipment_cargo_details' => ['goods_description', 'hs_code', 'commodity_type', 'num_packages', 'package_type', 'gross_weight', 'net_weight', 'volume_cbm', 'package_dimensions', 'marks_numbers', 'country_of_origin', 'cargo_value', 'package_description', 'un_number', 'dg_class', 'packing_group', 'total_pkgs_text', 'declared_value', 'bl_type', 'delivery_mode', 'stuffing_status', 'via_port', 'plot_number'],
        'shipment_parties' => ['forwarding_agent', 'destination_agent', 'destination_agent_address', 'destination_agent_phone', 'destination_agent_email', 'shipper_ref'],
        'shipment_financials' => ['ocean_freight', 'local_charges', 'doc_fee', 'other_charges', 'freight_in_words', 'destination_charges', 'tariff_item_no', 'lc_number', 'invoice_no', 'packing_list_no', 'co_no', 'customs_no', 'insured_by', 'insurance_policy', 'insurance_value', 'total_freight'],
        'shipment_documents' => ['bl_type', 'num_originals', 'place_of_issue', 'date_of_issue', 'declaration', 'signatory', 'designation', 'special_instructions', 'comments', 'originals_release_at', 'freight_payable_at', 'no_of_original_bl', 'issue_place', 'issue_date'],
        'shipment_charges' => ['tracking_number', 'charge_type', 'charge_category', 'description', 'amount', 'status', 'expiry_date', 'delay_days', 'remarks']
    ];

    foreach ($detailTables as $table => $fields) {
        if ($table === 'shipment_cargo_details') {
            // fetch all cargo rows and aggregate totals
            $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE shipment_id = ? ORDER BY id ASC");
            $stmt->execute([$shipmentId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) continue;

            // sum numeric totals
            $totalGross = 0;
            $totalNet = 0;
            $totalPkgs = 0;
            foreach ($rows as $r) {
                $totalGross += isset($r['gross_weight']) ? (float)$r['gross_weight'] : 0;
                $totalNet += isset($r['net_weight']) ? (float)$r['net_weight'] : 0;
                $totalPkgs += isset($r['num_packages']) ? (int)$r['num_packages'] : 0;
            }

            // set aggregated fields on shipment for listing usage
            $shipment['gross_weight'] = $totalGross > 0 ? (string)$totalGross : ($shipment['gross_weight'] ?? null);
            $shipment['net_weight'] = $totalNet > 0 ? (string)$totalNet : ($shipment['net_weight'] ?? null);
            $shipment['num_packages'] = $totalPkgs > 0 ? (string)$totalPkgs : ($shipment['num_packages'] ?? null);

            // also populate individual fields from first cargo row if not already present
            $detail = $rows[0];
            foreach ($fields as $field) {
                if (($shipment[$field] ?? null) === null || $shipment[$field] === '') {
                    $shipment[$field] = $detail[$field] ?? null;
                }
            }
            // attach full cargo rows for clients that need details
            $shipment['cargo_rows'] = $rows;
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE shipment_id = ? LIMIT 1");
            $stmt->execute([$shipmentId]);
            $detail = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$detail) {
                continue;
            }

            foreach ($fields as $field) {
                if (($shipment[$field] ?? null) === null || $shipment[$field] === '') {
                    $shipment[$field] = $detail[$field] ?? null;
                }
            }
        }
    }

    return $shipment;
}

function createShipment(PDO $pdo, array $data): string
{
    $tracking_number = generateTrackingNumber();
    $userContext = getCurrentUserContext();

    if ($userContext['role'] === 'agent' && $userContext['email'] !== '') {
        $data['destination_agent_email'] = $userContext['email'];
    }

    if (isset($data['status'])) {
        $norm = normalizeStatus($data['status']);
        if ($norm === null) {
            unset($data['status']);
        } else {
            $data['status'] = $norm;
        }
    }

    $containerEntries = extractContainerEntries($data);
    if (!empty($containerEntries)) {
        $firstContainer = $containerEntries[0];
        foreach (['container_no', 'container_type', 'seal_no', 'origin', 'destination'] as $field) {
            if (array_key_exists($field, $data) && is_array($data[$field])) {
                $data[$field] = $firstContainer[$field] ?? null;
            }
        }
    }

    $fields = [
        'booking_no',
        'company',
        'export_reference',
        'current_location',
        'status',
        'estimated_delivery',
        'actual_delivery'
    ];

    $insertFields = ['tracking_number'];
    $placeholders = ['?'];
    $values = [$tracking_number];

    foreach ($fields as $field) {
        if (array_key_exists($field, $data) && $data[$field] !== '') {
            $insertFields[] = $field;
            $placeholders[] = '?';
            $values[] = $data[$field];
        }
    }

    $pdo->beginTransaction();
    try {
        $sql = "INSERT INTO shipments (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        $shipmentId = (int)$pdo->lastInsertId();

        // insert containers first and capture their IDs to link cargo rows
        $containerIds = [];
        if (!empty($containerEntries)) {
            $containerStmt = $pdo->prepare("INSERT INTO shipment_containers (shipment_id, container_no, container_type, seal_no, origin, destination, iso_code, gross_weight, net_weight, soc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($containerEntries as $container) {
                $containerStmt->execute([
                    $shipmentId,
                    $container['container_no'] ?? null,
                    $container['container_type'] ?? null,
                    $container['seal_no'] ?? null,
                    $container['origin'] ?? null,
                    $container['destination'] ?? null,
                    $container['iso_code'] ?? null,
                    $container['gross_weight'] ?? null,
                    $container['net_weight'] ?? null,
                    $container['soc'] ?? 'N'
                ]);
                $containerIds[] = (int)$pdo->lastInsertId();
            }
        }

        // save detail groups (pass container ids mapping)
        saveShipmentDetailGroups($pdo, $shipmentId, $data, $containerIds);

        if (!empty($data['item_desc']) && is_array($data['item_desc'])) {
            $itemStmt = $pdo->prepare("INSERT INTO shipment_items (shipment_id, item_description, quantity, weight, cbm, unit_value) VALUES (?, ?, ?, ?, ?, ?)");

            foreach ($data['item_desc'] as $i => $desc) {
                if (empty($desc)) continue;
                $itemStmt->execute([
                    $shipmentId,
                    $desc,
                    $data['item_qty'][$i] ?? 1,
                    $data['item_weight'][$i] ?? null,
                    $data['item_cbm'][$i] ?? null,
                    $data['item_value'][$i] ?? null
                ]);
            }
        }

        addTrackingHistory($pdo, $tracking_number, 'pending', $data['current_location'] ?? 'Origin', 'Shipment created');
        $pdo->commit();

        return $tracking_number;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updateShipment(PDO $pdo, int $id, array $data): bool
{
    try {
        $userContext = getCurrentUserContext();

        if ($userContext['role'] === 'staagentff' && $userContext['email'] !== '') {
            $data['destination_agent_email'] = $userContext['email'];
        }

        $current = $pdo->prepare("SELECT tracking_number, current_location FROM shipments WHERE id = ?");
        $current->execute([$id]);
        $currentRow = $current->fetch(PDO::FETCH_ASSOC);

        if (!$currentRow) return false;

        $trackingNumber = $currentRow['tracking_number'];
        if (isset($data['status'])) {
            $norm = normalizeStatus($data['status']);
            if ($norm === null) {
                unset($data['status']);
            } else {
                $data['status'] = $norm;
            }
        }
        $isStatusUpdate = array_key_exists('status', $data);

        $containerEntries = extractContainerEntries($data);
        if (!empty($containerEntries)) {
            $firstContainer = $containerEntries[0];
            foreach (['container_no', 'container_type', 'seal_no', 'origin', 'destination'] as $field) {
                if (array_key_exists($field, $data) && is_array($data[$field])) {
                    $data[$field] = $firstContainer[$field] ?? null;
                }
            }
        }

        $allowedFields = [
            'company',
            'booking_no',
            'current_location',
            'status',
            'export_reference',
            'estimated_delivery',
            'actual_delivery'
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '') {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        $pdo->beginTransaction();
        try {
            $updated = false;
            if (!empty($fields)) {
                $sql = "UPDATE shipments SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $updated = $stmt->rowCount() > 0;
            }

            // replace containers first so we can capture their new IDs and link cargo rows
            $containerIds = [];
            if (!empty($containerEntries)) {
                $deleteContainerStmt = $pdo->prepare("DELETE FROM shipment_containers WHERE shipment_id = ?");
                $deleteContainerStmt->execute([$id]);

                $containerStmt = $pdo->prepare("INSERT INTO shipment_containers (shipment_id, container_no, container_type, seal_no, origin, destination, iso_code, gross_weight, net_weight, soc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($containerEntries as $container) {
                    $containerStmt->execute([
                        $id,
                        $container['container_no'] ?? null,
                        $container['container_type'] ?? null,
                        $container['seal_no'] ?? null,
                        $container['origin'] ?? null,
                        $container['destination'] ?? null,
                        $container['iso_code'] ?? null,
                        $container['gross_weight'] ?? null,
                        $container['net_weight'] ?? null,
                        $container['soc'] ?? 'N'
                    ]);
                    $containerIds[] = (int)$pdo->lastInsertId();
                }
            }

            $detailsUpdated = saveShipmentDetailGroups($pdo, $id, $data, $containerIds);

            if ($updated || $detailsUpdated || $isStatusUpdate) {
                if ($isStatusUpdate) {
                    $newLocation = $data['current_location'] ?? $currentRow['current_location'] ?? 'Unknown';
                    addTrackingHistory($pdo, $trackingNumber, $data['status'], $newLocation, 'Status updated');
                }
            }

            if (!empty($data['item_desc']) && is_array($data['item_desc'])) {
                $deleteStmt = $pdo->prepare("DELETE FROM shipment_items WHERE shipment_id = ?");
                $deleteStmt->execute([$id]);

                $itemStmt = $pdo->prepare("INSERT INTO shipment_items (shipment_id, item_description, quantity, weight, cbm, unit_value) VALUES (?, ?, ?, ?, ?, ?)");

                foreach ($data['item_desc'] as $i => $desc) {
                    if (empty($desc)) continue;
                    $itemStmt->execute([
                        $id,
                        $desc,
                        $data['item_qty'][$i] ?? 1,
                        $data['item_weight'][$i] ?? null,
                        $data['item_cbm'][$i] ?? null,
                        $data['item_value'][$i] ?? null
                    ]);
                }
            }

            $pdo->commit();
            return $updated || $detailsUpdated || $isStatusUpdate || !empty($data['item_desc']);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (PDOException $e) {
        return false;
    }
}

function deleteShipment(PDO $pdo, int $id): bool
{
    try {
        $stmt = $pdo->prepare("DELETE FROM shipments WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function updateShipmentStatus(PDO $pdo, string $tracking_number, string $status, string $location, string $remarks = ''): bool
{
    // Update shipment status
    $stmt = $pdo->prepare("UPDATE shipments SET status = ?, current_location = ? WHERE tracking_number = ?");
    $stmt->execute([$status, $location, $tracking_number]);

    // Add tracking history
    addTrackingHistory($pdo, $tracking_number, $status, $location, $remarks);

    return true;
}

function addTrackingHistory(PDO $pdo, string $tracking_number, string $status, string $location, string $remarks = ''): bool
{
    $stmt = $pdo->prepare("INSERT INTO tracking_history (tracking_number, status, location, remarks) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$tracking_number, $status, $location, $remarks]);
}

function getDashboardStats(PDO $pdo): array
{
    $stats = [];
    $userContext = getCurrentUserContext();
    $shipmentConditions = [];
    $shipmentParams = [];

    [$staffCondition, $staffParams] = getStaffDestinationAgentEmailCondition($pdo);
    if ($staffCondition !== '') {
        $shipmentConditions[] = $staffCondition;
        $shipmentParams = array_merge($shipmentParams, $staffParams);
    }

    $shipmentWhere = empty($shipmentConditions) ? ' WHERE 1=1' : ' WHERE ' . implode(' AND ', $shipmentConditions);

    $shipmentCountSql = "SELECT COUNT(*) as count FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id" . $shipmentWhere;
    $stmt = $pdo->prepare($shipmentCountSql);
    $stmt->execute($shipmentParams);
    $stats['total_shipments'] = (int)($stmt->fetch()['count'] ?? 0);

    $statusSql = "SELECT s.status, COUNT(*) as count FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id" . $shipmentWhere . " GROUP BY s.status";
    $stmt = $pdo->prepare($statusSql);
    $stmt->execute($shipmentParams);
    foreach ($stmt->fetchAll() as $row) {
        $stats['status'][$row['status']] = (int)$row['count'];
    }

    $deliveredTodaySql = "SELECT COUNT(*) as count FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id" . $shipmentWhere . " AND s.status = 'delivered' AND DATE(s.updated_at) = CURDATE()";
    $stmt = $pdo->prepare($deliveredTodaySql);
    $stmt->execute($shipmentParams);
    $stats['delivered_today'] = (int)($stmt->fetch()['count'] ?? 0);

    $pendingSql = "SELECT COUNT(*) as count FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id" . $shipmentWhere . " AND s.status NOT IN ('delivered', 'cancelled')";
    $stmt = $pdo->prepare($pendingSql);
    $stmt->execute($shipmentParams);
    $stats['pending'] = (int)($stmt->fetch()['count'] ?? 0);

    $recentSql = "SELECT COUNT(*) as count FROM shipments s LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id" . $shipmentWhere . " AND s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt = $pdo->prepare($recentSql);
    $stmt->execute($shipmentParams);
    $stats['recent_7days'] = (int)($stmt->fetch()['count'] ?? 0);

    $arrivalNoticeParams = [];
    $arrivalNoticeSql = "SELECT COUNT(*) as count FROM arrival_notices WHERE 1=1";
    if ($userContext['role'] === 'agent' && $userContext['user_id'] !== null) {
        $arrivalNoticeSql .= " AND staff_user_id = ?";
        $arrivalNoticeParams[] = $userContext['user_id'];
    }
    $stmt = $pdo->prepare($arrivalNoticeSql);
    $stmt->execute($arrivalNoticeParams);
    $stats['arrival_notices_total'] = (int)($stmt->fetch()['count'] ?? 0);

    $chargeSql = "SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(CASE WHEN sc.status = 'paid' THEN 1 ELSE 0 END), 0) AS paid_count,
            COALESCE(SUM(CASE WHEN sc.status != 'paid' THEN 1 ELSE 0 END), 0) AS pending_count,
            COALESCE(SUM(CASE WHEN sc.status = 'paid' THEN sc.amount ELSE 0 END), 0) AS paid_amount,
            COALESCE(SUM(CASE WHEN sc.status != 'paid' THEN sc.amount ELSE 0 END), 0) AS pending_amount
        FROM shipment_charges sc
        INNER JOIN shipments s ON sc.shipment_id = s.id
        LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id
        WHERE 1=1";
    $chargeParams = [];
    [$chargeCondition, $chargeConditionParams] = getStaffDestinationAgentEmailCondition($pdo);
    if ($chargeCondition !== '') {
        $chargeSql .= " AND $chargeCondition";
        $chargeParams = array_merge($chargeParams, $chargeConditionParams);
    }
    $stmt = $pdo->prepare($chargeSql);
    $stmt->execute($chargeParams);
    $chargeRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['shipment_charges_total'] = (int)($chargeRow['total_count'] ?? 0);
    $stats['shipment_charges_paid'] = (int)($chargeRow['paid_count'] ?? 0);
    $stats['shipment_charges_pending'] = (int)($chargeRow['pending_count'] ?? 0);
    $stats['shipment_charges_total_amount'] = round((float)($chargeRow['paid_amount'] ?? 0) + (float)($chargeRow['pending_amount'] ?? 0), 2);
    $stats['shipment_charges_paid_amount'] = round((float)($chargeRow['paid_amount'] ?? 0), 2);
    $stats['shipment_charges_pending_amount'] = round((float)($chargeRow['pending_amount'] ?? 0), 2);

    $invoiceSql = "SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(COALESCE(i.total_amount, 0)), 0) AS total_amount,
            COALESCE(SUM(CASE WHEN COALESCE(payments.paid_amount, 0) >= COALESCE(i.total_amount, 0) THEN 1 ELSE 0 END), 0) AS paid_count,
            COALESCE(SUM(COALESCE(payments.paid_amount, 0)), 0) AS paid_amount,
            COALESCE(SUM(CASE WHEN COALESCE(payments.paid_amount, 0) < COALESCE(i.total_amount, 0) THEN COALESCE(i.total_amount, 0) - COALESCE(payments.paid_amount, 0) ELSE 0 END), 0) AS pending_amount
        FROM invoices i
        LEFT JOIN (
            SELECT invoice_id, SUM(COALESCE(amount, 0)) AS paid_amount
            FROM invoice_payments
            GROUP BY invoice_id
        ) payments ON payments.invoice_id = i.id
        LEFT JOIN shipments s ON i.shipment_id = s.id
        LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id
        WHERE 1=1";
    $invoiceParams = [];
    if ($userContext['role'] === 'agent') {
        $invoiceSql .= " AND (i.created_by = ?";
        $invoiceParams[] = $userContext['user_id'];
        [$invoiceCondition, $invoiceConditionParams] = getStaffDestinationAgentEmailCondition($pdo);
        if ($invoiceCondition !== '') {
            $invoiceSql .= " OR $invoiceCondition";
            $invoiceParams = array_merge($invoiceParams, $invoiceConditionParams);
        }
        $invoiceSql .= ")";
    }
    $stmt = $pdo->prepare($invoiceSql);
    $stmt->execute($invoiceParams);
    $invoiceRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['invoices_total'] = (int)($invoiceRow['total_count'] ?? 0);
    $stats['invoices_total_amount'] = round((float)($invoiceRow['total_amount'] ?? 0), 2);
    $stats['invoices_paid'] = (int)($invoiceRow['paid_count'] ?? 0);
    $stats['invoices_paid_amount'] = round((float)($invoiceRow['paid_amount'] ?? 0), 2);
    $stats['invoices_pending_amount'] = round((float)($invoiceRow['pending_amount'] ?? 0), 2);

    $cargoSql = "SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(CASE WHEN COALESCE(cr.cargo_release_status, 'pending') = 'locked' THEN 1 ELSE 0 END), 0) AS locked_count,
            COALESCE(SUM(CASE WHEN COALESCE(cr.cargo_release_status, 'pending') = 'released' THEN 1 ELSE 0 END), 0) AS released_count
        FROM shipments s
        LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id
        LEFT JOIN cargo_release_records cr ON cr.shipment_id = s.id
        WHERE 1=1";
    $cargoParams = [];
    [$cargoCondition, $cargoConditionParams] = getStaffDestinationAgentEmailCondition($pdo);
    if ($cargoCondition !== '') {
        $cargoSql .= " AND $cargoCondition";
        $cargoParams = array_merge($cargoParams, $cargoConditionParams);
    }
    $stmt = $pdo->prepare($cargoSql);
    $stmt->execute($cargoParams);
    $cargoRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['cargo_locked'] = (int)($cargoRow['locked_count'] ?? 0);
    $stats['cargo_released'] = (int)($cargoRow['released_count'] ?? 0);

    return $stats;
}

function getRecentActivity(PDO $pdo, int $limit = 10): array
{
    $userContext = getCurrentUserContext();
    $query = "
        SELECT th.*, ss.shipper_name, sc.consignee_name
        FROM tracking_history th
        JOIN shipments s ON th.tracking_number = s.tracking_number
        LEFT JOIN shipment_shippers ss ON ss.shipment_id = s.id
        LEFT JOIN shipment_consignees sc ON sc.shipment_id = s.id
        LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id
        WHERE 1=1
    ";
    $params = [];

    [$recentCondition, $recentConditionParams] = getStaffDestinationAgentEmailCondition($pdo);
    if ($recentCondition !== '') {
        $query .= " AND $recentCondition";
        $params = array_merge($params, $recentConditionParams);
    }

    $limit = max(1, (int)$limit);
    $query .= " ORDER BY th.created_at DESC LIMIT {$limit}";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}