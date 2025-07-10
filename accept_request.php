<?php
/*****************************************************************
 *  accept_request.php – Donor clicks “Accept”
 *  • Works from a regular <form> POST  ➜ shows alert + redirect
 *  • Works from fetch() / AJAX        ➜ returns JSON
 *****************************************************************/
session_start();
require 'config.php';                     // sets $conn  (mysqli)

/* 1️⃣  Guard‑rails ------------------------------------------------ */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header('Location: login.php');
    exit();
}

$donorId   = (int) $_SESSION['user_id'];
$requestId = (int) ($_POST['request_id'] ?? 0);     // hidden field or fetch body

if ($requestId === 0) {                   // no ID → bounce back
    header('Location: donor_dashboard.php');
    exit();
}

/* Will the caller expect JSON? */
$isAjax = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

/* 2️⃣  Atomically claim the request while it’s still pending ------ */
$claim = $conn->prepare("
    UPDATE blood_requests
       SET donor_id = ?, status = 'accepted'
     WHERE id       = ?
       AND status   = 'pending'
       AND donor_id IS NULL
    LIMIT 1
");
$claim->bind_param('ii', $donorId, $requestId);
$claim->execute();
// 💌 Notify the seeker that their request was accepted
$notify = $conn->prepare("
    INSERT INTO notifications (user_id, message, type, related_id)
    VALUES (?, ?, 'accepted', ?)
");
$notify->bind_param('isi', $seeker_id, $notifMsg, $request_id);

$notifMsg = "✅ Your blood request #$request_id was accepted by a donor.";
$notify->execute();


/* 3️⃣  If exactly one row changed, claim succeeded ---------------- */
if ($claim->affected_rows === 1) {

    /* 3a. Find the seeker to notify */
    $getSeeker = $conn->prepare("
        SELECT seeker_id
          FROM blood_requests
         WHERE id = ?
         LIMIT 1
    ");
    $getSeeker->bind_param('i', $requestId);
    $getSeeker->execute();
    $getSeeker->bind_result($seekerId);
    $getSeeker->fetch();
    $getSeeker->close();

    /* 3b. Insert notification for the seeker */
    if ($seekerId) {
        $msg  = "🎉 Your blood request #$requestId has been accepted!";
        $note = $conn->prepare("
            INSERT INTO notifications (user_id, message, type, related_id)
            VALUES (?, ?, 'accepted', ?)
        ");
        $note->bind_param('isi', $seekerId, $msg, $requestId);
        $note->execute();
    }

    /* 3c. Respond ------------------------------------------------- */
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    /* Non‑AJAX: show alert + redirect */
    echo "<script>
            alert('✅ Request accepted.');
            window.location = 'donor_dashboard.php';
          </script>";
    exit();
}

/* 4️⃣  Someone else already accepted or status isn’t pending ------- */
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'This request has already been claimed by another donor.'
    ]);
    exit();
}

echo "<script>
        alert('⚠️ That request has already been claimed.');
        window.location = 'donor_dashboard.php';
      </script>";
exit();
?>
