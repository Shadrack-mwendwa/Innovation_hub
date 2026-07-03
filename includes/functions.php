<?php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > 5000000) {
        return false;
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    return false;
}

function generateCertificateNumber() {
    return 'CERT-' . date('Y') . '-' . strtoupper(uniqid());
}

function getUserName($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? $result['full_name'] : 'Unknown';
    } catch(Exception $e) {
        return 'Unknown';
    }
}

function timeAgo($timestamp) {
    if(empty($timestamp)) {
        return 'Just now';
    }
    
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    
    if($time_difference < 0) {
        return 'Just now';
    }
    
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}

function getNotificationCount($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch(Exception $e) {
        return 0;
    }
}

?>