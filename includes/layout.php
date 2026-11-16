<?php

if (!defined('MEDAPP')) {
    http_response_code(403);
    exit;
}

function icon($name)
{
    $paths = [
        'home' => '<path d="M4 11.5 12 5l8 6.5"/><path d="M7 10.5V19h10v-8.5"/>',
        'pill' => '<path d="M8.5 15.5 15.5 8.5a3.2 3.2 0 0 0-4.5-4.5L4 11a3.2 3.2 0 0 0 4.5 4.5z"/><path d="m9 9.5 5.5 5.5"/>',
        'bell' => '<path d="M6.5 16.5V11a5.5 5.5 0 1 1 11 0v5.5"/><path d="M5 16.5h14"/><path d="M10 18.5a2 2 0 0 0 4 0"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4.5l3 2"/>',
        'file' => '<path d="M7 3.5h7l4 4V20.5H7z"/><path d="M14 3.5V8h4"/>',
        'users' => '<circle cx="9" cy="9" r="2.5"/><path d="M4.5 18.5c.6-2.4 2.4-3.5 4.5-3.5s3.9 1.1 4.5 3.5"/><circle cx="16.5" cy="9.5" r="2"/><path d="M15 15c1.6-.3 3 .3 4 1.8"/>',
        'more' => '<circle cx="6" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="18" cy="12" r="1.2" fill="currentColor" stroke="none"/>',
        'info' => '<circle cx="12" cy="12" r="8"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
        'login' => '<circle cx="12" cy="8.5" r="3"/><path d="M6.5 19c.7-2.5 2.8-3.8 5.5-3.8s4.8 1.3 5.5 3.8"/>',
        'plus' => '<circle cx="12" cy="12" r="8"/><path d="M12 8.5v7"/><path d="M8.5 12h7"/>',
    ];
    $inner = isset($paths[$name]) ? $paths[$name] : '';
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}

function brand()
{
    return '<span class="brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32"><rect x="6" y="12" width="20" height="8" rx="4" transform="rotate(-40 16 16)"/></svg></span><span><strong>Medicine Reminder</strong><small>Digital Home Healthcare</small></span></span>';
}

function navigation()
{
    if (current_user()['role'] === 'admin') {
        return [
            ['key' => 'home', 'label' => 'Home', 'href' => 'admin/index.php', 'icon' => 'home'],
            ['key' => 'patients', 'label' => 'Patients', 'href' => 'admin/patients.php', 'icon' => 'users'],
            ['key' => 'meds', 'label' => 'Medicines', 'href' => 'admin/medicines.php', 'icon' => 'pill'],
            ['key' => 'reports', 'label' => 'Reports', 'href' => 'admin/reports.php', 'icon' => 'file'],
            ['key' => 'more', 'label' => 'More', 'href' => 'more.php', 'icon' => 'more'],
        ];
    }
    return [
        ['key' => 'today', 'label' => 'Today', 'href' => 'dashboard.php', 'icon' => 'home'],
        ['key' => 'meds', 'label' => 'Meds', 'href' => 'medicines.php', 'icon' => 'pill'],
        ['key' => 'remind', 'label' => 'Remind', 'href' => 'reminders.php', 'icon' => 'bell'],
        ['key' => 'activity', 'label' => 'Activity', 'href' => 'activity.php', 'icon' => 'clock'],
        ['key' => 'more', 'label' => 'More', 'href' => 'more.php', 'icon' => 'more'],
    ];
}

function page_open($title, $bodyClass, array $attrs = [])
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    $extra = '';
    foreach ($attrs as $key => $value) {
        $extra .= ' ' . e($key) . '="' . e($value) . '"';
    }
    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
    echo '<meta name="description" content="Medicine Reminder by Digital Home Healthcare Ltd helps patients record medicines, follow dosage schedules, and track every dose.">';
    echo '<meta name="theme-color" content="#1f7a4d">';
    echo '<meta name="mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">';
    echo '<meta name="apple-mobile-web-app-title" content="Reminder">';
    echo '<title>' . e($title) . ' · Medicine Reminder</title>';
    echo '<link rel="icon" href="' . e(url('assets/favicon.svg')) . '" type="image/svg+xml">';
    echo '<link rel="apple-touch-icon" href="' . e(url('assets/icons/icon-180.png')) . '">';
    echo '<link rel="manifest" href="' . e(url('manifest.webmanifest')) . '">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&amp;display=swap">';
    echo '<link rel="stylesheet" href="' . e(asset('assets/css/app.css')) . '">';
    echo '</head><body class="' . e($bodyClass) . '" data-base="' . e(app_base()) . '"' . $extra . '>';
}

function page_close()
{
    echo '<div class="install-help" id="install-help" hidden>';
    echo '<div class="install-help-card" role="dialog" aria-modal="true" aria-labelledby="install-help-title">';
    echo '<h2 id="install-help-title">Install the app</h2>';
    echo '<p id="install-help-lead">Add Medicine Reminder to this phone or computer. It opens on its own, with the bottom menu.</p>';
    echo '<ol id="install-help-steps"></ol>';
    echo '<button class="btn btn-primary" type="button" data-close-install>Close</button>';
    echo '</div></div>';
    echo '<script src="' . e(asset('assets/js/app.js')) . '"></script>';
    echo '</body></html>';
}

function public_open($title, $active = '')
{
    $GLOBALS['public_nav'] = $active;
    page_open($title, 'site-body');
    echo '<a class="skip" href="#content">Skip to content</a>';
    echo '<header class="site-header"><div class="wrap header-row">';
    echo '<a class="brand-link" href="' . e(url('index.php')) . '">' . brand() . '</a>';
    echo '</div></header><main id="content" tabindex="-1">';
    render_flashes();
}

function public_close()
{
    echo '</main><footer class="site-footer"><div class="wrap footer-grid">';
    echo '<div>' . brand() . '<p class="disclaimer">This app organises medication reminders. It does not replace advice from a doctor, pharmacist, or nurse.</p></div>';
    echo '<div class="footer-links">';
    echo '<a href="' . e(url('index.php')) . '">Home</a>';
    echo '<a href="' . e(url('about.php')) . '">About us</a>';
    echo '<a href="' . e(url('login.php')) . '">Log in</a>';
    echo '<a href="' . e(url('register.php')) . '">Register</a>';
    echo '</div></div>';
    echo '<div class="wrap"><p class="copy">Digital Home Healthcare Ltd</p></div>';
    echo '</footer>';
    public_tabs(isset($GLOBALS['public_nav']) ? $GLOBALS['public_nav'] : '');
    page_close();
}

function public_tabs($active)
{
    $items = [
        ['key' => 'home', 'label' => 'Home', 'href' => 'index.php', 'icon' => 'home'],
        ['key' => 'about', 'label' => 'About', 'href' => 'about.php', 'icon' => 'info'],
        ['key' => 'login', 'label' => 'Log in', 'href' => 'login.php', 'icon' => 'login'],
        ['key' => 'register', 'label' => 'Register', 'href' => 'register.php', 'icon' => 'plus'],
    ];
    echo '<nav class="tabbar" aria-label="Primary">';
    foreach ($items as $item) {
        $class = $item['key'] === $active ? ' class="is-active"' : '';
        $current = $item['key'] === $active ? ' aria-current="page"' : '';
        echo '<a' . $class . $current . ' href="' . e(url($item['href'])) . '">' . icon($item['icon']) . '<span>' . e($item['label']) . '</span></a>';
    }
    echo '</nav>';
}

function app_open($title, $active)
{
    $user = current_user();
    $attrs = [];
    if ($user['role'] === 'patient') {
        $attrs['data-notify-url'] = url('notify.php');
    }
    page_open($title, 'app-body', $attrs);
    echo '<a class="skip" href="#content">Skip to content</a>';
    echo '<div class="app-frame"><div class="app-main">';
    echo '<header class="topbar"><h1>' . e($title) . '</h1>';
    echo '<a class="avatar" href="' . e(url('profile.php')) . '" aria-label="Profile">' . e(initials($user['full_name'])) . '</a></header>';
    echo '<div id="content" class="content" tabindex="-1">';
    render_flashes();
}

function app_close($active)
{
    echo '</div></div></div>';
    echo '<nav class="tabbar" aria-label="App">';
    foreach (navigation() as $item) {
        $class = $item['key'] === $active ? ' class="is-active"' : '';
        $current = $item['key'] === $active ? ' aria-current="page"' : '';
        echo '<a' . $class . $current . ' href="' . e(url($item['href'])) . '">' . icon($item['icon']) . '<span>' . e($item['label']) . '</span></a>';
    }
    echo '</nav>';
    page_close();
}

function field_error($errors, $name)
{
    $message = isset($errors[$name]) ? $errors[$name] : '';
    echo '<p class="field-error" data-error-for="' . e($name) . '">' . e($message) . '</p>';
}

function invalid_class($errors, $name)
{
    return isset($errors[$name]) ? ' is-invalid' : '';
}

function empty_state($title, $text, $href = null, $label = null)
{
    echo '<div class="empty"><h2>' . e($title) . '</h2><p>' . e($text) . '</p>';
    if ($href && $label) {
        echo '<a class="btn btn-primary" href="' . e(url($href)) . '">' . e($label) . '</a>';
    }
    echo '</div>';
}

function pill_class($status)
{
    $known = ['taken', 'skipped', 'missed', 'due', 'scheduled'];
    return in_array($status, $known, true) ? $status : 'scheduled';
}

function render_dose(array $row, $actions)
{
    $clock = format_clock($row['time']);
    echo '<article class="dose">';
    echo '<div class="when"><strong>' . e($clock['h']) . '</strong><span>' . e($clock['ampm']) . '</span></div>';
    echo '<div class="dose-body"><div class="dose-top"><h3>' . e($row['name']) . '</h3>';
    echo '<span class="pill pill-' . e(pill_class($row['status'])) . '">' . e(status_label($row['status'])) . '</span></div>';
    echo '<p>' . e($row['dosage']) . ' · ' . e($row['quantity']) . ' · ' . e($row['form']) . '</p>';
    if (!empty($row['instructions'])) {
        echo '<p class="hint">' . e($row['instructions']) . '</p>';
    }
    if ($actions && $row['date'] <= date('Y-m-d')) {
        echo '<form method="post" action="' . e(url('mark.php')) . '" class="inline-actions">';
        echo csrf_field();
        echo '<input type="hidden" name="dose_time_id" value="' . (int) $row['dose_time_id'] . '">';
        echo '<input type="hidden" name="log_date" value="' . e($row['date']) . '">';
        echo '<input type="hidden" name="return" value="' . e(current_path()) . '">';
        $takenClass = $row['status'] === 'taken' ? 'btn btn-small btn-primary' : 'btn btn-small btn-ghost';
        $skipClass = $row['status'] === 'skipped' ? 'btn btn-small btn-primary' : 'btn btn-small btn-ghost';
        echo '<button class="' . $takenClass . '" type="submit" name="status" value="taken">Taken</button>';
        echo '<button class="' . $skipClass . '" type="submit" name="status" value="skipped">Skip</button>';
        echo '</form>';
    }
    echo '</div></article>';
}

function render_dose_groups(array $rows, $actions, $emptyTitle, $emptyText, $showDates = true)
{
    if (!$rows) {
        empty_state($emptyTitle, $emptyText);
        return;
    }
    echo '<div class="dose-list">';
    if (!$showDates) {
        foreach ($rows as $row) {
            render_dose($row, $actions);
        }
        echo '</div>';
        return;
    }
    $lastDate = null;
    foreach ($rows as $row) {
        if ($row['date'] !== $lastDate) {
            if ($lastDate !== null) {
                echo '</section>';
            }
            echo '<section class="day-group"><h2>' . e(format_day($row['date'])) . '</h2>';
            $lastDate = $row['date'];
        }
        render_dose($row, $actions);
    }
    echo '</section></div>';
}

function render_summary(array $summary)
{
    echo '<div class="summary-grid">';
    $labels = [
        'taken' => 'Taken',
        'skipped' => 'Skipped',
        'missed' => 'Missed',
        'due' => 'Due now',
        'scheduled' => 'Scheduled',
    ];
    foreach ($labels as $key => $label) {
        echo '<div class="summary-card"><strong>' . (int) $summary[$key] . '</strong><span>' . e($label) . '</span></div>';
    }
    echo '</div>';
    if ($summary['adherence'] === null) {
        echo '<p class="hint">Adherence appears once a dose is taken, skipped, or missed.</p>';
    } else {
        echo '<p class="adherence">Adherence for elapsed doses: <strong>' . (int) $summary['adherence'] . '%</strong>. This is taken doses divided by taken, skipped, and missed doses.</p>';
    }
}

function render_report_table(array $rows, $includePatient)
{
    $columns = $includePatient ? 7 : 6;
    echo '<div class="table-wrap"><table class="stack"><thead><tr>';
    if ($includePatient) {
        echo '<th>Patient</th>';
    }
    echo '<th>Date</th><th>Time</th><th>Medicine</th><th>Dosage</th><th>Amount</th><th>Status</th>';
    echo '</tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td data-label="Report" colspan="' . $columns . '">No doses in this period.</td></tr>';
    }
    foreach ($rows as $row) {
        $clock = format_clock($row['time']);
        echo '<tr>';
        if ($includePatient) {
            echo '<td data-label="Patient">' . e($row['patient']) . '</td>';
        }
        echo '<td data-label="Date">' . e(format_day($row['date'])) . '</td>';
        echo '<td data-label="Time">' . e($clock['label']) . '</td>';
        echo '<td data-label="Medicine">' . e($row['name']) . '</td>';
        echo '<td data-label="Dosage">' . e($row['dosage']) . '</td>';
        echo '<td data-label="Amount">' . e($row['quantity']) . '</td>';
        echo '<td data-label="Status"><span class="pill pill-' . e(pill_class($row['status'])) . '">' . e(status_label($row['status'])) . '</span></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function render_report_screen(array $opts)
{
    echo '<p class="lede">' . e($opts['intro']) . '</p>';
    echo '<form class="filters" method="get" action="' . e(url($opts['action'])) . '">';
    echo '<div class="field"><label for="from">From</label><input id="from" type="date" name="from" value="' . e($opts['from']) . '" required></div>';
    echo '<div class="field"><label for="to">To</label><input id="to" type="date" name="to" value="' . e($opts['to']) . '" required></div>';
    if (!empty($opts['includePatient'])) {
        echo '<div class="field"><label for="patient_id">Patient</label><select id="patient_id" name="patient_id">';
        echo '<option value="all">All patients</option>';
        foreach ($opts['patients'] as $patient) {
            $selected = (string) $opts['patientId'] === (string) $patient['id'] ? ' selected' : '';
            echo '<option value="' . (int) $patient['id'] . '"' . $selected . '>' . e($patient['full_name']) . '</option>';
        }
        echo '</select></div>';
    }
    echo '<div class="field field-action"><button class="btn btn-primary" type="submit">Show report</button></div>';
    echo '</form>';

    if (!empty($opts['error'])) {
        echo '<div class="flash flash-error" role="status">' . e($opts['error']) . '</div>';
        return;
    }

    render_summary($opts['summary']);
    $base = [
        'from' => $opts['from'],
        'to' => $opts['to'],
        'scope' => $opts['scope'],
    ];
    if (!empty($opts['patientId'])) {
        $base['patient_id'] = (int) $opts['patientId'];
    }
    echo '<div class="download-row">';
    echo '<a class="btn btn-primary" href="' . e(url('download.php?' . http_build_query($base + ['type' => 'csv']))) . '">Download CSV</a>';
    echo '<a class="btn btn-secondary" href="' . e(url('download.php?' . http_build_query($base + ['type' => 'pdf']))) . '">Download PDF</a>';
    echo '</div>';

    $total = count($opts['rows']);
    $preview = array_slice($opts['rows'], 0, 40);
    if ($total > 40) {
        echo '<p class="hint">Showing 40 of ' . (int) $total . ' rows. The download includes every row.</p>';
    }
    render_report_table($preview, !empty($opts['includePatient']));
}
