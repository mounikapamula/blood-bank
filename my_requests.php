<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$conn = new mysqli('localhost', 'root', '', 'mounika');
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$seekerId = $_SESSION['user_id'];

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request_id'])) {
  $cancelId = $_POST['cancel_request_id'];
  $stmt = $conn->prepare("DELETE FROM blood_requests WHERE id = ? AND seeker_id = ?");
  $stmt->bind_param("ii", $cancelId, $seekerId);
  $stmt->execute();
  $stmt->close();
  echo "<script>alert('❌ Request cancelled successfully.'); window.location.href='my_requests.php';</script>";
  exit();
}

// Fetch requests with donor info
$stmt = $conn->prepare("
  SELECT br.*, d.name AS donor_name, d.blood_group AS donor_blood_group, d.city AS donor_city
  FROM blood_requests br
  LEFT JOIN donors d ON br.donor_id = d.id
  WHERE br.seeker_id = ?
  ORDER BY br.id DESC
");
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
    <h1 class="text-3xl font-bold text-red-700 mb-6 text-center">My Blood Requests</h1>

    <?php if ($result->num_rows > 0): ?>
      <div class="space-y-4">
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            $donorName = $row['donor_name'] ?? null;
            $donorGroup = $row['donor_blood_group'] ?? null;
            $donorCity = $row['donor_city'] ?? '—';
            $donorLabel = $donorName ? "$donorName ($donorGroup)" : 'Not yet assigned';
          ?>
          <div class="bg-white p-4 rounded shadow border border-red-200">
            <h3 class="text-xl font-semibold text-red-800 mb-1">
              Requested from: <?= htmlspecialchars($donorLabel) ?>
            </h3>

            <p class="text-gray-700">📍 Donor City: <?= htmlspecialchars($donorCity) ?></p>
            <p class="text-gray-700">🏥 Hospital: <?= htmlspecialchars($row['hospital']) ?></p>
            <p class="text-gray-700">🩸 Units Needed: <?= htmlspecialchars($row['units_needed']) ?></p>
            <p class="text-gray-700">📝 Notes: <?= htmlspecialchars($row['notes']) ?: 'None' ?></p>
            <p class="text-gray-500 text-sm mt-2">Request ID: <?= $row['id'] ?></p>

            <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this request?');">
              <input type="hidden" name="cancel_request_id" value="<?= $row['id'] ?>">
              <button type="submit" class="mt-2 bg-red-600 text-white px-4 py-1 rounded hover:bg-red-700">
                ❌ Cancel Request
              </button>
            </form>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="text-center text-gray-500">You haven't made any blood requests yet.</p>
    <?php endif; ?>
  </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
