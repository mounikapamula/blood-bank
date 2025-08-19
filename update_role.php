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

// --- Input Validation ---
$newRole = $_POST['new_role'] ?? '';

// Whitelist valid roles
$validRoles = ['donor', 'seeker', 'both'];

if (!in_array($newRole, $validRoles)) {
    $_SESSION['message'] = 'Invalid role selected.';
    $_SESSION['message_type'] = 'error';
    header('Location: settings.php');
    exit();
}

// --- Database Operations ---
try {
    $stmt = $conn->prepare("UPDATE donors SET role = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("si", $newRole, $userId);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Your role has been updated to ' . htmlspecialchars($newRole) . '.';
        $_SESSION['message_type'] = 'success';
        // Update the role in the session too, if you store it there
        $_SESSION['role'] = $newRole;
    } else {
        throw new Exception("Database update failed: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['message'] = 'An error occurred during role update: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

$conn->close();
header('Location: settings.php');
exit();
?>