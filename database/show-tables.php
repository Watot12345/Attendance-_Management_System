<?php
/**
 * Database Table Inspector — database/show-tables.php
 * View all tables, inspect columns, and preview records directly from terminal.
 *
 * Usage:
 *   php database/show-tables.php           (Show all tables & row counts)
 *   php database/show-tables.php users     (Inspect columns & preview rows of `users`)
 *   php database/show-tables.php <table_name>
 */

require_once dirname(__DIR__) . '/includes/core/Database.php';

try {
    $db = Database::getConnection();
} catch (Exception $e) {
    echo "❌ Connection Error: " . $e->getMessage() . "\n";
    exit(1);
}

$requestedTable = isset($argv[1]) ? trim($argv[1]) : null;

echo "========================================================================================\n";
echo "  LAMS Database Inspector (Aiven Cloud MySQL)                                           \n";
echo "========================================================================================\n";

if ($requestedTable) {
    // Sanitize input table name
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($requestedTable, $tables)) {
        echo "❌ Error: Table '{$requestedTable}' does not exist in database.\n";
        echo "Available tables: " . implode(', ', $tables) . "\n";
        exit(1);
    }

    echo "TABLE: `{$requestedTable}`\n";
    echo "----------------------------------------------------------------------------------------\n";
    echo "  COLUMNS STRUCTURE\n";
    echo "----------------------------------------------------------------------------------------\n";
    printf(" %-24s | %-28s | %-6s | %-5s | %-12s\n", "Field", "Type", "Null", "Key", "Default");
    echo "--------------------------+------------------------------+--------+-------+-------------\n";

    $cols = $db->query("DESCRIBE `{$requestedTable}`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        $default = $col['Default'] === null ? 'NULL' : $col['Default'];
        printf(" %-24s | %-28s | %-6s | %-5s | %-12s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key'], 
            substr($default, 0, 12)
        );
    }
    echo "----------------------------------------------------------------------------------------\n";

    // Preview rows
    $count = $db->query("SELECT COUNT(*) FROM `{$requestedTable}`")->fetchColumn();
    echo "\n  DATA PREVIEW ({$count} total rows in table):\n";
    echo "----------------------------------------------------------------------------------------\n";

    if ($count == 0) {
        echo "  (No rows in table yet)\n";
    } else {
        $rows = $db->query("SELECT * FROM `{$requestedTable}` LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $i => $row) {
            echo "  [Row #" . ($i + 1) . "]\n";
            foreach ($row as $k => $v) {
                // Obfuscate password hash for privacy
                if ($k === 'password_hash') {
                    $v = substr($v, 0, 12) . '... (hashed)';
                }
                $valStr = ($v === null) ? 'NULL' : $v;
                echo "    • {$k}: {$valStr}\n";
            }
            echo "\n";
        }
    }

} else {
    // Show summary of all tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "ALL DATABASE TABLES (" . count($tables) . " tables found):\n";
    echo "----------------------------------------------------------------------------------------\n";
    printf(" %-4s | %-30s | %-10s | %-10s\n", "No.", "Table Name", "Columns", "Total Rows");
    echo "------+--------------------------------+------------+------------\n";

    foreach ($tables as $index => $table) {
        $colCount = $db->query("SHOW COLUMNS FROM `{$table}`")->rowCount();
        $rowCount = $db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

        printf("  %2d. | %-30s | %-10s | %-10s\n", 
            $index + 1, 
            $table, 
            $colCount . " cols", 
            $rowCount . " rows"
        );
    }

    echo "----------------------------------------------------------------------------------------\n";
    echo "💡 Tip: Inspect details of a specific table by passing its name:\n";
    echo "   php database/show-tables.php users\n";
    echo "   php database/show-tables.php library_settings\n";
    echo "   php database/show-tables.php attendance_records\n";
}

echo "========================================================================================\n";
