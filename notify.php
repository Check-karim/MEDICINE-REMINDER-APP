<?php

require __DIR__ . '/includes/init.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (!current_user() || current_user()['role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['due' => []]);
    exit;
}

$today = date('Y-m-d');
$rows = build_occurrences($today, $today, (int) current_user()['id']);
$due = [];
foreach ($rows as $row) {
    if ($row['status'] !== 'due') {
        continue;
    }
    $due[] = [
        'id' => (int) $row['dose_time_id'],
        'date' => $row['date'],
        'name' => $row['name'],
        'dosage' => $row['dosage'],
        'quantity' => $row['quantity'],
        'time' => format_clock($row['time'])['label'],
    ];
}

echo json_encode(['due' => $due]);
