<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('\\', '/', $path);
if ($path === false || $path === '') {
    $path = '/';
}

$blocked = ($path === '/config.php' || $path === '/router.php'
    || strpos($path, '/includes/') === 0
    || strpos($path, '/sql/') === 0);

if ($blocked) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

$full = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);

if ($path !== '/' && is_dir($full)) {
    $index = $full . DIRECTORY_SEPARATOR . 'index.php';
    if (!is_file($index)) {
        http_response_code(403);
        echo 'Forbidden';
        return true;
    }
    return false;
}

if ($path !== '/' && !is_file($full)) {
    require __DIR__ . '/not-found.php';
    return true;
}

return false;
