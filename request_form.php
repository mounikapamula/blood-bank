<?php
include 'config.php';
session_start();

// Fetch all hospitals with city info
$hospital_result = $conn->query("SELECT id, hospital_name, city, latitude, longitude FROM hospitals ORDER BY hospital_name ASC");
$hospitals = [];
$cities = [];
while ($row = $hospital_result->fetch_assoc()) {
    $hospitals[] = $row;
    if (!in_array($row['city'], $cities)) {
        $cities[] = $row['city'];
    }
}
sort($cities); // sort cities alphabetically
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Blood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    const hospitals = <?= json_encode($hospitals) ?>;

    function filterHospitals() {
        const selectedCity = document.getElementById("city").value;
        const hospitalDropdown = document.getElementById("hospital");
        hospitalDropdown.innerHTML = '<option value="">Select Hospital</option>';

        hospitals
            .filter(h => h.city === selectedCity)
            .forEach(h => {
                const opt = document.createElement("option");
                opt.value = h.id;
                opt.textContent = h.hospital_name;
                hospitalDropdown.appendChild(opt);
            });

        // Add "Other Hospital" option if city is selected
        if (selectedCity) {
            const optOther = document.createElement("option");
            optOther.value = "other";
            optOther.textContent = "Other Hospital";
            hospitalDropdown.appendChild(optOther);
        }
    }

    function toggleOtherHospital() {
        const hospitalDropdown = document.getElementById("hospital");
        const otherFields = document.getElementById("otherHospitalFields");

        if (hospitalDropdown.value === "other") {
            otherFields.style.display = "block";
            document.getElementById("latitude").value = "";
            document.getElementById("longitude").value = "";
        } else {
            otherFields.style.display = "none";
            const selectedHospital = hospitals.find(h => h.id == hospitalDropdown.value);
            if (selectedHospital) {
                document.getElementById("latitude").value = selectedHospital.latitude;
                document.getElementById("longitude").value = selectedHospital.longitude;
            }
        }
    }
    </script>
</head>
<body class="min-h-screen flex items-center justify-center bg-red-50">

<div class="bg-white p-8 rounded-xl shadow-md w-full max-w-4xl">
    <h2 class="text-3xl font-bold mb-6 text-center text-red-600">🩸 Request for Blood</h2>

    <form method="POST" action="submit_request.php" class="space-y-5">

        <!-- Blood Group -->
        <div>
            <label class="block text-gray-800 font-semibold mb-2">Blood Group</label>
            <select name="blood_group" required class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none">
                <option value="">Select</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
            </select>
        </div>

        <!-- Phone -->
        <div>
            <label class="block text-gray-800 font-semibold mb-2">Phone Number</label>
            <input type="text" name="phone" required class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none">
        </div>

        <!-- Required By -->
        <div>
            <label class="block text-gray-800 font-semibold mb-2">Required By</label>
            <input type="datetime-local" name="required_by" required class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none">
        </div>

        <!-- Address -->
        <div>
            <label class="block text-gray-800 font-semibold mb-2">Address (Optional)</label>
            <textarea name="address" rows="2" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none"></textarea>
        </div>

        <!-- Units Needed -->
        <div>
            <label class="block text-gray-800 font-semibold mb-2">Units Needed</label>
            <input type="number" name="units_needed" min="1" required class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none">
        </div>

        <!-- Notes -->
        <div>
            <label class="block text-gray-800 font-semibold mb-2">Notes (Optional)</label>
            <textarea name="notes" rows="3" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none"></textarea>
        </div>

        <!-- City Dropdown -->
        <div>
            <label class="block text-sm font-medium">City</label>
            <select name="city" id="city" required class="w-full p-2 border rounded-md" onchange="filterHospitals()">
                <option value="">Select City</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Hospital Dropdown -->
        <div>
            <label class="block text-sm font-medium">Hospital</label>
            <select name="hospital_id" id="hospital" required class="w-full p-2 border rounded-md" onchange="toggleOtherHospital()">
                <option value="">Select Hospital</option>
            </select>
        </div>

        <!-- Other Hospital Fields -->
        <div id="otherHospitalFields" style="display:none;">
            <div class="mt-2">
                <label class="block text-gray-800 font-semibold mb-2">Other Hospital Name</label>
                <input type="text" name="other_hospital_name" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none">
            </div>
            <div class="mt-2">
                <label class="block text-gray-800 font-semibold mb-2">Latitude</label>
                <input type="text" name="latitude" id="latitude" placeholder="Enter latitude" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none">
            </div>
            <div class="mt-2">
                <label class="block text-gray-800 font-semibold mb-2">Longitude</label>
                <input type="text" name="longitude" id="longitude" placeholder="Enter longitude" class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none">
            </div>
        </div>

        <!-- Search Radius -->
        <div>
            <label class="block text-gray-800 font-semibold mb-2">Search Radius</label>
            <select name="search_radius" required class="shadow-sm border border-gray-300 rounded-lg w-full py-2 px-3 focus:ring-2 focus:ring-red-400 focus:outline-none">
                <option value="10">10 km</option>
                <option value="25" selected>25 km</option>
                <option value="50">50 km</option>
                <option value="100">100 km</option>
            </select>
        </div>

        <!-- Submit -->
        <div class="flex justify-center">
            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg shadow-md hover:bg-red-700 transition duration-300">
                Submit Request
            </button>
        </div>
    </form>
</div>

</body>
</html>
