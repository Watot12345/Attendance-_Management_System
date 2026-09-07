<?php
/**
 * Database Migration Runner — database/migrate.php
 * Executes schema.sql against the configured Aiven MySQL database.
 */

require_once dirname(__DIR__) . '/includes/core/Database.php';

echo "===============================================================\n";
echo "  LAMS Database Migration Runner (Aiven Cloud MySQL)           \n";
echo "===============================================================\n\n";

$isStatusOnly = in_array('--status', $argv ?? []) || in_array('-s', $argv ?? []) || in_array('--show', $argv ?? []);

try {
    $db = Database::getConnection();
    echo "✓ Connected to Aiven MySQL successfully.\n";

    if (!$isStatusOnly) {
        $sqlFile = __DIR__ . '/schema.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("schema.sql file not found at: {$sqlFile}");
        }

        echo "✓ Reading schema.sql...\n";
        $sql = file_get_contents($sqlFile);

        echo "✓ Executing schema migration...\n";
        $db->exec($sql);
        echo "✓ Schema execution finished successfully!\n\n";
    } else {
        echo "ℹ Running in status-only mode (viewing tables)...\n\n";
    }

    // Verify tables created
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "---------------------------------------------------------------\n";
    echo "  CREATED TABLES REPORT (" . count($tables) . " tables)        \n";
    echo "---------------------------------------------------------------\n";

    foreach ($tables as $index => $table) {
        $colsStmt = $db->query("SHOW COLUMNS FROM `{$table}`");
        $colCount = $colsStmt->rowCount();
        $countStmt = $db->query("SELECT COUNT(*) FROM `{$table}`");
        $rowCount = $countStmt->fetchColumn();

        printf(" %2d. %-28s | %2d columns | %3d rows\n", $index + 1, $table, $colCount, $rowCount);
    }

    echo "---------------------------------------------------------------\n";
    echo "✓ Migration complete! All tables and seed data are ready.\n";
    echo "===============================================================\n";

} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
