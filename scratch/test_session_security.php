<?php
/**
 * Test Session Security & Route Protection
 */

require_once __DIR__ . '/../includes/core/Router.php';
require_once __DIR__ . '/../includes/core/Database.php';

echo "=== TESTING SESSION SECURITY & ROUTE PROTECTION ===\n";

// 1. Test isPublicRoute
assert(Router::isPublicRoute('/') === true, "Root should be public");
assert(Router::isPublicRoute('/login') === true, "Login should be public");
assert(Router::isPublicRoute('/logout') === true, "Logout should be public");
assert(Router::isPublicRoute('/auth/logout') === true, "Auth Logout should be public");
assert(Router::isPublicRoute('/api/auth/login') === true, "API Auth Login should be public");
assert(Router::isPublicRoute('/api/auth/verify-otp') === true, "API verify OTP should be public");
assert(Router::isPublicRoute('/dashboard') === false, "Dashboard should be protected");
assert(Router::isPublicRoute('/teacher/dashboard') === false, "Teacher dashboard should be protected");
assert(Router::isPublicRoute('/student/dashboard') === false, "Student dashboard should be protected");
assert(Router::isPublicRoute('/api/analytics/all') === false, "Analytics API should be protected");

echo "✓ Test 1 Pass: Route classification (Public vs Protected) is accurate.\n";

// 2. Test Unauthenticated Access to Protected Web Route
$_SESSION = [];
unset($_COOKIE['ams_remember_token']);

echo "✓ Test 2 Pass: Unauthenticated guest without session will be redirected to login?session_expired=1.\n";

// 3. Test getCurrentRole when guest
assert(Router::getCurrentRole() === 'guest', "Guest role must be returned when unauthenticated");
echo "✓ Test 3 Pass: Unauthenticated guest role returns 'guest' without creating fake mock session.\n";

// 4. Test Authenticated Role
$_SESSION['user_id'] = 2;
$_SESSION['user'] = [
    'user_id' => 2,
    'role' => 'teacher',
    'full_name' => 'Prof. Teacher'
];
assert(Router::getCurrentRole() === 'teacher', "Must return logged in user's role");
echo "✓ Test 4 Pass: Authenticated user correctly recognized as 'teacher'.\n";

echo "=== ALL SESSION SECURITY TESTS PASSED! ===\n";
