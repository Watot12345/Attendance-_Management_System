<?php
/**
 * Database Singleton Wrapper — includes/core/Database.php
 * Provides a secure, persistent PDO connection with SSL support for Aiven Cloud MySQL.
 */

class Database {
    private static ?PDO $instance = null;

    /**
     * Parse and load .env file if environment variables are not already set
     */
    private static function loadEnv(string $path): void {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                if (!array_key_exists($key, $_ENV)) {
                    putenv("$key=$val");
                    $_ENV[$key] = $val;
                }
            }
        }
    }

    /**
     * Get or initialize the PDO instance
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::loadEnv(dirname(__DIR__, 2) . '/.env');

            $host = getenv('DB_HOST') ?: 'mysql-ams-asierra389-4cc5.f.aivencloud.com';
            $port = getenv('DB_PORT') ?: '18778';
            $dbName = getenv('DB_NAME') ?: 'defaultdb';
            $user = getenv('DB_USER') ?: 'avnadmin';
            $pass = getenv('DB_PASS') ?: '';
            $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];

            self::$instance = new PDO($dsn, $user, $pass, $options);
        }

        return self::$instance;
    }
}
