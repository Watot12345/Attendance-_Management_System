<?php

function testLogin($identifier, $password) {
    $url = 'http://localhost:8000/api/auth/login';
    $data = json_encode(['identifier' => $identifier, 'password' => $password]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Testing $identifier ($password):\n";
    echo "HTTP $httpCode\n";
    echo "Response: $response\n\n";
}

testLogin('m.ramirez@bcp.edu.ph', 'teacher123');
testLogin('juan.delacruz@bcp.edu.ph', 'student123');
testLogin('2026-00123', 'student123');
testLogin('admin@bcp.edu.ph', 'admin123');
testLogin('wrong@bcp.edu.ph', 'invalidpass');
