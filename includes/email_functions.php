<?php
// Email utility functions for OTP and notifications

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/mail.php';

function generateOTP($length = 6): string
{
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= mt_rand(0, 9);
    }
    return $otp;
}

function getInvoiceBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($scheme . '://' . $host, '/');
}

function formatInvoiceCurrency($amount): string
{
    return 'AED ' . number_format((float)$amount, 2);
}

function resolveCompanyAssetUrl(string $path, string $baseUrl): string
{
    $baseUrl = rtrim($baseUrl, '/');

    if ($path === '') {
        return $baseUrl . '/assets/images/logo/logo_1781775249.png';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $normalizedPath = preg_replace('#^/+(?:[A-Za-z0-9_-]+/)?#', '/', $path);

    if (strpos($path, '/') === 0) {
        return $baseUrl . $normalizedPath;
    }

    return $baseUrl . '/' . ltrim($normalizedPath, '/');
}

function getCompanyBrandingForInvoice(PDO $pdo, array $invoice): array
{
    $companyId = null;

    if (!empty($invoice['company_id']) && is_numeric($invoice['company_id'])) {
        $companyId = (int)$invoice['company_id'];
    } elseif (!empty($invoice['shipment_id'])) {
        $shipmentStmt = $pdo->prepare('SELECT company FROM shipments WHERE id = ? LIMIT 1');
        $shipmentStmt->execute([(int)$invoice['shipment_id']]);
        $companyId = (int)$shipmentStmt->fetchColumn();
    }

    if ($companyId > 0) {
        $companyStmt = $pdo->prepare('SELECT display_name, logo_path, color_primary, color_secondary, website, carrier_name FROM companies WHERE id = ? AND is_active = 1 LIMIT 1');
        $companyStmt->execute([$companyId]);
        $company = $companyStmt->fetch(PDO::FETCH_ASSOC);

        if ($company) {
            return [
                'display_name' => $company['display_name'] ?: 'Royal Gulf Shipping & Logistics LLC',
                'logo_path' => $company['logo_path'] ?: '',
                'color_primary' => $company['color_primary'] ?: '#137F78',
                'color_secondary' => $company['color_secondary'] ?: '#0f625d',
                'website' => $company['website'] ?: 'https://royalgulfshipping.com',
                'carrier_name' => $company['carrier_name'] ?: ($company['display_name'] ?: 'Royal Gulf Shipping & Logistics LLC'),
            ];
        }
    }

    return [
        'display_name' => 'Royal Gulf Shipping & Logistics LLC',
        'logo_path' => '',
        'color_primary' => '#137F78',
        'color_secondary' => '#0f625d',
        'website' => 'https://royalgulfshipping.com',
        'carrier_name' => 'Royal Gulf Shipping & Logistics LLC',
    ];
}

function buildInvoiceHtml(array $invoice, array $lineItems = [], string $baseUrl = '', ?PDO $pdo = null): string
{
    if ($baseUrl === '') {
        $baseUrl = getInvoiceBaseUrl();
    }

    $branding = [
        'display_name' => 'Royal Gulf Shipping & Logistics LLC',
        'logo_path' => '',
        'color_primary' => '#137F78',
        'color_secondary' => '#0f625d',
        'website' => 'https://royalgulfshipping.com',
        'carrier_name' => 'Royal Gulf Shipping & Logistics LLC',
    ];

    if ($pdo instanceof PDO) {
        $branding = getCompanyBrandingForInvoice($pdo, $invoice);
    }

    $logoUrl = resolveCompanyAssetUrl($branding['logo_path'], $baseUrl);
    $companyName = htmlspecialchars($branding['display_name'], ENT_QUOTES, 'UTF-8');
    $companyWebsite = htmlspecialchars($branding['website'], ENT_QUOTES, 'UTF-8');
    $primaryColor = htmlspecialchars($branding['color_primary'], ENT_QUOTES, 'UTF-8');
    $secondaryColor = htmlspecialchars($branding['color_secondary'], ENT_QUOTES, 'UTF-8');
    $carrierName = htmlspecialchars($branding['carrier_name'], ENT_QUOTES, 'UTF-8');
    $invoiceNumber = htmlspecialchars($invoice['invoice_number'] ?? 'INV-XXXX-XXXX');
    $invoiceDate = !empty($invoice['created_at']) ? date('d M Y', strtotime($invoice['created_at'])) : date('d M Y');
    $consigneeName = htmlspecialchars($invoice['consignee_name'] ?? '');
    $consigneeEmail = htmlspecialchars($invoice['consignee_email'] ?? '');
    $consigneeAddress = nl2br(htmlspecialchars($invoice['consignee_address'] ?? ''));
    $consigneeContact = htmlspecialchars($invoice['consignee_contact'] ?? '');
    $trackingNumber = htmlspecialchars($invoice['shipment_tracking_number'] ?? '');
    $containerNumber = htmlspecialchars($invoice['container_number'] ?? '');
    $vesselVoyage = htmlspecialchars($invoice['vessel_voyage'] ?? '');
    $arrivalDate = !empty($invoice['arrival_date']) ? date('d M Y', strtotime($invoice['arrival_date'])) : '-';
    $freeDaysExpiry = !empty($invoice['free_days_expiry']) ? date('d M Y', strtotime($invoice['free_days_expiry'])) : '-';
    $notes = nl2br(htmlspecialchars($invoice['invoice_notes'] ?? 'Payment due within 7 days. Please include Invoice # with payment.'));
    $subtotal = (float)($invoice['subtotal'] ?? 0);
    $globalDiscount = (float)($invoice['global_discount'] ?? 0);
    $taxAmount = (float)($invoice['tax_amount'] ?? 0);
    $grandTotal = (float)($invoice['total_amount'] ?? 0);

    $rows = '';
    if (!empty($lineItems)) {
        foreach ($lineItems as $item) {
            $description = htmlspecialchars($item['description'] ?? '');
            $category = htmlspecialchars($item['category'] ?? '');
            $qty = htmlspecialchars($item['qty_or_days'] ?? 1);
            $rate = formatInvoiceCurrency($item['rate'] ?? 0);
            $amount = formatInvoiceCurrency($item['amount'] ?? 0);
            $rows .= "<tr><td>{$description}</td><td>{$category}</td><td>{$qty}</td><td>{$rate}</td><td>{$amount}</td></tr>";
        }
    } else {
        $rows = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#888;">No charge lines available.</td></tr>';
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {$invoiceNumber}</title>
</head>
<body style="margin:0; padding:10px 10px; background:#f0f4fb; font-family: Arial, Helvetica, sans-serif;">
<table align="center" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:680px; background:#ffffff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.06);">
    <tr>
        <td style="padding:0;">
            <!-- ========== HEADER ========== -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:linear-gradient(135deg, {$primaryColor} 0%, {$secondaryColor} 100%); color:#ffffff; padding:24px 32px; border-radius:12px 12px 0 0;">
                <tr>
                    <td style="padding:24px 28px 20px 28px;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="color:#ffffff; font-size:13px; line-height:1.6; width:60%;">
                                    <h1 style="margin:0 0 4px 0; font-size:26px; font-weight:700; color:#ffffff; letter-spacing:0.5px;">INVOICE</h1>
                                    <div style="opacity:0.85; font-size:13px;">
                                        Invoice # {$invoiceNumber} &nbsp;|&nbsp; Issued on {$invoiceDate}
                                    </div>
                                </td>
                                <td align="right" style="color:#ffffff; font-size:13px;">
                                    <div style="background:rgba(255,255,255,0.1); padding:8px 16px; border-radius:8px; display:inline-block;">
                                        <span style="font-weight:700; font-size:15px;">{$companyName}</span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!-- ========== BODY ========== -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="padding:22px 26px 26px 26px;">
                <!-- PARTY GRID (2 cols) -->
                <tr>
                    <td style="padding:0 0 18px 0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td width="48%" style="background:#f8fafd; border:1px solid #eef2f8; border-radius:10px; padding:14px 16px; vertical-align:top;">
                                    <div style="font-size:11px; font-weight:700; color:#5e6f8d; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Bill To</div>
                                    <div style="font-size:15px; font-weight:700; color:#0b1e33;">{$consigneeName}</div>
                                    <div style="font-size:13px; color:#2c3e58; line-height:1.6;">
                                        {$consigneeEmail}<br>{$consigneeAddress}<br>{$consigneeContact}
                                    </div>
                                </td>
                                <td width="4%">&nbsp;</td>
                                <td width="48%" style="background:#f8fafd; border:1px solid #eef2f8; border-radius:10px; padding:14px 16px; vertical-align:top;">
                                    <div style="font-size:11px; font-weight:700; color:#5e6f8d; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Company</div>
                                    <div style="font-size:15px; font-weight:700; color:#0b1e33;">{$companyName}</div>
                                    <div style="font-size:13px; color:#2c3e58; line-height:1.6;">
                                        Dubai, UAE<br>TRN: 100256789300003
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- META GRID (5 cols) -->
                <tr>
                    <td style="padding:0 0 18px 0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafd; border:1px solid #eef2f8; border-radius:10px; padding:12px 14px;">
                            <tr>
                                <td align="center" style="font-size:11px; font-weight:700; color:#6e7f9b; text-transform:uppercase; letter-spacing:0.3px;">Tracking</td>
                                <td align="center" style="font-size:11px; font-weight:700; color:#6e7f9b; text-transform:uppercase; letter-spacing:0.3px;">Container</td>
                                <td align="center" style="font-size:11px; font-weight:700; color:#6e7f9b; text-transform:uppercase; letter-spacing:0.3px;">Vessel / Voyage</td>
                                <td align="center" style="font-size:11px; font-weight:700; color:#6e7f9b; text-transform:uppercase; letter-spacing:0.3px;">Arrival</td>
                                <td align="center" style="font-size:11px; font-weight:700; color:#6e7f9b; text-transform:uppercase; letter-spacing:0.3px;">Free Days</td>
                            </tr>
                            <tr>
                                <td align="center" style="font-size:14px; font-weight:600; color:#0b1e33; padding-top:4px;">{$trackingNumber}</td>
                                <td align="center" style="font-size:14px; font-weight:600; color:#0b1e33; padding-top:4px;">{$containerNumber}</td>
                                <td align="center" style="font-size:14px; font-weight:600; color:#0b1e33; padding-top:4px;">{$vesselVoyage}</td>
                                <td align="center" style="font-size:14px; font-weight:600; color:#0b1e33; padding-top:4px;">{$arrivalDate}</td>
                                <td align="center" style="font-size:14px; font-weight:600; color:#0b1e33; padding-top:4px;">{$freeDaysExpiry}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- TABLE -->
                <tr>
                    <td style="padding:0 0 18px 0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse; font-size:13px;">
                            <thead>
                                <tr style="background:#f2f6fd; border-bottom:2px solid #dce4ef;">
                                    <th style="text-align:left; padding:12px 12px; font-size:11px; text-transform:uppercase; color:#1e314a; font-weight:700; border-bottom:2px solid #dce4ef;">Description</th>
                                    <th style="text-align:left; padding:12px 12px; font-size:11px; text-transform:uppercase; color:#1e314a; font-weight:700; border-bottom:2px solid #dce4ef;">Category</th>
                                    <th style="text-align:left; padding:12px 12px; font-size:11px; text-transform:uppercase; color:#1e314a; font-weight:700; border-bottom:2px solid #dce4ef;">Qty / Days</th>
                                    <th style="text-align:left; padding:12px 12px; font-size:11px; text-transform:uppercase; color:#1e314a; font-weight:700; border-bottom:2px solid #dce4ef;">Rate</th>
                                    <th style="text-align:right; padding:12px 12px; font-size:11px; text-transform:uppercase; color:#1e314a; font-weight:700; border-bottom:2px solid #dce4ef;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                            {$rows}
                        </tbody>
                        </table>
                    </td>
                </tr>
                <!-- SUMMARY -->
                <tr>
                    <td style="padding:0 0 14px 0;" align="right">
                        <table cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:320px; background:#f9fcff; border:1px solid #e4ebf5; border-radius:12px;">
                            <tr>
                                <td style="padding:10px 18px; border-bottom:1px solid #eef2f8; font-size:14px;">
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tr><td>Subtotal</td><td align="right">{$subtotal}</td></tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 18px; border-bottom:1px solid #eef2f8; font-size:14px; color:#b45309;">
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tr><td>Discount</td><td align="right">- {$globalDiscount}</td></tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 18px; border-bottom:1px solid #eef2f8; font-size:14px;">
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tr><td>Tax</td><td align="right">{$taxAmount}</td></tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:12px 18px; font-size:16px; font-weight:700; background:{$primaryColor}; color:#ffffff; border-radius:0 0 12px 12px;">
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tr><td>Total Due</td><td align="right">{$grandTotal}</td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- NOTES -->
                <tr>
                    <td style="padding:14px 18px; background:#f8fafd; border:1px solid #eef2f8; border-radius:10px; font-size:13px; color:#1f344b; line-height:1.6;">
                        <b style="color:#0b2b5c;">Notes:</b>{$notes}<br>
                    </td>
                </tr>
            </table>
            <!-- ========== FOOTER ========== -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fafcff; border-top:1px solid #ecf1f8; border-radius:0 0 12px 12px;">
                <tr>
                    <td style="padding:16px 26px; text-align:center; font-size:12px; color:#5e6f8d;">
                        This invoice was generated by <a href="{$companyWebsite}" style="color:{$primaryColor}; text-decoration:underline;">{$companyName}</a>. Don't reply to this email.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
HTML;
}

function sendInvoiceEmail(PDO $pdo, array $invoice, array $lineItems = []): bool
{
    $to = filter_var($invoice['consignee_email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$to) {
        return false;
    }

    $baseUrl = getInvoiceBaseUrl();
    $subject = 'Invoice ' . ($invoice['invoice_number'] ?? '') . ' from ' . ($invoice['company_name'] ?? 'Royal Gulf Shipping');
    $message = buildInvoiceHtml($invoice, $lineItems, $baseUrl, $pdo);

    $result = MailConfig::send($to, $subject, $message, true);

    if ($result && !empty($invoice['id'])) {
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'sent', updated_at = NOW() WHERE id = ?");
        $stmt->execute([(int)$invoice['id']]);
    }

    return $result;
}

function buildUserCredentialEmailPreview(string $username, string $email, string $password, bool $useHtml = true): array
{
    $subject = 'Login credentials for Portal';

    // Plain text fallback (no personal greeting)
    $plainText = "Your account has been created.\n\nUsername: $username\nPassword: $password\n\nLogin here: " . getInvoiceBaseUrl() . "/login.php\n\nPlease change your password after your first login.\n";

    if (!$useHtml) {
        return ['subject' => $subject, 'html' => nl2br(htmlspecialchars($plainText, ENT_QUOTES, 'UTF-8'))];
    }

    // Build HTML using provided template, do not show recipient name anywhere except in credentials box
    $baseUrl = getInvoiceBaseUrl();
    $logoSrc = htmlspecialchars(resolveCompanyAssetUrl('/assets/images/logo.png', $baseUrl), ENT_QUOTES, 'UTF-8');
    $escapedUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $escapedPassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
    $loginUrl = htmlspecialchars($baseUrl . '/login.php', ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Credentials</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f7fa;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f7fa; padding: 40px 0;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.08); overflow: hidden; max-width: 100%;">
                    
                    <!-- Header with Logo -->
                    <tr>
                        <td style="background-color: #097D76; padding: 35px 30px 30px 30px; text-align: center;">
                            <img src="{$logoSrc}" 
                                 alt="Company Logo" 
                                 style="max-width: 180px; height: auto; display: block; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 8px;"
                                 onerror="this.style.display='none'">
                            <div style="color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 1px; margin-top: 10px;">Royal Gulf Shipping</div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 40px 35px 30px 35px; background-color: #ffffff;">
                            <h2 style="color: #2d3748; font-size: 24px; font-weight: 700; margin: 0 0 8px 0;">Welcome aboard! 👋</h2>
                            <p style="color: #4a5568; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;">
                                Your account has been created successfully. Below are your login credentials to access the system.
                            </p>

                            <!-- Credentials Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8fafc; border-radius: 10px; border-left: 5px solid #097D76; margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px 25px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding: 6px 0; color: #4a5568; font-size: 15px; font-weight: 600; width: 100px;">Username:</td>
                                                <td style="padding: 6px 0; color: #2d3748; font-size: 15px; font-weight: 700; word-break: break-all;">
                                                    <span style="background-color: #edf2f7; padding: 4px 12px; border-radius: 4px; display: inline-block;">{$escapedUsername}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0; color: #4a5568; font-size: 15px; font-weight: 600;">Password:</td>
                                                <td style="padding: 6px 0; color: #2d3748; font-size: 15px; font-weight: 700; word-break: break-all;">
                                                    <span style="background-color: #edf2f7; padding: 4px 12px; border-radius: 4px; display: inline-block;">{$escapedPassword}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Login Button (Accent Color #F38120) -->
                            <div style="text-align: center; margin: 30px 0 20px 0;">
                                <a href="{$loginUrl}" 
                                   style="display: inline-block; background-color: #F38120; color: #ffffff; text-decoration: none; padding: 14px 50px; border-radius: 50px; font-size: 16px; font-weight: 700; letter-spacing: 0.5px; box-shadow: 0 4px 15px rgba(243, 129, 32, 0.35); transition: all 0.3s ease;">
                                    🔐 Login to Your Account
                                </a>
                            </div>

                            <hr style="border: none; border-top: 2px solid #e2e8f0; margin: 30px 0 20px 0;">

                            <div style="background-color: #fffbeb; border-left: 4px solid #F38120; padding: 15px 20px; border-radius: 6px; margin-bottom: 20px;">
                                <p style="margin: 0; color: #92400e; font-size: 14px; line-height: 1.6;">
                                    <strong>🔒 Security Tip:</strong> For your security, please change your password after your first login. Never share your credentials with anyone.
                                </p>
                            </div>

                            <p style="color: #718096; font-size: 14px; line-height: 1.6; margin: 0 0 5px 0;">
                                If you have any questions or need assistance, please contact our support team.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 35px; text-align: center; border-top: 2px solid #e2e8f0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="color: #a0aec0; font-size: 13px; line-height: 1.8;">
                                        Your Trusted Logistics Partner
                                        <br>
                                        &copy; 2026 All Rights Reserved.
                                        <br>
                                        <span style="color: #718096; font-size: 12px;">This is an automated system-generated email. Please do not reply.</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
                <!-- End Main Container -->
            </td>
        </tr>
    </table>

</body>
</html>
HTML;

    return ['subject' => $subject, 'html' => $html];
}

function sendUserCredentialsEmail(PDO $pdo, string $toEmail, string $username, string $password, bool $isResend = false): bool
{
    $to = filter_var($toEmail, FILTER_VALIDATE_EMAIL);
    if (!$to) {
        return false;
    }

    $preview = buildUserCredentialEmailPreview($username, $to, $password, true);
    $subject = $preview['subject'];
    $message = $preview['html'];

    $sent = MailConfig::send($to, $subject, $message, true);
    return $sent;
}

function sendOTPEmail(PDO $pdo, string $email, string $otp): bool
{
    $to = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$to) {
        return false;
    }

    $branding = getCompanyBrandingForTrackingEmail($pdo, $to);
    $companyName = htmlspecialchars($branding['display_name'], ENT_QUOTES, 'UTF-8');
    $companyWebsite = htmlspecialchars($branding['website'], ENT_QUOTES, 'UTF-8');
    $companyEmail = htmlspecialchars($branding['company_email'], ENT_QUOTES, 'UTF-8');
    $primaryColor = htmlspecialchars($branding['color_primary'], ENT_QUOTES, 'UTF-8');
    $secondaryColor = htmlspecialchars($branding['color_secondary'], ENT_QUOTES, 'UTF-8');
    $logoHtml = '';

    if (!empty($branding['logo_path'])) {
        $logoSrc = htmlspecialchars(resolveCompanyAssetUrl($branding['logo_path'], getInvoiceBaseUrl()), ENT_QUOTES, 'UTF-8');
        $logoHtml = "<img src=\"{$logoSrc}\" alt=\"{$companyName}\" style=\"max-height:56px;max-width:220px;display:block;margin:0 auto 14px;\" />";
    }

    $subject = "Your {$companyName} Tracking OTP Code - " . $otp;

    // HTML email template with company-specific branding
    $message = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$companyName} Tracking OTP</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5; font-family: Arial, Helvetica, sans-serif;">

<table align="center" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; margin:20px auto; background-color:#ffffff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <tr>
        <td style="padding:0;">
            <!-- ========= HEADER ========== -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:{$primaryColor}; border-radius:10px 10px 0 0;">
                <tr>
                    <td style="padding:32px 20px; text-align:center; color:#ffffff;">
                        <div style="margin-bottom:16px;">
                            {$logoHtml}
                        </div>
                        <h1 style="margin:0; font-size:28px; font-weight:700; color:#ffffff;">{$companyName}</h1>
                        <p style="margin:10px 0 0 0; font-size:15px; opacity:0.95; color:#ffffff;">Shipment Tracking Verification</p>
                    </td>
                </tr>
            </table>
            <!-- ========== CONTENT ========== -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="padding:36px 28px;">
                <tr>
                    <td style="font-size:15px; color:#344054; line-height:1.7;">
                        <p style="margin:16px 0;">Hello,</p>
                        <p style="margin:16px 0;">We received a request to access shipment tracking associated with this email address.</p>
                        <p style="margin:16px 0;">Please use the one-time password below to continue:</p>
                    </td>
                </tr>
                <!-- OTP Box -->
                <tr>
                    <td style="padding:0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f8fafc; border:2px solid {$primaryColor}; border-radius:12px; padding:28px 24px; text-align:center; margin:28px 0;">
                            <tr>
                                <td>
                                    <p style="font-size:44px; font-weight:800; color:{$primaryColor}; letter-spacing:8px; font-family:'Courier New', monospace; margin:0;">{$otp}</p>
                                    <p style="font-size:12px; color:#475569; text-transform:uppercase; margin-top:10px; letter-spacing:1px;">One-Time Password</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- Info Box -->
                <tr>
                    <td style="padding:0;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fffbf4; border-left:4px solid {$secondaryColor}; padding:18px 16px; margin:20px 0; border-radius:10px;">
                            <tr>
                                <td>
                                    <p style="margin:10px 0; font-size:14px; color:#334155;"><strong>Time Remaining:</strong> This code is valid for 20 minutes.</p>
                                    <p style="margin:10px 0; font-size:14px; color:#334155;"><strong>Security:</strong> Please do not share this code with anyone.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:15px; color:#344054; line-height:1.7;">
                        <p style="margin:16px 0;">If you did not request this code, you can safely ignore this email.</p>
                    </td>
                </tr>
            </table>
            <!-- ========== FOOTER ========== -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f8fafc; border-top:1px solid #e2e8f0; border-radius:0 0 10px 10px;">
                <tr>
                    <td style="padding:24px 20px; text-align:center;">
                        <p style="margin:8px 0; font-size:13px; color:#68737d;">&copy; 2026 {$companyName}. All rights reserved.</p>
                        <p style="margin:8px 0; font-size:13px; color:#68737d;"><a href="{$companyWebsite}" style="text-decoration:none; color:{$secondaryColor};">{$companyName}</a></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
HTML;

    try {
        $result = MailConfig::send($to, $subject, $message, true);

        if (!$result) {
            return false;
        }

        $logsDir = __DIR__ . '/../logs/emails';
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $emailLog = $logsDir . '/' . date('Y-m-d') . '_otp_emails.log';
        $logEntry = "\n" . str_repeat("=", 80) . "\n";
        $logEntry .= "Timestamp: $timestamp\n";
        $logEntry .= "To: $to\n";
        $logEntry .= "Subject: $subject\n";
        $logEntry .= "OTP Code: $otp\n";
        $logEntry .= str_repeat("-", 80) . "\n";
        $logEntry .= "Email sent successfully\n";
        $logEntry .= str_repeat("=", 80) . "\n";

        file_put_contents($emailLog, $logEntry, FILE_APPEND | LOCK_EX);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

function createOTPSession(PDO $pdo, string $email): string
{
    $email = filter_var(strtolower($email), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email address', 400);
    }

    $otp = generateOTP();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+20 minutes'));

    $stmt = $pdo->prepare("
        INSERT INTO otp_sessions (email, otp_code, is_verified, attempts, expires_at)
        VALUES (?, ?, 0, 0, ?)
    ");

    $stmt->execute([$email, $otp, $expiresAt]);

    return $otp;
}

function verifyOTP(PDO $pdo, string $email, string $otp): bool
{
    $email = strtolower(trim($email));
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$email) return false;

    $stmt = $pdo->prepare("
        SELECT id, attempts, expires_at FROM otp_sessions
        WHERE email = ? AND otp_code = ? AND is_verified = 0
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$email, $otp]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) return false;

    if (strtotime($session['expires_at']) < time()) {
        return false;
    }

    if ($session['attempts'] >= 3) {
        return false;
    }

    $updateStmt = $pdo->prepare("
        UPDATE otp_sessions
        SET is_verified = 1, verified_at = NOW()
        WHERE id = ?
    ");

    return $updateStmt->execute([$session['id']]);
}

function isSessionVerified(PDO $pdo, string $email): bool
{
    $email = strtolower(trim($email));
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$email) return false;

    $stmt = $pdo->prepare("
        SELECT id FROM otp_sessions
        WHERE email = ? AND is_verified = 1
        AND verified_at > DATE_SUB(NOW(), INTERVAL 20 MINUTE)
        ORDER BY verified_at DESC LIMIT 1
    ");
    $stmt->execute([$email]);

    return (bool)$stmt->fetch();
}

function getShipmentsByEmail(PDO $pdo, string $email): array
{
    $email = strtolower(trim($email));
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$email) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT s.* FROM shipments s
        LEFT JOIN shipment_shippers ss ON ss.shipment_id = s.id
        LEFT JOIN shipment_consignees sc ON sc.shipment_id = s.id
        WHERE LOWER(TRIM(ss.shipper_email)) = ?
           OR LOWER(TRIM(sc.consignee_email)) = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$email, $email]);

    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($shipments as &$shipment) {
        $shipment = enrichShipmentWithDetails($pdo, $shipment);
    }

    return $shipments;
}

function getCompanyBrandingForTrackingEmail(PDO $pdo, string $email): array
{
    $email = strtolower(trim($email));
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$email) {
        return [
            'display_name' => 'Royal Gulf Shipping',
            'logo_path' => '',
            'color_primary' => '#667eea',
            'color_secondary' => '#764ba2',
            'website' => 'https://royalgulfshipping.com',
            'company_email' => 'info@royalgulfshipping.com',
        ];
    }

    $stmt = $pdo->prepare(
        "SELECT s.company FROM shipments s"
        . " LEFT JOIN shipment_shippers ss ON ss.shipment_id = s.id"
        . " LEFT JOIN shipment_consignees sc ON sc.shipment_id = s.id"
        . " WHERE LOWER(TRIM(ss.shipper_email)) = ?"
        . " OR LOWER(TRIM(sc.consignee_email)) = ?"
        . " ORDER BY s.created_at DESC LIMIT 1"
    );
    $stmt->execute([$email, $email]);
    $companyId = (int)$stmt->fetchColumn();

    if ($companyId > 0) {
        $companyStmt = $pdo->prepare('SELECT display_name, logo_path, color_primary, color_secondary, website FROM companies WHERE id = ? AND is_active = 1 LIMIT 1');
        $companyStmt->execute([$companyId]);
        $company = $companyStmt->fetch(PDO::FETCH_ASSOC);

        if ($company) {
            return [
                'display_name' => $company['display_name'] ?: 'Royal Gulf Shipping',
                'logo_path' => $company['logo_path'] ?: '',
                'color_primary' => $company['color_primary'] ?: '#667eea',
                'color_secondary' => $company['color_secondary'] ?: '#764ba2',
                'website' => $company['website'] ?: 'https://royalgulfshipping.com',
                'company_email' => 'info@royalgulfshipping.com',
            ];
        }
    }

    return [
        'display_name' => 'Royal Gulf Shipping',
        'logo_path' => '',
        'color_primary' => '#667eea',
        'color_secondary' => '#764ba2',
        'website' => 'https://royalgulfshipping.com',
        'company_email' => 'info@royalgulfshipping.com',
    ];
}

function recordOTPAttempt(PDO $pdo, string $email): void
{
    $email = strtolower(trim($email));
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$email) return;

    $stmt = $pdo->prepare("
        SELECT id, attempts FROM otp_sessions
        WHERE email = ? AND is_verified = 0
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$email]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        $updateStmt = $pdo->prepare("
            UPDATE otp_sessions SET attempts = attempts + 1 WHERE id = ?
        ");
        $updateStmt->execute([$session['id']]);
    }
}
