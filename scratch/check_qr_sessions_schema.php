<?php
require_once 'c:/clients/Attendance-_Management_System/includes/core/Database.php';

$db = Database::getConnection();
$stmt = $db->query("DESCRIBE qr_sessions");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Columns in qr_sessions:\n";
foreach ($columns as $c) {
    echo "- {$c['Field']} ({$c['Type']}), Null: {$c['Null']}, Default: {$c['Default']}\n";
}
