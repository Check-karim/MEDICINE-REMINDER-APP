<?php

if (!defined('MEDAPP')) {
    http_response_code(403);
    exit;
}

function valid_name($name)
{
    if (!is_string($name)) {
        return false;
    }
    $name = normalize_name($name);
    $length = strlen($name);
    if ($length < 2 || $length > 80) {
        return false;
    }
    return (bool) preg_match('/^[A-Za-z]+( [A-Za-z]+)*$/', $name);
}

function normalize_name($name)
{
    $name = trim((string) $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return $name === null ? '' : $name;
}

function valid_username($username)
{
    if (!is_string($username)) {
        return false;
    }
    $length = strlen($username);
    if ($length < 3 || $length > 30) {
        return false;
    }
    if (preg_match('/\s/', $username)) {
        return false;
    }
    return (bool) preg_match('/^[A-Za-z][A-Za-z0-9._-]*$/', $username);
}

function valid_email($email)
{
    if (!is_string($email)) {
        return false;
    }
    $email = normalize_email($email);
    if ($email === '' || strlen($email) > 190) {
        return false;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return (bool) preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email);
}

function normalize_email($email)
{
    return strtolower(trim((string) $email));
}

function normalize_phone($phone)
{
    $phone = trim((string) $phone);
    if ($phone === '' || !preg_match('/^[0-9+\s().-]+$/', $phone)) {
        return null;
    }
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === null || strlen($digits) < 10 || strlen($digits) > 15) {
        return null;
    }
    if (strpos($phone, '+') === 0) {
        return '+' . $digits;
    }
    return $digits;
}

function valid_password($password)
{
    if (!is_string($password)) {
        return false;
    }
    $length = strlen($password);
    if ($length < PASSWORD_MIN || $length > 72) {
        return false;
    }
    return trim($password) !== '';
}

function valid_date($value)
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return false;
    }
    return $value >= '2000-01-01' && $value <= '2100-12-31';
}

function normalize_time($value)
{
    $value = trim((string) $value);
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
        return $value . ':00';
    }
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value)) {
        return $value;
    }
    return null;
}

function valid_medicine_name($name)
{
    if (!is_string($name)) {
        return false;
    }
    $length = strlen($name);
    if ($length < 2 || $length > 120) {
        return false;
    }
    if (!preg_match('/[A-Za-z]/', $name)) {
        return false;
    }
    return (bool) preg_match("/^[A-Za-z0-9][A-Za-z0-9 .()'\/+,&%-]*$/", $name);
}

function valid_dosage($value)
{
    if (!is_string($value)) {
        return false;
    }
    $length = strlen($value);
    if ($length < 1 || $length > 60) {
        return false;
    }
    return (bool) preg_match('/^[A-Za-z0-9 .()\/+%\'-]+$/', $value);
}

function valid_quantity($value)
{
    if (!is_string($value)) {
        return false;
    }
    $length = strlen($value);
    if ($length < 1 || $length > 40) {
        return false;
    }
    return (bool) preg_match('/^[A-Za-z0-9 .\/%-]+$/', $value);
}

function valid_instructions($value)
{
    if ($value === null || $value === '') {
        return true;
    }
    if (!is_string($value) || strlen($value) > 240) {
        return false;
    }
    return (bool) preg_match('/^[A-Za-z0-9 .,;:()\'"\/+%!?-]+$/', $value);
}

function days_string(array $indexes)
{
    $flags = array_fill(0, 7, '0');
    foreach ($indexes as $index) {
        $index = (int) $index;
        if ($index >= 0 && $index <= 6) {
            $flags[$index] = '1';
        }
    }
    $value = implode('', $flags);
    if (strpos($value, '1') === false) {
        return null;
    }
    return $value;
}

function parse_date_range($from, $to)
{
    if (!valid_date($from) || !valid_date($to)) {
        return [null, null, 'Choose a valid date range.'];
    }
    if ($from > $to) {
        return [null, null, 'The start date must be on or before the end date.'];
    }
    $start = DateTime::createFromFormat('Y-m-d', $from);
    $end = DateTime::createFromFormat('Y-m-d', $to);
    if ($start->diff($end)->days + 1 > 366) {
        return [null, null, 'Choose a range of 366 days or fewer.'];
    }
    return [$from, $to, null];
}

function medicine_forms()
{
    return ['Tablet', 'Capsule', 'Liquid', 'Drops', 'Inhaler', 'Injection', 'Cream', 'Other'];
}

function weekday_names()
{
    return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
}
