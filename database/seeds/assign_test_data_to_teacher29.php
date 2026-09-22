<?php
/**
 * Assign / Reassign test data to Teacher ID #29 (m.ramirez@bcp.edu.ph)
 */
require_once __DIR__ . '/../../includes/core/Database.php';

$db = Database::getConnection();

echo "=== ASSIGNING TEST DATA TO TEACHER #29 ===\n";

$sourceTeacherId = 2;
$targetTeacherId = 29;

// Verify target teacher exists
$targetUser = $db->query("SELECT user_id, first_name, last_name, email FROM users WHERE user_id = $targetTeacherId")->fetch(PDO::FETCH_ASSOC);
if (!$targetUser) {
    echo "Teacher #$targetTeacherId not found!\n";
    exit(1);
}
echo "Target Teacher: #{$targetUser['user_id']} - {$targetUser['first_name']} {$targetUser['last_name']} ({$targetUser['email']})\n";

$db->beginTransaction();

// 1. Reassign class_roster from Teacher 2 to Teacher 29
$rosterUpdated = $db->exec("UPDATE class_roster SET teacher_id = $targetTeacherId WHERE teacher_id = $sourceTeacherId");
echo "Roster entries assigned to Teacher #$targetTeacherId: $rosterUpdated\n";

// 2. Reassign attendance records from Teacher 2 to Teacher 29
$attUpdated = $db->exec("UPDATE attendance SET teacher_id = $targetTeacherId WHERE teacher_id = $sourceTeacherId");
echo "Attendance records assigned to Teacher #$targetTeacherId: $attUpdated\n";

// 3. Add fresh audit logs for Teacher 29
$auditLogs = [
    ['action' => 'update', 'desc' => 'Manual override by Teacher #29 for student Juan Dela Cruz (#2026-00001): replaced absent with manual entry (new_status: present, time: 08:15:00). Reason: Student presented clinic pass after medical consultation.'],
    ['action' => 'update', 'desc' => 'Manual override by Teacher #29 for student Maria Santos (#2026-00002): replaced absent with manual entry (new_status: tardy, time: 08:52:00). Reason: Late arrival due to public transport LRT-1 signal disruption.'],
    ['action' => 'update', 'desc' => 'Manual override by Teacher #29 for student Carlo Mendoza (#2026-00003): replaced tardy with manual entry (new_status: present, time: 08:10:00). Reason: Kiosk camera hardware scanner timeout; verified physically in classroom.'],
    ['action' => 'update', 'desc' => 'Manual override by Teacher #29 for student Bea Alonzo (#2026-00004): replaced absent with manual entry (new_status: present, time: 08:05:00). Reason: Student forgot physical QR ID badge; verified student handbook photo.'],
    ['action' => 'update', 'desc' => 'Manual override by Teacher #29 for student Gabriel Fernandez (#2026-00005): replaced absent with manual entry (new_status: present, time: 08:00:00). Reason: Approved athletic training excusal approved by Academic Head.']
];

$insAudit = $db->prepare("INSERT INTO audit_logs (user_id, action, description, reference_type, reference_id, created_at) VALUES (?, ?, ?, 'attendance', 1, NOW())");
foreach ($auditLogs as $al) {
    $insAudit->execute([$targetTeacherId, $al['action'], $al['desc']]);
}

$db->commit();

// 4. Clear cache files for teacher 29
$cacheDir = dirname(__DIR__, 2) . '/storage/cache';
if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '/*29*.cache') as $cf) {
        @unlink($cf);
    }
}

// 5. Final verification
$finalRoster = $db->query("SELECT COUNT(*) FROM class_roster WHERE teacher_id = $targetTeacherId")->fetchColumn();
$finalAtt = $db->query("SELECT COUNT(*) FROM attendance WHERE teacher_id = $targetTeacherId")->fetchColumn();
$finalSecs = $db->query("SELECT DISTINCT section FROM class_roster WHERE teacher_id = $targetTeacherId")->fetchAll(PDO::FETCH_COLUMN);

echo "\n=== COMPLETED SUCCESSFULLY! ===\n";
echo "Teacher #$targetTeacherId Rostered Students : $finalRoster\n";
echo "Teacher #$targetTeacherId Assigned Sections: " . implode(', ', $finalSecs) . "\n";
echo "Teacher #$targetTeacherId Attendance Records: $finalAtt records\n";
echo "Cache files cleared for Teacher #$targetTeacherId.\n";
