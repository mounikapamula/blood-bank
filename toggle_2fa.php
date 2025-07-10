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
// Unset the token after verification (optional for toggle, but good practice)
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
// The checkbox sends '1' if checked, nothing if unchecked.
// We cast to boolean to ensure it's either true or false.
$enable2FA = isset($_POST['enable_2fa']) && $_POST['enable_2fa'] === '1';

// --- Database Operations ---
try {
    $stmt = $conn->prepare("UPDATE donors SET enable_2fa = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    // Convert boolean to integer (0 or 1) for database storage
    $status = $enable2FA ? 1 : 0;
    $stmt->bind_param("ii", $status, $userId);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Two-Factor Authentication status updated successfully!';
        $_SESSION['message_type'] = 'success';
        // Optionally, update session variable if you store 2FA status there
        $_SESSION['enable_2fa'] = $enable2FA;
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