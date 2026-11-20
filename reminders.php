<?php

require __DIR__ . '/includes/init.php';
require_patient();

$today = date('Y-m-d');
$rows = build_occurrences($today, $today, (int) current_user()['id']);
$attention = [];
$later = [];
$done = [];
foreach ($rows as $row) {
    if ($row['status'] === 'taken' || $row['status'] === 'skipped') {
        $done[] = $row;
    } elseif ($row['status'] === 'scheduled') {
        $later[] = $row;
    } else {
        $attention[] = $row;
    }
}

app_open('Reminders', 'remind');
?>
<div class="note" id="notify-box">
  <p id="notify-status">Turn on an alarm for doses that are due now.</p>
  <button class="btn btn-secondary" type="button" id="notify-toggle">Turn on alarms</button>
  <p class="hint">App time is <?= e(date('g:i A')) ?>. A dose is due for <?= (int) DUE_GRACE_MINUTES ?> minutes, then it shows as missed until you mark it. The alarm sounds in the browser and in the installed app. Keep a window open so it can ring.</p>
</div>
<?php if (!$rows): ?>
  <?php empty_state('No reminders today', 'Add a medicine and choose a day that includes today.', 'medicine-form.php', 'Add a medicine'); ?>
<?php else: ?>
  <h2>Needs attention</h2>
  <?php if ($attention): ?>
    <?php render_dose_groups($attention, true, '', '', false); ?>
  <?php else: ?>
    <p class="hint">Nothing is due or missed right now.</p>
  <?php endif; ?>
  <h2>Later today</h2>
  <?php if ($later): ?>
    <?php render_dose_groups($later, true, '', '', false); ?>
  <?php else: ?>
    <p class="hint">No doses are still waiting later today.</p>
  <?php endif; ?>
  <h2>Done</h2>
  <?php if ($done): ?>
    <?php render_dose_groups($done, true, '', '', false); ?>
  <?php else: ?>
    <p class="hint">No doses have been marked yet today.</p>
  <?php endif; ?>
<?php endif; ?>
<?php app_close('remind'); ?>
