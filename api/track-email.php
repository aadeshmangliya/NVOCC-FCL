<?php
require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../includes/api_functions.php';
require_once '../includes/email_functions.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
global $pdo;
try {
    if ($method !== 'POST') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $otp = $data['otp'] ?? '';
    $action = $data['action'] ?? '';

    // Determine action based on request data
    if ($email && !$otp && $action !== 'get_shipments') {
        // Request OTP if only email is provided
        // Allow unlimited OTP resends - even if previously verified (session verification just means access granted)
        // First trim and lowercase, then validate
        $email = strtolower(trim($email));
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        if (!$email) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Valid email is required']);
            exit;
        }

        $shipments = getShipmentsByEmail($pdo, $email);
        if (empty($shipments)) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'No shipments found for this email address'
            ]);
            exit;
        }

        // Allow resend immediately - delete old unverified OTPs and create new one
        $deleteStmt = $pdo->prepare("DELETE FROM otp_sessions WHERE email = ? AND is_verified = 0");
        $deleteStmt->execute([$email]);

        $otp = createOTPSession($pdo, $email);

        if (sendOTPEmail($pdo, $email, $otp)) {
            echo json_encode([
                'success' => true,
                'message' => 'OTP sent to your email. Valid for 20 minutes.',
                'email' => $email
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
        }
        exit;
    }

    // Verify OTP if both email and otp are provided
    if ($email && $otp) {
        $email = strtolower(trim($email));
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        if (!$email || strlen($otp) !== 6 || !ctype_digit($otp)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Valid email and 6-digit OTP required']);
            exit;
        }

        if (verifyOTP($pdo, $email, $otp)) {
            echo json_encode([
                'success' => true,
                'message' => 'Email verified successfully',
                'email' => $email
            ]);
        } else {
            recordOTPAttempt($pdo, $email);
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid OTP or OTP expired']);
        }
        exit;
    }

    // Get shipments if action is explicitly 'get_shipments'
    if ($email && $action === 'get_shipments') {
        $email = strtolower(trim($email));
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        if (!$email) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Valid email is required']);
            exit;
        }

        // Verify that the email has been verified in this session
        $sessionVerified = isSessionVerified($pdo, $email);

        if (!$sessionVerified) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Email not verified or session expired']);
            exit;
        }

        $shipments = getShipmentsByEmail($pdo, $email);

        if (empty($shipments)) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'No shipments found for this email address',
                'data' => []
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $shipments,
            'total' => count($shipments)
        ]);
        exit;
    }

    // Invalid request
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request - email is required']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
