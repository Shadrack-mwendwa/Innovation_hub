<?php
require_once __DIR__ . '/../config/database.php';

function registerUser($username, $email, $password, $full_name, $phone) {
    global $pdo;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) 
                                VALUES (?, ?, ?, ?, ?, 'user')");
        $stmt->execute([$username, $email, $hashed_password, $full_name, $phone]);
        return $pdo->lastInsertId();
    } catch(PDOException $e) {
        return false;
    }
}

function loginUser($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        return true;
    }
    return false;
}

function logoutUser() {
    session_destroy();
    redirect('login.php');
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('index.php');
    }
}
?>