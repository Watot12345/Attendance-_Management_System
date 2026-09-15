<?php
require_once __DIR__ . '/../includes/core/Database.php';

try {
    $db = Database::getConnection();
    
    // Check if is_active column exists
    $stmt = $db->query("SHOW COLUMNS FROM qr_sessions LIKE 'is_active'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Adding 'is_active' column to qr_sessions table...\n";
        $db->exec("ALTER TABLE qr_sessions ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER `end`");
        echo "Column 'is_active' added successfully.\n";
    } else {
        echo "Column 'is_active' already exists in qr_sessions.\n";
    }

    // Check if index exists
    $idxStmt = $db->query("SHOW INDEX FROM qr_sessions WHERE Key_name = 'idx_qr_sessions_active_sec'");
    if (!$idxStmt->fetch()) {
        echo "Adding index idx_qr_sessions_active_sec...\n";
        $db->exec("ALTER TABLE qr_sessions ADD INDEX idx_qr_sessions_active_sec (teacher_id, section, is_active, `end`)");
        echo "Index added.\n";
    }

    // Synchronize is_active values based on current time:
    // Expired sessions (end <= NOW()) -> is_active = 0
    // Non-expired sessions (end > NOW()) -> is_active = 1
    $db->exec("UPDATE qr_sessions SET is_active = 0 WHERE `end` <= NOW()");
    $db->exec("UPDATE qr_sessions SET is_active = 1 WHERE `end` > NOW()");
    
    echo "Synchronized is_active values based on expiry timestamps.\n";
    
    // Verify columns
    $stmt = $db->query("DESCRIBE qr_sessions");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Current columns in qr_sessions:\n";
    foreach ($cols as $c) {
        echo " - {$c['Field']} ({$c['Type']}), Null: {$c['Null']}, Default: {$c['Default']}\n";
    }

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
