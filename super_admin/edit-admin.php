<?php
include '../config.php'; // DB connection
header('Content-Type: application/json');

// Sanitize and validate ID
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
    exit;
}

// Collect and sanitize inputs
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = trim($_POST['role'] ?? '');

// Validate email format if email is provided
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

// Check if email is already taken by another user
if ($email !== '') {
    $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    if (!$checkEmailStmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    $checkEmailStmt->bind_param("si", $email, $id);
    $checkEmailStmt->execute();
    $checkEmailStmt->store_result();

    if ($checkEmailStmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already in use by another account.']);
        $checkEmailStmt->close();
        exit;
    }
    $checkEmailStmt->close();
}

// Password confirmation check
if ($password !== '') {
    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit;
    }
    // Optional: Password strength validation here
}

// Prepare dynamic update
$fields = [];
$values = [];
$types = '';

// Only add non-empty fields to update
if ($first_name !== '') {
    $fields[] = "first_name = ?";
    $values[] = $first_name;
    $types .= 's';
}

if ($last_name !== '') {
    $fields[] = "last_name = ?";
    $values[] = $last_name;
    $types .= 's';
}

if ($email !== '') {
    $fields[] = "email = ?";
    $values[] = $email;
    $types .= 's';
}

if ($password !== '') {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $fields[] = "password = ?";
    $values[] = $hashedPassword;
    $types .= 's';
}

if ($role !== '') {
    $fields[] = "role = ?";
    $values[] = $role;
    $types .= 's';
}

if (empty($fields)) {
    echo json_encode(['status' => 'error', 'message' => 'No fields to update.']);
    exit;
}

// Add ID for WHERE clause
$values[] = $id;
$types .= 'i';

$fields_str = implode(', ', $fields);
$sql = "UPDATE users SET $fields_str WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$values);

if ($stmt->execute()) {
    // Return updated fields or fallback to null if empty
    echo json_encode([
        'status' => 'success',
        'message' => 'User updated successfully.',
        'updated_fields' => [
            'first_name' => $first_name ?: null,
            'last_name' => $last_name ?: null,
            'email' => $email ?: null,
            'role' => $role ?: null
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating account: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
