<?php

require dirname(__DIR__) . '/includes/init.php';
require_admin();

$medicines = all_medicines();
app_open('Medicines', 'meds');
?>
<p class="lede">Every medicine recorded by a patient. Patients edit their own schedules.</p>
<div class="table-wrap">
  <table class="stack">
    <thead>
      <tr>
        <th>Patient</th>
        <th>Medicine</th>
        <th>Dosage</th>
        <th>Form</th>
        <th>Schedule</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$medicines): ?>
        <tr><td data-label="Medicines" colspan="6">No medicines have been recorded yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($medicines as $medicine): ?>
        <?php
          $bits = [];
          foreach ($medicine['doses'] as $dose) {
              $bits[] = format_clock($dose['dose_time'])['label'] . ' · ' . days_label($dose['days']);
          }
        ?>
        <tr>
          <td data-label="Patient"><?= e($medicine['patient']) ?></td>
          <td data-label="Medicine"><?= e($medicine['name']) ?></td>
          <td data-label="Dosage"><?= e($medicine['dosage']) ?></td>
          <td data-label="Form"><?= e($medicine['form']) ?></td>
          <td data-label="Schedule"><?= e($bits ? implode('; ', $bits) : 'No times') ?></td>
          <td data-label="Status"><span class="badge <?= (int) $medicine['is_active'] === 1 ? 'badge-live' : 'badge-stop' ?>"><?= (int) $medicine['is_active'] === 1 ? 'Active' : 'Stopped' ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php app_close('meds'); ?>
