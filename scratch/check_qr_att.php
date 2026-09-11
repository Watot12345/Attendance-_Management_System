<?php
require_once 'includes/core/Database.php';
$db = Database::getConnection();

echo "=== CLASS ROSTER SAMPLE ===\n";
$rosters = $db->query("SELECT * FROM class_roster LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($rosters);

echo "=== QR SESSIONS SAMPLE ===\n";
$qrs = $db->query("SELECT * FROM qr_sessions LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($qrs);

echo "=== ATTENDANCE SAMPLE ===\n";
$att = $db->query("SELECT * FROM attendance LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($att);
