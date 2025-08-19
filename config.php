<?php
$host = "localhost";
$user = "root";
$password = ""; // default in XAMPP
$dbname = "mounika"; // change to your DB name

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
