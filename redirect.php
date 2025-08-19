<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');  // safety
  exit();
}

/* Send to the right dashboard */
switch ($_SESSION['role'] ?? 'donor') {
  case 'seeker':
    header('Location: seeker_dashboard.php');
    break;
  case 'both':
    header('Location: header.php');   // <‑‑ shows BOTH buttons
    break;
  default: // donor
    header('Location: donor_dashboard.php');
}
exit();
