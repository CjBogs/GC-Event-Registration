<?php
require '../config.php';
session_start();

// Ensure the user is logged in and request is POST
if (!isset($_SESSION['email']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Unauthorized or invalid request.";
    $_SESSION['message_type'] = "error";
    header("Location: super_admin_dashboard.php#eventManage");
    exit();
}

$email = $_SESSION['email'];
$eventId = isset($_POST['id']) ? intval($_POST['id']) : 0;

// Validate event ID
if ($eventId <= 0) {
    $_SESSION['message'] = "Invalid event ID.";
    $_SESSION['message_type'] = "error";
    header("Location: super_admin_dashboard.php#eventManage");
    exit();
}

// Prepare and execute delete query
$stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
$stmt->bind_param("i", $eventId);
$stmt->execute();

// Check if deletion was successful
if ($stmt->affected_rows > 0) {
    $_SESSION['message'] = "Event deleted successfully.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Event not found or could not be deleted.";
    $_SESSION['message_type'] = "error";
}

$stmt->close();
$conn->close();

// Redirect back to dashboard
header("Location: super_admin_dashboard.php#eventManage");
exit();
