<?php
/*****************************************************************
 *  DONOR DASHBOARD  – with real‑time bell‑icon notifications
 *****************************************************************/
session_start();
require 'config.php';                      // sets $conn (mysqli)

/* 1️⃣  Authorisation */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['donor','both'], true)) {
    header('Location: login.php');
    exit;
}
$donorId = (int) $_SESSION['user_id'];

/* 2️⃣  Donor profile */
$prof = $conn->prepare(
    "SELECT name, blood_group, city,
            COALESCE(profile_pic,'assets/default_pp.png') AS profile_pic
     FROM   donors
     WHERE  id = ?"
);
$prof->bind_param('i', $donorId);
$prof->execute();
$donor = $prof->get_result()->fetch_assoc();
if (!$donor) { echo 'Donor record not found.'; exit; }

/* 3️⃣  Eligibility (last donation) */
$lastQ = $conn->prepare(
    "SELECT MAX(donated_at) AS last_don FROM donations WHERE donor_id = ?"
);
$lastQ->bind_param('i', $donorId);
$lastQ->execute();
$lastRow  = $lastQ->get_result()->fetch_assoc();
$lastDate = $lastRow['last_don'] ? new DateTime($lastRow['last_don']) : null;

$eligible = true; $daysLeft = 0; $nextDate = null;
if ($lastDate) {
    $next = (clone $lastDate)->modify('+90 days');
    $today = new DateTime('today', new DateTimeZone('Asia/Kolkata'));
    if ($today < $next) {
        $eligible = false;
        $nextDate = $next->format('d M Y');
        $daysLeft = $today->diff($next)->days;
    }
}

/* 4️⃣  Matching open requests (blood group + city) */
$req = $conn->prepare(
    "SELECT br.id, br.hospital, br.city,
            br.units_needed, br.requested_at,
            d.name AS seeker_name
     FROM   blood_requests br
     JOIN   donors d ON d.id = br.seeker_id
     WHERE  br.blood_group = ?
       AND  br.city        = ?
       AND  br.donor_id IS NULL
       AND  br.status = 'pending'
     ORDER  BY br.requested_at DESC"
);
$req->bind_param('ss', $donor['blood_group'], $donor['city']);
$req->execute();
$open = $req->get_result();

/* 5️⃣  Donation history */
$hist = $conn->prepare(
    "SELECT hospital, city, units, donated_at
     FROM   donations
     WHERE  donor_id = ?
     ORDER  BY donated_at DESC"
);
$hist->bind_param('i', $donorId);
$hist->execute();
$donations = $hist->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Donor Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<!-- 🔴 Top bar -->
<nav class="bg-red-700 text-white">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">

    <!-- Logo -->
    <a href="donor_dashboard.php" class="flex items-center gap-2 text-xl font-semibold">
      <img src="assets/logo.png" class="h-8" alt="">
      Blood Bank Portal
    </a>

    <!-- Right side -->
    <div class="flex items-center gap-6">

      <!-- 🔔 Bell + badge + dropdown (JS‑driven) -->
      <div class="relative inline-block">
        <button id="bellBtn"
                class="relative p-2 text-yellow-400 hover:text-yellow-300 focus:outline-none">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.293.293A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z" />
            <path d="M10 18a2 2 0 002-2H8a2 2 0 002 2z" />
          </svg>
          <span id="notifCount"
                class="absolute -top-1 -right-1 bg-red-600 text-white text-xs
                       px-1.5 rounded-full hidden"></span>
        </button>

        <div id="notifDropdown"
             class="absolute right-0 mt-2 w-80 bg-white border border-gray-200
                    rounded shadow-lg z-50 hidden">
          <div class="p-3 font-semibold border-b">Notifications</div>
          <div id="notifBody" class="max-h-80 overflow-y-auto divide-y"></div>
        </div>
      </div>

      <!-- Avatar dropdown -->
      <div class="relative" x-data="{open:false}">
        <button @click="open=!open" class="flex items-center gap-2 focus:outline-none">
          <img src="<?=htmlspecialchars($donor['profile_pic'])?>"
               class="h-9 w-9 rounded-full object-cover border" alt="">
          <span class="hidden sm:block"><?=htmlspecialchars($donor['name'])?></span>
        </button>

        <div x-show="open" x-cloak @click.away="open=false"
             class="absolute right-0 mt-2 w-48 bg-white text-gray-700
                    rounded-md shadow-lg ring-1 ring-black/10">
          <a href="profile.php"  class="block px-4 py-2 text-sm hover:bg-gray-100">My Profile</a>
          <a href="settings.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Settings</a>
          <form action="logout.php" method="post">
            <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">
              Logout
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</nav>

<main class="max-w-7xl mx-auto p-6 space-y-10">

  <!-- Profile / Eligibility / Stats -->
  <div class="grid lg:grid-cols-3 gap-6">
    <!-- Profile -->
    <div class="bg-white rounded-xl shadow p-6 flex gap-4 items-center">
      <img src="<?=htmlspecialchars($donor['profile_pic'])?>" class="h-24 w-24 rounded-full object-cover border" alt="">
      <div>
        <h2 class="text-xl font-bold"><?=htmlspecialchars($donor['name'])?></h2>
        <p class="text-gray-600">Blood Group: <strong><?=$donor['blood_group']?></strong></p>
        <p class="text-gray-600">City: <?=htmlspecialchars($donor['city'])?></p>
      </div>
    </div>

    <!-- Eligibility -->
    <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between">
      <h3 class="text-lg font-semibold mb-2">Eligibility</h3>
      <?php if ($eligible): ?>
        <p class="text-green-600 font-semibold text-2xl">Eligible ✔</p>
        <p class="text-sm text-gray-500">You can donate today.</p>
      <?php else: ?>
        <p class="text-yellow-500 font-semibold text-xl">Not Eligible</p>
        <p class="text-sm text-gray-500">
          Next date: <strong><?=$nextDate?></strong><br>
          (<?=$daysLeft?> days left)
        </p>
      <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between">
      <h3 class="text-lg font-semibold mb-2">Donation Stats</h3>
      <p class="text-3xl font-bold text-red-600"><?=$donations->num_rows?></p>
      <p class="text-sm text-gray-500">Total donations recorded</p>
    </div>
  </div>

  <!-- Matching Requests -->
  <section class="bg-white rounded-xl shadow p-6">
    <h2 class="text-xl font-semibold mb-4">Matching Requests</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-red-100">
          <tr>
            <th class="p-2">Seeker</th>
            <th class="p-2">Hospital / City</th>
            <th class="p-2 text-center">Units</th>
            <th class="p-2">Requested</th>
            <th class="p-2">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($open->num_rows): ?>
          <?php while ($r = $open->fetch_assoc()): ?>
            <tr class="border-t">
              <td class="p-2"><?=htmlspecialchars($r['seeker_name'])?></td>
              <td class="p-2"><?=htmlspecialchars($r['hospital'])?> / <?=htmlspecialchars($r['city'])?></td>
              <td class="p-2 text-center"><?=$r['units_needed']?></td>
              <td class="p-2"><?=date('d M Y H:i', strtotime($r['requested_at']))?></td>
              <td class="p-2">
                <?php if ($eligible): ?>
                  <a href="accept_request.php?request_id=<?=$r['id']?>"
                     class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                    Accept
                  </a>
                <?php else: ?>
                  <span class="text-gray-400 italic">Wait</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center py-4 text-gray-500">
              No matching requests at the moment.
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Donation history -->
  <section class="bg-white rounded-xl shadow p-6">
    <h2 class="text-xl font-semibold mb-4">My Donations</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-green-100">
          <tr>
            <th class="p-2">Hospital / City</th>
            <th class="p-2 text-center">Units</th>
            <th class="p-2">Date</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($donations->num_rows): ?>
          <?php while ($d = $donations->fetch_assoc()): ?>
            <tr class="border-t">
              <td class="p-2"><?=htmlspecialchars($d['hospital'])?> / <?=htmlspecialchars($d['city'])?></td>
              <td class="p-2 text-center"><?=$d['units']?></td>
              <td class="p-2"><?=date('d M Y H:i', strtotime($d['donated_at']))?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="3" class="text-center py-4 text-gray-500">
              No donations recorded yet.
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<!-- 🔔 Notification JS -->
<script>
/* toggle dropdown */
document.getElementById('bellBtn').addEventListener('click', () => {
  document.getElementById('notifDropdown').classList.toggle('hidden');
});

/* load + render notifications */
async function loadNotifications() {
  const res  = await fetch('notifications.php');
  const list = await res.json();

  const body  = document.getElementById('notifBody');
  const badge = document.getElementById('notifCount');

  const unseen = list.filter(n => n.seen == 0);
  badge.textContent = unseen.length;
  badge.classList.toggle('hidden', unseen.length === 0);

  body.innerHTML = '';

list.forEach(n => {
  const card = document.createElement('div');
  card.className = 'relative px-4 py-3 border-b group';

  /* close (×) button */
  const closeBtn = document.createElement('button');
  closeBtn.innerHTML = '&times;';
  closeBtn.className =
      'absolute top-2 right-2 text-gray-400 hover:text-gray-600 text-lg leading-none';
  closeBtn.onclick = () => dismissNotif(n.id, card);
  card.appendChild(closeBtn);

  /* message + time */
  card.innerHTML += `
    <p class="text-sm mb-1 text-gray-800">${n.message}</p>
    <p class="text-xs text-gray-400">
        ${new Date(n.created_at).toLocaleString()}
    </p>
  `;

  /* Accept button (unchanged) */
  if (n.type === 'request') {
    const btn = document.createElement('button');
    btn.textContent = 'Accept';
    btn.className   = 'mt-2 bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded';
    btn.onclick     = () => acceptRequest(n.related_id);
    card.appendChild(btn);
  }

  body.appendChild(card);
});

/* accept via AJAX */
async function acceptRequest(requestId) {
  const res = await fetch('accept_request.php', {
    method: 'POST',
    headers: {
      'Content-Type':'application/x-www-form-urlencoded',
      'X-Requested-With':'XMLHttpRequest'
    },
    body: `request_id=${requestId}&accept=1`
  });
  const out = await res.json();
  alert(out.success ? '✅ Request accepted!' :
        (out.error || 'Already accepted by someone else.'));
  loadNotifications();
}

/* initial + poll every 30 s */
loadNotifications();
setInterval(loadNotifications, 30000);


</script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
