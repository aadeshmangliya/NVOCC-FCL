<?php
// Global helper functions

function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function getCurrentUser() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function getShipmentStatusBadge($status) {
    $badges = [
        'pending' => ['bg' => 'warning', 'text' => 'Pending'],
        'picked_up' => ['bg' => 'info', 'text' => 'Picked Up'],
        'in_transit' => ['bg' => 'primary', 'text' => 'In Transit'],
        'out_for_delivery' => ['bg' => 'secondary', 'text' => 'Out for Delivery'],
        'delivered' => ['bg' => 'success', 'text' => 'Delivered'],
        'cancelled' => ['bg' => 'danger', 'text' => 'Cancelled']
    ];
    
    return $badges[$status] ?? ['bg' => 'secondary', 'text' => $status];
}

function generateTrackingNumber() {
    return 'TRK' . date('YmdHis') . rand(1000, 9999);
}

function formatCurrency($amount) {
    return '₹ ' . number_format($amount, 2);
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return "just now";
    if ($diff < 3600) return round($diff / 60) . " minutes ago";
    if ($diff < 86400) return round($diff / 3600) . " hours ago";
    if ($diff < 604800) return round($diff / 86400) . " days ago";
    
    return date('M d, Y', $time);
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function getNotificationMessage($status) {
    $messages = [
        'pending' => 'Your shipment has been created and is pending pickup.',
        'picked_up' => 'Your shipment has been picked up.',
        'in_transit' => 'Your shipment is on its way.',
        'out_for_delivery' => 'Your shipment is out for delivery today.',
        'delivered' => 'Your shipment has been successfully delivered.',
        'cancelled' => 'Your shipment has been cancelled.'
    ];
    
    return $messages[$status] ?? 'Shipment status updated.';
}

?>
