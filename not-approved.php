<?php
session_start();

// Redirect if not logged in as admin or if not pending status
if (
    !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' ||
    !isset($_SESSION['status']) || strtolower($_SESSION['status']) !== 'pending'
) {
    header("Location: landing-page.php"); // or admin dashboard
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Account Pending Approval</title>
    <!-- Using Tailwind CDN for simplicity -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded shadow text-center max-w-md">
        <h1 class="text-2xl font-semibold mb-4">Account Pending Approval</h1>
        <p class="mb-6">
            Your admin account is currently pending approval by the Super Admin.<br />
            Please wait until your account is approved before logging in.
        </p>
        <a href="logout.php" class="text-blue-600 hover:underline font-medium">Back to Login</a>
    </div>
</body>

</html>