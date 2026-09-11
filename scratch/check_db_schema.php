<?php
require_once 'includes/core/Database.php';
$db = Database::getConnection();

echo "=== USERS COLUMNS ===\n";
$cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} - {$c['Type']} (Null: {$c['Null']}, Default: {$c['Default']})\n";
}

echo "\n=== ALL TABLES ===\n";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
