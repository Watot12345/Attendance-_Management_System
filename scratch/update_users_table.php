<?php
require_once 'includes/core/Database.php';
$db = Database::getConnection();

$existingCols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

$columnsToAdd = [
    'remember_token'      => 'VARCHAR(255) NULL AFTER remembered',
    'remember_expires_at' => 'DATETIME NULL AFTER remember_token',
    'otp_code'            => 'VARCHAR(10) NULL AFTER remember_expires_at',
    'otp_expires_at'      => 'DATETIME NULL AFTER otp_code',
];

foreach ($columnsToAdd as $col => $def) {
    if (!in_array($col, $existingCols)) {
        try {
            $db->exec("ALTER TABLE users ADD COLUMN `$col` $def");
            echo "Added column: $col\n";
        } catch (Exception $e) {
            echo "Error adding $col: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Column already exists: $col\n";
    }
}

echo "\nVerification:\n";
$cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    if (in_array($c['Field'], ['ip_address', 'user_agent', 'remembered', 'remember_token', 'remember_expires_at', 'otp_code', 'otp_expires_at'])) {
        echo "Column OK: {$c['Field']} ({$c['Type']})\n";
    }
}
