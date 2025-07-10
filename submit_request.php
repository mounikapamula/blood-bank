<?php
session_start();
require 'config.php'; // make sure $conn is defined

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$seeker_id = $_SESSION['user_id'];
$blood_group = $_POST['blood_group'] ?? '';
$hospital = $_POST['hospital'] ?? '';
$city = $_POST['city'] ?? '';
$units_needed = $_POST['units_needed'] ?? 1;
$notes = $_POST['notes'] ?? '';

// Prepare insert with NULL donor_id initially
$stmt = $conn->prepare("
    INSERT INTO blood_requests (seeker_id, donor_id, blood_group, hospital, city, units_needed, notes)
    VALUES (?, NULL, ?, ?, ?, ?, ?)
");

$stmt->bind_param("isssis", $seeker_id, $blood_group, $hospital, $city, $units_needed, $notes);

if ($stmt->execute()) {
    echo "<script>alert('🩸 Blood request submitted successfully!'); window.location.href='my_requests.php';</script>";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
