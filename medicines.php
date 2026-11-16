<?php

require __DIR__ . '/includes/init.php';
require_patient();

if (is_post()) {
    check_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $medicine = medicine_for_user($id, (int) current_user()['id']);
    if (!$medicine) {
        set_flash('error', 'That medicine could not be found.');
        redirect('medicines.php');
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM medicines WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, (int) current_user()['id']]);
        set_flash('success', 'Medicine deleted.');
    } elseif ($action === 'toggle') {
        $next = (int) $medicine['is_active'] === 1 ? 0 : 1;
        $stmt = db()->prepare('UPDATE medicines SET is_active = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$next, $id, (int) current_user()['id']]);
        set_flash('success', $next ? 'Medicine resumed.' : 'Medicine stopped. Past doses stay in your history.');
    }
    redirect('medicines.php');
}

$medicines = medicines_for_user((int) current_user()['id']);
app_open('Medicines', 'meds');
?>
<div class="card-actions">
  <a class="btn btn-primary" href="<?= e(url('medicine-form.php')) ?>">Add medicine</a>
</div>
<?php if (!$medicines): ?>
  <?php empty_state('No medicines yet', 'Add the first medicine, including the times you take it.', 'medicine-form.php', 'Add a medicine'); ?>
<?php else: ?>
  <div class="field">
    <label for="med-filter">Search</label>
    <input id="med-filter" type="search" placeholder="Search by name" data-filter="#med-list">
  </div>
  <div id="med-list">
    <?php foreach ($medicines as $medicine): ?>
      <?php
        $search = strtolower($medicine['name'] . ' ' . $medicine['dosage'] . ' ' . $medicine['form']);
        $stopped = (int) $medicine['is_active'] !== 1;
      ?>
      <article class="med-card<?= $stopped ? ' is-stopped' : '' ?>" data-search="<?= e($search) ?>">
        <div class="med-head">
          <h2><?= e($medicine['name']) ?></h2>
          <span class="badge <?= $stopped ? 'badge-stop' : 'badge-live' ?>"><?= $stopped ? 'Stopped' : 'Active' ?></span>
        </div>
        <p><?= e($medicine['dosage']) ?> · <?= e($medicine['form']) ?></p>
        <?php if (!empty($medicine['instructions'])): ?>
          <p class="hint"><?= e($medicine['instructions']) ?></p>
        <?php endif; ?>
        <ul>
          <?php foreach ($medicine['doses'] as $dose): ?>
            <li><?= e(format_clock($dose['dose_time'])['label']) ?> · <?= e($dose['quantity']) ?> · <?= e(days_label($dose['days'])) ?></li>
          <?php endforeach; ?>
        </ul>
        <p class="hint">From <?= e(date('j M Y', strtotime($medicine['start_date']))) ?><?php if (!empty($medicine['end_date'])): ?> to <?= e(date('j M Y', strtotime($medicine['end_date']))) ?><?php endif; ?></p>
        <div class="card-actions">
          <a class="btn btn-small btn-secondary" href="<?= e(url('medicine-form.php?id=' . (int) $medicine['id'])) ?>">Edit</a>
          <form method="post" action="<?= e(url('medicines.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int) $medicine['id'] ?>">
            <button class="btn btn-small btn-ghost" type="submit"><?= $stopped ? 'Resume' : 'Stop' ?></button>
          </form>
          <form method="post" action="<?= e(url('medicines.php')) ?>" data-confirm="Delete this medicine and its dose history?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $medicine['id'] ?>">
            <button class="btn btn-small btn-danger" type="submit">Delete</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php app_close('meds'); ?>
