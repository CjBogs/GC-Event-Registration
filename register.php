<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // DB connection
    $conn = new mysqli("localhost", "root", "", "users_db");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Sanitize and validate inputs
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $course     = $_POST['course'] ?? null;
    $year       = $_POST['year'] ?? null;
    $block      = $_POST['block'] ?? null;
    $role       = $_POST['role'];
    $status     = 'approved';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<p style='color: red;'>Invalid email format.</p>";
        exit;
    }

    // Check if email already exists
    $checkSql = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo "<p style='color: red;'>Email is already registered.</p>";
        $checkStmt->close();
        $conn->close();
        exit;
    }
    $checkStmt->close();

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insertSql = "INSERT INTO users (first_name, last_name, email, password, profile_image, course, year, block, role, status)
                  VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param(
        "sssssssss",
        $first_name,
        $last_name,
        $email,
        $passwordHash,
        $course,
        $year,
        $block,
        $role,
        $status
    );

    if ($stmt->execute()) {
        echo "<p style='color: green;'>User registered successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Temporary Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <form method="POST" action="" class="bg-white p-8 rounded-lg shadow-md w-full max-w-lg space-y-4">
        <h2 class="text-2xl font-bold mb-4 text-center text-green-800">Temporary Register Form</h2>

        <div class="grid grid-cols-2 gap-4">
            <input type="text" name="first_name" placeholder="First Name" required class="input" />
            <input type="text" name="last_name" placeholder="Last Name" required class="input" />
        </div>

        <input type="email" name="email" placeholder="Email" required class="input w-full" />
        <input type="password" name="password" placeholder="Password" required class="input w-full" />

        <div class="grid grid-cols-3 gap-4">
            <input type="text" name="course" placeholder="Course" class="input" />
            <input type="text" name="year" placeholder="Year" class="input" />
            <input type="text" name="block" placeholder="Block" class="input" />
        </div>

        <select name="role" required class="input w-full">
            <option value="" disabled selected>Select Role</option>
            <option value="user">Student</option>
            <option value="admin">Admin</option>
        </select>

        <button type="submit"
            class="w-full bg-green-700 text-white font-semibold py-2 rounded hover:bg-green-800 transition">Register</button>
    </form>
    <div class="text-center mt-4">
        <a href="landing-page.php" class="text-sm text-red-600 hover:underline">Back to Login</a>
    </div>

    <style>
        .input {
            @apply border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-600;
        }
    </style>
</body>

</html>