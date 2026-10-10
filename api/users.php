<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
require_once '../config/database.php';
require_once '../includes/email_functions.php';
// require_once '../includes/auth.php';


global $pdo;
// Check if user is admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$userId = basename($path);
if (!is_numeric($userId)) {
    $userId = null;
}
if ($userId === null && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
    $userId = (int)$_REQUEST['id'];
}

// Fallback: try PATH_INFO (some servers populate PATH_INFO instead)
if ($userId === null && !empty($_SERVER['PATH_INFO'])) {
    $maybe = basename($_SERVER['PATH_INFO']);
    if (is_numeric($maybe)) {
        $userId = (int)$maybe;
    }
}

// For DELETE or other verbs, also try to read JSON body for id
if ($userId === null) {
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);
    if (is_array($jsonInput) && !empty($jsonInput['id']) && is_numeric($jsonInput['id'])) {
        $userId = (int)$jsonInput['id'];
    }
}

// Normalize userId: convert numeric strings to int and treat non-positive as missing
if ($userId !== null) {
    if (is_numeric($userId)) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            $userId = null;
        }
    } else {
        $userId = null;
    }
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_REQUEST['action'] ?? $data['action'] ?? null;

function generateRandomPassword(int $length = 12): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*()-_';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

function getUserSelectColumns(): string
{
    return 'id, username, email, role, is_active, created_at';
}

function ensureUsersTableHasIsActive(PDO $pdo): void
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'is_active'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec('ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1');
    }
}

try {
    ensureUsersTableHasIsActive($pdo);

    switch ($method) {
        case 'GET':
            if ($action === 'preview_credentials') {
                // If an ID is provided, preview for an existing user (masked password).
                if ($userId !== null) {
                    $stmt = $pdo->prepare('SELECT id, username, email, role FROM users WHERE id = ?');
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        exit;
                    }

                    // For existing users do not expose real password in preview.
                    $maskedPassword = '********';
                    $preview = buildUserCredentialEmailPreview($user['username'], $user['email'], $maskedPassword, true);

                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'subject' => $preview['subject'],
                            'html' => $preview['html'],
                            'generated_password' => null,
                            'existing_user' => true,
                        ]
                    ]);
                    break;
                }

                // No user id: allow preview via query params for legacy GET previews
                $qUsername = trim($_REQUEST['username'] ?? '');
                $qEmail = trim($_REQUEST['email'] ?? '');
                $qPassword = trim($_REQUEST['password'] ?? '');

                if ($qUsername !== '' && $qEmail !== '') {
                    if ($qPassword === '') $qPassword = generateRandomPassword(10);
                    $preview = buildUserCredentialEmailPreview($qUsername, $qEmail, $qPassword, true);
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'subject' => $preview['subject'],
                            'html' => $preview['html'],
                            'generated_password' => $qPassword,
                            'existing_user' => false,
                        ]
                    ]);
                    break;
                }

                // Fallback: no id and no query data
                echo json_encode(['success' => false, 'message' => 'User ID required for preview']);
                exit;
            }

            if ($userId !== null) {
                $stmt = $pdo->prepare('SELECT ' . getUserSelectColumns() . ' FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    echo json_encode(['success' => true, 'data' => $user]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            } else {
                $stmt = $pdo->prepare('SELECT ' . getUserSelectColumns() . ' FROM users ORDER BY created_at DESC');
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $users]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?: [];

            // Allow preview for new users (no id) via POST { action: 'preview_credentials', username, email, password? }
            if ($action === 'preview_credentials' && empty($userId)) {
                $username = trim($data['username'] ?? '');
                $email = trim($data['email'] ?? '');
                $password = trim($data['password'] ?? '');

                if ($username === '' || $email === '') {
                    echo json_encode(['success' => false, 'message' => 'Username and email are required for preview']);
                    exit;
                }

                if ($password === '') {
                    $password = generateRandomPassword(10);
                }

                $preview = buildUserCredentialEmailPreview($username, $email, $password, true);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'subject' => $preview['subject'],
                        'html' => $preview['html'],
                        'generated_password' => $password,
                        'existing_user' => false,
                    ]
                ]);
                break;
            }

            if ($action === 'send_credentials') {
                if (empty($data['id']) || !is_numeric($data['id'])) {
                    echo json_encode(['success' => false, 'message' => 'User ID required']);
                    exit;
                }

                $targetUserId = (int)$data['id'];
                $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE id = ?');
                $stmt->execute([$targetUserId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                    exit;
                }

                $newPassword = generateRandomPassword(10);
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $update->execute([$hashedPassword, $targetUserId]);

                $sent = sendUserCredentialsEmail($pdo, $user['email'], $user['username'], $newPassword, true);
                if ($sent) {
                    echo json_encode(['success' => true, 'message' => 'Credentials resent successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send credentials email']);
                }
                break;
            }

            // Validate required fields
            if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            // Check if username exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$data['username']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
                exit;
            }

            // Check if email exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }

            // Create user
            $isActive = isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1;
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$data['username'], $data['email'], $hashedPassword, $data['role'], $isActive]);
            $newUserId = $pdo->lastInsertId();

            if (!empty($data['send_credentials']) && $data['send_credentials'] === true) {
                sendUserCredentialsEmail($pdo, $data['email'], $data['username'], $data['password'], false);
            }

            echo json_encode(['success' => true, 'message' => 'User created successfully', 'user_id' => $newUserId]);
            break;

        case 'PUT':
            if ($userId === null) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Build update query dynamically
            $updates = [];
            $params = ['id' => $userId];

            if (isset($data['username'])) {
                // Check if username exists
                $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                $stmt->execute([$data['username'], $userId]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Username already exists']);
                    exit;
                }
                $updates[] = 'username = :username';
                $params['username'] = $data['username'];
            }

            if (isset($data['email'])) {
                // Check if email exists
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $stmt->execute([$data['email'], $userId]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
                $updates[] = 'email = :email';
                $params['email'] = $data['email'];
            }

            if (isset($data['role'])) {
                $updates[] = 'role = :role';
                $params['role'] = $data['role'];
            }

            if (isset($data['is_active'])) {
                $updates[] = 'is_active = :is_active';
                $params['is_active'] = $data['is_active'] ? 1 : 0;
            }

            if (isset($data['password']) && !empty($data['password'])) {
                $updates[] = 'password = :password';
                $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (empty($updates)) {
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }

            $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;

        case 'DELETE':
            if ($userId === null) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }

            // Don't allow deleting yourself
            if ($userId == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);

            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
