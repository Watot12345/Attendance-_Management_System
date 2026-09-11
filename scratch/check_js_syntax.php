<?php
$content = file_get_contents('includes/views/teacher/live-session.php');
// Extract the main script block
if (preg_match_all('/<script>(.*?)<\/script>/s', $content, $matches)) {
    foreach ($matches[1] as $idx => $js) {
        file_put_contents("scratch/extracted_$idx.js", $js);
        echo "Extracted script $idx (length: " . strlen($js) . ")\n";
    }
}
