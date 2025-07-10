<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) exit;

$notifId = (int) ($_POST['id'] ?? 0);
$userId  = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $notifId, $userId);
$stmt->execute();
