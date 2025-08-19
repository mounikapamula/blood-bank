<?php
$icon = 'ℹ️'; // default icon
$color = 'gray-700';

if ($n['type'] === 'success') {
    $icon = '✅';
    $color = 'green-700';
} elseif ($n['type'] === 'warning') {
    $icon = '⚠️';
    $color = 'yellow-700';
} elseif ($n['type'] === 'danger' || $n['type'] === 'error') {
    $icon = '❌';
    $color = 'red-700';
// 🩸 NEW: Handle 'accepted' notification type
} elseif ($n['type'] === 'accepted') {
    $icon = '🩸'; // Blood drop icon
    $color = 'red-700';
}
?>

<div id="notif-<?= $n['id'] ?>" class="bg-white p-4 rounded shadow relative animate-fade-in border-l-4 border-<?= $color ?>">
    <button onclick="document.getElementById('notif-<?= $n['id'] ?>').remove()" 
            class="absolute top-2 right-2 text-gray-400 hover:text-black text-xl font-bold">
        &times;
    </button>
    <p class="text-<?= $color ?> font-semibold mb-1"><?= $icon ?> <?= htmlspecialchars($n['message']) ?></p>
    <p class="text-sm text-gray-500"><?= date('m/d/Y, h:i:s A', strtotime($n['created_at'])) ?></p>
</div>