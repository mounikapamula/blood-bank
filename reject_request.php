<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['request_id'])) {
    header('Location: donor_dashboard.php');
    exit();
}

$requestId = (int)$_GET['request_id'];
$donorId = (int)$_SESSION['user_id'];

// Here's the core logic: we update the status of the request to 'rejected'.
// This is the simplest method, as discussed earlier.
$stmt = $conn->prepare("UPDATE blood_requests SET status = 'rejected' WHERE id = ?");
$stmt->bind_param('i', $requestId);

if ($stmt->execute()) {
    echo "<script>alert('Request rejected successfully.'); window.location.href='donor_dashboard.php';</script>";
} else {
    echo "<script>alert('Error rejecting request.'); window.location.href='donor_dashboard.php';</script>";
}

$stmt->close();
$conn->close();
?>