<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$seeker_id = (int)$_SESSION['user_id'];

// Get all the form data
$request_for   = $_POST['request_for'] ?? '';
$hospital_id   = $_POST['hospital_id'] ?? '';
$hospital      = '';
$city          = trim($_POST['city'] ?? '');
$units_needed  = (int)($_POST['units_needed'] ?? 1);
$notes         = trim($_POST['notes'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$required_by   = $_POST['required_by'] ?? null;
$address       = trim($_POST['address'] ?? '');
$search_radius = (int)($_POST['search_radius'] ?? 25);
$latitude      = null;
$longitude     = null;

// Determine the blood group
if ($request_for === 'myself') {
    $stmt_user = $conn->prepare("SELECT blood_group FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $seeker_id);
    $stmt_user->execute();
    $result = $stmt_user->get_result();
    $user_data = $result->fetch_assoc();
    $stmt_user->close();

    if (!$user_data || empty($user_data['blood_group'])) {
        echo "<script>alert('Please complete your profile information (blood group) first.'); window.location.href='profile.php';</script>";
        exit();
    }
    $blood_group = $user_data['blood_group'];
} else {
    $blood_group = trim($_POST['blood_group'] ?? '');
}

// Handle hospital selection
if ($hospital_id === 'other') {
    $hospital  = trim($_POST['other_hospital_name'] ?? '');
    $latitude  = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
} else {
    // Fetch hospital details from DB
    $stmt_hosp = $conn->prepare("SELECT hospital_name, latitude, longitude FROM hospitals WHERE id = ?");
    $stmt_hosp->bind_param("i", $hospital_id);
    $stmt_hosp->execute();
    $stmt_hosp->bind_result($hospital, $latitude, $longitude);
    $stmt_hosp->fetch();
    $stmt_hosp->close();
}

// Basic validation
if (empty($blood_group) || empty($hospital) || empty($city) || empty($phone) || empty($required_by)) {
    echo "<script>alert('Blood Group, Hospital, City, Phone, and Required By date are required.'); window.location.href='request_form.php';</script>";
    exit();
}

// Prevent duplicate pending requests
$check_stmt = $conn->prepare("
    SELECT id FROM blood_requests
    WHERE seeker_id = ? 
      AND blood_group = ? 
      AND city = ? 
      AND status = 'pending'
");
$check_stmt->bind_param("iss", $seeker_id, $blood_group, $city);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo "<script>alert('You already have a pending request for this blood group in this city.'); window.location.href='my_requests.php';</script>";
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// ✅ Ensure correct data types
$null_donor_id = NULL;
$status        = 'pending';
$latitude      = ($latitude !== null && $latitude !== '') ? (float)$latitude : null;
$longitude     = ($longitude !== null && $longitude !== '') ? (float)$longitude : null;

// Insert new blood request
$sql = "INSERT INTO blood_requests 
    (seeker_id, donor_id, blood_group, hospital, city, units_needed, notes, phone, required_by, address, latitude, longitude, search_radius, status, requested_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("iisssisssssdis", 
        $seeker_id,       // i
        $null_donor_id,   // i
        $blood_group,     // s
        $hospital,        // s
        $city,            // s
        $units_needed,    // i
        $notes,           // s
        $phone,           // s
        $required_by,     // s
        $address,         // s
        $latitude,        // d
        $longitude,       // d
        $search_radius,   // i
        $status           // s
    );

    if ($stmt->execute()) {
        echo "<script>alert('🩸 Blood request submitted successfully!'); window.location.href='my_requests.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}

$conn->close();
?>
