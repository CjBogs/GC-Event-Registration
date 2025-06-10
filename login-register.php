<?php
session_start();
require_once 'config.php';
require_once 'helpers.php'; // Contains isAllowedDomain(), redirectUserByRole(), etc.

// ---------- LOGIN ----------
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check Terms & Conditions checkbox
    if (empty($_POST['terms'])) {
        $_SESSION['login_error'] = 'You must agree to the Terms and Conditions before logging in.';
        $_SESSION['active_form'] = 'login';
        header("Location: landing-page.php");
        exit();
    }

    if (!$email || !$password) {
        $_SESSION['login_error'] = 'Please enter email and password.';
        $_SESSION['active_form'] = 'login';
        header("Location: landing-page.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !isAllowedDomain($email, $allowed_domains)) {
        $_SESSION['login_error'] = 'Only Gmail or Gordon College emails are allowed.';
        $_SESSION['active_form'] = 'login';
        header("Location: landing-page.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);

    if (!$stmt->execute()) {
        $stmt->close();
        $_SESSION['login_error'] = 'Database error during login.';
        $_SESSION['active_form'] = 'login';
        header("Location: landing-page.php");
        exit();
    }

    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $stmt->close();

        if (password_verify($password, $user['password'])) {
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_image'] = $user['profile_image'] ?? 'default.png';
            $_SESSION['role'] = strtolower($user['role']);
            $_SESSION['status'] = strtolower(trim($user['status'] ?? ''));

            // Redirect user based on role
            redirectUserByRole($user);
        } else {
            $_SESSION['login_error'] = 'Incorrect password.';
            $_SESSION['active_form'] = 'login';
            header("Location: landing-page.php");
            exit();
        }
    } else {
        $stmt->close();
        $_SESSION['login_error'] = 'Account not found.';
        $_SESSION['active_form'] = 'login';
        header("Location: landing-page.php");
        exit();
    }
}
