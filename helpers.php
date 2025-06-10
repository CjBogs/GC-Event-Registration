<?php
// Prevent re-declaring helper functions
if (!function_exists('isAllowedDomain')) {
    /**
     * Check if the email belongs to an allowed domain (case-insensitive)
     */
    function isAllowedDomain(string $email, array $allowed_domains): bool
    {
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        return in_array($domain, array_map('strtolower', $allowed_domains));
    }
}

if (!function_exists('truncate')) {
    /**
     * Sanitize and truncate long text safely
     */
    function truncate(string $text, int $maxChars = 50): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        return strlen($text) > $maxChars ? substr($text, 0, $maxChars) . '...' : $text;
    }
}

if (!function_exists('redirectUserByRole')) {
    /**
     * Redirect user based on role or super admin email
     */
    function redirectUserByRole(array $user): void
    {
        $role = strtolower($user['role'] ?? 'user');
        $email = strtolower($user['email'] ?? '');
        $superAdminEmail = strtolower(SUPER_ADMIN_EMAIL);

        if ($email === $superAdminEmail || $role === 'super_admin') {
            header("Location: super_admin/super_admin_dashboard.php");
            exit();
        }

        // Default redirection
        header("Location: user/user-dashboard.php");
        exit();
    }
}

if (!function_exists('isSuperAdmin')) {
    /**
     * Check if current session belongs to the Super Admin
     */
    function isSuperAdmin(): bool
    {
        return isset($_SESSION['email']) &&
            strtolower($_SESSION['email']) === strtolower(SUPER_ADMIN_EMAIL);
    }
}
