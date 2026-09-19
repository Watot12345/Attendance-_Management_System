<?php
require 'includes/core/Database.php';
$pdo = Database::getConnection();
echo "=== parent_alerts ===\n";
print_r($pdo->query('DESCRIBE parent_alerts')->fetchAll());
echo "=== audit_logs ===\n";
print_r($pdo->query('DESCRIBE audit_logs')->fetchAll());
