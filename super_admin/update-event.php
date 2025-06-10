<?php
session_start();
require_once '../config.php';

// Check if user is authenticated and request is POST
if (!isset($_SESSION['email']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../landing-page.php");
    exit();
}

// Retrieve and sanitize input
$eventId     = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$eventDate   = $_POST['event_date'] ?? '';

// Basic validation
if ($eventId <= 0 || !$title || !$description || !$eventDate) {
    $_SESSION['message'] = "Please complete all fields.";
    $_SESSION['message_type'] = "error";
    header("Location: super_admin_dashboard.php#eventManage");
    exit();
}

// Update the event
$stmt = $conn->prepare("
    UPDATE events
    SET title = ?, description = ?, event_date = ?
    WHERE id = ?
");
$stmt->bind_param("sssi", $title, $description, $eventDate, $eventId);

if ($stmt->execute()) {
    $_SESSION['message'] = "Event updated successfully.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to update event.";
    $_SESSION['message_type'] = "error";
}

$stmt->close();
$conn->close();

header("Location: super_admin_dashboard.php#eventManage");
exit();
