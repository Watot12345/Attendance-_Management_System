<?php
require_once __DIR__ . '/../includes/core/Database.php';
$db = Database::getConnection();
$cols = $db->query("DESCRIBE class_roster")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} - {$c['Type']}\n";
}
