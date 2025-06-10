<?php
session_start();
include('config.php'); // DB connection

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
    $fileTmpPath = $_FILES['profile_image']['tmp_name'];
    $fileName = basename($_FILES['profile_image']['name']);
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array(strtolower($fileExtension), $allowed)) {
        $newFileName = uniqid() . '.' . $fileExtension;
        $uploadPath = 'uploads/' . $newFileName;

        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            $email = $_SESSION['email']; // Assuming email is in session

            // Update DB with new image path
            $query = "UPDATE users SET profile_image = ? WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $newFileName, $email);
            $stmt->execute();

            // Update session variable
            $_SESSION['profile_image'] = $newFileName;

            // Get user role for redirect
            // Assuming role is stored in users table or in session
            if (!isset($_SESSION['role'])) {
                // If role not in session, fetch it from DB
                $roleQuery = "SELECT role FROM users WHERE email = ?";
                $roleStmt = $conn->prepare($roleQuery);
                $roleStmt->bind_param('s', $email);
                $roleStmt->execute();
                $roleStmt->bind_result($role);
                $roleStmt->fetch();
                $roleStmt->close();
                $_SESSION['role'] = $role;
            } else {
                $role = $_SESSION['role'];
            }

            // Redirect based on role
            if ($role === 'super_admin') {
                header("Location: super_admin/super_admin_dashboard.php");
            } else {
                header("Location: user/user-dashboard.php");
            }
            exit();
        } else {
            echo "Failed to upload file.";
        }
    } else {
        echo "Invalid file type.";
    }
} else {
    echo "No file selected or upload error.";
}
