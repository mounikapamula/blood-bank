<?php
session_start();
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        // Try 'users' table
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        // If not found, try 'donors' table
        if (!$user) {
            $stmt = $conn->prepare("SELECT * FROM donors WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();
        }

        // Validate password
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_name']   = $user['name'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['blood_group'] = $user['blood_group'] ?? '';
            $_SESSION['profile_pic'] = $user['profile_pic'] ?? '';

            $table = $user['role'] === 'seeker' ? 'users' : 'donors';
            $stmt  = $conn->prepare("UPDATE $table SET last_login = NOW() WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();

            $dashboard = $user['role'] === 'seeker' ? 'seeker_dashboard.php' : 'donar_dsahboard.php';
            header("Location: $dashboard");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login • Blood Bank Portal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-50 min-h-screen flex items-center justify-center">
  <div class="bg-white shadow-md rounded-lg p-8 w-full max-w-sm">
    <h2 class="text-2xl font-bold text-center text-red-600 mb-6">🔐 Login</h2>

    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" required
               class="mt-1 block w-full px-4 py-2 border rounded-lg focus:ring-red-500 focus:border-red-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Password</label>
        <input type="password" name="password" required
               class="mt-1 block w-full px-4 py-2 border rounded-lg focus:ring-red-500 focus:border-red-500" />
      </div>
      <button type="submit"
              class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg font-semibold">
        Login
      </button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-4">
      Don't have an account? <a href="register.php" class="text-red-600 hover:underline">Register</a>
    </p>
  </div>
</body>
</html>
