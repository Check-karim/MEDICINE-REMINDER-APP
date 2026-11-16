<?php

require __DIR__ . '/includes/init.php';
require_patient();

$report = load_report(false);
$report['action'] = 'reports.php';
$report['intro'] = 'Download your medication schedule and activity as a PDF or a CSV file. The file uses the dates selected below.';

app_open('Reports', 'more');
render_report_screen($report);
app_close('more');
