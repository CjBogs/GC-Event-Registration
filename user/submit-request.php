<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $course      = trim($_POST['course'] ?? '');
    $year        = trim($_POST['year'] ?? '');
    $block       = trim($_POST['block'] ?? '');
    $email       = $_SESSION['email'] ?? '';

    $uploadDir = '../uploads/requests/';
    $filePath = '';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    // Only allow PDF, DOC, and DOCX files
    if (isset($_FILES['request_file']) && $_FILES['request_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['request_file']['tmp_name'];
        $fileName    = basename($_FILES['request_file']['name']);
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'doc', 'docx'];

        if (!in_array($fileExt, $allowedExts)) {
            die("Invalid file type. Only PDF, DOC, and DOCX are allowed.");
        }

        $uniqueName = uniqid('request_', true) . '.' . $fileExt;
        $filePath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($fileTmpPath, $filePath)) {
            die("Failed to move uploaded file.");
        }
    } else {
        die("File upload error.");
    }

    if ($title && $description && $event_date && $email && $filePath) {
        $relativePath = 'uploads/requests/' . basename($filePath);

        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, user_email, course, year, block, request_form_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param('ssssssss', $title, $description, $event_date, $email, $course, $year, $block, $relativePath);

        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }

        $stmt->close();

        $_SESSION['flash_success'] = "Your event request has been sent and is awaiting admin approval.";

        header("Location: user-dashboard.php#requestEvent");
        exit();
    } else {
        echo "Missing fields or upload failed.";
    }
} else {
    header("Location: user-dashboard.php#request-event");
    exit();
}
