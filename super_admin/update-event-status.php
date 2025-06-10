<?php
session_start();
require_once '../config.php';
require_once '../helpers.php';

// Redirect if not Super Admin
if (!isSuperAdmin()) {
    header("Location: ../landing-page.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if ($eventId > 0 && in_array($action, ['approve', 'reject'], true) && $reason !== '') {
        $status = $action === 'approve' ? 'approved' : 'rejected';

        // Use prepared statement to securely update both status and reason
        $stmt = $conn->prepare("UPDATE events SET status = ?, review_reason = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('ssi', $status, $reason, $eventId);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Event has been " . htmlspecialchars($status) . " with reason.";
            } else {
                $_SESSION['message'] = "Failed to update event status. Please try again.";
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error: " . htmlspecialchars($conn->error);
        }
    } else {
        $_SESSION['message'] = "Invalid data. Reason is required.";
    }
} else {
    $_SESSION['message'] = "Invalid request method.";
}

header("Location: super_admin_dashboard.php#eventsApproval");
exit();
