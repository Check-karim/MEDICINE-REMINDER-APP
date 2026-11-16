<?php

require dirname(__DIR__) . '/includes/init.php';
require_admin();

$today = date('Y-m-d');
$summary = summarize_rows(build_occurrences($today, $today));
$patientCount = count_by_sql("SELECT COUNT(*) FROM users WHERE role = 'patient'");
$medicineCount = count_by_sql('SELECT COUNT(*) FROM medicines WHERE is_active = 1');
$recent = recent_logs(6);

app_open('Staff home', 'home');
?>
<p class="lede">Digital Home Healthcare Ltd. Review today’s medication activity, then open a patient or a report.</p>
<div class="summary-grid">
  <div class="summary-card"><strong><?= (int) $patientCount ?></strong><span>Patients</span></div>
  <div class="summary-card"><strong><?= (int) $medicineCount ?></strong><span>Active medicines</span></div>
  <div class="summary-card"><strong><?= (int) $summary['taken'] ?></strong><span>Taken today</span></div>
  <div class="summary-card"><strong><?= (int) $summary['missed'] ?></strong><span>Missed today</span></div>
  <div class="summary-card"><strong><?= (int) $summary['due'] ?></strong><span>Due now</span></div>
</div>
<div class="card-actions" style="margin-top:16px;">
  <a class="btn btn-primary" href="<?= e(url('admin/reports.php')) ?>">Download reports</a>
  <a class="btn btn-secondary" href="<?= e(url('admin/patients.php')) ?>">View patients</a>
</div>
<h2>Latest marks</h2>
<?php if (!$recent): ?>
  <?php empty_state('No doses marked yet', 'Activity appears here after a patient marks a dose.', 'admin/patients.php', 'View patients'); ?>
<?php else: ?>
  <div class="dose-list">
    <?php foreach ($recent as $item): ?>
      <?php $clock = format_clock($item['scheduled_time']); ?>
      <article class="dose">
        <div class="when"><strong><?= e($clock['h']) ?></strong><span><?= e($clock['ampm']) ?></span></div>
        <div class="dose-body">
          <div class="dose-top">
            <h3><?= e($item['medicine_name']) ?></h3>
            <span class="pill pill-<?= e(pill_class($item['status'])) ?>"><?= e(status_label($item['status'])) ?></span>
          </div>
          <p><?= e($item['full_name']) ?> · <?= e($item['dosage']) ?> · <?= e(format_day($item['log_date'])) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php app_close('home'); ?>
