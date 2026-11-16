<?php

require __DIR__ . '/includes/init.php';
require_patient();

$fallbackFrom = date('Y-m-d', strtotime('-6 days'));
$fallbackTo = date('Y-m-d');
$fromInput = isset($_GET['from']) ? (string) $_GET['from'] : $fallbackFrom;
$toInput = isset($_GET['to']) ? (string) $_GET['to'] : $fallbackTo;
list($from, $to, $error) = parse_date_range($fromInput, $toInput);
$displayFrom = $error ? (valid_date($fromInput) ? $fromInput : $fallbackFrom) : $from;
$displayTo = $error ? (valid_date($toInput) ? $toInput : $fallbackTo) : $to;

$allowed = ['all', 'taken', 'skipped', 'missed', 'due', 'scheduled'];
$status = isset($_GET['status']) ? (string) $_GET['status'] : 'all';
if (!in_array($status, $allowed, true)) {
    $status = 'all';
}

$rows = [];
if (!$error) {
    $rows = build_occurrences($from, $to, (int) current_user()['id'], 'desc');
    if ($status !== 'all') {
        $rows = array_values(array_filter($rows, function ($row) use ($status) {
            return $row['status'] === $status;
        }));
    }
}

app_open('Activity', 'activity');
?>
<p class="lede">Taken, skipped, missed, due, and scheduled doses for the dates you choose. You can correct a past dose from this list.</p>
<form class="filters" method="get" action="<?= e(url('activity.php')) ?>">
  <div class="field">
    <label for="from">From</label>
    <input id="from" type="date" name="from" value="<?= e($displayFrom) ?>" required>
  </div>
  <div class="field">
    <label for="to">To</label>
    <input id="to" type="date" name="to" value="<?= e($displayTo) ?>" required>
  </div>
  <div class="field">
    <label for="status">Status</label>
    <select id="status" name="status">
      <?php foreach ($allowed as $option): ?>
        <option value="<?= e($option) ?>"<?= $status === $option ? ' selected' : '' ?>><?= e($option === 'all' ? 'All' : status_label($option)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field field-action"><button class="btn btn-primary" type="submit">Show</button></div>
</form>
<?php if ($error): ?>
  <div class="flash flash-error" role="alert"><?= e($error) ?></div>
<?php else: ?>
  <?php render_dose_groups($rows, true, 'No activity in this period', 'Try a wider date range, or add a medicine first.'); ?>
<?php endif; ?>
<?php app_close('activity'); ?>
