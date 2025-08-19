<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'];

$table = $role === 'seeker' ? 'users' : 'donors';

// Fetch profile info including latitude & longitude
$sql = "
  SELECT
      name,
      email,
      blood_group,
      city,
      last_login,
      enable_2fa,
      latitude,
      longitude,
      COALESCE(profile_pic,'assets/default_pp.png') AS profile_pic
  FROM   $table
  WHERE  id = ?
  LIMIT  1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: login.php');
    exit();
}

$donationCount = 0;
if ($role !== 'seeker') {
    $q  = $conn->prepare("SELECT COUNT(*) FROM donations WHERE donor_id = ?");
    $q->bind_param('i', $userId);
    $q->execute();
    $q->bind_result($donationCount);
    $q->fetch();
    $q->close();
}

$profilePic = (!empty($user['profile_pic']) && file_exists($user['profile_pic']))
              ? $user['profile_pic']
              : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile • Blood Bank Portal</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen font-[Inter]" x-data="{}">

<nav class="bg-red-700 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
    <a href="donar_dsahboard.php" class="flex items-center gap-2 text-xl font-semibold">
      <img src="assets/logo.png" class="h-8" alt="">
      Blood Bank Portal
    </a>
    <p class="text-gray-100 text-sm">
      Last login:
      <?= !empty($user['last_login']) ? date("d M Y, H:i", strtotime($user['last_login'])) : 'Never' ?>
    </p>
    <div class="relative" x-data="{open:false}">
      <button @click="open=!open" class="flex items-center gap-2 focus:outline-none">
        <img src="<?= htmlspecialchars($profilePic) ?>" class="h-9 w-9 rounded-full object-cover ring-1 ring-white/50" alt="">
        <span class="hidden sm:block"><?= htmlspecialchars($user['name']) ?></span>
        <svg class="h-4 w-4 text-white/70" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.698l3.71-3.47a.75.75 0 011.04 1.08l-4.25 4a.75.75 0 01-1.04 0l-4.25-4a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
        </svg>
      </button>

      <div x-show="open" x-cloak @click.away="open=false"
           class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow ring-1 ring-black/10 py-1 text-gray-700">
        <a href="profile.php" class="block px-4 py-2 text-sm hover:bg-gray-100">My Profile</a>
        <a href="settings.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Settings</a>
        <form action="logout.php" method="post">
          <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">Logout</button>
        </form>
      </div>
    </div>
  </div>
</nav>

<main class="max-w-5xl mx-auto p-6 space-y-10">
  <h1 class="text-2xl font-semibold text-gray-800">My Profile</h1>

  <section class="bg-white rounded-xl shadow-md p-6 flex items-center gap-6">
    <img src="<?= htmlspecialchars($profilePic) ?>" class="h-24 w-24 rounded-full object-cover ring-2 ring-red-600" alt="">
    <div class="space-y-1">
      <h2 class="text-xl font-semibold"><?= htmlspecialchars($user['name']) ?></h2>
      <p class="text-sm text-gray-600">
        <?= htmlspecialchars($role === 'seeker' ? 'Seeker' : ($role === 'donor' ? 'Donor' : 'Donor & Seeker')) ?>
      </p>
      <p class="text-sm"><span class="font-medium">Email:</span> <?= htmlspecialchars($user['email']) ?></p>
      <?php if (!empty($user['blood_group'])): ?>
        <p class="text-sm"><span class="font-medium">Blood Group:</span> <?= htmlspecialchars($user['blood_group']) ?></p>
      <?php endif; ?>
      <?php if (!empty($user['city'])): ?>
        <p class="text-sm"><span class="font-medium">City:</span> <?= htmlspecialchars($user['city']) ?></p>
      <?php endif; ?>
      <p class="text-sm text-gray-500">
        Last login:
        <?= !empty($user['last_login']) ? date("d M Y, H:i", strtotime($user['last_login'])) : 'Never' ?>
      </p>

      <!-- Show donor location if available -->
      <?php if ($role !== 'seeker'): ?>
        <p class="text-sm"><span class="font-medium">Latitude:</span> <?= htmlspecialchars($user['latitude'] ?? 'Not set') ?></p>
        <p class="text-sm"><span class="font-medium">Longitude:</span> <?= htmlspecialchars($user['longitude'] ?? 'Not set') ?></p>
        
        <!-- Location update form -->
        <form action="update_location.php" method="post" class="mt-3 space-y-2">
          <input type="hidden" name="latitude" id="latitude">
          <input type="hidden" name="longitude" id="longitude">
          <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
            Save My Current Location
          </button>
        </form>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($role !== 'seeker'): ?>
  <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-md p-6">
      <h3 class="text-lg font-semibold mb-2">Eligibility</h3>
      <p class="text-2xl font-bold text-green-600 flex items-center gap-2">
        Eligible
        <svg class="h-6 w-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.484a1 1 0 01-1.414 0L3.296 9.71a1 1 0 011.408-1.42l3.838 3.833 6.792-6.833a1 1 0 011.37 0z" clip-rule="evenodd"/>
        </svg>
      </p>
      <p class="text-sm text-gray-600">You can donate today.</p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6">
      <h3 class="text-lg font-semibold mb-2">Donation Stats</h3>
      <p class="text-3xl font-bold text-red-600"><?= $donationCount ?></p>
      <p class="text-sm text-gray-600">Total donations recorded</p>
    </div>
  </div>
  <?php endif; ?>

</main>

<script>
  // Auto-detect location when profile loads
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      document.getElementById('latitude').value = position.coords.latitude;
      document.getElementById('longitude').value = position.coords.longitude;
    });
  }
</script>
</body>
</html>
