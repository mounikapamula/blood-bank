<?php
// seeker_dashboard.php – revised (bell beside avatar + live notifications)
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seeker') {
  header('Location: login.php');
  exit();
}

$conn = new mysqli('localhost', 'root', '', 'mounika');
if ($conn->connect_error) {
  die('Connection failed: ' . $conn->connect_error);
}

// ---- session helpers ----
$userId     = $_SESSION['user_id'];
$userName   = $_SESSION['user_name'] ?? 'Seeker';

// Profile pic fix (use default if not set or file doesn't exist)
$profilePic = $_SESSION['profile_pic'] ?? '';
if (!$profilePic || (!filter_var($profilePic, FILTER_VALIDATE_URL) && !file_exists(__DIR__ . '/' . $profilePic))) {
  $profilePic = 'https://ui-avatars.com/api/?name=' . urlencode($userName);
}

// ---- recent requests query ----
$sql  = "SELECT br.id, br.units_needed, br.hospital, br.city,
                br.requested_at, d.name AS donor_name
         FROM   blood_requests br
         LEFT JOIN donors d ON br.donor_id = d.id
         WHERE  br.seeker_id = ?
         ORDER  BY br.id DESC
         LIMIT 5";
$stmt = $conn->prepare($sql);
$result = null;
if ($stmt) {
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  $result = new ArrayObject();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seeker Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-red-50 min-h-screen">

  <!-- Navbar -->
  <header class="bg-red-700 text-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto flex items-center justify-between py-4 px-6">

      <h1 class="text-2xl font-bold flex items-center gap-1">🩸&nbsp;Seeker&nbsp;Dashboard</h1>

      <!-- right side -->
      <div class="flex items-center gap-6">

        <!-- 🔔 Bell Icon → link to notifications -->
        <a href="notifications.php" class="relative p-2 text-yellow-400 hover:text-yellow-300 focus:outline-none">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.293.293A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z"/>
            <path d="M10 18a2 2 0 002-2H8a2 2 0 002 2z"/>
          </svg>
        </a>

        <!-- 👤 Avatar Dropdown -->
        <div class="relative" x-data="{ open: false }">
          <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
            <img src="<?= htmlspecialchars($profilePic) ?>"
                 alt="avatar" class="w-8 h-8 rounded-full border-2 border-white"/>
            <svg :class="{ 'rotate-180': open }" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    clip-rule="evenodd"/>
            </svg>
          </button>

          <div x-show="open" x-cloak @click.away="open=false"
               class="absolute right-0 mt-2 w-48 bg-white text-gray-700
                      rounded shadow-lg z-10">
            <a href="profile.php"      class="block px-4 py-2 hover:bg-red-100">My Profile</a>
            <a href="settings.php"     class="block px-4 py-2 hover:bg-red-100">Settings</a>
            <a href="my_requests.php"  class="block px-4 py-2 hover:bg-red-100">📝 My Requests</a>
            <div class="border-t"></div>
            <a href="logout.php"       class="block px-4 py-2 hover:bg-red-100 text-red-600">Logout</a>
          </div>
        </div>

      </div>
    </div>
  </header>

  <!-- MAIN Content -->
  <main class="max-w-7xl mx-auto px-6 py-10">

    <h2 class="text-3xl font-bold text-red-700 mb-6">
      Welcome, <?= htmlspecialchars($userName) ?>!
    </h2>

    <!-- Quick Links -->
    <div class="grid md:grid-cols-2 gap-6 mb-12">
      <a href="request_form.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg border hover:scale-[1.02] transition">
        <h3 class="text-xl font-semibold text-red-600">📝 Request Blood</h3>
        <p class="text-sm text-gray-600 mt-1">Fill details to request blood from suitable donors.</p>
      </a>

      <a href="donor_search.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg border hover:scale-[1.02] transition">
        <h3 class="text-xl font-semibold text-red-600">🔍 Search Donors</h3>
        <p class="text-sm text-gray-600 mt-1">Find donors by blood group or location.</p>
      </a>
    </div>

    <!-- Recent Requests Table -->
    <h3 class="text-2xl font-semibold text-gray-800 mb-4">📝 Recent Requests</h3>
    <div class="overflow-x-auto bg-white shadow rounded-lg">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="py-3 px-4">Donor</th>
            <th class="py-3 px-4">Units</th>
            <th class="py-3 px-4">Hospital</th>
            <th class="py-3 px-4">City</th>
            <th class="py-3 px-4">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="border-t">
                <td class="py-2 px-4"><?= htmlspecialchars($row['donor_name'] ?? 'N/A') ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($row['units_needed']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($row['hospital']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($row['city']) ?></td>
                <td class="py-2 px-4 text-gray-500 text-xs"><?= date('d M Y', strtotime($row['requested_at'] ?? 'now')) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5" class="text-center py-4 text-gray-500">No recent requests.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</body>
</html>
