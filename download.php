<?php

require __DIR__ . '/includes/init.php';
require_login();

$type = isset($_GET['type']) ? (string) $_GET['type'] : '';
if (!in_array($type, ['csv', 'pdf'], true)) {
    http_response_code(400);
    echo 'Choose a CSV or PDF report.';
    exit;
}

list($from, $to, $error) = parse_date_range(
    isset($_GET['from']) ? (string) $_GET['from'] : '',
    isset($_GET['to']) ? (string) $_GET['to'] : ''
);
if ($error) {
    http_response_code(400);
    echo $error;
    exit;
}

$isAdmin = current_user()['role'] === 'admin';
$includePatient = $isAdmin && (isset($_GET['scope']) && $_GET['scope'] === 'all');
$userId = (int) current_user()['id'];
$who = current_user()['full_name'];

if (!$includePatient && $isAdmin) {
    http_response_code(403);
    echo 'Open reports from the staff area.';
    exit;
}

if ($includePatient) {
    $userId = null;
    $who = 'All patients';
    if (isset($_GET['patient_id']) && $_GET['patient_id'] !== '' && $_GET['patient_id'] !== 'all') {
        $patientId = (int) $_GET['patient_id'];
        $stmt = db()->prepare('SELECT id, full_name FROM users WHERE id = ? AND role = \'patient\' LIMIT 1');
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        if (!$patient) {
            http_response_code(400);
            echo 'Choose a valid patient.';
            exit;
        }
        $userId = (int) $patient['id'];
        $who = $patient['full_name'];
    }
}

$rows = build_occurrences($from, $to, $userId);
$summary = summarize_rows($rows);
$filename = 'medication-report-' . $from . '-to-' . $to . '.' . $type;

if ($type === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');
    echo build_report_csv($rows, $includePatient);
    exit;
}

$lines = [
    'Patient: ' . $who,
    'Period: ' . $from . ' to ' . $to,
    'Generated: ' . date('j M Y, g:i A'),
    'Taken ' . $summary['taken'] . ', skipped ' . $summary['skipped'] . ', missed ' . $summary['missed'] . ', due ' . $summary['due'] . ', scheduled ' . $summary['scheduled'],
];
if ($summary['adherence'] !== null) {
    $lines[] = 'Adherence for elapsed doses: ' . $summary['adherence'] . '%';
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');
echo build_report_pdf($lines, $rows, $includePatient);
