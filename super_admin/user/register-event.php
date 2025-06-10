<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['email'])) {
    header("Location: ../landing-page.php");
    exit();
}

$email = $_SESSION['email'];
$eventId = $_POST['event_id'] ?? null;
$course = $_POST['course'] ?? '';
$yearLevel = $_POST['year'] ?? '';
$block = $_POST['block'] ?? '';

if (!$eventId || !filter_var($eventId, FILTER_VALIDATE_INT)) {
    $_SESSION['flash_registration'] = 'Invalid event ID.';
    header("Location: user-dashboard.php#userEvents");
    exit();
}

require('../config.php');

// Check for duplicate registration
$stmt = $conn->prepare("SELECT 1 FROM event_registrations WHERE user_email = ? AND event_id = ?");
if (!$stmt) {
    $_SESSION['flash_registration'] = 'Database error.';
    header("Location: user-dashboard.php#userEvents");
    exit();
}
$stmt->bind_param('si', $email, $eventId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $_SESSION['flash_registration'] = 'You are already registered for this event.';
    header("Location: user-dashboard.php#userEvents");
    exit();
}
$stmt->close();

// Insert with 'approved' status
$stmt = $conn->prepare("INSERT INTO event_registrations (user_email, event_id, course, year, block, status) VALUES (?, ?, ?, ?, ?, 'approved')");
if (!$stmt) {
    $_SESSION['flash_registration'] = 'Database error.';
    header("Location: user-dashboard.php#userEvents");
    exit();
}
$stmt->bind_param('sisss', $email, $eventId, $course, $yearLevel, $block);

if ($stmt->execute()) {
    $_SESSION['flash_registration'] = 'Registered successfully! Check your events in the View Events section.';
    $stmt->close();
    $conn->close();
    header("Location: user-dashboard.php#userEvents");
    exit();
} else {
    $_SESSION['flash_registration'] = 'Registration failed. Please try again.';
    $stmt->close();
    $conn->close();
    header("Location: user-dashboard.php#userEvents");
    exit();
}
