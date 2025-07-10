<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'mounika');
$userId = $_SESSION['user_id'];

$q = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$q->bind_param("i", $userId);
$q->execute();
$res = $q->get_result();
?>
<h2>Your Notifications</h2>
<ul>
<?php while ($row = $res->fetch_assoc()): ?>
    <li><?= $row['message'] ?> — <small><?= $row['created_at'] ?></small></li>
<?php endwhile; ?>
</ul>
