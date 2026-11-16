<?php

require dirname(__DIR__) . '/includes/init.php';
require_admin();

$search = trim($_GET['q'] ?? '');
if (is_post()) {
    check_csrf();
    $search = trim($_POST['q'] ?? '');
    $deleted = delete_patient((int) ($_POST['user_id'] ?? 0));
    set_flash($deleted ? 'success' : 'error', $deleted ? 'Patient account deleted.' : 'That patient could not be deleted.');
    redirect('admin/patients.php' . ($search !== '' ? '?q=' . rawurlencode($search) : ''));
}

$rows = patients($search);
app_open('Patients', 'patients');
?>
<form class="filters" method="get" action="<?= e(url('admin/patients.php')) ?>">
  <div class="field">
    <label for="q">Search</label>
    <input id="q" name="q" value="<?= e($search) ?>" placeholder="Name, username, email, or phone">
  </div>
  <div class="field field-action"><button class="btn btn-primary" type="submit">Search</button></div>
</form>
<div class="table-wrap">
  <table class="stack">
    <thead>
      <tr>
        <th>Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Medicines</th>
        <th>Joined</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td data-label="Patients" colspan="7"><?= $search !== '' ? 'No patients match this search.' : 'No patients have registered yet.' ?></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $patient): ?>
        <tr>
          <td data-label="Name"><?= e($patient['full_name']) ?></td>
          <td data-label="Username"><?= e($patient['username']) ?></td>
          <td data-label="Email"><?= e($patient['email']) ?></td>
          <td data-label="Phone"><?= e($patient['phone']) ?></td>
          <td data-label="Medicines"><?= (int) $patient['medicine_count'] ?></td>
          <td data-label="Joined"><?= e(date('j M Y', strtotime($patient['created_at']))) ?></td>
          <td data-label="Action">
            <form method="post" action="<?= e(url('admin/patients.php')) ?>" data-confirm="Delete this patient and all of their medicine records?">
              <?= csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= (int) $patient['id'] ?>">
              <input type="hidden" name="q" value="<?= e($search) ?>">
              <button class="btn btn-small btn-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php app_close('patients'); ?>
