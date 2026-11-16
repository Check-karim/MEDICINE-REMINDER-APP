<?php

require __DIR__ . '/includes/init.php';

http_response_code(404);
if (current_user()) {
    app_open('Page not found', '');
    empty_state('Page not found', 'That page is not part of Medicine Reminder.', current_user()['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php', 'Back home');
    app_close('');
} else {
    public_open('Page not found');
    echo '<div class="wrap" style="padding:28px 0 48px;">';
    empty_state('Page not found', 'That page is not part of Medicine Reminder.', 'index.php', 'Back home');
    echo '</div>';
    public_close();
}
