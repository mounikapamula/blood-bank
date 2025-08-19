<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$pic = $_SESSION['profile_pic'] ?: 'https://ui-avatars.com/api/?name=User';
?>
<!DOCTYPE html><html><head><title>Full Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="p-6">
  <h1 class="text-2xl font-bold mb-4">Welcome!</h1>
  <img src="<?= htmlspecialchars($pic) ?>" class="w-20 h-20 rounded-full mb-4">
  <div class="space-x-4">
    <?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$pic = $_SESSION['profile_pic'] ?: 'https://ui-avatars.com/api/?name=User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Full Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-50 min-h-screen">
  <h1 class="text-2xl font-bold mb-4">Welcome!</h1>

  <!-- profile pic -->
  <img src="<?= htmlspecialchars($pic) ?>" class="w-20 h-20 rounded-full mb-4">

  <!-- action bar + NEW search bar wrapped in flex -->
  <div class="flex flex-wrap gap-4 items-center">
    <a href="donate.php"  class="bg-red-600 text-white px-4 py-2 rounded">
      Donate Blood
    </a>

    <a href="request.php" class="bg-red-600 text-white px-4 py-2 rounded">
      Request Blood
    </a>

    <!-- ========== NEW: donor search bar ========== -->
    <form action="donor_search.php" method="get"
          class="relative w-full max-w-xs flex-1">
      <input
        type="text"
        name="q"
        placeholder="Search blood donors…"
        class="w-full pl-10 pr-3 py-2 rounded-lg bg-white/90 shadow
               focus:bg-white focus:outline-none text-black"
      />
      <button type="submit"
              class="absolute left-0 inset-y-0 flex items-center pl-2">
        <!-- simple magnifier icon -->
        <svg class="h-5 w-5 text-gray-500" fill="none"
             stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5
                   a7.5 7.5 0 0013.15 6.15z" />
        </svg>
      </button>
    </form>
    <!-- ========== /NEW ========== -->
  </div>
</body>
</html>

    <a href="donate.php"  class="bg-red-600 text-white px-4 py-2 rounded">Donate Blood</a>
    <a href="request.php" class="bg-red-600 text-white px-4 py-2 rounded">Request Blood</a>
  </div>
</body></html>
