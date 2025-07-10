<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Get seeker's own blood group from session
$myBloodGroup = isset($_SESSION['blood_group']) ? $_SESSION['blood_group'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Request Blood</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // Toggle blood group input based on selection
    function toggleBloodGroupField() {
      const myselfRadio = document.getElementById('for_myself');
      const fixedGroupDiv = document.getElementById('fixed_group');
      const dropdownGroupDiv = document.getElementById('select_group');

      if (myselfRadio.checked) {
        fixedGroupDiv.classList.remove('hidden');
        dropdownGroupDiv.classList.add('hidden');
      } else {
        fixedGroupDiv.classList.add('hidden');
        dropdownGroupDiv.classList.remove('hidden');
      }
    }
  </script>
</head>
<body class="bg-red-50 flex items-center justify-center min-h-screen">
  <div class="bg-white p-6 rounded shadow-md w-full max-w-md">
    <a href="seeker_dashboard.php" class="text-red-600 hover:underline mb-4 inline-block">
      ← Back to Home
    </a>
    <h2 class="text-2xl font-bold text-red-700 mb-4">🩸 Request Blood</h2>

    <form method="POST" action="submit_request.php">

      <!-- Toggle: Myself or Someone Else -->
      <label class="block mb-2 font-medium text-gray-700">Who is this request for?</label>
      <div class="flex items-center mb-4 space-x-4">
        <label class="flex items-center">
          <input type="radio" name="request_for" id="for_myself" value="myself" checked onchange="toggleBloodGroupField()" />
          <span class="ml-2">Myself</span>
        </label>
        <label class="flex items-center">
          <input type="radio" name="request_for" id="for_other" value="other" onchange="toggleBloodGroupField()" />
          <span class="ml-2">Someone else</span>
        </label>
      </div>

      <!-- Blood Group for Myself (readonly) -->
      <div id="fixed_group">
        <label class="block mb-1 font-medium">Your Blood Group</label>
        <input type="text" value="<?= htmlspecialchars($myBloodGroup) ?>" readonly class="w-full px-3 py-2 border rounded bg-gray-100 mb-2" />
        <input type="hidden" name="blood_group" value="<?= htmlspecialchars($myBloodGroup) ?>" />
      </div>

      <!-- Blood Group for Someone Else (dropdown) -->
      <div id="select_group" class="hidden">
        <label class="block mb-1 font-medium">Select Blood Group</label>
        <select name="blood_group" class="w-full px-3 py-2 border rounded mb-2">
          <option value="">-- Choose Blood Group --</option>
          <option value="A+">A+</option>
          <option value="A-">A-</option>
          <option value="B+">B+</option>
          <option value="B-">B-</option>
          <option value="AB+">AB+</option>
          <option value="AB-">AB-</option>
          <option value="O+">O+</option>
          <option value="O-">O-</option>
        </select>
      </div>

      <!-- Rest of the Form -->
      <label class="block mb-1 font-medium">Hospital</label>
      <input type="text" name="hospital" required class="w-full px-3 py-2 border rounded mb-4" />

      <label class="block mb-1 font-medium">City</label>
      <input type="text" name="city" required class="w-full px-3 py-2 border rounded mb-4" />

      <label class="block mb-1 font-medium">Units Needed</label>
      <input type="number" name="units_needed" min="1" required class="w-full px-3 py-2 border rounded mb-4" />

      <label class="block mb-1 font-medium">Notes (optional)</label>
      <textarea name="notes" class="w-full px-3 py-2 border rounded mb-4" rows="3"></textarea>

      <button type="submit" class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700">
        Submit Request
      </button>
    </form>
  </div>

  <script>
    // Ensure correct field is shown on page load
    toggleBloodGroupField();
  </script>
</body>
</html>
