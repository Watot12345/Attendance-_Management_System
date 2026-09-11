<?php
require_once __DIR__ . '/../includes/core/Database.php';
$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT user_id, email, first_name, last_name, role FROM users WHERE role='teacher' LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
