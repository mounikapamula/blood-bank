<?php
session_start();
require 'config.php';

// 1️⃣ Guard-rails: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

// 2️⃣ Determine the notification type based on the user's role
$notificationType = null;
if ($role === 'seeker') {
    // A seeker's notification is a request being 'accepted'
    $notificationType = 'accepted'; 
    $pageTitle = 'Seeker Notifications';
    $backLink = 'seeker_dashboard.php';
} elseif ($role === 'donor') {
    // A donor's notification is for a new 'request'
    $notificationType = 'request';
    $pageTitle = 'Donor Notifications';
    $backLink = 'donor_dashboard.php';
} else {
    // Handle invalid or missing role
    echo "Invalid user role.";
    exit();
}

// 3️⃣ Mark all notifications as seen for this user
$stmt_update = $conn->prepare("UPDATE notifications SET seen = 1 WHERE user_id = ? AND type = ?");
$stmt_update->bind_param('is', $userId, $notificationType);
$stmt_update->execute();
$stmt_update->close();

// 4️⃣ Fetch notifications for the user
$stmt = $conn->prepare("
    SELECT id, message, type, related_id, created_at, seen
    FROM notifications
    WHERE user_id = ? AND type = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->bind_param('is', $userId, $notificationType);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-red-50 min-h-screen">
    <header class="bg-red-700 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between py-4 px-6">
            <h1 class="text-2xl font-bold">🔔 Your Notifications</h1>
            <a href="<?= htmlspecialchars($backLink) ?>" class="text-red-300 hover:text-white transition">
                ← Back to Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-10">
        <div class="space-y-6">
            <?php if (empty($notifications)): ?>
                <p class="text-gray-500 text-center text-lg mt-10">You have no notifications at the moment.</p>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
                        <p class="text-gray-800 text-lg">
                            <span class="font-bold text-red-600">🩸</span>
                            <?= htmlspecialchars($n['message']) ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-2">Received on: <?= date('F j, Y, g:i a', strtotime($n['created_at'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>