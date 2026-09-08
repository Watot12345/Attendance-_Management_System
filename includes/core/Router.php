<?php
/**
 * Router — includes/core/Router.php
 * Front controller URL router. Maps URL paths to includes/views/ templates.
 */

if (!function_exists('url')) {
    /**
     * Generate an application URL supporting root and subdirectory deployments
     */
    function url(string $path = ''): string {
        $base = Router::getBasePath();
        $path = ltrim($path, '/');
        if ($path === '') {
            return $base !== '' ? $base : '/';
        }
        return ($base !== '' ? $base : '') . '/' . $path;
    }
}

class Router {
    /**
     * Map of clean URL paths to view files relative to includes/views/
     */
    private static array $routes = [
        '/'                      => 'auth/login.php',
        '/auth'                  => 'auth/login.php',
        '/login'                 => 'auth/login.php',
        '/logout'                => 'auth/login.php',

        // Dashboard & Portals
        '/dashboard'             => 'dashboard/index.php',
        '/dashboard/excuse-slips'=> 'dashboard/excuse-slips.php',
        '/dashboard/analytics'   => 'dashboard/analytics.php',
        '/dashboard/tools'       => 'dashboard/tools.php',
        '/dashboard/ml-analytics'=> 'dashboard/ml-analytics.php',

        // Teacher Portal
        '/teacher/dashboard'     => 'teacher/dashboard.php',
        '/teacher/classes'       => 'teacher/classes.php',
        '/teacher/import-roster' => 'teacher/import-roster.php',
        '/teacher/roster'        => 'teacher/roster.php',
        '/teacher/live-session'  => 'teacher/live-session.php',
        '/teacher/attendance-history' => 'teacher/attendance-history.php',
        '/teacher/daily-attendance' => 'attendance/daily.php',
        '/teacher/attendance'    => 'attendance/daily.php',
        '/teacher/tardy-logs'    => 'alerts/history.php',
        '/teacher/excuse-slips'  => 'dashboard/excuse-slips.php',
        '/teacher/awards'        => 'awards/index.php',

        // Student Portal
        '/student/dashboard'     => 'student/dashboard.php',
        '/student/classes'       => 'student/classes.php',
        '/student/scanner'       => 'student/scanner.php',
        '/student/scan-result'   => 'student/scan-result.php',
        '/student/calendar'      => 'calendar/index.php',
        '/student/history'       => 'student/history.php',
        '/student/excuse-slips'  => 'student/excuse-slips.php',

        // Admin Portal
        '/admin/dashboard'       => 'admin/dashboard.php',
        '/admin/students'        => 'admin/students.php',
        '/admin/import-students' => 'admin/import-students.php',
        '/admin/teachers'        => 'admin/teachers.php',
        '/admin/courses-sections'=> 'admin/courses-sections.php',
        '/admin/reports'         => 'admin/reports.php',

        // Attendance (Legacy / Core)
        '/attendance'            => 'attendance/daily.php',
        '/attendance/daily'      => 'attendance/daily.php',
        '/attendance/scan'       => 'attendance/scan.php',
        '/attendance/manual-entry'=> 'attendance/manual-entry.php',
        '/attendance/teachers'   => 'attendance/teachers.php',

        // Calendar
        '/calendar'              => 'calendar/index.php',

        // Analytics
        '/analytics'             => 'analytics/dashboard.php',
        '/analytics/dashboard'   => 'analytics/dashboard.php',
        '/analytics/patterns'    => 'analytics/patterns.php',
        '/analytics/at-risk'     => 'analytics/at-risk.php',

        // Users
        '/users'                 => 'users/list.php',
        '/users/list'            => 'users/list.php',
        '/users/create'          => 'users/create.php',
        '/users/edit'            => 'users/edit.php',
        '/users/profile'         => 'users/profile.php',

        // Alerts
        '/alerts'                => 'alerts/index.php',
        '/alerts/history'        => 'alerts/history.php',
        '/alerts/settings'       => 'alerts/settings.php',

        // Settings
        '/settings'              => 'settings/index.php',

        // Awards & Exports
        '/awards'                => 'awards/index.php',
        '/exports'               => 'exports/index.php',

        // Excuses
        '/excuses'               => 'excuses/index.php',
        '/excuses/submit'        => 'excuses/submit.php',
        '/excuses/review'        => 'excuses/review.php',
        '/excuses/detail'        => 'excuses/detail.php',

        // Parent View
        '/parent'                => 'parent/attendance.php',

        // Convenience Aliases
        '/scan'                  => 'student/scanner.php',
        '/manual-entry'          => 'attendance/manual-entry.php',
        '/teachers'              => 'admin/teachers.php',
        '/excuse-slips'          => 'excuses/index.php',
        '/profile'               => 'users/profile.php',
        '/history'               => 'alerts/history.php',
    ];

    /**
     * Determine the base URL path (empty string if root, or /subdir if deployed in folder)
     */
    public static function getBasePath(): string {
        static $base = null;
        if ($base !== null) {
            return $base;
        }

        // 1. On PHP's built-in web server, root is always the project root
        if (php_sapi_name() === 'cli-server') {
            $base = '';
            return $base;
        }

        // 2. On Apache/Nginx, determine subdirectory relative to DOCUMENT_ROOT
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']) : '';
        $appRoot = str_replace('\\', '/', realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2));

        if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
            $sub = substr($appRoot, strlen($docRoot));
            $base = ($sub === false || $sub === '' || $sub === '/') ? '' : '/' . trim($sub, '/\\');
            return $base;
        }

        // 3. Fallback using SCRIPT_NAME if executed from within includes/
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (stripos($script, '/includes/') !== false) {
            $sub = substr($script, 0, stripos($script, '/includes/'));
            $base = rtrim($sub, '/\\');
            return $base;
        }

        $dir = rtrim(dirname($script), '/\\');
        $base = ($dir === '' || $dir === '\\' || $dir === '/') ? '' : $dir;
        return $base;
    }

    /**
     * Dispatch the current request
     */
    public static function dispatch(): void {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        $baseDir = self::getBasePath();

        // Strip project subdirectory if running in XAMPP or subfolder
        if ($baseDir !== '' && stripos($path, $baseDir) === 0) {
            $path = substr($path, strlen($baseDir));
        }

        // Clean up slashes
        $path = '/' . ltrim($path, '/');
        if (strlen($path) > 1) {
            $path = rtrim($path, '/');
        }

        // Redirect any direct /includes/views/... requests to clean URLs
        if (strpos($path, '/includes/views/') === 0) {
            $sub = substr($path, strlen('/includes/views/'));
            $clean = preg_replace('/(\/index)?\.php$/i', '', $sub);
            $clean = ltrim($clean, '/');
            if ($clean === 'auth/login' || $clean === 'auth') {
                $clean = 'auth';
            }
            $target = url($clean);
            if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') {
                $target .= '?' . $_SERVER['QUERY_STRING'];
            }
            header('Location: ' . $target, true, 301);
            exit;
        }

        // 1. Direct route match
        if (isset(self::$routes[$path])) {
            self::render(self::$routes[$path]);
            return;
        }

        // 2. Strip /index.php, /index, or .php
        $noIndex = preg_replace('#(/index)?(\.php)?$#i', '', $path);
        if ($noIndex !== '' && isset(self::$routes[$noIndex])) {
            self::render(self::$routes[$noIndex]);
            return;
        }

        // 3. Dynamic match against includes/views/ directory
        $viewsBase = dirname(__DIR__) . '/views/';
        $cleanRelative = ltrim($noIndex, '/');

        $candidates = [
            $cleanRelative,
            $cleanRelative . '.php',
            $cleanRelative . '/index.php',
        ];

        foreach ($candidates as $candidate) {
            $candidatePath = $viewsBase . $candidate;
            if (is_file($candidatePath)) {
                self::render($candidate);
                return;
            }
        }

        // 4. Not found -> 404
        self::notFound();
    }

    /**
     * Render the target view file or execute controller action
     */
    private static function render(string $handler): void {
        // 1. Controller Action (e.g. 'AuthController@login' or 'UserController@store')
        if (str_contains($handler, '@')) {
            [$controllerName, $method] = explode('@', $handler, 2);

            // Look in includes/controllers/ or includes/controller/
            $controllerFile = dirname(__DIR__) . '/controllers/' . $controllerName . '.php';
            if (!is_file($controllerFile)) {
                $controllerFile = dirname(__DIR__) . '/controller/' . $controllerName . '.php';
            }

            if (is_file($controllerFile)) {
                require_once $controllerFile;
                if (class_exists($controllerName) && method_exists($controllerName, $method)) {
                    http_response_code(200);
                    $instance = new $controllerName();
                    $instance->$method();
                    exit;
                }
            }
            self::notFound();
        }

        // 2. Direct View File (e.g. 'dashboard/index.php')
        $file = dirname(__DIR__) . '/views/' . ltrim($handler, '/\\');
        if (is_file($file)) {
            http_response_code(200);
            include $file;
            exit;
        }
        self::notFound();
    }

    /**
     * Display 404 error view
     */
    private static function notFound(): void {
        http_response_code(404);
        $errorFile = dirname(__DIR__) . '/views/errors/404.php';
        if (is_file($errorFile)) {
            include $errorFile;
        } else {
            echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1></body></html>';
        }
        exit;
    }
}
