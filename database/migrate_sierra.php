<?php
/**
 * Migration runner for Sierra modules.
 */
require_once dirname(__DIR__) . '/includes/core/Database.php';

try {
    $db = Database::getConnection();
    echo "Connected to Railway MySQL.\n";

    $sqlFile = __DIR__ . '/sierra_schema.sql';
    $sql = file_get_contents($sqlFile);

    // Split statements
    $db->exec($sql);
    echo "Successfully executed sierra_schema.sql!\n";

    // Verify created tables
    $tables = ['teachers', 'faculty_import_logs', 'system_settings'];
    foreach ($tables as $t) {
        $count = $db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo "Table `{$t}` exists (rows: {$count}).\n";
    }

    // Verify views
    $db->query("SELECT * FROM `v_attendance_summary` LIMIT 1");
    echo "View `v_attendance_summary` is queryable.\n";

    $db->query("SELECT * FROM `attendance_records` LIMIT 1");
    echo "View `attendance_records` is queryable.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
