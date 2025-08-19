<?php
// notifications_api.php - A reusable function to create a notification

function createNotification($conn, $user_id, $message, $type, $related_id = null) {
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, message, type, related_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $user_id, $message, $type, $related_id);
    $stmt->execute();
    $stmt->close();
}
?>