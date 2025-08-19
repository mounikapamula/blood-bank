<?php
function notifyMatchingPendingRequests(mysqli $conn,
                                       int $donorId,
                                       string $bloodGroup,
                                       string $city): void
{
    $sel = $conn->prepare("
        SELECT id
        FROM blood_requests
        WHERE status = 'pending'
          AND blood_group = ?
          AND city = ?
    ");
    $sel->bind_param('ss', $bloodGroup, $city);
    $sel->execute();
    $res = $sel->get_result();

    $ins = $conn->prepare("
        INSERT IGNORE INTO notifications
        (user_id, message, type, related_id)
        VALUES (?, ?, 'request', ?)
    ");

    while ($row = $res->fetch_assoc()) {
        $reqId = $row['id'];
        $msg = "🆕 Blood request #$reqId needs $bloodGroup blood in $city.";
        $ins->bind_param('isi', $donorId, $msg, $reqId);
        $ins->execute();
    }
}
