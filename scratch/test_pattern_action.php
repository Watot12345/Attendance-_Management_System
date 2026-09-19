<?php
require 'includes/core/Database.php';
$pdo = Database::getConnection();

$beforeAlerts = (int)$pdo->query('SELECT COUNT(*) FROM parent_alerts')->fetchColumn();
$beforeLogs = (int)$pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

echo "=== Testing POST /api/analytics/apply-pattern-action ===\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/analytics/apply-pattern-action');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'pattern_id' => 'pat_mon_spike',
    'pattern_title' => 'Monday Absence Anomaly (2.2x Weekday Average)'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response JSON:\n$res\n\n";

$afterAlerts = (int)$pdo->query('SELECT COUNT(*) FROM parent_alerts')->fetchColumn();
$afterLogs = (int)$pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

echo "parent_alerts rows: before=$beforeAlerts, after=$afterAlerts (Diff: +" . ($afterAlerts - $beforeAlerts) . ")\n";
echo "audit_logs rows: before=$beforeLogs, after=$afterLogs (Diff: +" . ($afterLogs - $beforeLogs) . ")\n";
