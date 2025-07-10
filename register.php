<?php
/* ------------------------------------------------------------------
   register.php  –  separate storage
   ------------------------------------------------------------------
   • seeker  → INSERT into USERS only
   • donor   → INSERT into DONORS only
   • both    → INSERT into USERS  + DONORS
-------------------------------------------------------------------*/
session_start();
require 'config.php';           // supplies $conn (mysqli)

// let mysqli throw exceptions (development convenience)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$role = $_POST['role'] ?? 'donor';  // 'donor', 'seeker', or 'both'


$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ─ Gather & basic‑validate ──────────────────────────────── */
    $name        = trim($_POST['name'] ?? '');
    $email       = strtolower(trim($_POST['email'] ?? ''));
    $phone       = trim($_POST['phone'] ?? '');
    $bloodGroup  = trim($_POST['blood_group'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $role        = $_POST['role'] ?? 'donor';        // donor | seeker | both
    $passRaw     = $_POST['password'] ?? '';
    $passConfirm = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$phone || !$city || !$passRaw) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid e‑mail format.';
    } elseif ($passRaw !== $passConfirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['donor','seeker','both'], true)) {
        $error = 'Invalid role selected.';
    } elseif (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== 0) {
        $error = 'Please choose a profile picture.';
    }

    /* ─ Handle profile‑picture upload ────────────────────────── */
    $profilePath = '';
    if (!$error) {
        $targetDir = __DIR__ . '/uploads/';
        if (!is_dir($targetDir)) { mkdir($targetDir, 0755, true); }
        $ext        = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $profilePath = 'uploads/' . uniqid('pp_', true) . '.' . $ext;
        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $profilePath)) {
            $error = 'Failed to upload image.';
        }
    }

    /* ─ DATABASE INSERTS ─────────────────────────────────────── */
    if (!$error) {
        $hashedPwd = password_hash($passRaw, PASSWORD_BCRYPT);

        /* 1.  INSERT into USERS  (only if role = seeker | both)  */
        if ($role === 'seeker' || $role === 'both') {

            // prevent duplicate e‑mails in USERS
            $chk = $conn->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows) {
                $error = 'E‑mail already registered as a seeker/both.';
            }
            $chk->close();

            if (!$error) {
                $u = $conn->prepare(
                    "INSERT INTO users
                           (name, email, password, blood_group, location,
                            role, is_donor, last_donated_date)
                     VALUES (?,?,?,?,?,?,?,NULL)"
                );
                $isDonorFlag = ($role === 'both') ? 1 : 0;   // for completeness
                $u->bind_param(
                    "ssssssi",
                    $name,
                    $email,
                    $hashedPwd,
                    $bloodGroup,
                    $city,
                    $role,            // seeker | both
                    $isDonorFlag
                );
                $u->execute();
                $u->close();
            }
        }

        /* 2.  INSERT into DONORS (only if role = donor | both) */
        if (!$error && ($role === 'donor' || $role === 'both')) {

            // prevent duplicate e‑mails in DONORS
            $chk = $conn->prepare("SELECT 1 FROM donors WHERE email = ? LIMIT 1");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows) {
                $error = 'E‑mail already registered as a donor.';
            }
            $chk->close();

            if (!$error) {
                $d = $conn->prepare(
                    "INSERT INTO donors
                           (name, email, phone, blood_group,
                            city, password, role, profile_pic)
                     VALUES (?,?,?,?,?,?,?,?)"
                );
                $d->bind_param(
                    "ssssssss",
                    $name,
                    $email,
                    $phone,
                    $bloodGroup,
                    $city,
                    $hashedPwd,
                    $role,          // donor | both
                    $profilePath
                );
                $d->execute();
                $d->close();
            }
        }
    }

    /* ─ Redirect or show error ───────────────────────────────── */
    if (!$error) {
        // For simplicity, seekers go to seeker_dashboard, donors to donor_dashboard
        $dest = ($role === 'seeker') ? 'seeker_dashboard.php' : 'donor_dashboard.php';
        header("Location: $dest");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-50 flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded-xl shadow w-full max-w-lg">
    <h2 class="text-2xl font-bold text-red-700 mb-4">Create Account</h2>

    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-300 text-red-800 p-3 rounded mb-4">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4 text-left">

      <!-- Name -->
      <div>
        <label class="block text-sm font-medium">Full Name</label>
        <input name="name"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
               required class="w-full border rounded px-3 py-2">
      </div>

      <!-- Email -->
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required class="w-full border rounded px-3 py-2">
      </div>

      <!-- Phone & City -->
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium">Phone</label>
          <input name="phone"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                 required class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">City</label>
          <input name="city"
                 value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"
                 required class="w-full border rounded px-3 py-2">
        </div>
      </div>

      <!-- Blood Group -->
      <div>
        <label class="block text-sm font-medium">Blood Group</label>
        <select name="blood_group" required class="w-full border rounded px-3 py-2">
          <option value="">-- Select --</option>
          <?php foreach (["A+","A-","B+","B-","O+","O-","AB+","AB-"] as $g): ?>
            <option value="<?= $g ?>"
              <?= ($g === ($_POST['blood_group'] ?? '')) ? 'selected' : '' ?>>
              <?= $g ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Role -->
      <div>
        <label class="block text-sm font-medium">Registering As</label>
        <select name="role" required class="w-full border rounded px-3 py-2">
          <option value="donor"  <?= ($_POST['role'] ?? '') === 'donor'  ? 'selected' : '' ?>>Donor</option>
          <option value="seeker" <?= ($_POST['role'] ?? '') === 'seeker' ? 'selected' : '' ?>>Seeker</option>
          <option value="both"   <?= ($_POST['role'] ?? '') === 'both'   ? 'selected' : '' ?>>Both</option>
        </select>
      </div>

      <!-- Passwords -->
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium">Password</label>
          <input type="password" name="password" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Confirm Password</label>
          <input type="password" name="confirm_password" required class="w-full border rounded px-3 py-2">
        </div>
      </div>

      <!-- Profile Picture -->
      <div>
        <label class="block text-sm font-medium">Profile Picture</label>
        <input type="file" name="profile_pic" accept="image/*" required class="w-full">
      </div>

      <button class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 rounded">
        Register
      </button>
    </form>
  </div>
</body>
</html>
