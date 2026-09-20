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

            // 1. Resolve connection parameters from DB_* or Railway MYSQL* or defaults
            $host = getenv('DB_HOST') 
                 ?: getenv('MYSQLHOST') 
                 ?: ($_ENV['DB_HOST'] ?? ($_ENV['MYSQLHOST'] ?? 'yamabiko.proxy.rlwy.net'));

            $port = getenv('DB_PORT') 
                 ?: getenv('MYSQLPORT') 
                 ?: ($_ENV['DB_PORT'] ?? ($_ENV['MYSQLPORT'] ?? '57011'));

            $dbName = getenv('DB_NAME') 
                   ?: getenv('MYSQLDATABASE') 
                   ?: ($_ENV['DB_NAME'] ?? ($_ENV['MYSQLDATABASE'] ?? 'railway'));

            $user = getenv('DB_USER') 
                 ?: getenv('MYSQLUSER') 
                 ?: ($_ENV['DB_USER'] ?? ($_ENV['MYSQLUSER'] ?? 'root'));

            $pass = getenv('DB_PASS') 
                 ?: getenv('MYSQLPASSWORD') 
                 ?: ($_ENV['DB_PASS'] ?? ($_ENV['MYSQLPASSWORD'] ?? 'UKpKFxDomvRzxgSvsWSAMAAKBSjuKZVY'));

            $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

            // 2. Parse DATABASE_URL / MYSQL_URL if provided
            $dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: ($_ENV['DATABASE_URL'] ?? ($_ENV['MYSQL_URL'] ?? ''));
            if (!empty($dbUrl)) {
                $parsed = parse_url($dbUrl);
                if (!empty($parsed['host'])) $host = $parsed['host'];
                if (!empty($parsed['port'])) $port = (string)$parsed['port'];
                if (!empty($parsed['user'])) $user = $parsed['user'];
                if (!empty($parsed['pass'])) $pass = $parsed['pass'];
                if (!empty($parsed['path'])) $dbName = ltrim($parsed['path'], '/');
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10,
            ];

            // Only set SSL option when pdo_mysql extension is loaded (defines this constant)
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            date_default_timezone_set('Asia/Manila');
            self::$instance = new PDO($dsn, $user, $pass, $options);
            try {
                self::$instance->exec("SET time_zone = '+08:00'");
            } catch (Throwable $e) {}
        }

        return self::$instance;
    }
}
