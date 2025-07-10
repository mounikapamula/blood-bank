<?php
// index.php – Enhanced Blood Donation Portal Home Page with Role‑Aware Buttons
session_start();
$isLoggedIn      = isset($_SESSION['user_id']);
$role            = $_SESSION['role'] ?? 'donor'; // donor | seeker | both
$eligibilityStatus = $_SESSION['eligibility_status'] ?? null;
$nextAppointment   = $_SESSION['next_appointment'] ?? null;

$conn = new mysqli('localhost', 'root', '', 'mounika');
if ($conn->connect_error) { die('Connection failed: ' . $conn->connect_error); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blood Donation Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
    .hover-scale:hover{transform:scale(1.05);transition:transform .2s ease-in-out}
  </style>
</head>
<body class="min-h-screen flex flex-col bg-gradient-to-b from-red-50 to-white">
  <!-- NAVBAR -->
  <header class="bg-red-700 text-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto flex items-center justify-between py-4 px-6">
      <h1 class="text-2xl font-bold flex items-center gap-2">🩸 Blood Donation Portal</h1>
      <?php if ($isLoggedIn): ?>
        <div x-data="{open:false}" class="relative" @keydown.escape="open=false">
          <button @click="open=!open" class="flex items-center gap-2 focus:outline-none">
            <img src="https://ui-avatars.com/api/?name=User&background=ffffff&color=dd0000" class="w-8 h-8 rounded-full border-2 border-white shadow"/>
            <svg :class="{'rotate-180':open}" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
          </button>
          <div x-show="open" x-transition @click.away="open=false" class="absolute right-0 mt-2 w-48 bg-white text-gray-700 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden">
            <a href="profile.php"  class="block px-4 py-2 hover:bg-red-600 hover:text-white">My Profile</a>
            <a href="settings.php" class="block px-4 py-2 hover:bg-red-600 hover:text-white">Settings</a>
            <a href="my_requests.php" class="block px-4 py-2 hover:bg-red-600 hover:text-white">📝 My Requests</a>
            <div class="border-t"></div>
            <a href="logout.php"   class="block px-4 py-2 hover:bg-red-600 hover:text-white">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="login.php" class="underline">Login</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- HERO -->
  <main class="flex-grow flex flex-col items-center justify-center text-center px-6 py-12">
    <h2 class="text-5xl font-extrabold text-red-800 mb-4">Be a Lifesaver Today</h2>
    <p class="text-lg text-gray-700 max-w-xl mb-10">Every drop counts. Join the movement to save lives by donating or requesting blood quickly and safely.</p>

    <div class="flex flex-wrap gap-6 justify-center mb-6">
  <?php if ($isLoggedIn): ?>
    <!--<a href="donate.php" class="hover-scale bg-red-600 hover:bg-red-700 text-white text-lg font-semibold py-3 px-8 rounded-full shadow-lg transition">🩸 Donate Blood</a>
    <a href="request.php" class="hover-scale bg-red-600 hover:bg-red-700 text-white text-lg font-semibold py-3 px-8 rounded-full shadow-lg transition">🆘 Request Blood</a>-->
  <?php else: ?>
    <a href="register.php" class="hover-scale bg-red-600 hover:bg-red-700 text-white text-lg font-semibold py-3 px-8 rounded-full shadow-lg transition">Register</a>
  <?php endif; ?>
  <a href="self_assessment.php" class="hover-scale bg-red-600 hover:bg-red-700 text-white text-lg font-semibold py-3 px-8 rounded-full shadow-lg transition">🧬 Am I Eligible?</a>
</div>



    <?php if ($isLoggedIn && $eligibilityStatus==='eligible'): ?>
      <p class="text-green-600 font-semibold mb-2">✅ You’re eligible to donate</p>
    <?php elseif ($isLoggedIn && $eligibilityStatus==='deferred'): ?>
      <p class="text-red-600 font-semibold mb-2">❌ You are temporarily deferred from donating</p>
    <?php endif; ?>

    <?php if ($isLoggedIn && $nextAppointment): ?>
      <p class="text-blue-600 text-sm mb-6">📅 Your next appointment: <?= htmlspecialchars($nextAppointment) ?></p>
    <?php endif; ?>

    <div class="text-gray-800 space-y-3">
      <?php if (!$isLoggedIn): ?>
        <p>New here? <a href="register.php" class="text-red-600 underline">Register</a></p>
      <?php endif; ?>
      <p>Want to learn more? <a href="info.php" class="text-red-600 underline">Visit Info Center</a></p>
    </div>
  </main>

  <footer class="bg-gray-100 py-4 shadow-inner mt-8">
    <div class="max-w-7xl mx-auto text-center text-sm text-gray-600">© <?= date('Y') ?> Blood Donation. All rights reserved.</div>
  </footer>
</body>
</html>
