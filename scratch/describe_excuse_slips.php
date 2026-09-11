<?php
require_once __DIR__ . '/../includes/core/Database.php';
$db = Database::getConnection();
$cols = $db->query("DESCRIBE excuse_slips")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} - {$c['Type']}\n";
}
