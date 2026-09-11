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
        'auth'                   => 'auth/login.php',
        '/auth'                  => 'auth/login.php',
        '/login'                 => 'auth/login.php',
        '/logout'                => 'auth/login.php',

        // Dashboard & Portals
        '/dashboard'             => 'dashboard/index.php',
        '/dashboard/excuse-slips'=> 'dashboard/excuse-slips.php',
        '/dashboard/analytics'   => 'dashboard/analytics.php',
        '/dashboard/tools'       => 'dashboard/tools.php',
        '/dashboard/ml-analytics'=> 'dashboard/analytics.php',

        // Teacher Portal
        '/teacher/dashboard'     => 'teacher/dashboard.php',
        '/teacher/classes'       => 'teacher/classes.php',
        '/teacher/import-roster' => 'teacher/import-roster.php',
        '/teacher/roster'        => 'teacher/classes.php',
        '/teacher/live-session'  => 'teacher/live-session.php',
        '/teacher/attendance-history' => 'teacher/attendance-history.php',
        '/teacher/daily-attendance' => 'attendance/daily.php',
        '/teacher/attendance'    => 'attendance/daily.php',
        '/teacher/tardy-logs'    => 'alerts/history.php',
        '/teacher/excuse-slips'  => 'dashboard/excuse-slips.php',
        '/teacher/awards'        => 'awards/index.php',

        // Student Portal
        '/student/dashboard'     => 'calendar/index.php',
        '/student/classes'       => 'calendar/index.php',
        '/student/scanner'       => 'student/scanner.php',
        '/student/scan-result'   => 'student/scan-result.php',
        '/student/calendar'      => 'calendar/index.php',
        '/student/history'       => 'student/history.php',
        '/student/excuse-slips'  => 'student/excuse-slips.php',

        // Admin Portal
        '/admin/dashboard'       => 'admin/dashboard.php',
        '/admin/students'        => 'StudentController@index',
        '/admin/students/store'  => 'StudentController@store',
        '/admin/import-students' => 'admin/import-students.php',
        '/admin/teachers'        => 'admin/teachers.php',
        '/admin/reports'         => 'admin/reports.php',

        // Attendance (Legacy / Core)
        '/attendance'            => 'attendance/daily.php',
        '/attendance/daily'      => 'attendance/daily.php',
        '/attendance/scan'       => 'teacher/live-session.php',
        '/attendance/manual-entry'=> 'attendance/manual-entry.php',
        '/attendance/teachers'   => 'admin/teachers.php',

        // Calendar
        '/calendar'              => 'calendar/index.php',

        // Analytics
        '/analytics'             => 'dashboard/analytics.php',
        '/analytics/dashboard'   => 'dashboard/analytics.php',
        '/analytics/patterns'    => 'dashboard/analytics.php',
        '/analytics/at-risk'     => 'dashboard/analytics.php',

        // Users
        '/users'                 => 'users/list.php',
        '/users/list'            => 'users/list.php',
        '/users/create'          => 'users/create.php',
        '/users/store'           => 'UserController@store',
        '/users/edit'            => 'users/edit.php',
        '/users/profile'         => 'users/profile.php',

        // API Endpoints
        '/api/users/delete'      => 'UserController@apiDelete',

        // Alerts
        '/alerts'                => 'alerts/index.php',
        '/alerts/history'        => 'alerts/history.php',
        '/alerts/settings'       => 'settings/index.php',

        // Settings
        '/settings'              => 'settings/index.php',

        // Awards & Exports
        '/awards'                => 'awards/index.php',
        '/exports'               => 'admin/reports.php',

        // Excuses
        '/excuses'               => 'dashboard/excuse-slips.php',
        '/excuses/submit'        => 'student/excuse-slips.php',
        '/excuses/review'        => 'dashboard/excuse-slips.php',
        '/excuses/detail'        => 'dashboard/excuse-slips.php',

        // Parent View
        '/parent'                => 'parent/attendance.php',

        // Error Pages
        '/403'                   => 'errors/403.php',
        '/404'                   => 'errors/404.php',
        '/500'                   => 'errors/500.php',

        // Convenience Aliases
        '/scan'                  => 'student/scanner.php',
        '/manual-entry'          => 'attendance/manual-entry.php',
        '/teachers'              => 'admin/teachers.php',
        '/excuse-slips'          => 'dashboard/excuse-slips.php',
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
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']), '/') : '';
        $appRoot = rtrim(str_replace('\\', '/', realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2)), '/');

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

        // Decode URL encoding (%20, spaces, etc.) for both path and base directory
        $decodedPath = rawurldecode($path);
        $decodedBase = rawurldecode($baseDir);

        // Strip project subdirectory if running in XAMPP or subfolder
        if ($decodedBase !== '' && stripos($decodedPath, $decodedBase) === 0) {
            $decodedPath = substr($decodedPath, strlen($decodedBase));
        }

        // Strip front controller /index.php if present at start (e.g. /index.php/dashboard or /index.php)
        if (stripos($decodedPath, '/index.php') === 0) {
            $decodedPath = substr($decodedPath, strlen('/index.php'));
        }

        // Clean up slashes
        $path = '/' . ltrim($decodedPath, '/');
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

        // 2. Strip /index, .php extension, or trailing /index (e.g. /dashboard.php -> /dashboard)
        $cleanCandidate = preg_replace('#(/index)?(\.php)?$#i', '', $path);
        if ($cleanCandidate === '') {
            $cleanCandidate = '/';
        }
        if (isset(self::$routes[$cleanCandidate])) {
            self::render(self::$routes[$cleanCandidate]);
            return;
        }

        // 3. Dynamic match against includes/views/ directory
        $viewsBase = dirname(__DIR__) . '/views/';
        $cleanRelative = ltrim($cleanCandidate, '/');

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
