<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['email']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../landing-page.php");
    exit();
}

$loggedInEmail = $_SESSION['email'];

// Sanitize input
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate required fields
if (empty($first_name) || empty($last_name)) {
    $_SESSION['message'] = "First name and last name are required.";
    $_SESSION['message_type'] = "error";
    header("Location: ../user/user-dashboard.php");
    exit();
}

// Validate password if provided
if (!empty($password)) {
    if (strlen($password) < 6) {
        $_SESSION['message'] = "Password must be at least 6 characters long.";
        $_SESSION['message_type'] = "error";
        header("Location: ../user/user-dashboard.php");
        exit();
    }
    if ($password !== $confirm_password) {
        $_SESSION['message'] = "Password and Confirm Password do not match.";
        $_SESSION['message_type'] = "error";
        header("Location: ../user/user-dashboard.php");
        exit();
    }
}

try {
    if (!empty($password)) {
        // Hash password and update with password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET first_name = ?, last_name = ?, password = ? WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssss', $first_name, $last_name, $password_hash, $loggedInEmail);
    } else {
        // Update without password
        $query = "UPDATE users SET first_name = ?, last_name = ? WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sss', $first_name, $last_name, $loggedInEmail);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Profile updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Update failed. Please try again.";
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();
} catch (Exception $e) {
    $_SESSION['message'] = "An unexpected error occurred: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

header("Location: ../user/user-dashboard.php");
exit();
