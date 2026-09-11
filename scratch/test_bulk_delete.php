<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

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

echo "=== BULK DELETE UNIT & INTEGRATION TESTS ===\n\n";

// 1. Create 3 test excuse slips
$insertStmt = $db->prepare("
    INSERT INTO excuse_slips (
        student_id, teacher_id, subject, date_of_absence, reason, explanation, status, created_at, updated_at
    ) VALUES (1, 2, 'IT301 — Bulk Test Subject', CURDATE(), 'Other Valid Reason', 'Testing bulk delete functionality', 'pending', NOW(), NOW())
");

$testSlipIds = [];
for ($i = 0; $i < 3; $i++) {
    $insertStmt->execute();
    $testSlipIds[] = (int)$db->lastInsertId();
}

echo "Created test excuse slips: " . implode(', ', $testSlipIds) . "\n";

// Test 1: Bulk delete 2 slips via API call
runTest("Bulk Delete 2 Slips via /api/excuses/bulk-delete", function() use ($testSlipIds, $db) {
    $idsToDelete = [$testSlipIds[0], $testSlipIds[1]];
    
    $ch = curl_init('http://localhost:8000/api/excuses/bulk-delete');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'excuse_slip_ids' => $idsToDelete,
        'student_id'      => 1
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode !== 200 || ($json['status'] ?? '') !== 'success' || ($json['deleted_count'] ?? 0) !== 2) {
        return "API call failed: HTTP $httpCode - Response: $response";
    }

    // Check DB
    $check = $db->prepare("SELECT COUNT(*) FROM excuse_slips WHERE excuse_slip_id IN (?, ?)");
    $check->execute($idsToDelete);
    $count = (int)$check->fetchColumn();
    if ($count !== 0) {
        return "Records still exist in DB!";
    }

    return true;
});

// Test 2: Bulk delete remaining slip
runTest("Bulk Delete Remaining Slip", function() use ($testSlipIds, $db) {
    $idsToDelete = [$testSlipIds[2]];
    
    $ch = curl_init('http://localhost:8000/api/excuses/bulk-delete');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'excuse_slip_ids' => $idsToDelete,
        'student_id'      => 1
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode !== 200 || ($json['status'] ?? '') !== 'success' || ($json['deleted_count'] ?? 0) !== 1) {
        return "API call failed: HTTP $httpCode - Response: $response";
    }
    return true;
});

// Test 3: Empty IDs rejection
runTest("Empty IDs Rejection (HTTP 400)", function() {
    $ch = curl_init('http://localhost:8000/api/excuses/bulk-delete');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'excuse_slip_ids' => [],
        'student_id'      => 1
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 400) {
        return "Expected 400, got $httpCode";
    }
    return true;
});

// Test 4: Frontend HTML UI elements presence
runTest("Frontend HTML UI Bulk Delete Elements", function() {
    $html = file_get_contents('http://localhost:8000/student/excuse-slips');
    if (strpos($html, 'id="bulk-action-bar"') === false) {
        return "Missing id='bulk-action-bar'";
    }
    if (strpos($html, 'id="select-all-checkbox"') === false) {
        return "Missing id='select-all-checkbox'";
    }
    if (strpos($html, 'id="btn-bulk-delete"') === false) {
        return "Missing id='btn-bulk-delete'";
    }
    if (strpos($html, 'openBulkDeleteModal') === false) {
        return "Missing openBulkDeleteModal JS function";
    }
    return true;
});

echo "\nAll Bulk Delete unit and integration tests completed!\n";
