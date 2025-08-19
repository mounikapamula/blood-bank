<?php
/*****************************************************************
 * accept_request.php – Donor clicks “Accept”
 *****************************************************************/
session_start();
require 'config.php';
require 'notifications_api.php';

/* 1️⃣ Guard-rails */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header('Location: login.php');
    exit();
}

$donorId = (int)$_SESSION['user_id'];
$requestId = (int)($_GET['request_id'] ?? $_POST['request_id'] ?? 0);

if ($requestId === 0) {
    header('Location: donor_dashboard.php');
    exit();
}

$isAjax = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);
// After the request has been successfully updated and assigned to the donor
if ($stmt->affected_rows > 0) {
    // 🔴 Step 1: Find the seeker's user ID
    $stmt_seeker = $conn->prepare("SELECT seeker_id FROM blood_requests WHERE id = ?");
    $stmt_seeker->bind_param("i", $requestId);
    $stmt_seeker->execute();
    $seekerResult = $stmt_seeker->get_result();
    $seekerRow = $seekerResult->fetch_assoc();
    $seekerId = $seekerRow['seeker_id'];
    $stmt_seeker->close();

    // 🔴 Step 2: Call the reusable function to create the notification
    $message = "Your blood request has been accepted by a donor!";
    createNotification($conn, $seekerId, $message, 'accepted', $requestId);

    // Now, return a success response to your JavaScript
    echo json_encode(['success' => 'Request accepted and notification sent.']);
}

/* 2️⃣ Transaction for atomicity */
$conn->begin_transaction();

try {
    /* 2a. Claim the request atomically. */
    $claimStmt = $conn->prepare("
        UPDATE blood_requests
        SET donor_id = ?, status = 'accepted'
        WHERE id = ? AND status = 'pending' AND donor_id IS NULL
        LIMIT 1
    ");
    $claimStmt->bind_param('ii', $donorId, $requestId);
    $claimStmt->execute();

    if ($claimStmt->affected_rows === 0) {
        $conn->rollback();
        $errorMsg = 'This request has already been claimed by another donor or is no longer available.';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMsg]);
        } else {
            echo "<script>alert('⚠️ " . htmlspecialchars($errorMsg) . "'); window.location='donor_dashboard.php';</script>";
        }
        exit();
    }
    $claimStmt->close();

    /* 2b. Find the seeker to notify and get their contact info */
    $getSeekerStmt = $conn->prepare("
        SELECT br.seeker_id, u.name, u.email, u.phone
        FROM blood_requests br
        JOIN users u ON br.seeker_id = u.id
        WHERE br.id = ?
        LIMIT 1
    ");
    $getSeekerStmt->bind_param('i', $requestId);
    $getSeekerStmt->execute();
    $result = $getSeekerStmt->get_result();
    $seekerData = $result->fetch_assoc();
    $getSeekerStmt->close();

    // NEW: Get donor's contact info from the users table
    $getDonorStmt = $conn->prepare("
        SELECT name, email, phone FROM users WHERE id = ? LIMIT 1
    ");
    $getDonorStmt->bind_param('i', $donorId);
    $getDonorStmt->execute();
    $donorData = $getDonorStmt->get_result()->fetch_assoc();
    $getDonorStmt->close();

    if ($seekerData && $donorData) {
        // -------------------------------------------------------------------
        // 🩸 NEW: Send Email Notification
        // -------------------------------------------------------------------
        $to = $seekerData['email'];
        $subject = "✅ Your Blood Request Has Been Accepted!";
        
        $message = "Hello " . htmlspecialchars($seekerData['name']) . ",\n\n";
        $message .= "Good news! Your request for a blood donation has been accepted by a donor.\n\n";
        $message .= "Here are the donor's details:\n";
        $message .= "Name: " . htmlspecialchars($donorData['name']) . "\n";
        $message .= "Email: " . htmlspecialchars($donorData['email'] ?? 'N/A') . "\n";
        $message .= "Phone: " . htmlspecialchars($donorData['phone'] ?? 'N/A') . "\n\n";
        $message .= "Please contact the donor directly to coordinate the donation.\n\n";
        $message .= "Thank you,\nYour Blood Donation Portal";
        
        $headers = 'From: noreply@yourwebsite.com' . "\r\n" .
                   'Reply-To: noreply@yourwebsite.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        // The mail() function might fail silently. Check your server's configuration.
        mail($to, $subject, $message, $headers);

        // -------------------------------------------------------------------
        
        // NEW: Include donor's contact info in the notification message
        $notifMsg = "🎉 Your blood request has been accepted by " . htmlspecialchars($donorData['name']) . "!";
        $notifMsg .= " You can contact them at: Phone: " . htmlspecialchars($donorData['phone'] ?? 'N/A') . ", Email: " . htmlspecialchars($donorData['email'] ?? 'N/A') . ".";

        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type, related_id)
            VALUES (?, ?, 'accepted', ?)
        ");
        $notifStmt->bind_param('isi', $seekerData['seeker_id'], $notifMsg, $requestId);
        $notifStmt->execute();
        $notifStmt->close();
    }
    
    // Commit the transaction after all operations succeed
    $conn->commit();

    /* 3️⃣ Respond to the client */
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Request accepted successfully.']);
    } else {
        echo "<script>alert('✅ Request accepted successfully! Seeker has been notified.'); window.location='donor_dashboard.php';</script>";
    }

} catch (mysqli_sql_exception $exception) {
    // Catch any SQL errors and rollback
    $conn->rollback();
    $errorMsg = "Database error: " . $exception->getMessage();
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $errorMsg]);
    } else {
        echo "<script>alert('⚠️ " . htmlspecialchars($errorMsg) . "'); window.location='donor_dashboard.php';</script>";
    }
}

$conn->close();
?>