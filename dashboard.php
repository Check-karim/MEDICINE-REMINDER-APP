<?php

require __DIR__ . '/includes/init.php';
require_patient();

$today = date('Y-m-d');
$rows = build_occurrences($today, $today, (int) current_user()['id']);
$summary = summarize_rows($rows);
$total = count($rows);
$percent = $total ? (int) round(($summary['taken'] / $total) * 100) : 0;

$next = null;
$rank = ['missed' => 0, 'due' => 1, 'scheduled' => 2];
foreach ($rows as $row) {
    if (!isset($rank[$row['status']])) {
        continue;
    }
    if ($next === null || $rank[$row['status']] < $rank[$next['status']]) {
        $next = $row;
    }
}

app_open('Today', 'today');
?>
<section class="today-card">
  <p class="eyebrow"><?= e(date('l, j M')) ?></p>
  <h2><?= e(greeting()) ?>, <?= e(first_name(current_user()['full_name'])) ?></h2>
  <?php if ($total === 0): ?>
    <p>Nothing is scheduled today. Add a medicine to build your list.</p>
  <?php else: ?>
    <p class="stat-line"><strong><?= (int) $summary['taken'] ?></strong> of <?= (int) $total ?> doses taken</p>
    <div class="bar" aria-hidden="true"><span style="width: <?= (int) $percent ?>%"></span></div>
  <?php endif; ?>
</section>

<?php if ($total > 0 && !$next): ?>
  <section class="next-card tone-ok">
    <h2>You are up to date today</h2>
    <p>Every dose on today’s schedule has been marked.</p>
  </section>
<?php elseif ($next): ?>
  <?php $clock = format_clock($next['time']); ?>
  <section class="next-card tone-<?= e(pill_class($next['status'])) ?>">
    <p class="eyebrow"><?= e(status_label($next['status'])) ?></p>
    <h2><?= e($next['name']) ?> at <?= e($clock['label']) ?></h2>
    <p><?= e($next['dosage']) ?> · <?= e($next['quantity']) ?></p>
  </section>
<?php endif; ?>

<?php if ($total === 0): ?>
  <?php empty_state('No medicines for today', 'Add a medicine and choose the days and times you take it.', 'medicine-form.php', 'Add a medicine'); ?>
<?php else: ?>
  <?php render_dose_groups($rows, true, 'No medicines for today', 'Add a medicine to see it here.'); ?>
<?php endif; ?>
<?php app_close('today'); ?>
