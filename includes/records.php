<?php

if (!defined('MEDAPP')) {
    http_response_code(403);
    exit;
}

function user_by_id($id)
{
    $stmt = db()->prepare('SELECT id, full_name, username, email, phone, role, created_at FROM users WHERE id = ?');
    $stmt->execute([(int) $id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function user_by_username($username)
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function value_taken($field, $value, $exceptId = 0)
{
    $allowed = ['username', 'email', 'phone'];
    if (!in_array($field, $allowed, true)) {
        throw new InvalidArgumentException('Unknown user field.');
    }
    $stmt = db()->prepare("SELECT id FROM users WHERE {$field} = ? AND id <> ? LIMIT 1");
    $stmt->execute([$value, (int) $exceptId]);
    return (bool) $stmt->fetch();
}

function medicines_for_user($userId)
{
    $stmt = db()->prepare('SELECT * FROM medicines WHERE user_id = ? ORDER BY is_active DESC, name ASC');
    $stmt->execute([(int) $userId]);
    $medicines = $stmt->fetchAll();
    return attach_doses($medicines);
}

function medicine_for_user($medicineId, $userId)
{
    $stmt = db()->prepare('SELECT * FROM medicines WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([(int) $medicineId, (int) $userId]);
    $medicine = $stmt->fetch();
    if (!$medicine) {
        return null;
    }
    $medicine['doses'] = doses_for_medicine($medicine['id']);
    return $medicine;
}

function doses_for_medicine($medicineId)
{
    $stmt = db()->prepare('SELECT * FROM dose_times WHERE medicine_id = ? ORDER BY dose_time ASC');
    $stmt->execute([(int) $medicineId]);
    return $stmt->fetchAll();
}

function attach_doses(array $medicines)
{
    if (!$medicines) {
        return [];
    }
    $ids = [];
    foreach ($medicines as $medicine) {
        $ids[] = (int) $medicine['id'];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM dose_times WHERE medicine_id IN ({$placeholders}) ORDER BY dose_time ASC");
    $stmt->execute($ids);
    $grouped = [];
    foreach ($stmt->fetchAll() as $dose) {
        $grouped[$dose['medicine_id']][] = $dose;
    }
    foreach ($medicines as $index => $medicine) {
        $medicines[$index]['doses'] = isset($grouped[$medicine['id']]) ? $grouped[$medicine['id']] : [];
    }
    return $medicines;
}

function all_medicines()
{
    $rows = db()->query(
        'SELECT m.*, u.full_name AS patient, u.username
         FROM medicines m
         INNER JOIN users u ON u.id = m.user_id
         ORDER BY m.is_active DESC, u.full_name ASC, m.name ASC'
    )->fetchAll();
    return attach_doses($rows);
}

function patients($search = '')
{
    $sql = 'SELECT u.*,
                (SELECT COUNT(*) FROM medicines m WHERE m.user_id = u.id) AS medicine_count
            FROM users u
            WHERE u.role = \'patient\'';
    $params = [];
    $search = trim($search);
    if ($search !== '') {
        $like = '%' . like_escape($search) . '%';
        $sql .= ' AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        $params = [$like, $like, $like, $like];
    }
    $sql .= ' ORDER BY u.full_name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function like_escape($value)
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

function delete_patient($userId)
{
    $stmt = db()->prepare('DELETE FROM users WHERE id = ? AND role = \'patient\'');
    $stmt->execute([(int) $userId]);
    return $stmt->rowCount() > 0;
}

function schedules($userId = null)
{
    $sql = 'SELECT m.id AS medicine_id, m.user_id, m.name, m.dosage, m.form, m.instructions,
                   m.start_date, m.end_date, m.is_active,
                   d.id AS dose_time_id, d.dose_time, d.quantity, d.days,
                   u.full_name AS patient, u.username
            FROM medicines m
            INNER JOIN dose_times d ON d.medicine_id = m.id
            INNER JOIN users u ON u.id = m.user_id';
    $params = [];
    if ($userId !== null) {
        $sql .= ' WHERE m.user_id = ?';
        $params[] = (int) $userId;
    }
    $sql .= ' ORDER BY d.dose_time ASC, m.name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function log_index($from, $to, $userId = null)
{
    $sql = 'SELECT dose_time_id, log_date, status, logged_at
            FROM dose_logs
            WHERE log_date BETWEEN ? AND ?';
    $params = [$from, $to];
    if ($userId !== null) {
        $sql .= ' AND user_id = ?';
        $params[] = (int) $userId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[$row['dose_time_id'] . '|' . $row['log_date']] = $row;
    }
    return $map;
}

function schedule_applies(array $row, $date, $today)
{
    if ($row['start_date'] > $date) {
        return false;
    }
    if (!empty($row['end_date']) && $row['end_date'] < $date) {
        return false;
    }
    if ((int) $row['is_active'] !== 1 && $date >= $today) {
        return false;
    }
    $days = str_pad((string) $row['days'], 7, '0');
    $index = (int) date('N', strtotime($date . ' 12:00:00')) - 1;
    return isset($days[$index]) && $days[$index] === '1';
}

function occurrence_status($date, $time, $log, $now)
{
    if ($log) {
        return $log['status'];
    }
    $stamp = strtotime($date . ' ' . $time);
    if ($stamp === false) {
        return 'missed';
    }
    if ($stamp > $now) {
        return 'scheduled';
    }
    $grace = DUE_GRACE_MINUTES * 60;
    if (($now - $stamp) <= $grace) {
        return 'due';
    }
    return 'missed';
}

function build_occurrences($from, $to, $userId = null, $order = 'asc')
{
    list($from, $to, $error) = parse_date_range($from, $to);
    if ($error) {
        return [];
    }

    $today = date('Y-m-d');
    $now = time();
    $logs = log_index($from, $to, $userId);
    $rows = [];

    $cursor = DateTime::createFromFormat('Y-m-d', $from);
    $end = DateTime::createFromFormat('Y-m-d', $to);
    $cursor->setTime(0, 0, 0);
    $end->setTime(0, 0, 0);

    $items = schedules($userId);
    for ($day = clone $cursor; $day <= $end; $day->modify('+1 day')) {
        $date = $day->format('Y-m-d');
        foreach ($items as $item) {
            if (!schedule_applies($item, $date, $today)) {
                continue;
            }
            $key = $item['dose_time_id'] . '|' . $date;
            $log = isset($logs[$key]) ? $logs[$key] : null;
            $rows[] = [
                'date' => $date,
                'time' => $item['dose_time'],
                'medicine_id' => (int) $item['medicine_id'],
                'dose_time_id' => (int) $item['dose_time_id'],
                'user_id' => (int) $item['user_id'],
                'patient' => $item['patient'],
                'username' => $item['username'],
                'name' => $item['name'],
                'dosage' => $item['dosage'],
                'form' => $item['form'],
                'quantity' => $item['quantity'],
                'instructions' => $item['instructions'],
                'status' => occurrence_status($date, $item['dose_time'], $log, $now),
                'logged_at' => $log ? $log['logged_at'] : null,
            ];
        }
    }

    usort($rows, function ($a, $b) use ($order) {
        $left = [$a['date'], $a['time'], $a['patient'], $a['name']];
        $right = [$b['date'], $b['time'], $b['patient'], $b['name']];
        $result = $left <=> $right;
        return $order === 'desc' ? -$result : $result;
    });

    return $rows;
}

function summarize_rows(array $rows)
{
    $summary = [
        'taken' => 0,
        'skipped' => 0,
        'missed' => 0,
        'due' => 0,
        'scheduled' => 0,
        'total' => count($rows),
        'elapsed' => 0,
        'adherence' => null,
    ];
    foreach ($rows as $row) {
        if (isset($summary[$row['status']])) {
            $summary[$row['status']]++;
        }
    }
    $summary['elapsed'] = $summary['taken'] + $summary['skipped'] + $summary['missed'];
    if ($summary['elapsed'] > 0) {
        $summary['adherence'] = (int) round(($summary['taken'] / $summary['elapsed']) * 100);
    }
    return $summary;
}

function mark_dose($userId, $doseTimeId, $date, $status)
{
    if (!in_array($status, ['taken', 'skipped'], true) || !valid_date($date) || $date > date('Y-m-d')) {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT d.id, d.dose_time, d.days, m.id AS medicine_id, m.name, m.dosage, m.start_date, m.end_date, m.is_active
         FROM dose_times d
         INNER JOIN medicines m ON m.id = d.medicine_id
         WHERE d.id = ? AND m.user_id = ?
         LIMIT 1'
    );
    $stmt->execute([(int) $doseTimeId, (int) $userId]);
    $dose = $stmt->fetch();
    if (!$dose) {
        return false;
    }

    $today = date('Y-m-d');
    $row = [
        'start_date' => $dose['start_date'],
        'end_date' => $dose['end_date'],
        'is_active' => $dose['is_active'],
        'days' => $dose['days'],
    ];
    if (!schedule_applies($row, $date, $today) && !($date < $today && schedule_applies($row, $date, '9999-12-31'))) {
        return false;
    }
    if ($date < $dose['start_date']) {
        return false;
    }
    if (!empty($dose['end_date']) && $date > $dose['end_date']) {
        return false;
    }
    $days = str_pad((string) $dose['days'], 7, '0');
    $index = (int) date('N', strtotime($date . ' 12:00:00')) - 1;
    if (!isset($days[$index]) || $days[$index] !== '1') {
        return false;
    }

    $save = db()->prepare(
        'INSERT INTO dose_logs
            (user_id, medicine_id, dose_time_id, medicine_name, dosage, scheduled_time, log_date, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            medicine_name = VALUES(medicine_name),
            dosage = VALUES(dosage),
            scheduled_time = VALUES(scheduled_time),
            logged_at = CURRENT_TIMESTAMP'
    );
    $save->execute([
        (int) $userId,
        (int) $dose['medicine_id'],
        (int) $dose['id'],
        $dose['name'],
        $dose['dosage'],
        $dose['dose_time'],
        $date,
        $status,
    ]);
    return true;
}

function days_label($days)
{
    $names = weekday_names();
    $days = str_pad((string) $days, 7, '0');
    if ($days === '1111111') {
        return 'Every day';
    }
    if ($days === '1111100') {
        return 'Weekdays';
    }
    if ($days === '0000011') {
        return 'Weekends';
    }
    $picked = [];
    for ($i = 0; $i < 7; $i++) {
        if (isset($days[$i]) && $days[$i] === '1') {
            $picked[] = $names[$i];
        }
    }
    return $picked ? implode(', ', $picked) : 'No days';
}

function status_label($status)
{
    switch ($status) {
        case 'taken':
            return 'Taken';
        case 'skipped':
            return 'Skipped';
        case 'missed':
            return 'Missed';
        case 'due':
            return 'Due now';
        case 'scheduled':
            return 'Scheduled';
        default:
            return 'Scheduled';
    }
}

function format_day($date)
{
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($date === $today) {
        return 'Today';
    }
    if ($date === $yesterday) {
        return 'Yesterday';
    }
    return date('D, j M Y', strtotime($date . ' 12:00:00'));
}

function format_clock($time)
{
    $stamp = strtotime('2000-01-01 ' . $time);
    if ($stamp === false) {
        return ['h' => substr((string) $time, 0, 5), 'ampm' => '', 'label' => substr((string) $time, 0, 5)];
    }
    return [
        'h' => date('g:i', $stamp),
        'ampm' => date('A', $stamp),
        'label' => date('g:i A', $stamp),
    ];
}

function recent_logs($limit = 6)
{
    $limit = (int) $limit;
    if ($limit < 1 || $limit > 50) {
        $limit = 6;
    }
    return db()->query(
        "SELECT l.medicine_name, l.dosage, l.status, l.log_date, l.scheduled_time, l.logged_at, u.full_name
         FROM dose_logs l
         INNER JOIN users u ON u.id = l.user_id
         ORDER BY l.logged_at DESC
         LIMIT {$limit}"
    )->fetchAll();
}

function count_by_sql($sql)
{
    return (int) db()->query($sql)->fetchColumn();
}

function load_report($includePatient)
{
    $fallbackFrom = date('Y-m-d', strtotime('-29 days'));
    $fallbackTo = date('Y-m-d');
    $fromInput = isset($_GET['from']) ? (string) $_GET['from'] : $fallbackFrom;
    $toInput = isset($_GET['to']) ? (string) $_GET['to'] : $fallbackTo;
    list($from, $to, $error) = parse_date_range($fromInput, $toInput);

    $patientId = 0;
    if ($includePatient && isset($_GET['patient_id']) && $_GET['patient_id'] !== '' && $_GET['patient_id'] !== 'all') {
        $patientId = (int) $_GET['patient_id'];
        $check = db()->prepare('SELECT id FROM users WHERE id = ? AND role = \'patient\' LIMIT 1');
        $check->execute([$patientId]);
        if (!$check->fetch()) {
            $patientId = 0;
            if (!$error) {
                $error = 'Choose a valid patient.';
            }
        }
    }

    $rows = [];
    $summary = summarize_rows([]);
    if (!$error) {
        if ($includePatient) {
            $userId = $patientId > 0 ? $patientId : null;
        } else {
            $userId = (int) current_user()['id'];
        }
        $rows = build_occurrences($from, $to, $userId);
        $summary = summarize_rows($rows);
    }

    return [
        'from' => $error ? (valid_date($fromInput) ? $fromInput : $fallbackFrom) : $from,
        'to' => $error ? (valid_date($toInput) ? $toInput : $fallbackTo) : $to,
        'error' => $error,
        'rows' => $rows,
        'summary' => $summary,
        'patientId' => $patientId,
        'patients' => $includePatient ? patients() : [],
        'scope' => $includePatient ? 'all' : 'self',
        'includePatient' => $includePatient,
    ];
}
