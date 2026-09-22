<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();
$stmt = $db->query("DESCRIBE attendance");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Columns in attendance table:\n";
foreach ($cols as $c) {
    echo " - {$c['Field']} ({$c['Type']})\n";
}
