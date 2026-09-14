<?php
/**
 * SettingsController — includes/controllers/SettingsController.php
 * Handles global institutional system configurations, alerts, and reporting preferences.
 */

require_once dirname(__DIR__) . '/core/Database.php';

class SettingsController {
    /**
     * Cache for loaded settings in current request
     */
    private static ?array $settingsCache = null;

    /**
     * Get a setting value by key with optional default
     */
    public static function get(string $key, mixed $default = null): mixed {
        if (self::$settingsCache === null) {
            self::loadAll();
        }
        return self::$settingsCache[$key] ?? $default;
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAll(): array {
        if (self::$settingsCache === null) {
            self::loadAll();
        }
        return self::$settingsCache;
    }

    /**
     * Set/update a setting value in database
     */
    public static function set(string $key, mixed $value): bool {
        $db = Database::getConnection();
        $strVal = is_bool($value) ? ($value ? '1' : '0') : (string)$value;

        $stmt = $db->prepare("
            INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = NOW()
        ");
        $res = $stmt->execute([$key, $strVal]);

        if (self::$settingsCache !== null) {
            self::$settingsCache[$key] = $strVal;
        }

        return $res;
    }

    /**
     * Preload all settings from system_settings table
     */
    private static function loadAll(): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT `setting_key`, `setting_value` FROM `system_settings`");
            $rows = $stmt->fetchAll();
            self::$settingsCache = [];
            foreach ($rows as $r) {
                self::$settingsCache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Exception $e) {
            self::$settingsCache = [];
        }
    }

    /**
     * API: GET /api/settings
     */
    public function apiIndex(): void {
        header('Content-Type: application/json');
        try {
            $settings = self::getAll();
            echo json_encode([
                'status'  => 'success',
                'data'    => $settings
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to fetch settings: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API: POST /api/settings
     */
    public function apiSave(): void {
        header('Content-Type: application/json');
        try {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            } else {
                $payload = $_POST;
            }

            if (empty($payload)) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'No settings provided to save.'
                ]);
                exit;
            }

            // Save key-value pairs
            foreach ($payload as $key => $val) {
                $cleanKey = trim($key);
                if ($cleanKey === '') continue;
                self::set($cleanKey, $val);
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'System settings updated successfully.',
                'data'    => self::getAll()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to save settings: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}
