<?php

require __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$errors = [];
$passwordErrors = [];
$old = [
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'phone' => $user['phone'],
];

if (is_post()) {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'profile') {
        $name = normalize_name($_POST['full_name'] ?? '');
        $email = normalize_email($_POST['email'] ?? '');
        $phoneInput = trim($_POST['phone'] ?? '');
        $phone = normalize_phone($phoneInput);
        $old = ['full_name' => $name, 'email' => $email, 'phone' => $phoneInput];

        if (!valid_name($name)) {
            $errors['full_name'] = 'Name cannot contain numbers or special characters.';
        }
        if (!valid_email($email)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (value_taken('email', $email, (int) $user['id'])) {
            $errors['email'] = 'That email address is already in use.';
        }
        if ($phone === null) {
            $errors['phone'] = 'Enter a valid phone number (10 to 15 digits).';
        } elseif (value_taken('phone', $phone, (int) $user['id'])) {
            $errors['phone'] = 'That phone number is already in use.';
        }

        if (!$errors) {
            $stmt = db()->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phone, (int) $user['id']]);
            set_flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([(int) $user['id']]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($current, $hash)) {
            $passwordErrors['current_password'] = 'The current password is not correct.';
        }
        if (!valid_password($password)) {
            $passwordErrors['password'] = 'Password must be at least 6 characters.';
        }
        if ($password !== $confirm) {
            $passwordErrors['password_confirm'] = 'Passwords do not match.';
        }
        if (!$passwordErrors) {
            $update = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
            set_flash('success', 'Password updated.');
            redirect('profile.php');
        }
    }
}

app_open('Profile', 'more');
?>
<div class="panel">
  <p class="eyebrow"><?= e($user['role'] === 'admin' ? 'Staff' : 'Patient') ?></p>
  <h2><?= e($user['full_name']) ?></h2>
  <p>Username: <strong><?= e($user['username']) ?></strong></p>
  <p class="hint">Member since <?= e(date('j M Y', strtotime($user['created_at']))) ?>. The username stays the same.</p>
</div>

<h2>Contact details</h2>
<form method="post" action="<?= e(url('profile.php')) ?>" data-validate="profile" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="profile">
  <div class="field">
    <label for="full_name">Full name</label>
    <input id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" maxlength="80" required class="<?= trim(invalid_class($errors, 'full_name')) ?>">
    <p class="hint">Letters and spaces only.</p>
    <?php field_error($errors, 'full_name'); ?>
  </div>
  <div class="field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="<?= e($old['email']) ?>" maxlength="190" required class="<?= trim(invalid_class($errors, 'email')) ?>">
    <?php field_error($errors, 'email'); ?>
  </div>
  <div class="field">
    <label for="phone">Phone number</label>
    <input id="phone" name="phone" type="tel" value="<?= e($old['phone']) ?>" maxlength="20" required class="<?= trim(invalid_class($errors, 'phone')) ?>">
    <p class="hint">Use 10 to 15 digits. A leading + is optional.</p>
    <?php field_error($errors, 'phone'); ?>
  </div>
  <div class="form-actions"><button class="btn btn-primary" type="submit">Save details</button></div>
</form>

<h2>Password</h2>
<p class="hint">A new password must be at least 6 characters. The predefined staff password can be changed here.</p>
<form method="post" action="<?= e(url('profile.php')) ?>" data-validate="password" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <div class="field">
    <label for="current_password">Current password</label>
    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="<?= trim(invalid_class($passwordErrors, 'current_password')) ?>">
    <?php field_error($passwordErrors, 'current_password'); ?>
  </div>
  <div class="field">
    <label for="password">New password</label>
    <div class="password-wrap">
      <input id="password" name="password" type="password" autocomplete="new-password" required class="<?= trim(invalid_class($passwordErrors, 'password')) ?>">
      <button class="text-btn" type="button" data-show-password="password">Show</button>
    </div>
    <?php field_error($passwordErrors, 'password'); ?>
  </div>
  <div class="field">
    <label for="password_confirm">Confirm new password</label>
    <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required class="<?= trim(invalid_class($passwordErrors, 'password_confirm')) ?>">
    <?php field_error($passwordErrors, 'password_confirm'); ?>
  </div>
  <div class="form-actions"><button class="btn btn-primary" type="submit">Update password</button></div>
</form>
<?php app_close('more'); ?>
