<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../landing-page.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $created_by = $_SESSION['email'];

    // Basic validation
    if ($title === '' || $description === '' || $event_date === '') {
        $_SESSION['event_message'] = "Please fill in all required fields.";
        $_SESSION['event_message_type'] = "error";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Validate event_date format
    $date_valid = DateTime::createFromFormat('Y-m-d', $event_date);
    if (!$date_valid || $date_valid->format('Y-m-d') !== $event_date) {
        $_SESSION['event_message'] = "Invalid event date format.";
        $_SESSION['event_message_type'] = "error";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Prevent past dates (optional)
    $today = new DateTime('today');
    if ($date_valid < $today) {
        $_SESSION['event_message'] = "Event date cannot be in the past.";
        $_SESSION['event_message_type'] = "error";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Prepare statement
    $sql = "INSERT INTO events (title, description, event_date, created_by, user_email, status) VALUES (?, ?, ?, ?, ?, 'approved')";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        // Log error in production, show generic error to user
        $_SESSION['event_message'] = "Database error. Please try again later.";
        $_SESSION['event_message_type'] = "error";
        header("Location: super_admin_dashboard.php#eventCreation");
        exit();
    }

    $stmt->bind_param("sssss", $title, $description, $event_date, $created_by, $created_by);

    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['event_message'] = "Event created successfully!";
        $_SESSION['event_message_type'] = "success";
        header("Location: super_admin_dashboard.php#eventCreation");
        exit();
    } else {
        $_SESSION['event_message'] = "Failed to create event: " . $stmt->error;
        $_SESSION['event_message_type'] = "error";
        header("Location: super_admin_dashboard.php#eventCreation");
        exit();
    }
} else {
    header("Location: super_admin_dashboard.php#eventCreation");
    exit();
}
