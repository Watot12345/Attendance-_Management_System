<?php
require_once __DIR__ . '/../includes/core/Database.php';
$db = Database::getConnection();

$rows = $db->query("SELECT * FROM class_roster")->fetchAll(PDO::FETCH_ASSOC);
echo "Total rows in class_roster: " . count($rows) . "\n";
print_r($rows);
