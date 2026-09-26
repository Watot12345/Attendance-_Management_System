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
        '/auth/logout'           => 'AuthController@logout',
        '/auth/login'            => 'AuthController@login',
        '/api/auth/login'        => 'AuthController@login',
        '/api/auth/verify-otp'   => 'AuthController@verifyOtp',
        '/api/auth/resend-otp'   => 'AuthController@resendOtp',
        '/api/auth/check-remembered' => 'AuthController@checkRemembered',
        '/api/auth/check-pending-approval' => 'AuthController@checkPendingApproval',
        '/api/auth/respond-login-request'   => 'AuthController@respondLoginRequest',
        '/api/auth/login-request-status'     => 'AuthController@loginRequestStatus',
        '/api/auth/forgot-password'  => 'AuthController@forgotPassword',
        '/api/auth/verify-reset-otp' => 'AuthController@verifyResetOtp',
        '/api/auth/reset-password'   => 'AuthController@resetPassword',
        '/api/auth/logout'           => 'AuthController@logout',
        '/api/auth/me'               => 'AuthController@me',

        // Dashboard & Portals
        '/dashboard'             => 'dashboard/index.php',
        '/dashboard/excuse-slips'=> 'dashboard/excuse-slips.php',
        '/dashboard/analytics'   => 'dashboard/analytics.php',
        '/dashboard/tools'       => 'dashboard/tools.php',
        '/dashboard/ml-analytics'=> 'dashboard/analytics.php',

        // Teacher Portal
        '/teacher/dashboard'     => 'teacher/dashboard.php',
        '/teacher/consecutive-absences' => 'teacher/consecutive-absences.php',
        '/teacher/dropout-watchlist'    => 'teacher/consecutive-absences.php',
        '/teacher/at-risk'              => 'teacher/consecutive-absences.php',
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
        '/api/student/calendar'  => 'StudentController@apiCalendarData',
        '/api/users/delete'      => 'UserController@apiDelete',
        '/api/excuses/submit'    => 'ExcuseController@submit',
        '/api/excuses/update'    => 'ExcuseController@update',
        '/api/excuses/delete'    => 'ExcuseController@delete',
        '/api/excuses/bulk-delete' => 'ExcuseController@bulkDelete',
        '/api/excuses/list'      => 'ExcuseController@listStudent',
        '/api/excuses/review'    => 'ExcuseController@review',
        '/api/teacher/roster/resolve-section' => 'StudentController@apiResolveSection',
        '/api/teacher/roster/validate' => 'StudentController@validateRoster',
        '/api/teacher/roster/import'   => 'StudentController@importClassRoster',
        '/api/teacher/dashboard/overview' => 'TeacherController@apiDashboardOverview',
        '/api/teacher/qr-session/generate' => 'AttendanceController@generateQrSession',
        '/api/teacher/qr-session/active'   => 'AttendanceController@getActiveQrSession',
        '/api/teacher/qr-session/close'    => 'AttendanceController@closeQrSession',
        '/api/teacher/attendance/live-feed'=> 'AttendanceController@getLiveAttendanceFeed',
        '/api/teacher/attendance/void-proxy'=> 'AttendanceController@voidProxyAttendance',
        '/api/attendance/check-in'         => 'AttendanceController@recordCheckIn',
        '/api/attendance/daily'            => 'AttendanceController@apiDailyAttendance',
        '/api/attendance/manual-entry'     => 'AttendanceController@apiManualEntry',
        '/api/teacher/roster/students'     => 'AttendanceController@apiGetRosterStudents',
        '/api/teacher/awards/calculate'        => 'AttendanceController@apiCalculateAwards',
        '/api/teacher/awards/update-candidate' => 'AttendanceController@apiUpdateAwardCandidate',
        '/api/teacher/awards/seed-sample'      => 'AttendanceController@apiSeedAwardsSample',

        // Teachers Master & Bulk Import API
        '/api/teachers'                 => 'TeacherController@handleRoot',
        '/api/teachers/create'          => 'TeacherController@apiCreate',
        '/api/teachers/update'          => 'TeacherController@apiUpdate',
        '/api/teachers/delete'          => 'TeacherController@apiDelete',
        '/api/teachers/reset-password'  => 'TeacherController@apiResetPassword',
        '/api/teachers/import'          => 'TeacherController@apiImport',
        '/api/teachers/export'          => 'TeacherController@apiExport',

        // System Settings API
        '/api/settings'                 => 'SettingsController@apiIndex',
        '/api/settings/save'            => 'SettingsController@apiSave',

        // User Profile & Personal Preferences API
        '/api/user/profile'             => 'UserController@apiProfile',
        '/api/user/profile/update'      => 'UserController@apiUpdateProfile',
        '/api/user/password/update'     => 'UserController@apiUpdatePassword',
        '/api/user/preferences'         => 'SettingsController@apiUserPreferences',
        '/api/user/preferences/save'    => 'SettingsController@apiSaveUserPreferences',

        // Overview Dashboard API
        '/api/dashboard/overview'       => 'DashboardController@apiOverview',

        // Machine Learning & Analytics API
        '/api/analytics/all'            => 'AnalyticsController@apiAll',
        '/api/analytics/overview'       => 'AnalyticsController@apiOverview',
        '/api/analytics/patterns'       => 'AnalyticsController@apiPatterns',
        '/api/analytics/preview-pattern'=> 'AnalyticsController@apiPreviewPattern',
        '/api/analytics/at-risk'        => 'AnalyticsController@apiAtRisk',
        '/api/analytics/retrain'        => 'AnalyticsController@apiRetrain',
        '/api/analytics/intervene'      => 'AnalyticsController@apiIntervene',
        '/api/analytics/apply-pattern-action' => 'AnalyticsController@apiApplyPatternAction',
        '/api/analytics/seed-demo-attendance' => 'AnalyticsController@apiSeedDemoAttendance',

        // Alerts
        '/alerts'                => 'alerts/index.php',
        '/alerts/history'        => 'alerts/history.php',
        '/alerts/settings'       => 'settings/index.php',

        // Settings
        '/settings'              => 'settings/index.php',
        '/personal-settings'     => 'settings/personal.php',

        // Awards & Exports
        '/awards'                => 'awards/index.php',
        '/exports'               => 'admin/reports.php',
        '/export'                => 'admin/reports.php',
        '/reports'               => 'admin/reports.php',

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
     * Check if a given path is public (no active session required)
     */
    public static function isPublicRoute(string $path): bool {
        $publicExact = [
            '/',
            '/auth',
            '/auth/login',
            '/login',
            '/logout',
            '/auth/logout',
            '/api/auth/login',
            '/api/auth/verify-otp',
            '/api/auth/resend-otp',
            '/api/auth/check-remembered',
            '/api/auth/forgot-password',
            '/api/auth/verify-reset-otp',
            '/api/auth/reset-password',
            '/api/auth/logout',
            '/api/auth/me',
            '/healthcheck',
            '/mailcheck',
            '/403',
            '/404',
            '/500',
        ];

        if (in_array($path, $publicExact, true)) {
            return true;
        }

        if (str_starts_with($path, '/api/auth/')) {
            return true;
        }

        return false;
    }

    /**
     * Enforce authentication on all protected routes and API endpoints.
     * Redirects unauthenticated guests to login.
     */
    public static function enforceAuth(string $path): void {
        if (self::isPublicRoute($path)) {
            return;
        }

        startSessionSafely();
        $hasSession = !empty($_SESSION['user_id']) && !empty($_SESSION['user']);

        // Check if device is remembered via trusted cookie (if user did not explicitly log out)
        if (!$hasSession && !empty($_COOKIE['ams_remember_token']) && empty($_GET['logged_out'])) {
            try {
                require_once dirname(__DIR__) . '/core/Database.php';
                $db = Database::getConnection();
                $token = $_COOKIE['ams_remember_token'];
                $remStmt = $db->prepare("
                    SELECT * FROM users 
                    WHERE remember_token = :token 
                      AND remember_expires_at > NOW() 
                      AND status = 'active' 
                    LIMIT 1
                ");
                $remStmt->execute([':token' => $token]);
                $rememberedUser = $remStmt->fetch(PDO::FETCH_ASSOC);

                if ($rememberedUser) {
                    $_SESSION['user_id'] = (int)$rememberedUser['user_id'];
                    $_SESSION['role']    = $rememberedUser['role'];
                    $_SESSION['user']    = [
                        'user_id'     => (int)$rememberedUser['user_id'],
                        'student_id'  => $rememberedUser['student_id'] ?? null,
                        'employee_id' => $rememberedUser['employee_id'] ?? null,
                        'first_name'  => $rememberedUser['first_name'],
                        'last_name'   => $rememberedUser['last_name'],
                        'full_name'   => trim("{$rememberedUser['first_name']} {$rememberedUser['last_name']}"),
                        'email'       => $rememberedUser['email'],
                        'role'        => $rememberedUser['role'],
                        'avatar_path' => $rememberedUser['avatar_path'] ?? null,
                    ];

                    if ($rememberedUser['role'] === 'teacher') {
                        $_SESSION['teacher_id'] = (int)$rememberedUser['user_id'];
                        unset($_SESSION['student_id']);
                    } elseif ($rememberedUser['role'] === 'student') {
                        $_SESSION['student_id'] = (int)$rememberedUser['user_id'];
                        unset($_SESSION['teacher_id']);
                    } else {
                        unset($_SESSION['teacher_id'], $_SESSION['student_id']);
                    }

                    $hasSession = true;
                }
            } catch (Throwable $e) {}
        }

        if (!$hasSession) {
            // Unauthenticated request
            if (str_starts_with($path, '/api/')) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
                echo json_encode([
                    'status'        => 'error',
                    'authenticated' => false,
                    'message'       => 'Unauthorized. Your session has expired or you are not signed in.',
                    'redirect_url'  => url('login?session_expired=1')
                ]);
                exit;
            }

            header('Location: ' . url('login?session_expired=1'));
            exit;
        }

        // Role-Based Access Control (RBAC)
        $userRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'student');

        if (str_starts_with($path, '/teacher') && $userRole === 'student') {
            header('Location: ' . url('student/dashboard'));
            exit;
        }

        if (str_starts_with($path, '/student') && $userRole === 'teacher') {
            header('Location: ' . url('teacher/dashboard'));
            exit;
        }

        if ((str_starts_with($path, '/admin') || $path === '/settings') && $userRole !== 'admin') {
            $dest = ($userRole === 'teacher') ? url('teacher/dashboard') : url('student/dashboard');
            header('Location: ' . $dest);
            exit;
        }
    }

    /**
     * Synchronizes session role and user persona for the active role (Admin Switcher only)
     */
    public static function syncSessionUserForRole(string $role): void {
        startSessionSafely();
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'admin' && ($_SESSION['role'] ?? '') !== 'admin')) {
            return; // Only active administrators can switch personas
        }

        $_SESSION['role'] = $role;
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
            }
        } catch (Throwable $e) {}
    }

    /**
     * Get the currently active role ('admin', 'teacher', 'student')
     */
    public static function getCurrentRole(): string {
        startSessionSafely();

        // Administrator quick switch
        if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin') {
            if (isset($_GET['switch_role']) && in_array($_GET['switch_role'], ['admin', 'teacher', 'student'], true)) {
                self::syncSessionUserForRole($_GET['switch_role']);
                return $_SESSION['role'];
            }
        }

        $role = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '');
        if (!empty($role) && in_array($role, ['admin', 'teacher', 'student'], true)) {
            return $role;
        }

        $path = trim(self::getCurrentPath(), '/');
        if (str_starts_with($path, 'teacher')) {
            return 'teacher';
        }
        if (str_starts_with($path, 'student')) {
            return 'student';
        }
        return 'admin';
    }

    /**
     * Dispatch the current request
     */
    public static function dispatch(): void {
        $path = self::getCurrentPath();

        // Temporary health-check endpoint for debugging Railway deployment
        if ($path === '/healthcheck') {
            header('Content-Type: application/json; charset=utf-8');
            $result = [
                'php_version' => PHP_VERSION,
                'sapi' => php_sapi_name(),
                'extensions' => [
                    'pdo' => extension_loaded('pdo'),
                    'pdo_mysql' => extension_loaded('pdo_mysql'),
                    'mbstring' => extension_loaded('mbstring'),
                    'openssl' => extension_loaded('openssl'),
                ],
                'env_vars' => [
                    'DB_HOST' => !empty(getenv('DB_HOST')) ? getenv('DB_HOST') : '(not set)',
                    'DB_PORT' => !empty(getenv('DB_PORT')) ? getenv('DB_PORT') : '(not set)',
                    'DB_NAME' => !empty(getenv('DB_NAME')) ? getenv('DB_NAME') : '(not set)',
                    'DB_USER' => !empty(getenv('DB_USER')) ? '***set***' : '(not set)',
                    'DB_PASS' => !empty(getenv('DB_PASS')) ? '***set***' : '(not set)',
                ],
                'db_connection' => 'untested',
            ];
            try {
                require_once dirname(__DIR__) . '/core/Database.php';
                $db = Database::getConnection();
                $ver = $db->getAttribute(PDO::ATTR_SERVER_VERSION);
                $result['db_connection'] = 'OK (MySQL ' . $ver . ')';
                $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                $result['tables'] = $tables;
            } catch (Throwable $e) {
                $result['db_connection'] = 'FAILED: ' . $e->getMessage();
            }
            echo json_encode($result, JSON_PRETTY_PRINT);
            exit;
        }

        // Live Mail & SMTP diagnostic endpoint for Railway
        if ($path === '/mailcheck') {
            header('Content-Type: application/json; charset=utf-8');
            require_once dirname(__DIR__) . '/core/Mailer.php';
            
            $testTo = $_GET['to'] ?? Mailer::getEnv('Email') ?: 'managementattendance6@gmail.com';
            $smtpUser = Mailer::getEnv('Email') ?: Mailer::getEnv('SMTP_USER', '(not set)');
            $hasPass  = !empty(Mailer::getEnv('APP_PASSWORD') ?: Mailer::getEnv('SMTP_PASS', ''));

            $res = Mailer::sendOtp($testTo, '999888', 'login', 'Railway Diagnostic');
            
            echo json_encode([
                'timestamp'        => date('Y-m-d H:i:s T'),
                'php_version'      => PHP_VERSION,
                'openssl_loaded'   => extension_loaded('openssl'),
                'smtp_user_set'    => $smtpUser,
                'smtp_pass_set'    => $hasPass,
                'target_recipient' => $testTo,
                'delivery_result'  => $res,
            ], JSON_PRETTY_PRINT);
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

        // Enforce Authentication and RBAC on all protected routes
        self::enforceAuth($path);

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
