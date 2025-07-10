<?php
/*****************************************************************
 * SETTINGS PAGE (Donor / Seeker / Both)
 *****************************************************************/

session_start();

require 'config.php'; // $conn  (mysqli)

// 🔐 User not logged in? Redirect.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}


$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

// 🔁 Decide which table to query based on user role
$tables = [
    'donor'  => 'donors',
    'seeker' => 'users',
    'both'   => 'donors' // You can change this to a shared table if needed
];
$table = $tables[$role] ?? 'donors'; // default fallback

// 🔑 CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// 📥 Fetch user details
$stmt = $conn->prepare(
    "SELECT name, email, role, last_login, enable_2fa, COALESCE(profile_pic,'assets/default_pp.png') AS profile_pic
     FROM $table
     WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: login.php');
    exit();
}

$twoFAEnabled = (bool)$user['enable_2fa'];

// 📦 Feedback messages
$message = '';
$messageType = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message'], $_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Settings</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen" x-data="{}">

<nav class="bg-red-700 text-white">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="donor_dashboard.php" class="flex items-center gap-2 text-xl font-semibold">
      <img src="assets/logo.png" class="h-8" alt="">
      Blood Bank Portal
    </a>
    <div class="relative" x-data="{ open:false }">
      <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
        <img src="<?=htmlspecialchars($user['profile_pic'])?>" class="h-9 w-9 rounded-full object-cover border border-white/40" alt="">
        <span class="hidden sm:block"><?=htmlspecialchars($user['name'])?></span>
        <svg class="h-4 w-4 text-white/70" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.698l3.71-3.47a.75.75 0 011.04 1.08l-4.25 4a.75.75 0 01-1.04 0l-4.25-4a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
      </button>
      <div x-show="open" x-cloak @click.away="open=false" class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 text-gray-700">
        <a href="profile.php" class="block px-4 py-2 text-sm hover:bg-gray-100">My Profile</a>
        <a href="settings.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Settings</a>
        <form action="logout.php" method="post">
          <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">Logout</button>
        </form>
      </div>
    </div>
  </div>
</nav>

<main class="max-w-4xl mx-auto p-6 space-y-8">
  <h1 class="text-2xl font-semibold text-gray-800">Settings</h1>

  <?php if ($message): ?>
  <div class="p-3 rounded-md
    <?php
      if ($messageType === 'success') echo 'bg-green-100 text-green-800 border border-green-200';
      else if ($messageType === 'error') echo 'bg-red-100 text-red-800 border border-red-200';
      else echo 'bg-blue-100 text-blue-800 border border-blue-200';
    ?>
    ">
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>

  <!-- 🔐 SECURITY SECTION -->
  <section class="bg-white rounded-xl shadow p-6" id="security">
    <h2 class="text-lg font-semibold mb-4 text-gray-800">Security</h2>

    <form action="change_password.php" method="post" class="space-y-4 max-w-md" onsubmit="return validatePasswordChange(this);">
      <input type="hidden" name="user_id" value="<?=$userId?>">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <div>
        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
        <input name="current_password" id="current_password" type="password" required class="mt-1 w-full rounded border-gray-300 focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
          <input name="new_password" id="new_password" type="password" minlength="6" required class="mt-1 w-full rounded border-gray-300 focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
        </div>
        <div>
          <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
          <input name="confirm_password" id="confirm_password" type="password" minlength="6" required class="mt-1 w-full rounded border-gray-300 focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
        </div>
      </div>
      <p id="password_match_error" class="text-red-500 text-sm hidden">New passwords do not match.</p>
      <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">Update Password</button>
    </form>

    <hr class="my-6 border-gray-200">

    <form action="toggle_2fa.php" method="post" class="flex items-center gap-4">
      <input type="hidden" name="user_id" value="<?=$userId?>">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <span class="text-sm font-medium text-gray-700">Two‑Factor Authentication</span>
      <label class="inline-flex relative cursor-pointer">
        <input type="checkbox" name="enable_2fa" value="1" <?= $twoFAEnabled ? 'checked' : '' ?> class="sr-only peer" onchange="this.form.submit()">
        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-red-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:h-5 after:w-5 after:rounded-full after:transition-all peer-checked:after:translate-x-full"></div>
      </label>
    </form>

    <p class="mt-4 text-sm text-gray-500">Last login: <strong><?= date('d M Y, H:i', strtotime($user['last_login'])) ?></strong></p>

    <hr class="my-6 border-gray-200">

    <!-- 🔥 Deactivate / Delete -->
    <div class="space-y-3 max-w-md">
      <form action="deactivate_account.php" method="post" onsubmit="return confirm('Deactivate your account? You can reactivate by logging in again.');">
        <input type="hidden" name="user_id" value="<?=$userId?>">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-opacity-50">Deactivate Account</button>
      </form>
      <form action="delete_account.php" method="post" onsubmit="return confirm('⚠️ Permanently delete your account? This cannot be undone.');">
        <input type="hidden" name="user_id" value="<?=$userId?>">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <button type="submit" class="w-full bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-opacity-50">Delete Account Permanently</button>
      </form>
    </div>
  </section>

  <!-- 🔄 SWITCH ROLE -->
  <section class="bg-white rounded-xl shadow p-6" id="role-switch">
    <h2 class="text-lg font-semibold mb-4 text-gray-800">Switch Role</h2>
    <form action="update_role.php" method="post" class="max-w-sm space-y-4">
      <input type="hidden" name="user_id" value="<?=$userId?>">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <label class="block text-sm font-medium text-gray-700">Current Role: <strong class="ml-1 capitalize"><?=$user['role']?></strong></label>
      <select name="new_role" required class="w-full rounded border-gray-300 focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
        <option value="donor"  <?= $user['role']==='donor'  ? 'selected':'' ?>>Donor</option>
        <option value="seeker" <?= $user['role']==='seeker' ? 'selected':'' ?>>Seeker</option>
        <option value="both"   <?= $user['role']==='both'   ? 'selected':'' ?>>Both (Donor & Seeker)</option>
      </select>
      <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">Update Role</button>
    </form>
    <p class="mt-3 text-sm text-gray-500">Use this switch if you ever need to request blood urgently while still keeping your donor profile.</p>
  </section>
</main>

<script>
    function validatePasswordChange(form) {
        const newPassword = form.querySelector('#new_password').value;
        const confirmPassword = form.querySelector('#confirm_password').value;
        const errorMessage = form.querySelector('#password_match_error');
        if (newPassword !== confirmPassword) {
            errorMessage.classList.remove('hidden');
            return false;
        } else {
            errorMessage.classList.add('hidden');
        }
        return true;
    }
</script>

</body>
</html>
