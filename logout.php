<?php
session_start();

require_once 'config.php';

// Force $isSecure false if testing on localhost without HTTPS
$isSecure = false;
// For production, use:
// $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

// Clear remember_token in DB if cookie exists
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    // Clear the cookie by setting expiration in the past
    setcookie('remember_token', '', time() - 3600, '/', '', $isSecure, true);
}

session_unset();
session_destroy();

header("Location: landing-page.php");
exit();
