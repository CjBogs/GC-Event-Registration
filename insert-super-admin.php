<?php
require_once 'config.php';

// Define user info
$email = 'admin1@gordoncollege.edu.ph';
$first_name = 'Joseph';
$last_name = 'Angelo';
$password = password_hash('1', PASSWORD_DEFAULT); // Securely hash the password
$role = 'super_admin'; // or 'admin', 'student', etc.
$status = 'approved';
$profile_image = 'default.png';
$course = '';
$year = '';
$block = '';
$created_at = date('Y-m-d H:i:s');

// Check if user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "User already exists.";
} else {
    $stmt->close();

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, profile_image, created_at, status, course, year, block) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "sssssssssss",
        $first_name,
        $last_name,
        $email,
        $password,
        $role,
        $profile_image,
        $created_at,
        $status,
        $course,
        $year,
        $block
    );

    if ($stmt->execute()) {
        echo "User inserted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
