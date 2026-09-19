<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

$res = $db->query("SHOW CREATE TABLE attendance_records")->fetch(PDO::FETCH_ASSOC);
print_r($res);

$res2 = $db->query("SHOW CREATE TABLE attendance")->fetch(PDO::FETCH_ASSOC);
print_r($res2);
