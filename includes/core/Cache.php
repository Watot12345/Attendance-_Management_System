<?php
/**
 * High-Performance Application Cache Layer
 * includes/core/Cache.php
 * 
 * Provides ultra-fast in-memory and persistent file-based caching
 * to eliminate remote database network latency (500ms -> 0.1ms).
 */

class Cache {

    private static array $memoryCache = [];
    private static string $cacheDir = '';

    private static function init(): void {
        if (empty(self::$cacheDir)) {
            self::$cacheDir = dirname(__DIR__, 2) . '/storage/cache';
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0777, true);
            }
        }
    }

    private static function getFilePath(string $key): string {
        self::init();
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $key);
        return self::$cacheDir . '/' . md5($key) . '_' . $safeKey . '.cache';
    }

    /**
     * Retrieve an item from cache
     */
    public static function get(string $key, mixed $default = null): mixed {
        // 1. Check L1 memory cache
        if (array_key_exists($key, self::$memoryCache)) {
            $item = self::$memoryCache[$key];
            if ($item['expires_at'] === 0 || $item['expires_at'] >= time()) {
                return $item['value'];
            }
            unset(self::$memoryCache[$key]);
        }

        // 2. Check L2 disk cache
        $file = self::getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $raw = @file_get_contents($file);
        if (empty($raw)) {
            return $default;
        }

        $data = @unserialize($raw);
        if ($data === false || !is_array($data) || !isset($data['expires_at'])) {
            @unlink($file);
            return $default;
        }

        if ($data['expires_at'] !== 0 && $data['expires_at'] < time()) {
            @unlink($file);
            return $default;
        }

        // Store into L1 memory cache
        self::$memoryCache[$key] = $data;
        return $data['value'];
    }

    /**
     * Store an item in cache for a given number of seconds
     */
    public static function set(string $key, mixed $value, int $ttlSeconds = 300): bool {
        self::init();
        $expiresAt = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        $data = [
            'key'        => $key,
            'expires_at' => $expiresAt,
            'created_at' => time(),
            'value'      => $value
        ];

        // 1. Set L1 memory cache
        self::$memoryCache[$key] = $data;

        // 2. Set L2 disk cache
        $file = self::getFilePath($key);
        return @file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result
     */
    public static function remember(string $key, int $ttlSeconds, callable $callback): mixed {
        $value = self::get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttlSeconds);
        return $value;
    }

    /**
     * Check if an item exists in cache and is not expired
     */
    public static function has(string $key): bool {
        return self::get($key) !== null;
    }

    /**
     * Remove an item from the cache
     */
    public static function forget(string $key): bool {
        unset(self::$memoryCache[$key]);
        $file = self::getFilePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    /**
     * Invalidate items matching a prefix/wildcard
     */
    public static function forgetPattern(string $pattern): void {
        self::init();
        foreach (self::$memoryCache as $k => $v) {
            if (fnmatch($pattern, $k)) {
                unset(self::$memoryCache[$k]);
            }
        }
        $safePattern = preg_replace('/[^a-zA-Z0-9_\-\.\*]/', '_', $pattern);
        $files = glob(self::$cacheDir . '/*_' . $safePattern . '.cache');
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
    }

    /**
     * Flush all cached items
     */
    public static function flush(): bool {
        self::$memoryCache = [];
        self::init();
        $files = glob(self::$cacheDir . '/*.cache');
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        return true;
    }
}
