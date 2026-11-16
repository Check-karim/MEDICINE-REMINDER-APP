<?php

require __DIR__ . '/includes/init.php';
require_patient();

function render_dose_editor_row($index, array $dose)
{
    $time = substr((string) (isset($dose['time']) ? $dose['time'] : '08:00'), 0, 5);
    $quantity = (string) (isset($dose['quantity']) ? $dose['quantity'] : '1');
    $days = str_pad((string) (isset($dose['days']) ? $dose['days'] : '1111111'), 7, '0');
    echo '<div class="dose-row">';
    if (!empty($dose['id'])) {
        echo '<input type="hidden" name="doses[' . e($index) . '][id]" value="' . (int) $dose['id'] . '">';
    }
    echo '<div class="dose-row-top">';
    echo '<div class="field"><label>Time</label><input type="time" name="doses[' . e($index) . '][time]" value="' . e($time) . '" required></div>';
    echo '<div class="field"><label>Amount</label><input name="doses[' . e($index) . '][quantity]" value="' . e($quantity) . '" maxlength="40" placeholder="1 tablet" required></div>';
    echo '<button class="btn btn-small btn-danger" type="button" data-remove-dose>Remove</button>';
    echo '</div><div class="day-picker" aria-label="Days">';
    foreach (weekday_names() as $dayIndex => $label) {
        $checked = isset($days[$dayIndex]) && $days[$dayIndex] === '1' ? ' checked' : '';
        echo '<label class="day"><input type="checkbox" name="doses[' . e($index) . '][days][]" value="' . (int) $dayIndex . '"' . $checked . '><span>' . e($label) . '</span></label>';
    }
    echo '</div></div>';
}

function posted_doses($source)
{
    $rows = [];
    if (!isset($source['doses']) || !is_array($source['doses'])) {
        return $rows;
    }
    foreach ($source['doses'] as $dose) {
        if (!is_array($dose)) {
            continue;
        }
        $days = '0000000';
        if (!empty($dose['days']) && is_array($dose['days'])) {
            $built = days_string($dose['days']);
            if ($built) {
                $days = $built;
            }
        }
        $rows[] = [
            'id' => isset($dose['id']) ? (int) $dose['id'] : 0,
            'time' => substr((string) ($dose['time'] ?? ''), 0, 5),
            'quantity' => trim((string) ($dose['quantity'] ?? '')),
            'days' => $days,
        ];
    }
    return $rows;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$medicine = null;
if ($id) {
    $medicine = medicine_for_user($id, (int) current_user()['id']);
    if (!$medicine) {
        set_flash('error', 'That medicine could not be found.');
        redirect('medicines.php');
    }
}

$errors = [];
$form = [
    'name' => $medicine['name'] ?? '',
    'dosage' => $medicine['dosage'] ?? '',
    'form' => $medicine['form'] ?? 'Tablet',
    'instructions' => $medicine['instructions'] ?? '',
    'start_date' => $medicine['start_date'] ?? date('Y-m-d'),
    'end_date' => $medicine['end_date'] ?? '',
    'is_active' => isset($medicine) ? (int) $medicine['is_active'] : 1,
];
$doses = [];
if ($medicine) {
    foreach ($medicine['doses'] as $dose) {
        $doses[] = [
            'id' => (int) $dose['id'],
            'time' => substr($dose['dose_time'], 0, 5),
            'quantity' => $dose['quantity'],
            'days' => $dose['days'],
        ];
    }
}
if (!$doses) {
    $doses[] = ['id' => 0, 'time' => '08:00', 'quantity' => '1', 'days' => '1111111'];
}

if (is_post()) {
    check_csrf();
    $form['name'] = trim(preg_replace('/\s+/', ' ', $_POST['name'] ?? ''));
    $form['dosage'] = trim(preg_replace('/\s+/', ' ', $_POST['dosage'] ?? ''));
    $form['form'] = trim($_POST['form'] ?? '');
    $form['instructions'] = trim(preg_replace('/\s+/', ' ', $_POST['instructions'] ?? ''));
    $form['start_date'] = trim($_POST['start_date'] ?? '');
    $form['end_date'] = trim($_POST['end_date'] ?? '');
    $form['is_active'] = $id ? (!empty($_POST['is_active']) ? 1 : 0) : 1;
    $doses = posted_doses($_POST);

    if (!valid_medicine_name($form['name'])) {
        $errors['name'] = 'Enter a medicine name using letters, numbers, and simple punctuation.';
    }
    if (!valid_dosage($form['dosage'])) {
        $errors['dosage'] = 'Enter the strength, such as 500 mg or 5 ml.';
    }
    if (!in_array($form['form'], medicine_forms(), true)) {
        $errors['form'] = 'Choose a form.';
    }
    if (!valid_instructions($form['instructions'])) {
        $errors['instructions'] = 'Use plain text, up to 240 characters.';
    }
    if (!valid_date($form['start_date'])) {
        $errors['start_date'] = 'Choose a valid start date.';
    }
    if ($form['end_date'] !== '' && !valid_date($form['end_date'])) {
        $errors['end_date'] = 'Choose a valid end date, or leave it blank.';
    } elseif ($form['end_date'] !== '' && valid_date($form['start_date']) && $form['end_date'] < $form['start_date']) {
        $errors['end_date'] = 'The end date must be on or after the start date.';
    }
    if (!$doses || count($doses) > 8) {
        $errors['doses'] = 'Add between 1 and 8 dose times.';
    }

    $clean = [];
    $seen = [];
    foreach ($doses as $dose) {
        $time = normalize_time($dose['time']);
        if (!$time) {
            $errors['doses'] = 'Enter a valid time for every dose.';
            break;
        }
        $clock = substr($time, 0, 5);
        if (isset($seen[$clock])) {
            $errors['doses'] = 'Each dose time must be different.';
            break;
        }
        $seen[$clock] = true;
        if (!valid_quantity($dose['quantity'])) {
            $errors['doses'] = 'Enter the amount, such as 1 tablet or 5 ml.';
            break;
        }
        if ($dose['days'] === '0000000') {
            $errors['doses'] = 'Choose at least one day for every dose time.';
            break;
        }
        $clean[] = [
            'id' => (int) $dose['id'],
            'time' => $time,
            'quantity' => $dose['quantity'],
            'days' => $dose['days'],
        ];
    }

    if (!$errors) {
        $db = db();
        $db->beginTransaction();
        try {
            $instructions = $form['instructions'] === '' ? null : $form['instructions'];
            $endDate = $form['end_date'] === '' ? null : $form['end_date'];
            if ($id) {
                $stmt = $db->prepare(
                    'UPDATE medicines
                     SET name = ?, dosage = ?, form = ?, instructions = ?, start_date = ?, end_date = ?, is_active = ?
                     WHERE id = ? AND user_id = ?'
                );
                $stmt->execute([
                    $form['name'], $form['dosage'], $form['form'], $instructions,
                    $form['start_date'], $endDate, $form['is_active'], $id, (int) current_user()['id'],
                ]);
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO medicines (user_id, name, dosage, form, instructions, start_date, end_date, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
                );
                $stmt->execute([
                    (int) current_user()['id'], $form['name'], $form['dosage'], $form['form'],
                    $instructions, $form['start_date'], $endDate,
                ]);
                $id = (int) $db->lastInsertId();
            }

            $existing = [];
            foreach (doses_for_medicine($id) as $dose) {
                $existing[(int) $dose['id']] = true;
            }
            $keep = [];
            $update = $db->prepare('UPDATE dose_times SET dose_time = ?, quantity = ?, days = ? WHERE id = ? AND medicine_id = ?');
            $insert = $db->prepare('INSERT INTO dose_times (medicine_id, dose_time, quantity, days) VALUES (?, ?, ?, ?)');
            foreach ($clean as $dose) {
                if ($dose['id'] && isset($existing[$dose['id']])) {
                    $update->execute([$dose['time'], $dose['quantity'], $dose['days'], $dose['id'], $id]);
                    $keep[] = $dose['id'];
                } else {
                    $insert->execute([$id, $dose['time'], $dose['quantity'], $dose['days']]);
                    $keep[] = (int) $db->lastInsertId();
                }
            }
            $placeholders = implode(',', array_fill(0, count($keep), '?'));
            $delete = $db->prepare("DELETE FROM dose_times WHERE medicine_id = ? AND id NOT IN ({$placeholders})");
            $delete->execute(array_merge([$id], $keep));
            $db->commit();
            set_flash('success', 'Medicine saved.');
            redirect('medicines.php');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}

app_open($medicine ? 'Edit medicine' : 'Add medicine', 'meds');
?>
<?php if (!empty($errors['doses'])): ?>
  <div class="flash flash-error" role="alert"><?= e($errors['doses']) ?></div>
<?php endif; ?>
<form method="post" action="<?= e(url('medicine-form.php' . ($id ? '?id=' . $id : ''))) ?>" data-validate="medicine">
  <?= csrf_field() ?>
  <div class="field">
    <label for="name">Medicine name</label>
    <input id="name" name="name" value="<?= e($form['name']) ?>" maxlength="120" required class="<?= trim(invalid_class($errors, 'name')) ?>">
    <?php field_error($errors, 'name'); ?>
  </div>
  <div class="field">
    <label for="dosage">Dosage</label>
    <input id="dosage" name="dosage" value="<?= e($form['dosage']) ?>" maxlength="60" placeholder="500 mg" required class="<?= trim(invalid_class($errors, 'dosage')) ?>">
    <?php field_error($errors, 'dosage'); ?>
  </div>
  <div class="field">
    <label for="form">Form</label>
    <select id="form" name="form" class="<?= trim(invalid_class($errors, 'form')) ?>">
      <?php foreach (medicine_forms() as $option): ?>
        <option value="<?= e($option) ?>"<?= $form['form'] === $option ? ' selected' : '' ?>><?= e($option) ?></option>
      <?php endforeach; ?>
    </select>
    <?php field_error($errors, 'form'); ?>
  </div>
  <div class="field">
    <label for="instructions">Instructions</label>
    <textarea id="instructions" name="instructions" maxlength="240" placeholder="After food"><?= e($form['instructions']) ?></textarea>
    <?php field_error($errors, 'instructions'); ?>
  </div>
  <div class="field">
    <label for="start_date">Start date</label>
    <input id="start_date" type="date" name="start_date" value="<?= e($form['start_date']) ?>" required class="<?= trim(invalid_class($errors, 'start_date')) ?>">
    <?php field_error($errors, 'start_date'); ?>
  </div>
  <div class="field">
    <label for="end_date">End date</label>
    <input id="end_date" type="date" name="end_date" value="<?= e($form['end_date']) ?>" class="<?= trim(invalid_class($errors, 'end_date')) ?>">
    <p class="hint">Leave this blank if the medicine does not have an end date.</p>
    <?php field_error($errors, 'end_date'); ?>
  </div>
  <?php if ($id): ?>
    <div class="field">
      <label class="check-line"><input type="checkbox" name="is_active" value="1"<?= $form['is_active'] ? ' checked' : '' ?>> Active on the schedule</label>
    </div>
  <?php endif; ?>

  <h2>Dose times</h2>
  <p class="hint">A dose stays due for 60 minutes. After that it is counted as missed until you mark it. Removing a time also removes its history.</p>
  <div id="dose-list">
    <?php foreach ($doses as $index => $dose): ?>
      <?php render_dose_editor_row($index, $dose); ?>
    <?php endforeach; ?>
  </div>
  <p class="field-error" data-error-for="doses"></p>
  <button class="btn btn-secondary" type="button" id="add-dose">Add another time</button>
  <div class="form-actions" style="margin-top:16px;">
    <button class="btn btn-primary" type="submit">Save medicine</button>
  </div>
</form>
<template id="dose-template">
  <?php render_dose_editor_row('__INDEX__', ['id' => 0, 'time' => '08:00', 'quantity' => '1', 'days' => '1111111']); ?>
</template>
<?php app_close('meds'); ?>
