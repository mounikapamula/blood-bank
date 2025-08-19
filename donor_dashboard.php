<?php

/*****************************************************************
 *  DONOR DASHBOARD  – with real‑time bell‑icon notifications
 *****************************************************************/
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['donor', 'both'], true)) {
  header('Location: login.php');
  exit;
}
$donorId = (int) $_SESSION['user_id'];

$prof = $conn->prepare(
  "SELECT name, blood_group, city,
            COALESCE(profile_pic,'assets/default_pp.png') AS profile_pic
     FROM donors
     WHERE id = ?"
);
$prof->bind_param('i', $donorId);
$prof->execute();
$donor = $prof->get_result()->fetch_assoc();
if (!$donor) {
  echo 'Donor record not found.';
  exit;
}

$lastQ = $conn->prepare(
  "SELECT MAX(donated_at) AS last_don FROM donations WHERE donor_id = ?"
);
$lastQ->bind_param('i', $donorId);
$lastQ->execute();
$lastRow  = $lastQ->get_result()->fetch_assoc();
$lastDate = $lastRow['last_don'] ? new DateTime($lastRow['last_don']) : null;

$eligible = true;
$daysLeft = 0;
$nextDate = null;
if ($lastDate) {
  $next = (clone $lastDate)->modify('+90 days');
  $today = new DateTime('today', new DateTimeZone('Asia/Kolkata'));
  if ($today < $next) {
    $eligible = false;
    $nextDate = $next->format('d M Y');
    $daysLeft = $today->diff($next)->days;
  }
}

$req = $conn->prepare(
  "SELECT br.id, br.hospital, br.city, br.units_needed, br.requested_at, u.name AS seeker_name
FROM blood_requests br
LEFT JOIN users u ON u.id = br.seeker_id
WHERE br.blood_group = ?
  AND br.city = ?
  AND br.donor_id IS NULL
  AND br.status = 'pending'
ORDER BY br.requested_at DESC"
);
$req->bind_param('ss', $donor['blood_group'], $donor['city']);
$req->execute();
$open = $req->get_result();

$hist = $conn->prepare(
  "SELECT hospital, city, units, donated_at
     FROM donations
     WHERE donor_id = ?
     ORDER BY donated_at DESC"
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
        <a href="notifications.php" class="relative p-2 text-yellow-400 hover:text-yellow-300 focus:outline-none">
    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 2a6 6 0 00-6 6v3.586l-.293.293A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z" />
        <path d="M10 18a2 2 0 002-2H8a2 2 0 002 2z" />
    </svg>
    <span id="notifCount"
          class="absolute -top-1 -right-1 bg-red-600 text-white text-xs
                 px-1.5 rounded-full hidden"></span>
</a>

        <!-- Avatar dropdown -->
        <div class="relative" x-data="{open:false}">
          <button @click="open=!open" class="flex items-center gap-2 focus:outline-none">
            <img src="<?= htmlspecialchars($donor['profile_pic']) ?>"
              class="h-9 w-9 rounded-full object-cover border" alt="">
            <span class="hidden sm:block"><?= htmlspecialchars($donor['name']) ?></span>
          </button>

          <div x-show="open" x-cloak @click.away="open=false"
            class="absolute right-0 mt-2 w-48 bg-white text-gray-700
                    rounded-md shadow-lg ring-1 ring-black/10">
            <a href="profile.php" class="block px-4 py-2 text-sm hover:bg-gray-100">My Profile</a>
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
        <img src="<?= htmlspecialchars($donor['profile_pic']) ?>" class="h-24 w-24 rounded-full object-cover border" alt="">
        <div>
          <h2 class="text-xl font-bold"><?= htmlspecialchars($donor['name']) ?></h2>
          <p class="text-gray-600">Blood Group: <strong><?= $donor['blood_group'] ?></strong></p>
          <p class="text-gray-600">City: <?= htmlspecialchars($donor['city']) ?></p>
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
            Next date: <strong><?= $nextDate ?></strong><br>
            (<?= $daysLeft ?> days left)
          </p>
        <?php endif; ?>
      </div>

      <!-- Stats -->
      <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between">
        <h3 class="text-lg font-semibold mb-2">Donation Stats</h3>
        <p class="text-3xl font-bold text-red-600"><?= $donations->num_rows ?></p>
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
                  <td class="p-2"><?= htmlspecialchars($r['seeker_name']) ?></td>
                  <td class="p-2"><?= htmlspecialchars($r['hospital']) ?> / <?= htmlspecialchars($r['city']) ?></td>
                  <td class="p-2 text-center"><?= $r['units_needed'] ?></td>
                  <td class="p-2"><?= date('d M Y H:i', strtotime($r['requested_at'])) ?></td>
                  <td class="p-2">
                    <?php if ($eligible): ?>
                       <div class="flex items-center gap-4">
                      <button onclick="acceptRequest(<?= $r['id'] ?>, this)"
    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
    Accept
</button>
                      <a href="reject_request.php?request_id=<?= $r['id'] ?>"
           class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">
            Reject
        </a>
        </div>
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
                  <td class="p-2"><?= htmlspecialchars($d['hospital']) ?> / <?= htmlspecialchars($d['city']) ?></td>
                  <td class="p-2 text-center"><?= $d['units'] ?></td>
                  <td class="p-2"><?= date('d M Y H:i', strtotime($d['donated_at'])) ?></td>
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
    // 🔔 Real-time Notifications for the Donor Dashboard

    const notifDropdown = document.getElementById('notifDropdown');
    const notifBody = document.getElementById('notifBody');
    const notifCount = document.getElementById('notifCount');

    // Toggles the notification dropdown visibility
    document.getElementById('bellBtn').addEventListener('click', () => {
        notifDropdown.classList.toggle('hidden');
    });

    // Dismisses a notification from the dropdown
    async function dismissNotif(notifId, element) {
        element.remove();
        // Optional: Send an AJAX request to delete from the database
        // fetch('dismiss_notification.php?id=' + notifId);
    }

    // Handles the AJAX request to accept a blood request
    async function acceptRequest(requestId, buttonElement) {
    // Disable the button immediately to prevent multiple clicks
    buttonElement.disabled = true;
    buttonElement.textContent = 'Accepting...';
    buttonElement.classList.add('opacity-50', 'cursor-not-allowed');

    const res = await fetch('accept_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `request_id=${requestId}`
    });
    const out = await res.json();

    if (out.success) {
        alert('✅ Request accepted!');
        // Update the button text to show success
        buttonElement.textContent = 'Accepted';
        buttonElement.classList.replace('bg-green-600', 'bg-gray-400');
    } else {
        alert(out.error || 'Failed to accept request.');
        // Re-enable the button if the request failed
        buttonElement.disabled = false;
        buttonElement.textContent = 'Accept';
        buttonElement.classList.remove('opacity-50', 'cursor-not-allowed');
    }

    loadNotifications();
}

    // Fetches and displays notifications
    async function loadNotifications() {
        try {
            const res = await fetch('notifications.php');
            const notifications = await res.json();

            const unreadCount = notifications.filter(n => n.seen == 0).length;
            notifCount.textContent = unreadCount;
            notifCount.classList.toggle('hidden', unreadCount === 0);

            notifBody.innerHTML = '';
            if (notifications.length === 0) {
                notifBody.innerHTML = '<div class="p-4 text-center text-gray-500 text-sm">No new notifications.</div>';
            } else {
                notifications.forEach(n => {
                    const card = document.createElement('div');
                    card.className = 'relative px-4 py-3 border-b group';
                    
                    // Display the notification message
                    let cardContent = `
                        <p class="text-gray-800 text-sm font-medium">
                            <span class="text-red-600">🩸</span> ${n.message}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            ${new Date(n.created_at).toLocaleString()}
                        </p>
                    `;

                    // If it's a new request notification, add an "Accept" button
                    if (n.type === 'request') {
                        cardContent += `
                            <button onclick="acceptRequest(${n.related_id}); dismissNotif(${n.id}, this.parentElement);"
                                class="mt-2 bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded">
                                Accept
                            </button>
                        `;
                    }
                    
                    // Add a dismiss button
                    cardContent += `
                        <button onclick="dismissNotif(${n.id}, this.parentElement);" 
                            class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 text-lg leading-none">
                            &times;
                        </button>
                    `;
                    
                    card.innerHTML = cardContent;
                    notifBody.appendChild(card);
                });
            }
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    // Load notifications immediately and then every 30 seconds
    loadNotifications();
    setInterval(loadNotifications, 30000);
</script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>

</html>