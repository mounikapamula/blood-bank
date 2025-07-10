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
    // Start a transaction for data integrity (if you have related tables)
    $conn->begin_transaction();

    // !!! IMPORTANT !!!
    // If you have other tables linked to 'donors' by foreign keys (e.g., donations, requests),
    // you MUST delete related records first or set ON DELETE CASCADE in your DB schema.
    // Example (conceptual, adjust to your actual schema):
    // $stmt = $conn->prepare("DELETE FROM user_donations WHERE user_id = ?");
    // $stmt->bind_param("i", $userId);
    // $stmt->execute();
    // $stmt->close();

    // $stmt = $conn->prepare("DELETE FROM user_requests WHERE user_id = ?");
    // $stmt->bind_param("i", $userId);
    // $stmt->execute();
    // $stmt->close();
    // !!! END IMPORTANT !!!

    // Now, delete the user from the donors table
    $stmt = $conn->prepare("DELETE FROM donors WHERE id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $conn->commit(); // Commit the transaction
        session_destroy(); // Destroy the session, log the user out
        $_SESSION['message'] = 'Your account has been permanently deleted.';
        $_SESSION['message_type'] = 'success';
        // Redirect to the homepage or a public message page after deletion
        header('Location: index.php'); // Or login.php, or a dedicated "account deleted" page
        exit();
    } else {
        $conn->rollback(); // Rollback on error
        throw new Exception("Database deletion failed: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    if ($conn->in_transaction) { // Check if a transaction is active before rollback
        $conn->rollback();
    }
    $_SESSION['message'] = 'An error occurred during account deletion: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    header('Location: settings.php'); // Redirect back to settings on error
    exit();
}

$conn->close();
?>