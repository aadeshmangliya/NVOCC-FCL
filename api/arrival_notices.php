<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/admin_security.php';
require_once '../config/env.php';
require_once '../config/mail.php';

global $pdo;
function getCurrentArrivalNoticeContext(): array
{
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'role' => strtolower(trim((string)($_SESSION['role'] ?? 'admin'))),
        'name' => trim((string)($_SESSION['username'] ?? '')),
        'email' => strtolower(trim((string)($_SESSION['email'] ?? ''))),
    ];
}

function getShipmentsForArrival(PDO $pdo): array
{
    $context = getCurrentArrivalNoticeContext();
    $query = "SELECT s.id, s.tracking_number, ss.shipper_name, sc.consignee_name, sc.consignee_email, sr.eta, s.actual_delivery, s.estimated_delivery, s.created_at 
              FROM shipments s 
              LEFT JOIN shipment_parties sp ON sp.shipment_id = s.id
              LEFT JOIN shipment_shippers ss ON ss.shipment_id = s.id
              LEFT JOIN shipment_consignees sc ON sc.shipment_id = s.id
              LEFT JOIN shipment_routes sr ON sr.shipment_id = s.id
              LEFT JOIN arrival_notices an ON s.id = an.shipment_id 
              WHERE an.id IS NULL";
    $params = [];

    if ($context['role'] === 'agent' && $context['email'] !== '') {
        $query .= " AND LOWER(COALESCE(sp.destination_agent_email, '')) = ?";
        $params[] = $context['email'];
    }

    $query .= " ORDER BY s.created_at DESC LIMIT 200";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getArrivalNotices(PDO $pdo): array
{
    $context = getCurrentArrivalNoticeContext();
    $query = "SELECT * FROM arrival_notices WHERE 1=1";
    $params = [];

    if ($context['role'] === 'agent') {
        $query .= " AND staff_user_id = ?";
        $params[] = $context['user_id'];
    }

    $query .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSentArrivalNoticeLogs(PDO $pdo): array
{
    $context = getCurrentArrivalNoticeContext();
    $query = "SELECT * FROM arrival_notices WHERE status = 'sent'";
    $params = [];

    if ($context['role'] === 'agent') {
        $query .= " AND staff_user_id = ?";
        $params[] = $context['user_id'];
    }

    $query .= " ORDER BY sent_at DESC, created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createOrUpdateArrivalNotice(PDO $pdo, array $payload): array
{
    $context = getCurrentArrivalNoticeContext();

    // Check if shipment already has an arrival notice (only for new records)
    if (empty($payload['id']) && !empty($payload['shipment_id'])) {
        $checkStmt = $pdo->prepare("SELECT id FROM arrival_notices WHERE shipment_id = ? LIMIT 1");
        $checkStmt->execute([$payload['shipment_id']]);
        if ($checkStmt->fetch()) {
            throw new Exception('This shipment already has an arrival notice. Only one arrival notice per shipment is allowed.');
        }
    }

    $data = [
        'staff_user_id' => $context['user_id'],
        'staff_name' => $context['name'],
        'staff_email' => $context['email'],
        'shipment_id' => $payload['shipment_id'] ?? null,
        'tracking_number' => $payload['tracking_number'] ?? null,
        'consignee_name' => $payload['consignee_name'] ?? null,
        'consignee_email' => $payload['consignee_email'] ?? null,
        'arrival_date' => $payload['arrival_date'] ?? null,
        'free_days_allowed' => $payload['free_days_allowed'] ?? 0,
        'notice_message' => $payload['notice_message'] ?? null,
    ];

    if (!empty($payload['id']) && is_numeric($payload['id'])) {
        $id = (int)$payload['id'];
        $stmt = $pdo->prepare("UPDATE arrival_notices SET staff_user_id = ?, staff_name = ?, staff_email = ?, shipment_id = ?, tracking_number = ?, consignee_name = ?, consignee_email = ?, arrival_date = ?, free_days_allowed = ?, notice_message = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([
            $data['staff_user_id'],
            $data['staff_name'],
            $data['staff_email'],
            $data['shipment_id'],
            $data['tracking_number'],
            $data['consignee_name'],
            $data['consignee_email'],
            $data['arrival_date'],
            $data['free_days_allowed'],
            $data['notice_message'],
            $id,
        ]);
        return ['id' => $id, 'created' => false];
    }

    $stmt = $pdo->prepare("INSERT INTO arrival_notices (staff_user_id, staff_name, staff_email, shipment_id, tracking_number, consignee_name, consignee_email, arrival_date, free_days_allowed, notice_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['staff_user_id'],
        $data['staff_name'],
        $data['staff_email'],
        $data['shipment_id'],
        $data['tracking_number'],
        $data['consignee_name'],
        $data['consignee_email'],
        $data['arrival_date'],
        $data['free_days_allowed'],
        $data['notice_message'],
    ]);
    return ['id' => (int)$pdo->lastInsertId(), 'created' => true];
}

function getCompanyBrandingForArrivalNotice(PDO $pdo, array $notice): array
{
    $companyId = null;

    if (!empty($notice['company_id']) && is_numeric($notice['company_id'])) {
        $companyId = (int)$notice['company_id'];
    } elseif (!empty($notice['shipment_id'])) {
        $shipmentStmt = $pdo->prepare("SELECT company FROM shipments WHERE id = ? LIMIT 1");
        $shipmentStmt->execute([$notice['shipment_id']]);
        $companyId = (int)$shipmentStmt->fetchColumn();
    }

    if ($companyId > 0) {
        $companyStmt = $pdo->prepare("SELECT display_name, logo_path, color_primary, color_secondary, website FROM companies WHERE id = ? AND is_active = 1 LIMIT 1");
        $companyStmt->execute([$companyId]);
        $company = $companyStmt->fetch(PDO::FETCH_ASSOC);

        if ($company) {
            return [
                'display_name' => $company['display_name'] ?: 'Shipping',
                'logo_path' => $company['logo_path'] ?: '',
                'color_primary' => $company['color_primary'] ?: '#FF883A',
                'color_secondary' => $company['color_secondary'] ?: '#137F78',
                'website' => $company['website'] ?: 'https://royalgulfshipping.com',
            ];
        }
    }

    return [
        'display_name' => 'Shipping',
        'logo_path' => '',
        'color_primary' => '#FF883A',
        'color_secondary' => '#137F78',
        'website' => 'https://royalgulfshipping.com',
    ];
}

function sendArrivalNoticeEmail(PDO $pdo, array $notice): bool
{
    if (empty($notice['consignee_email'])) {
        return false;
    }

    $branding = getCompanyBrandingForArrivalNotice($pdo, $notice);
    $companyName = htmlspecialchars($branding['display_name'], ENT_QUOTES, 'UTF-8');
    $companyWebsite = htmlspecialchars($branding['website'], ENT_QUOTES, 'UTF-8');
    $primaryColor = htmlspecialchars($branding['color_primary'], ENT_QUOTES, 'UTF-8');
    $secondaryColor = htmlspecialchars($branding['color_secondary'], ENT_QUOTES, 'UTF-8');
    $logoHtml = '';

    if (!empty($branding['logo_path'])) {
        $logoSrc = htmlspecialchars($branding['logo_path'], ENT_QUOTES, 'UTF-8');
        $logoHtml = '<img src="' . $logoSrc . '" alt="' . $companyName . '" style="max-height:54px;max-width:220px;display:block;margin:0 auto 14px;" />';
    }

    $subject = 'Arrival Notice for Shipment ' . ($notice['tracking_number'] ?: 'N/A');

    // HTML email template with company-specific branding
    $message = <<<HTML
<html lang="en">
<body style="margin:0; padding:0; background-color:#f5f7fa; font-family: Arial, Helvetica, sans-serif;">

<table align="center" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; margin:0 auto; background-color:#ffffff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
    <tr>
        <td style="padding:0;">
            <!-- ========== HEADER ========== -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:{$primaryColor}; border-radius:8px 8px 0 0;">
                <tr>
                    <td style="padding:40px 20px; text-align:center; color:#ffffff;">
                        {$logoHtml}
                        <h1 style="margin:10px 0 4px 0; font-size:28px; font-weight:600; color:#ffffff;">{$companyName}</h1>
                        <p style="margin:10px 0 0 0; font-size:14px; opacity:0.9; color:#ffffff;">Shipment Arrival Notice</p>
                    </td>
                </tr>
            </table>

            <!-- ========== CONTENT ========== -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="padding:40px 30px;">
                <!-- Greeting -->
                <tr>
                    <td style="padding:0 0 10px 0; font-size:15px; color:#333333; line-height:1.6;">
                        Dear <strong>{$notice['consignee_name']}</strong>,
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 0 16px 0; font-size:15px; color:#333333; line-height:1.6;">
                        We are pleased to inform you that your shipment has arrived at the destination port.
                    </td>
                </tr>

                <!-- Notice Box -->
                <tr>
                    <td style="padding:0 0 16px 0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f8f9fa; border:2px solid {$primaryColor}; border-radius:8px; padding:25px;">
                            <tr>
                                <td>
                                    <h3 style="color:{$primaryColor}; margin:0 0 15px 0; font-size:18px;">Shipment Details</h3>
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f0f0f0; border-left:4px solid {$secondaryColor}; padding:15px; border-radius:4px;">
                                        <tr>
                                            <td style="padding:6px 0;">
                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                    <tr>
                                                        <td style="font-weight:600; color:{$secondaryColor}; font-size:14px;">Tracking Number:</td>
                                                        <td style="text-align:right; color:#333333; font-size:14px; font-weight:bold;">{$notice['tracking_number']}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;">
                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                    <tr>
                                                        <td style="font-weight:600; color:{$secondaryColor}; font-size:14px;">Arrival Date:</td>
                                                        <td style="text-align:right; color:#333333; font-size:14px;">{$notice['arrival_date']}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
HTML;

    // Conditional block for free days
    if (!empty($notice['free_days_allowed'])) {
        $message .= <<<HTML
                                        <tr>
                                            <td style="padding:6px 0;">
                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                    <tr>
                                                        <td style="font-weight:600; color:{$secondaryColor}; font-size:14px;">Free Days Allowed:</td>
                                                        <td style="text-align:right; color:#333333; font-size:14px; font-weight:bold;">{$notice['free_days_allowed']} days</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
HTML;
    }
    $message .= <<<HTML
         
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- Info Box -->
                <tr>
                    <td style="padding:0 0 16px 0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF5EB; border-left:4px solid {$primaryColor}; padding:15px; border-radius:4px;">
                            <tr>
                                <td>
                                    <p style="margin:0; font-size:13px; color:#CC6A1A;"><strong>Important Dates:</strong> Please note the free days allowed for cargo storage. Storage charges may apply after the free period expires.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
HTML;

    // Additional Info block
    if (!empty($notice['notice_message'])) {
        $message .= <<<HTML
                <tr>
                    <td style="padding:0 0 16px 0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#E8F5E9; border-left:4px solid {$secondaryColor}; padding:15px; border-radius:4px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 4px 0; font-size:13px; color:#0D6B62;"><strong>Additional Information:</strong></p>
                                    <p style="margin:0; font-size:13px; color:#0D6B62;"> 
HTML;
        $message .= nl2br(htmlspecialchars($notice['notice_message']));
        $message .= <<<HTML
                                          </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
HTML;
    }

    $message .= <<<HTML

                <!-- Divider -->
                <tr>
                    <td style="padding:10px 0 20px 0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="height:1px; background-color:#eeeeee;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Closing -->
                <tr>
                    <td style="font-size:15px; color:#333333; line-height:1.8;">
                        <p style="margin:0 0 20px 0;">If you have any questions or need further assistance, please don't hesitate to contact us.</p>
                        <p style="margin:0;">
                            Best regards,<br>
                            <span style="color:{$primaryColor}; font-weight:600;">{$companyName}</span><br>
                            Your Trusted Logistics Partner
                        </p>
                    </td>
                </tr>

            </table>

            <!-- ========== FOOTER ========== -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f8f9fa; border-top:1px solid #eeeeee; border-radius:0 0 8px 8px;">
                <tr>
                    <td style="padding:25px; text-align:center;">
                        <p style="margin:5px 0; font-size:12px; color:#999999;">&copy; 2026 {$companyName}. All rights reserved.</p>
                        <p style="margin:5px 0; font-size:12px; color:#999999;"><a href="{$companyWebsite}" style="color:{$secondaryColor}; text-decoration:none;">Visit our website</a></p>
                        <p style="margin:5px 0; font-size:12px; color:#999999;">This is an automated message. Please do not reply to this email.</p>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
HTML;

    $sent = MailConfig::send($notice['consignee_email'], $subject, $message, true);
    if ($sent) {
        $stmt = $pdo->prepare("UPDATE arrival_notices SET status = 'sent', email_status = 'sent', sent_at = NOW(), send_count = send_count + 1, last_error = NULL WHERE id = ?");
        $stmt->execute([$notice['id']]);
        return true;
    }

    $stmt = $pdo->prepare("UPDATE arrival_notices SET status = 'draft', email_status = 'failed', last_error = 'Email delivery failed' WHERE id = ?");
    $stmt->execute([$notice['id']]);
    return false;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (!empty($_GET['action']) && $_GET['action'] === 'shipments') {
            echo json_encode(['success' => true, 'data' => getShipmentsForArrival($pdo)]);
            exit;
        }

        if (!empty($_GET['action']) && $_GET['action'] === 'logs') {
            echo json_encode(['success' => true, 'data' => getSentArrivalNoticeLogs($pdo)]);
            exit;
        }

        echo json_encode(['success' => true, 'data' => getArrivalNotices($pdo)]);
        exit;
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $payload['action'] ?? 'draft';

        // If resending an existing notice, don't update it, just fetch and send
        if ($action === 'send' && !empty($payload['id']) && empty($payload['shipment_id'])) {
            $noticeId = (int)$payload['id'];
            $noticeStmt = $pdo->prepare("SELECT * FROM arrival_notices WHERE id = ?");
            $noticeStmt->execute([$noticeId]);
            $notice = $noticeStmt->fetch(PDO::FETCH_ASSOC);

            if (!$notice) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Arrival notice not found.']);
                exit;
            }

            $emailSent = sendArrivalNoticeEmail($pdo, $notice);
            $noticeStmt->execute([$noticeId]);
            $notice = $noticeStmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'saved' => true,
                'email_sent' => $emailSent,
                'data' => $notice,
                'message' => $emailSent ? 'Arrival notice sent successfully.' : 'Arrival notice was saved, but the email could not be sent.'
            ]);
            exit;
        }

        $result = createOrUpdateArrivalNotice($pdo, $payload);

        $noticeId = $result['id'];
        $noticeStmt = $pdo->prepare("SELECT * FROM arrival_notices WHERE id = ?");
        $noticeStmt->execute([$noticeId]);
        $notice = $noticeStmt->fetch(PDO::FETCH_ASSOC);

        if ($action === 'send') {
            $emailSent = sendArrivalNoticeEmail($pdo, $notice);
            $noticeStmt->execute([$noticeId]);
            $notice = $noticeStmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'saved' => true,
                'email_sent' => $emailSent,
                'data' => $notice,
                'message' => $emailSent ? 'Arrival notice sent successfully.' : 'Arrival notice was saved, but the email could not be sent.'
            ]);
            exit;
        }

        echo json_encode(['success' => true, 'saved' => true, 'data' => $notice, 'message' => 'Arrival notice saved as draft.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
