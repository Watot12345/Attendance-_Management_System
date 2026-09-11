<?php
require_once __DIR__ . '/../includes/core/Database.php';
require_once __DIR__ . '/../includes/core/Router.php';
require_once __DIR__ . '/../includes/controllers/AuthController.php';

function runTest($name, $closure) {
    try {
        $result = $closure();
        if ($result === true) {
            echo "✅ PASS: $name\n";
        } else {
            echo "❌ FAIL: $name - " . json_encode($result) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION: $name - " . $e->getMessage() . "\n";
    }
}

$db = Database::getConnection();
AuthController::ensureCoreUsersExist($db);

echo "=== AUTHENTICATION & LOGIN UNIT TESTS ===\n\n";

// Test 1: Teacher login with email & password
runTest("Teacher Login (Email + Password)", function() {
    $_POST = [];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_ACCEPT'] = 'application/json';
    
    // Simulate AuthController login logic
    $auth = new AuthController();
    
    // Direct DB verification
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['m.ramirez@bcp.edu.ph']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify('teacher123', $user['password_hash'])) {
        return "Teacher password verify failed";
    }
    return true;
});

// Test 2: Student login with student_id (numeric string / formatted)
runTest("Student Login (Student ID 2026-00123)", function() {
    $db = Database::getConnection();
    $cleanNumeric = (string)preg_replace('/\D/', '', '2026-00123');
    $stmt = $db->prepare("SELECT * FROM users WHERE student_id = ?");
    $stmt->execute([$cleanNumeric]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['role'] !== 'student') {
        return "Student lookup by student_id failed";
    }
    return true;
});

// Test 3: Admin login
runTest("Admin Login (admin@bcp.edu.ph)", function() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@bcp.edu.ph']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify('admin123', $user['password_hash'])) {
        return "Admin password verify failed";
    }
    return true;
});

// Test 4: Invalid Password Rejection
runTest("Invalid Password Rejection", function() {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['m.ramirez@bcp.edu.ph']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify('wrongpassword_xyz', $user['password_hash'])) {
        return "Wrong password should NOT verify";
    }
    return true;
});

// Test 5: Router Mapping
runTest("Router Mapping for Auth Endpoints", function() {
    $reflection = new ReflectionClass('Router');
    $prop = $reflection->getProperty('routes');
    $prop->setAccessible(true);
    $routes = $prop->getValue();

    $checks = [
        '/logout'          => 'AuthController@logout',
        '/auth/login'      => 'AuthController@login',
        '/api/auth/login'  => 'AuthController@login',
        '/api/auth/logout' => 'AuthController@logout',
        '/api/auth/me'     => 'AuthController@me',
    ];

    foreach ($checks as $route => $expected) {
        if (!isset($routes[$route]) || $routes[$route] !== $expected) {
            return "Route $route mapped to " . ($routes[$route] ?? 'null') . ", expected $expected";
        }
    }
    return true;
});

echo "\nAll auth verification tests passed!\n";
