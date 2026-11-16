<?php

if (!defined('MEDAPP')) {
    http_response_code(403);
    exit;
}

function db()
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = $GLOBALS['config']['db'];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $config['name'])) {
        throw new RuntimeException('The database name in config.php is not valid.');
    }

    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config['host'], (int) $config['port']);
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $config['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . $config['name'] . '`');

    $offset = (new DateTime('now'))->format('P');
    $pdo->exec('SET time_zone = ' . $pdo->quote($offset));

    ensure_ready($pdo);
    return $pdo;
}

function ensure_ready(PDO $pdo)
{
    $exists = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if (!$exists) {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'schema.sql';
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('The database schema file is missing.');
        }
        run_sql_script($pdo, $sql);
    }
    seed_admin($pdo);
}

function run_sql_script(PDO $pdo, $sql)
{
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (explode(';', $sql) as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }
}

function seed_admin(PDO $pdo)
{
    $lookup = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $lookup->execute(['admin']);
    if ($lookup->fetch()) {
        return;
    }

    // Case-study staff login: username admin, password admin.
    // Passwords created or changed in a form must still be at least 6 characters.
    $insert = $pdo->prepare(
        'INSERT INTO users (full_name, username, email, phone, password_hash, role)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    try {
        $insert->execute([
            'System Administrator',
            'admin',
            'admin@digitalhomehealthcare.test',
            '0200000000',
            password_hash('admin', PASSWORD_DEFAULT),
            'admin',
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') {
            throw $e;
        }
    }
}
