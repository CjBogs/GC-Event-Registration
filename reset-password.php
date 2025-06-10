<?php
session_start();

// DB connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "users_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';
$showForm = false;

// Get email and token from URL
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// Validate the link
if (empty($email) || empty($token)) {
    $error = "Invalid or missing password reset link.";
} else {
    $stmt = $conn->prepare("SELECT reset_expiry FROM users WHERE email = ? AND reset_token = ?");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($reset_expiry);
        $stmt->fetch();

        // Check if token expired
        if (strtotime($reset_expiry) < time()) {
            $error = "This reset link has expired. Please request a new one.";
        } else {
            $showForm = true;
        }
    } else {
        $error = "Invalid password reset link.";
    }
    $stmt->close();
}

// Handle password reset form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showForm) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in both password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Hash and update
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute()) {
            $success = "Your password has been successfully reset. You can now <a href='landing-page.php' class='underline text-red-600'>login</a>.";
            $showForm = false;
        } else {
            $error = "Failed to reset password. Please try again.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Reset Your Password</h2>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4 text-sm">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <form method="POST" action="">
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="new_password" name="new_password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-red-500 focus:border-red-500" />

                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-red-500 focus:border-red-500" />

                <button type="submit"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-lg transition">
                    Reset Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>
