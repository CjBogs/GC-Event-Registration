<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // Composer autoload for PHPMailer

$success = '';
$error = '';

// DB connection — change these values accordingly
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "users_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match('/@(gmail\.com|gordoncollege\.edu\.ph)$/', $email)) {
        $error = "Only @gmail.com and @gordoncollege.edu.ph emails are allowed.";
    } else {
        // Check if email exists in DB
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            // Email not found, but we do not reveal that to user for security
            $success = "If an account with that email exists, a password reset link has been sent.";
        } else {
            // Email found, generate reset token and expiry (1 hour from now)
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token and expiry in DB
            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
            $stmt->bind_param("sss", $token, $expiry, $email);
            if ($stmt->execute()) {
                // Send email with reset link
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = '202310393@gordoncollege.edu.ph'; // your Gmail
                    $mail->Password   = 'nqsnnqnfonycyzmc';               // your Gmail app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('202310393@gordoncollege.edu.ph', 'Your Website');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';

                    $resetLink = 'http://localhost/FINAL_SYSTEM/reset-password.php?email=' . urlencode($email) . '&token=' . $token;

                    $mail->Body = "
                        <p>Dear user,</p>
                        <p>You requested a password reset. Click the link below to reset your password:</p>
                        <p><a href='$resetLink'>$resetLink</a></p>
                        <p>If you did not request this, please ignore this email.</p>
                        <br>
                        <p>Regards,<br>Your Website Team</p>
                    ";

                    $mail->send();
                    $success = "If an account with that email exists, a password reset link has been sent.";
                } catch (Exception $e) {
                    $error = "Failed to send email. Please try again later.";
                    // Uncomment below to debug:
                    // $error .= ' Mailer Error: ' . $mail->ErrorInfo;
                }
            } else {
                $error = "Failed to generate reset link. Please try again later.";
            }
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
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Forgot Password</h2>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Enter your email address</label>
            <input type="email" id="email" name="email" placeholder="your@example.com" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-red-500 focus:border-red-500">

            <button type="submit"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-lg transition">
                Send Reset Link
            </button>

            <div class="text-center mt-4">
                <a href="landing-page.php" class="text-sm text-red-600 hover:underline">Back to Login</a>
            </div>
        </form>
    </div>
</body>

</html>