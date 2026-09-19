<?php
require_once __DIR__ . '/../includes/core/Database.php';

try {
    $db = Database::getConnection();
    echo "--- ATTENDANCE INDICES ---\n";
    foreach ($db->query("SHOW INDEX FROM attendance")->fetchAll(PDO::FETCH_ASSOC) as $idx) {
        echo "{$idx['Key_name']} -> {$idx['Column_name']} (Seq: {$idx['Seq_in_index']})\n";
    }

    echo "\n--- CLASS ROSTER INDICES ---\n";
    foreach ($db->query("SHOW INDEX FROM class_roster")->fetchAll(PDO::FETCH_ASSOC) as $idx) {
        echo "{$idx['Key_name']} -> {$idx['Column_name']} (Seq: {$idx['Seq_in_index']})\n";
    }

    echo "\n--- USERS INDICES ---\n";
    foreach ($db->query("SHOW INDEX FROM users")->fetchAll(PDO::FETCH_ASSOC) as $idx) {
        echo "{$idx['Key_name']} -> {$idx['Column_name']} (Seq: {$idx['Seq_in_index']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
