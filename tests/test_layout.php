<?php
$html = file_get_contents('http://127.0.0.1:8000/exports?refresh=1');
if (!$html) {
    echo "Could not fetch URL\n";
    exit(1);
}

$posFilter = strpos($html, 'id="filter-search"');
$posKpi = strpos($html, 'id="kpi-total-students"');
$posCharts = strpos($html, 'id="chartSectionRates"');
$posTable = strpos($html, 'id="roster-attendance-table"');

echo "Filter position: $posFilter\n";
echo "KPI position: $posKpi\n";
echo "Charts position: $posCharts\n";
echo "Table position: $posTable\n";

if ($posFilter !== false && $posKpi !== false && $posCharts !== false && $posTable !== false) {
    if ($posFilter < $posKpi && $posKpi < $posCharts && $posCharts < $posTable) {
        echo "VERIFICATION PASSED: Filters are placed at the TOP (Filters -> KPIs -> Charts -> Table)\n";
    } else {
        echo "VERIFICATION FAILED: Order is incorrect\n";
    }
} else {
    echo "VERIFICATION FAILED: Missing element IDs\n";
}
