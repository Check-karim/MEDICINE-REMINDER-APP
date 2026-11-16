<?php

require __DIR__ . '/includes/init.php';
require_patient();

$return = safe_return($_POST['return'] ?? 'reminders.php');
if (!is_post()) {
    redirect($return);
}

check_csrf();
$ok = mark_dose(
    (int) current_user()['id'],
    (int) ($_POST['dose_time_id'] ?? 0),
    (string) ($_POST['log_date'] ?? ''),
    (string) ($_POST['status'] ?? '')
);
set_flash($ok ? 'success' : 'error', $ok ? 'Dose updated.' : 'That dose could not be updated.');
redirect($return);
