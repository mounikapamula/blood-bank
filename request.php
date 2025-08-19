<?php
/*****************************************************************
 *  my_requests.php
 *  ----------------
 *  • Shows every blood‑request created by the logged‑in user
 *  • Lets the user cancel a request (POST cancel_request_id)
 *  • Lets the user create a request (POST blood_group …)
 *****************************************************************/

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* ─────── 1. DATABASE CONNECTION ─────── */
$conn = new mysqli('localhost', 'root', '', 'mounika');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$seekerId = (int)$_SESSION['user_id'];

/* ─────── 2. HANDLE POST ACTIONS ─────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* A. Cancel an existing request */
    if (isset($_POST['cancel_request_id'])) {
        $stmt = $conn->prepare(
            "DELETE FROM blood_requests
             WHERE id = ? AND seeker_id = ?"
        );
        $stmt->bind_param("ii", $_POST['cancel_request_id'], $seekerId);
        $stmt->execute();
        $stmt->close();

        header("Location: my_requests.php?flash=cancelled");
        exit();
    }

    /* B. Create a new request */
    if (isset($_POST['blood_group'], $_POST['units'],
              $_POST['hospital'],    $_POST['city'])) {

        $blood_group  = $_POST['blood_group'];          // e.g. "A+"
        $units_needed = (int)$_POST['units'];           // integer
        $hospital     = $_POST['hospital'];
        $city         = $_POST['city'];
        $notes        = $_POST['notes'] ?? '';

       $insert = $conn->prepare("
  INSERT INTO blood_requests (seeker_id, donor_id, units_needed, hospital, city, notes)
  VALUES (?, ?, ?, ?, ?, ?)
");
$insert->bind_param("iiisss", $seeker_id, $donor_id, $units, $hospital, $city, $notes);

        /* 6 placeholders  →  6 letters   →  6 variables     */
        $stmt->bind_param("isisss",
            $seekerId,      // i  (int)
            $blood_group,   // s  (string)
            $units_needed,  // i
            $hospital,      // s
            $city,          // s
            $notes          // s
        );
        $stmt->execute();
        $stmt->close();

        header("Location: my_requests.php?flash=added");
        exit();
    }
}

/* ─────── 3. FETCH ALL REQUESTS MADE BY THE USER ─────── */
$stmt = $conn->prepare(
    "SELECT br.id,
            br.blood_group,
            br.units_needed,
            br.hospital,
            br.city,
            br.notes,
            br.status,
            u.name AS donor_name,
            u.city AS donor_city
     FROM   blood_requests br
     LEFT   JOIN users u ON u.id = br.donor_id
     WHERE  br.seeker_id = ?
     ORDER  BY br.requested_at DESC"
);
$stmt->bind_param("i", $seekerId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Blood Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-50 p-6 min-h-screen">
    <div class="max-w-4xl mx-auto">

        <!-- Flash messages -->
        <?php if (isset($_GET['flash'])): ?>
            <?php if ($_GET['flash'] === 'cancelled'): ?>
                <p class="text-center text-green-700 mb-4">Request cancelled ✔</p>
            <?php elseif ($_GET['flash'] === 'added'): ?>
                <p class="text-center text-green-700 mb-4">Request added ✔</p>
            <?php endif; ?>
        <?php endif; ?>

        <h1 class="text-3xl font-bold text-red-700 mb-6 text-center">
            My Blood Requests
        </h1>

        <?php if ($result->num_rows > 0): ?>
            <div class="space-y-4">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="bg-white p-4 rounded shadow border border-red-200">
                        <h3 class="text-xl font-semibold text-red-800 mb-1">
                            Requested from:
                            <?= htmlspecialchars($row['donor_name'] ?? '—') ?>
                            (<?= $row['blood_group'] ?>)
                        </h3>
                        <p class="text-gray-700">
                            📍 Donor City:
                            <?= htmlspecialchars($row['donor_city'] ?? '—') ?>
                        </p>
                        <p class="text-gray-700">
                            🏥 Hospital: <?= htmlspecialchars($row['hospital']) ?>
                        </p>
                        <p class="text-gray-700">
                            🩸 Units Needed: <?= $row['units_needed'] ?>
                        </p>
                        <p class="text-gray-700">
                            📝 Notes:
                            <?= htmlspecialchars($row['notes']) ?: 'None' ?>
                        </p>
                        <p class="text-gray-500 text-sm mt-2">
                            Request ID: <?= $row['id'] ?> |
                            Status: <?= $row['status'] ?>
                        </p>

                        <!-- Cancel Button -->
                        <form method="POST"
                              onsubmit="return confirm('Cancel this request?');">
                            <input type="hidden"
                                   name="cancel_request_id"
                                   value="<?= $row['id'] ?>">
                            <button type="submit"
                                    class="mt-2 bg-red-600 text-white px-4 py-1 rounded hover:bg-red-700">
                                ❌ Cancel Request
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-500">
                You haven't made any blood requests yet.
            </p>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
