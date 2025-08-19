<?php
require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);


if (isset($_GET['city'])) {
    $city = $_GET['city'];
    $stmt = $conn->prepare("SELECT id, hospital_name FROM hospitals WHERE city = ? ORDER BY hospital_name ASC");
    $stmt->bind_param("s", $city);
    $stmt->execute();
    $result = $stmt->get_result();

    $hospitals = [];
    while ($row = $result->fetch_assoc()) {
        $hospitals[] = $row;
    }

    echo json_encode($hospitals);
}
?>
