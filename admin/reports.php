<?php

require dirname(__DIR__) . '/includes/init.php';
require_admin();

$report = load_report(true);
$report['action'] = 'admin/reports.php';
$report['intro'] = 'Download medication schedules and activity for every patient, or for one patient. Files are available as PDF and CSV.';

app_open('Reports', 'reports');
render_report_screen($report);
app_close('reports');
