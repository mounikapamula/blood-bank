<?php
/**********************************************************************
 * donor_search.php  –  FINAL
 *********************************************************************/
session_start();
$loggedInId = $_SESSION['user_id'] ?? 0;

$conn = new mysqli('localhost', 'root', '', 'mounika');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$q    = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

if ($q === '') {
    $stmt = $conn->prepare(
        "SELECT id, name, blood_group, city, phone
           FROM donors
          WHERE id != ?
          ORDER BY name"
    );
    $stmt->bind_param("i", $loggedInId);
} else {
    $stmt = $conn->prepare(
        "SELECT id, name, blood_group, city, phone
           FROM donors
          WHERE id != ?
            AND (name LIKE ? OR blood_group LIKE ? OR city LIKE ?)
          ORDER BY name"
    );
    $stmt->bind_param("isss", $loggedInId, $like, $like, $like);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Find Donor</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-red-50">
  <div class="w-full max-w-[420px] bg-white rounded-2xl shadow-lg px-5 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <a href="seeker_dashboard.php" class="text-gray-700">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
             d="M15 19l-7-7 7-7" /></svg>
      </a>
      <h1 class="text-lg font-semibold text-gray-900">Find Donor</h1>
      <button class="bg-red-600 p-3 rounded-xl shadow-md hover:bg-red-700">
        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
             d="M3 4h18M6 8h12M10 12h4M14 16h-4M3 20h18" /></svg>
      </button>
    </div>

    <!-- Search bar -->
    <form action="donor_search.php" method="get" class="relative mb-6">
      <input
        type="text"
        name="q"
        value="<?= htmlspecialchars($q) ?>"
        placeholder="Search"
        class="w-full bg-gray-100 pl-11 pr-4 py-3 rounded-xl placeholder-gray-500
               focus:bg-white focus:outline-none"
      />
      <button class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
             d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z" /></svg>
      </button>
    </form>

    <!-- Donor list -->
    <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="bg-gray-100 rounded-xl py-3 px-4">
          <div class="flex items-center justify-between">
            <!-- left: avatar + info -->
            <div class="flex items-center gap-3">
              <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['name']) ?>&size=64&background=F3F4F6&color=111"
                   class="h-10 w-10 rounded-full" />
              <div>
                <p class="font-semibold text-gray-900 leading-tight">
                  <?= htmlspecialchars($row['name']) ?>
                </p>
                <p class="text-sm text-gray-500 flex items-center gap-1">
                  <svg class="h-4 w-4 text-red-500" fill="currentColor"
                       viewBox="0 0 24 24"><path
                    d="M12 2C8.134 2 5 5.134 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.866-3.134-7-7-7z"/></svg>
                  <?= htmlspecialchars($row['city']) ?>
                </p>
              </div>
            </div>

            <!-- right: blood group badge -->
            <div class="relative">
              <svg class="h-10 w-10 text-red-600" fill="currentColor"
                   viewBox="0 0 24 24"><path
                d="M12 2.69l-.717.737C8.262 6.49 6 9.677 6 12.75 6 17.081 8.91 20 12 20s6-2.919 6-7.25c0-3.073-2.262-6.26-5.283-9.323L12 2.69z"/></svg>
              <span
                class="absolute inset-0 flex items-center justify-center font-bold text-white text-sm">
                <?= $row['blood_group'] ?>
              </span>
            </div>
          </div>

          <!-- Request Blood form -->
          <form action="request_form.php" method="get" class="mt-3">
            <input type="hidden" name="donor_id" value="<?= $row['id'] ?>">
            <button
              type="submit"
              class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
              Request Blood
            </button>
          </form>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
        <p class="text-center text-gray-500">No donors found.</p>
    <?php endif; ?>
    </div>
  </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
