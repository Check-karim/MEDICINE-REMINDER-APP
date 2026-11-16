<?php

require __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect(current_user()['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
}

$errors = [];
$old = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
];

if (is_post()) {
    check_csrf();
    $name = normalize_name($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = normalize_email($_POST['email'] ?? '');
    $phoneInput = trim($_POST['phone'] ?? '');
    $phone = normalize_phone($phoneInput);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    $old = [
        'full_name' => $name,
        'username' => $username,
        'email' => $email,
        'phone' => $phoneInput,
    ];

    if (!valid_name($name)) {
        $errors['full_name'] = 'Name cannot contain numbers or special characters.';
    }
    if (!valid_username($username)) {
        $errors['username'] = 'Username cannot contain spaces, and it cannot start with a number or a special character.';
    } elseif (strtolower($username) === 'admin') {
        $errors['username'] = 'That username is reserved.';
    } elseif (value_taken('username', $username)) {
        $errors['username'] = 'That username is already in use.';
    }
    if (!valid_email($email)) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (value_taken('email', $email)) {
        $errors['email'] = 'That email address is already in use.';
    }
    if ($phone === null) {
        $errors['phone'] = 'Enter a valid phone number (10 to 15 digits).';
    } elseif (value_taken('phone', $phone)) {
        $errors['phone'] = 'That phone number is already in use.';
    }
    if (!valid_password($password)) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO users (full_name, username, email, phone, password_hash, role)
                 VALUES (?, ?, ?, ?, ?, \'patient\')'
            );
            $stmt->execute([$name, $username, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            set_flash('success', 'Account created. You can log in now.');
            redirect('login.php');
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            $errors['form'] = 'An account with that username, email, or phone already exists.';
        }
    }
}

public_open('Register', 'register');
?>
<div class="auth-wrap">
  <div class="auth-card">
    <p class="eyebrow">Patient account</p>
    <h1>Create an account</h1>
    <p class="lede">Your name, username, email, and phone are checked before the account is saved.</p>
    <?php if (!empty($errors['form'])): ?>
      <div class="flash flash-error" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('register.php')) ?>" data-validate="register" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" maxlength="80" autocomplete="name" required class="<?= trim(invalid_class($errors, 'full_name')) ?>">
        <p class="hint">Letters and spaces only. Numbers and special characters are not allowed.</p>
        <?php field_error($errors, 'full_name'); ?>
      </div>
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" value="<?= e($old['username']) ?>" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" required class="<?= trim(invalid_class($errors, 'username')) ?>">
        <p class="hint">Start with a letter. No spaces. Then use letters, numbers, dots, hyphens, or underscores.</p>
        <?php field_error($errors, 'username'); ?>
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= e($old['email']) ?>" maxlength="190" autocomplete="email" inputmode="email" required class="<?= trim(invalid_class($errors, 'email')) ?>">
        <?php field_error($errors, 'email'); ?>
      </div>
      <div class="field">
        <label for="phone">Phone number</label>
        <input id="phone" name="phone" type="tel" value="<?= e($old['phone']) ?>" maxlength="20" autocomplete="tel" inputmode="tel" required class="<?= trim(invalid_class($errors, 'phone')) ?>">
        <p class="hint">Use 10 to 15 digits. A leading + is optional.</p>
        <?php field_error($errors, 'phone'); ?>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <div class="password-wrap">
          <input id="password" name="password" type="password" autocomplete="new-password" required class="<?= trim(invalid_class($errors, 'password')) ?>">
          <button class="text-btn" type="button" data-show-password="password">Show</button>
        </div>
        <p class="hint">At least 6 characters.</p>
        <?php field_error($errors, 'password'); ?>
      </div>
      <div class="field">
        <label for="password_confirm">Confirm password</label>
        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required class="<?= trim(invalid_class($errors, 'password_confirm')) ?>">
        <?php field_error($errors, 'password_confirm'); ?>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Create account</button>
      </div>
    </form>
    <p class="hint">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a>. This app organises reminders and does not replace clinical advice.</p>
  </div>
</div>
<?php public_close(); ?>
