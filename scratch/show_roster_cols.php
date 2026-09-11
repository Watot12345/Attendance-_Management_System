<?php
require_once __DIR__ . '/../includes/core/Database.php';
$db = Database::getConnection();
$cols = $db->query("SHOW COLUMNS FROM class_roster")->fetchAll(PDO::FETCH_ASSOC);
echo "Columns in class_roster:\n";
foreach ($cols as $c) {
    echo " - {$c['Field']} ({$c['Type']})\n";
}
