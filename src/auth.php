<?php
session_start();
include('config.php');

// Kalau takde session, cuba check cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $connect->real_escape_string($_COOKIE['remember_token']);

    $stmt = $connect->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Set semula session
        $_SESSION['user_id'] = $user['users_id'];
        $_SESSION['role']    = $user['role'];
    }
}

// Kalau masih tak login, redirect ke login page
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
