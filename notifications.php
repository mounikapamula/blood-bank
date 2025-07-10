<?php
session_start();
require 'config.php';
require_once 'notify_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? '';

// 1️⃣ Notify matching pending requests
if ($role === 'donor' || $role === 'both') {
    $info = $conn->prepare("SELECT blood_group, city FROM donors WHERE id = ?");
    $info->bind_param('i', $userId);
    $info->execute();
    if ($d = $info->get_result()->fetch_assoc()) {
        notifyMatchingPendingRequests(
            $conn,
            $userId,
            $d['blood_group'] ?? '',
            $d['city'] ?? ''
        );
    }
}

// 2️⃣ Fetch notifications
$stmt = $conn->prepare("
    SELECT id, message, type, related_id, created_at, seen
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3️⃣ Mark all as seen
$conn->query("UPDATE notifications SET seen = 1 WHERE user_id = $userId");

function isToday($datetime) {
    return date('Y-m-d', strtotime($datetime)) === date('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notifications</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .animate-fade-in {
      animation: fadeIn 0.3s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="bg-red-50 min-h-screen">
  <div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-3xl font-bold text-red-700">🔔 Your Notifications</h1>
      <a href="seeker_dashboard.php" class="text-red-600 hover:underline">← Back to Home</a>
    </div>

    <!-- Notification Container -->
    <div class="space-y-6">
      <?php
        $grouped = ['today' => [], 'earlier' => []];
        foreach ($notifications as $n) {
            $key = isToday($n['created_at']) ? 'today' : 'earlier';
            $grouped[$key][] = $n;
        }
      ?>

      <?php if (count($notifications) === 0): ?>
        <p class="text-gray-500">You have no notifications at the moment.</p>
      <?php endif; ?>

      <?php if (!empty($grouped['today'])): ?>
        <h2 class="text-xl font-semibold text-gray-700">📅 Today</h2>
        <?php foreach ($grouped['today'] as $n): ?>
          <?php include 'notif_card.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if (!empty($grouped['earlier'])): ?>
        <h2 class="text-xl font-semibold text-gray-700">📂 Earlier</h2>
        <?php foreach ($grouped['earlier'] as $n): ?>
          <?php include 'notif_card.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
