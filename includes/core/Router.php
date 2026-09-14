<?php
/**
 * Router — includes/core/Router.php
 * Front controller URL router. Maps URL paths to includes/views/ templates.
 */

if (!function_exists('startSessionSafely')) {
    function startSessionSafely(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $savePath = session_save_path() ?: sys_get_temp_dir();
            if (!is_dir($savePath) || !is_writable($savePath)) {
                $temp = sys_get_temp_dir();
                if (is_dir($temp) && is_writable($temp)) {
                    @session_save_path($temp);
                }
            }
            @session_start();
        }
    }
}
startSessionSafely();

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

if (!function_exists('asset')) {
    /**
     * Generate asset URL supporting relative assets directory
     */
    function asset(string $path = ''): string {
        $path = ltrim($path, '/');
        if (!str_starts_with($path, 'assets/')) {
            $path = 'assets/' . $path;
        }
        return url($path);
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
        '/logout'                => 'AuthController@logout',
        '/auth/login'            => 'AuthController@login',
        '/api/auth/login'        => 'AuthController@login',
        '/api/auth/logout'       => 'AuthController@logout',
        '/api/auth/me'           => 'AuthController@me',

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
        '/teacher/import-roster/template' => 'StudentController@downloadRosterTemplate',
        '/teacher/roster/template' => 'StudentController@downloadRosterTemplate',
        '/teacher/roster'        => 'teacher/classes.php',
        '/teacher/live-session'  => 'teacher/live-session.php',
        '/teacher/attendance-history' => 'teacher/attendance-history.php',
        '/teacher/attendance/history' => 'teacher/attendance-history.php',
        '/teacher/history'       => 'teacher/attendance-history.php',
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
        '/admin/dashboard'         => 'admin/dashboard.php',
        '/admin/students'          => 'StudentController@index',
        '/admin/students/store'    => 'StudentController@store',
        '/admin/students/import'   => 'StudentController@import',
        '/admin/students/template' => 'StudentController@downloadTemplate',
        '/admin/import-students'   => 'admin/import-students.php',
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
        '/api/excuses/submit'    => 'ExcuseController@submit',
        '/api/excuses/update'    => 'ExcuseController@update',
        '/api/excuses/delete'    => 'ExcuseController@delete',
        '/api/excuses/bulk-delete' => 'ExcuseController@bulkDelete',
        '/api/excuses/list'      => 'ExcuseController@listStudent',
        '/api/excuses/review'    => 'ExcuseController@review',
        '/api/teacher/roster/validate' => 'StudentController@validateRoster',
        '/api/teacher/roster/import'   => 'StudentController@importClassRoster',
        '/api/teacher/qr-session/generate' => 'AttendanceController@generateQrSession',
        '/api/teacher/qr-session/active'   => 'AttendanceController@getActiveQrSession',
        '/api/teacher/qr-session/close'    => 'AttendanceController@closeQrSession',
        '/api/teacher/attendance/live-feed'=> 'AttendanceController@getLiveAttendanceFeed',
        '/api/attendance/check-in'         => 'AttendanceController@recordCheckIn',

        // Teachers Master & Bulk Import API
        '/api/teachers'                 => 'TeacherController@handleRoot',
        '/api/teachers/create'          => 'TeacherController@apiCreate',
        '/api/teachers/update'          => 'TeacherController@apiUpdate',
        '/api/teachers/delete'          => 'TeacherController@apiDelete',
        '/api/teachers/reset-password'  => 'TeacherController@apiResetPassword',
        '/api/teachers/import'          => 'TeacherController@apiImport',

        // System Settings API
        '/api/settings'                 => 'SettingsController@apiIndex',
        '/api/settings/save'            => 'SettingsController@apiSave',

        // Overview Dashboard API
        '/api/dashboard/overview'       => 'DashboardController@apiOverview',

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
     * Get the clean request path relative to the application base directory (e.g. /teacher/dashboard)
     */
    public static function getCurrentPath(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        $baseDir = self::getBasePath();
        $decodedPath = rawurldecode($path);
        $decodedBase = rawurldecode($baseDir);

        if ($decodedBase !== '' && stripos($decodedPath, $decodedBase) === 0) {
            $decodedPath = substr($decodedPath, strlen($decodedBase));
        }

        if (stripos($decodedPath, '/index.php') === 0) {
            $decodedPath = substr($decodedPath, strlen('/index.php'));
        }

        $clean = '/' . ltrim($decodedPath, '/');
        if (strlen($clean) > 1) {
            $clean = rtrim($clean, '/');
        }
        return $clean;
    }

    /**
     * Synchronizes session role and user persona for the active role
     */
    public static function syncSessionUserForRole(string $role): void {
        startSessionSafely();
        $_SESSION['role'] = $role;

        $loadedFromDb = false;
        try {
            require_once dirname(__DIR__) . '/core/Database.php';
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE role = ? AND status = 'active' ORDER BY user_id ASC LIMIT 1");
            $stmt->execute([$role]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($u) {
                $_SESSION['user_id'] = (int)$u['user_id'];
                $_SESSION['user'] = [
                    'user_id'     => (int)$u['user_id'],
                    'student_id'  => $u['student_id'] ?? null,
                    'employee_id' => $u['employee_id'] ?? null,
                    'first_name'  => $u['first_name'],
                    'last_name'   => $u['last_name'],
                    'full_name'   => trim("{$u['first_name']} {$u['last_name']}"),
                    'email'       => $u['email'],
                    'role'        => $role,
                    'avatar_path' => $u['avatar_path'] ?? null,
                ];
                if ($role === 'teacher') {
                    $_SESSION['teacher_id'] = (int)$u['user_id'];
                    unset($_SESSION['student_id']);
                } elseif ($role === 'student') {
                    $_SESSION['student_id'] = (int)$u['user_id'];
                    unset($_SESSION['teacher_id']);
                } else {
                    unset($_SESSION['teacher_id'], $_SESSION['student_id']);
                }
                $loadedFromDb = true;
            }
        } catch (Throwable $e) {
            $loadedFromDb = false;
        }

        if (!$loadedFromDb) {
            if ($role === 'teacher') {
                $_SESSION['user_id'] = 2;
                $_SESSION['teacher_id'] = 2;
                unset($_SESSION['student_id']);
                $_SESSION['user'] = [
                    'user_id'     => 2,
                    'employee_id' => 'EMP-1001',
                    'first_name'  => 'Manuel',
                    'last_name'   => 'Ramirez',
                    'full_name'   => 'Prof. Manuel Ramirez',
                    'email'       => 'm.ramirez@bcp.edu.ph',
                    'role'        => 'teacher',
                    'department'  => 'College of Computer Studies',
                    'avatar_path' => null,
                ];
            } elseif ($role === 'student') {
                $_SESSION['user_id'] = 1;
                $_SESSION['student_id'] = 1;
                unset($_SESSION['teacher_id']);
                $_SESSION['user'] = [
                    'user_id'     => 1,
                    'student_id'  => '2026-00123',
                    'first_name'  => 'Juan',
                    'last_name'   => 'Dela Cruz',
                    'full_name'   => 'Juan Dela Cruz',
                    'email'       => 'juan.delacruz@bcp.edu.ph',
                    'role'        => 'student',
                    'section'     => 'BSIT 3-A',
                    'avatar_path' => null,
                ];
            } else {
                $_SESSION['user_id'] = 999;
                unset($_SESSION['teacher_id'], $_SESSION['student_id']);
                $_SESSION['user'] = [
                    'user_id'     => 999,
                    'employee_id' => 'ADM-001',
                    'first_name'  => 'System',
                    'last_name'   => 'Administrator',
                    'full_name'   => 'System Administrator',
                    'email'       => 'admin@bcp.edu.ph',
                    'role'        => 'admin',
                    'avatar_path' => null,
                ];
            }
        }
    }

    /**
     * Get the currently active role ('admin', 'teacher', 'student')
     */
    public static function getCurrentRole(): string {
        startSessionSafely();

        // 1. Explicit query param switch (e.g. ?switch_role=student or ?role=teacher)
        if (isset($_GET['switch_role']) && in_array($_GET['switch_role'], ['admin', 'teacher', 'student'], true)) {
            self::syncSessionUserForRole($_GET['switch_role']);
            return $_SESSION['role'];
        }
        if (isset($_GET['role']) && in_array($_GET['role'], ['admin', 'teacher', 'student'], true)) {
            $path = self::getCurrentPath();
            if (!str_starts_with($path, '/api/')) {
                self::syncSessionUserForRole($_GET['role']);
                return $_SESSION['role'];
            }
        }

        // 2. URL route prefix auto-detection
        $path = self::getCurrentPath();
        if (str_starts_with($path, '/teacher')) {
            if (($_SESSION['role'] ?? '') !== 'teacher') {
                self::syncSessionUserForRole('teacher');
            }
            return 'teacher';
        }
        if (str_starts_with($path, '/student')) {
            if (($_SESSION['role'] ?? '') !== 'student') {
                self::syncSessionUserForRole('student');
            }
            return 'student';
        }
        if (str_starts_with($path, '/admin') || $path === '/dashboard') {
            if (($_SESSION['role'] ?? '') !== 'admin') {
                self::syncSessionUserForRole('admin');
            }
            return 'admin';
        }

        // 3. Fallback to session, default to admin
        return $_SESSION['role'] ?? 'admin';
    }

    /**
     * Dispatch the current request
     */
    public static function dispatch(): void {
        $path = self::getCurrentPath();

        // 0. Quick switch-role endpoint
        if ($path === '/switch-role') {
            $role = $_GET['role'] ?? 'admin';
            if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
                $role = 'admin';
            }
            self::syncSessionUserForRole($role);

            $dest = match ($role) {
                'teacher' => url('teacher/dashboard'),
                'student' => url('student/calendar'),
                default   => url('dashboard'),
            };
            header('Location: ' . $dest);
            exit;
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

        // 2b. Dynamic RESTful route match (e.g. /api/teachers/{id})
        if (preg_match('#^/api/teachers/(\d+)$#', $cleanCandidate, $matches)) {
            $_GET['id'] = (int)$matches[1];
            self::render('TeacherController@handleRoot');
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
