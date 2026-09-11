<?php
$html = file_get_contents('http://localhost:8000/student/excuse-slips');

if (strpos($html, 'You are not enrolled in any class rosters yet') !== false) {
    echo "❌ BUG FOUND: Page still says 'You are not enrolled in any class rosters yet'\n";
} else {
    echo "✅ SUCCESS: No 'not enrolled' warning found!\n";
}

if (strpos($html, 'IT301 — Web Systems and Technologies (Prof. Ramirez)') !== false) {
    echo "✅ SUCCESS: IT301 (Prof. Ramirez) found in subject dropdown!\n";
} else {
    echo "❌ IT301 subject missing\n";
}

if (strpos($html, 'IT303 — Systems Integration and Architecture (Prof. Santos)') !== false) {
    echo "✅ SUCCESS: IT303 (Prof. Santos) found in subject dropdown!\n";
} else {
    echo "❌ IT303 subject missing\n";
}

if (strpos($html, '★ All Subject Teachers (All 2 Enrolled Classes)') !== false) {
    echo "✅ SUCCESS: 'All Subject Teachers (All 2 Enrolled Classes)' option is active!\n";
} else {
    echo "❌ All Subject Teachers option missing\n";
}

if (strpos($html, 'Prof. Ramirez') !== false && strpos($html, 'Prof. Santos') !== false) {
    echo "✅ SUCCESS: Both Prof. Ramirez & Prof. Santos pills rendered!\n";
} else {
    echo "❌ Faculty pills missing\n";
}
