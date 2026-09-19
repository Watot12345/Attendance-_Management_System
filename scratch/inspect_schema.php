<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES:\n" . implode(", ", $tables) . "\n\n";

foreach ($tables as $t) {
    echo "--- SCHEMA FOR $t ---\n";
    $cols = $db->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']} ({$col['Type']})\n";
    }
    $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "  [Total rows: $count]\n\n";
}
