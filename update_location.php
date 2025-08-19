<?php
session_start();
require 'config.php';

// Make sure user is logged in & is a donor
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] === 'seeker') {
    header("Location: login.php");
    exit();
}

$userId   = (int) $_SESSION['user_id'];
$role     = $_SESSION['role'];
$table    = $role === 'donor' ? 'donors' : 'donors'; // donors table only

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lat = isset($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $lng = isset($_POST['longitude']) ? trim($_POST['longitude']) : null;

    if ($lat && $lng) {
        $sql = "UPDATE $table SET latitude = ?, longitude = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddi", $lat, $lng, $userId);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Location updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update location.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Could not get latitude/longitude.";
    }
}

header("Location: profile.php");
exit();
?>
