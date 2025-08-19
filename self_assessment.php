<?php
/*****************************************************************
 *  eligibility.php  ▸  Pre‑Registration Donor Eligibility Quiz
 *  ------------------------------------------------------------
 *  • Anyone (logged‑in or not) can take the quiz.
 *  • If PASS  → sets $_SESSION['pre_eligible'] = true  and sends the
 *                user to login.php (so they can sign in / sign up).
 *  • If FAIL  → stays on this page, lists reasons.
 *
 *  You can later check $_SESSION['pre_eligible'] in login or
 *  register_donor.php to verify they passed the quiz.
 *****************************************************************/

session_start();
require 'config.php'; // keep if you need DB later; harmless otherwise

$errors = [];
$passed = null;  // null = not submitted yet

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── Collect inputs ──────────────────────────────────────────
    $age         = (int) ($_POST['age']          ?? 0);
    $weight      = (int) ($_POST['weight']       ?? 0);
    $tattoo      = ($_POST['tattoo']     ?? 'no') === 'yes';
    $cold        = ($_POST['cold']       ?? 'no') === 'yes';
    $lastDonate  = (int) ($_POST['last_donate']  ?? 0); // months

    // New boolean questions (yes = disqualify)
    $dental      = ($_POST['dental']     ?? 'no') === 'yes';
    $riskSex     = ($_POST['risk_sex']   ?? 'no') === 'yes';
    $ivDrugs     = ($_POST['iv_drugs']   ?? 'no') === 'yes';
    $chronic     = ($_POST['chronic']    ?? 'no') === 'yes';
    $pregnant    = ($_POST['pregnant']   ?? 'no') === 'yes';
    $meds        = ($_POST['meds']       ?? 'no') === 'yes';
    $vaccine     = ($_POST['vaccine']    ?? 'no') === 'yes';

    // ── Eligibility rules ──────────────────────────────────────
    if ($age   < 18 || $age > 65) $errors[] = 'Age must be between 18 and 65.';
    if ($weight < 50)             $errors[] = 'Weight must be at least 50 kg.';
    if ($tattoo)                  $errors[] = 'Wait 6 months after tattoos/piercings.';
    if ($cold)                    $errors[] = 'You must be symptom‑free for 2 weeks.';
    if ($lastDonate < 3)          $errors[] = 'Wait at least 3 months between donations.';

    // new yes/no rules
    if ($dental)   $errors[] = 'Major dental work requires 1 month deferral.';
    if ($riskSex)  $errors[] = 'High‑risk sexual activity defers donation for 12 months.';
    if ($ivDrugs)  $errors[] = 'IV drug use permanently defers donation.';
    if ($chronic)  $errors[] = 'Chronic conditions like heart disease or cancer disqualify.';
    if ($pregnant) $errors[] = 'Pregnancy / recent birth defers donation for 9 months.';
    if ($meds)     $errors[] = 'Certain medications defer or disqualify donation.';
    if ($vaccine)  $errors[] = 'Recent vaccination may require a deferral period.';

    $passed = empty($errors);

    if ($passed) {
        // Mark quiz passed for this session so they can register or log in
        $_SESSION['pre_eligible'] = true;
        header('Location: login.php');
        exit;
    } else {
        unset($_SESSION['pre_eligible']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Donor Eligibility Assessment</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">

  <div class="bg-white w-full max-w-2xl p-8 rounded-xl shadow">
    <h1 class="text-2xl font-semibold text-center mb-6 text-red-700">Donor Eligibility Assessment</h1>

    <?php if ($passed === false): ?>
      <div class="bg-red-100 border border-red-300 text-red-800 p-4 rounded mb-6">
        <p class="font-semibold mb-2">You are <span class="underline">not eligible</span> to donate at this moment:</p>
        <ul class="list-disc list-inside space-y-1">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
        <p class="mt-2 text-sm text-gray-600">Please review the criteria and try again when eligible.</p>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
      <!-- Age -->
      <div>
        <label class="block text-sm font-medium mb-1">Age</label>
        <input type="number" name="age" min="0" required class="w-full border rounded p-2">
      </div>
      <!-- Weight -->
      <div>
        <label class="block text-sm font-medium mb-1">Weight (kg)</label>
        <input type="number" name="weight" min="0" required class="w-full border rounded p-2">
      </div>
      <!-- Tattoo -->
      <div>
        <label class="block text-sm font-medium mb-1">Tattoo/piercing in last 6 months?</label>
        <select name="tattoo" class="w-full border rounded p-2">
          <option value="no">No</option><option value="yes">Yes</option>
        </select>
      </div>
      <!-- Cold/Flu -->
      <div>
        <label class="block text-sm font-medium mb-1">Cold/flu symptoms in last 2 weeks?</label>
        <select name="cold" class="w-full border rounded p-2">
          <option value="no">No</option><option value="yes">Yes</option>
        </select>
      </div>
      <!-- Last donation -->
      <div>
        <label class="block text-sm font-medium mb-1">Months since last blood donation</label>
        <input type="number" name="last_donate" min="0" required class="w-full border rounded p-2">
      </div>
      <!-- Extra questions (Yes = fail) -->
      <?php
        $questions = [
          'dental'   => 'Underwent major dental work in the last month?',
          'risk_sex' => 'Engaged in high‑risk sexual activity in the past 12 months?',
          'iv_drugs' => 'Ever used intravenous drugs?',
          'chronic'  => 'Have chronic medical condition (heart disease, epilepsy, cancer)?',
          'pregnant' => 'Currently pregnant or recently gave birth (within 9 months)?',
          'meds'     => 'Currently on medication that might affect donation?',
          'vaccine'  => 'Had any vaccination recently (within deferral period)?'
        ];
        foreach ($questions as $name => $label): ?>
          <div>
            <label class="block text-sm font-medium mb-2"><?= $label ?></label>
            <div class="flex items-center space-x-6">
              <label class="inline-flex items-center">
                <input type="radio" name="<?= $name ?>" value="no" checked class="text-red-600">
                <span class="ml-2">No</span>
              </label>
              <label class="inline-flex items-center">
                <input type="radio" name="<?= $name ?>" value="yes" class="text-red-600">
                <span class="ml-2">Yes</span>
              </label>
            </div>
          </div>
      <?php endforeach; ?>

      <button type="submit" class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700">Submit Assessment</button>
    </form>
  </div>

</body>
</html>
