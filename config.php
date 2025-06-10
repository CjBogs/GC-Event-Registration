<?php
// Toggle between 'local' and 'production'
$environment = 'local'; // Change to 'production' when deploying

if ($environment === 'local') {
    // Local XAMPP/MySQL setup
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "users_db";
    $port = 3306;
} else {
    // Remote (Render or Freesqldatabase)
    $servername = getenv('DB_HOST') ?: "sql12.freesqldatabase.com";
    $username = getenv('DB_USER') ?: "sql12783588";
    $password = getenv('DB_PASS') ?: "aK5gne4yYM";
    $dbname = getenv('DB_NAME') ?: "sql12783588";
    $port = 3306;
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection error.");
}

// Automatically close connection when script ends
register_shutdown_function(function () use (&$conn) {
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
});

// Allowed email domains
$allowed_domains = ['gmail.com', 'gordoncollege.edu.ph'];

// Define Super Admin email
if (!defined('SUPER_ADMIN_EMAIL')) {
    define('SUPER_ADMIN_EMAIL', 'admin1@gordoncollege.edu.ph');
}
