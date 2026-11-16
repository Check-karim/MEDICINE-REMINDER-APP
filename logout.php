<?php

require __DIR__ . '/includes/init.php';

if (!current_user()) {
    redirect('login.php');
}

if (is_post()) {
    check_csrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: ' . url('login.php?logged_out=1'));
    exit;
}

app_open('Log out', 'more');
?>
<div class="panel">
  <h2>Sign out of this device?</h2>
  <p>You can log in again with the same username and password.</p>
  <form method="post" action="<?= e(url('logout.php')) ?>">
    <?= csrf_field() ?>
    <button class="btn btn-primary" type="submit">Log out</button>
  </form>
</div>
<?php app_close('more'); ?>
