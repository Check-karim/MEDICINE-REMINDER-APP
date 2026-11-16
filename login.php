<?php

require __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect(current_user()['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
}

if (isset($_GET['logged_out'])) {
    set_flash('success', 'You have signed out.');
}

$error = '';
$username = '';

if (is_post()) {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = $username !== '' ? user_by_username($username) : null;
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Those details do not match our records.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $update = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
        }
        redirect($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
    }
}

public_open('Log in', 'login');
?>
<div class="auth-wrap">
  <div class="auth-card">
    <p class="eyebrow">Welcome back</p>
    <h1>Log in</h1>
    <p class="lede">Patients use the account they registered. Staff can use the predefined account below.</p>
    <div class="staff-card">
      <p>Predefined staff account</p>
      <dl>
        <dt>Username</dt>
        <dd>admin</dd>
        <dt>Password</dt>
        <dd>admin</dd>
      </dl>
    </div>
    <?php if ($error): ?>
      <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('login.php')) ?>" autocomplete="on">
      <?= csrf_field() ?>
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" value="<?= e($username) ?>" autocomplete="username" autocapitalize="none" spellcheck="false" required>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <div class="password-wrap">
          <input id="password" name="password" type="password" autocomplete="current-password" required>
          <button class="text-btn" type="button" data-show-password="password">Show</button>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Log in</button>
      </div>
    </form>
    <p class="hint">New here? <a href="<?= e(url('register.php')) ?>">Create a patient account</a>.</p>
  </div>
</div>
<?php public_close(); ?>
