<?php
session_start();
require 'config.php'; // Your database connection file

// --- Security Checks (CRITICAL) ---

// 1. CSRF Token Verification
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['message'] = 'Security token mismatch. Please try again.';
    $_SESSION['message_type'] = 'error';
    header('Location: settings.php');
    exit();
}
// Unset the token after verification to prevent replay attacks
unset($_SESSION['csrf_token']);

// 2. User Authentication and Authorization
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect unauthenticated users
    exit();
}

$submittedUserId = (int)$_POST['user_id'];
$sessionUserId = (int)$_SESSION['user_id'];

// Verify the submitted user_id matches the session user_id
if ($submittedUserId !== $sessionUserId) {
    $_SESSION['message'] = 'Unauthorized action. The user ID does not match your session.';
    $_SESSION['message_type'] = 'error';
    header('Location: settings.php');
    exit();
}

// Use the authenticated user ID from the session for all operations
$userId = $sessionUserId;

// --- Input Validation ---
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    $_SESSION['message'] = 'All password fields are required.';
    $_SESSION['message_type'] = 'error';
    header('Location: settings.php');
    exit();
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['message'] = 'New password and confirm password do not match.';
    $_SESSION['message_type'] = 'error';
    header('Location: settings.php');
    exit();
}

if (strlen($newPassword) < 6) { // Ensure consistent with client-side minlength
    $_SESSION['message'] = 'New password must be at least 6 characters long.';
    $_SESSION['message_type'] = 'error';
    header('Location: settings.php');
    exit();
}

// --- Database Operations ---
try {
    // 1. Fetch current hashed password from the database
    $stmt = $conn->prepare("SELECT password FROM donors WHERE id = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $_SESSION['message'] = 'User not found.';
        $_SESSION['message_type'] = 'error';
        header('Location: settings.php');
        exit();
    }

    $hashedPasswordFromDB = $user['password'];

    // 2. Verify current password
    if (!password_verify($currentPassword, $hashedPasswordFromDB)) {
        $_SESSION['message'] = 'Incorrect current password.';
        $_SESSION['message_type'] = 'error';
        header('Location: settings.php');
        exit();
    }

    // 3. Hash the new password
    $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    if ($newHashedPassword === false) {
        throw new Exception("Password hashing failed.");
    }

    // 4. Update the password in the database
    $stmt = $conn->prepare("UPDATE donors SET password = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("si", $newHashedPassword, $userId);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Password updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        throw new Exception("Database update failed: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['message'] = 'An error occurred: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

$conn->close();
header('Location: settings.php');
exit();
?>