<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

$t0 = microtime(true);
$db->query("SELECT 1");
echo "Roundtrip 1: " . round((microtime(true) - $t0) * 1000, 2) . " ms\n";

$t1 = microtime(true);
$db->query("SELECT 1");
echo "Roundtrip 2: " . round((microtime(true) - $t1) * 1000, 2) . " ms\n";
