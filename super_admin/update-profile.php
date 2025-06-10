<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['email']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../landing-page.php");
    exit();
}

$loggedInEmail = $_SESSION['email'];
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? ''; // optional
$profile_img = $_FILES['profile_image'] ?? null;

// Validation
if (empty($first_name) || empty($last_name) || empty($email)) {
    $_SESSION['message'] = "First name, last name, and email are required.";
    $_SESSION['message_type'] = "error";
    header("Location: super_admin_dashboard.php");
    exit();
}

$allowed_domains = ['gmail.com', 'gordoncollege.edu.ph'];
$domain = substr(strrchr($email, "@"), 1);
if (!in_array($domain, $allowed_domains)) {
    $_SESSION['message'] = "Only emails from gmail.com or gordoncollege.edu.ph are allowed.";
    $_SESSION['message_type'] = "error";
    header("Location: super_admin_dashboard.php");
    exit();
}

// Email uniqueness check
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND email != ?");
$stmt->bind_param('ss', $email, $loggedInEmail);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $_SESSION['message'] = "This email is already used by another account.";
    $_SESSION['message_type'] = "error";
    $stmt->close();
    header("Location: super_admin_dashboard.php");
    exit();
}
$stmt->close();

// Get current user's profile image (to delete if replaced)
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE email = ?");
$stmt->bind_param("s", $loggedInEmail);
$stmt->execute();
$result = $stmt->get_result();
$old_image = ($row = $result->fetch_assoc()) ? $row['profile_image'] : null;
$stmt->close();

// Handle image upload if a file was submitted
$image_filename = $old_image;
if ($profile_img && $profile_img['error'] === UPLOAD_ERR_OK) {
    $img_name = $profile_img['name'];
    $img_tmp = $profile_img['tmp_name'];
    $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($img_ext, $allowed_types)) {
        $new_filename = uniqid('profile_', true) . '.' . $img_ext;
        $upload_path = '../uploads/' . $new_filename;

        if (move_uploaded_file($img_tmp, $upload_path)) {
            // Delete old image if it's not default
            if ($old_image && file_exists("../uploads/$old_image") && $old_image !== 'default.png') {
                unlink("../uploads/$old_image");
            }
            $image_filename = $new_filename;
        } else {
            $_SESSION['message'] = "Failed to upload image.";
            $_SESSION['message_type'] = "error";
            header("Location: super_admin_dashboard.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "Invalid image format. Allowed: jpg, jpeg, png, gif.";
        $_SESSION['message_type'] = "error";
        header("Location: super_admin_dashboard.php");
        exit();
    }
}

// Update user profile
if (!empty($password)) {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ?, profile_image = ? WHERE email = ?");
    $stmt->bind_param('ssssss', $first_name, $last_name, $email, $password_hash, $image_filename, $loggedInEmail);
} else {
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_image = ? WHERE email = ?");
    $stmt->bind_param('sssss', $first_name, $last_name, $email, $image_filename, $loggedInEmail);
}

if ($stmt->execute()) {
    $_SESSION['email'] = $email; // update session email
    $_SESSION['message'] = "Profile updated successfully.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to update profile.";
    $_SESSION['message_type'] = "error";
}
$stmt->close();

header("Location: super_admin_dashboard.php");
exit();
