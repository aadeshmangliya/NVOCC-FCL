<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAdminAccess() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'officestaff', 'agent'], true)) {
        header('Location: login.php');
        exit();
    }
}

// Prevent direct access to PHP files in admin folder
function preventDirectAccess() {
    $allowed_files = ['login.php', 'logout.php'];
    $current_file = basename($_SERVER['PHP_SELF']);
    
    if (!in_array($current_file, $allowed_files)) {
        checkAdminAccess();
    }
}
preventDirectAccess();
?>