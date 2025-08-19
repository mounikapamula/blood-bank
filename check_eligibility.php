<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Example check from database (pseudo-code)
// if last_eligibility_result != 'eligible' OR older than 3 months:
header("Location: self_assessment.php");
exit();

// else:
header("Location: donation_form.php");
exit();
?>
