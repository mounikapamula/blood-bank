<?php
session_start();
require 'config.php';

$userId = (int) $_SESSION['user_id'];
$nid    = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($nid) {
    // mark ONE notification as read
    $sql = "UPDATE notifications SET seen = 1
            WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $nid, $userId);
    $stmt->execute();
} else {
    // mark ALL as read
    $conn->query("UPDATE notifications SET seen = 1 WHERE user_id = $userId");
}
echo 'OK';
