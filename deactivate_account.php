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

// --- Database Operations ---
try {
    // Assuming you have an 'is_active' column (BOOLEAN or TINYINT(1)) in your 'donors' table
    // You might need to add it: ALTER TABLE donors ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
    $stmt = $conn->prepare("UPDATE donors SET is_active = FALSE WHERE id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        // Destroy the session after deactivation so the user is logged out
        session_destroy();
        $_SESSION['message'] = 'Your account has been deactivated. You can reactivate by logging in again.';
        $_SESSION['message_type'] = 'success';
        // Redirect to login page after deactivation
        header('Location: login.php');
        exit();
    } else {
        throw new Exception("Database update failed: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['message'] = 'An error occurred during deactivation: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    header('Location: settings.php'); // Redirect back to settings on error
    exit();
}

$conn->close();
?>