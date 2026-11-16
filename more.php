<?php

require __DIR__ . '/includes/init.php';
require_login();

app_open('More', 'more');
?>
<nav class="link-list" aria-label="More">
  <button class="linkish" type="button" data-install-app>
    <span><strong>Install app</strong><small class="hint">Add it to this phone or computer</small></span>
    <span data-install-label>Install</span>
  </button>
  <?php if (current_user()['role'] === 'patient'): ?>
    <a href="<?= e(url('reports.php')) ?>"><span><strong>Reports</strong><small class="hint">Download PDF and CSV</small></span><span>Open</span></a>
  <?php endif; ?>
  <a href="<?= e(url('profile.php')) ?>"><span><strong>Profile</strong><small class="hint">Name, email, phone, password</small></span><span>Open</span></a>
  <a href="<?= e(url('about.php')) ?>"><span><strong>About us</strong><small class="hint">Digital Home Healthcare Ltd</small></span><span>Open</span></a>
  <a href="<?= e(url('logout.php')) ?>"><span><strong>Log out</strong><small class="hint"><?= e(current_user()['username']) ?></small></span><span>Open</span></a>
</nav>
<?php app_close('more'); ?>
