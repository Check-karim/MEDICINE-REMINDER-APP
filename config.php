<?php

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'medicine_reminder',
        'user' => 'root',
        'pass' => '',
    ],
    // UTC+2, no daylight-saving change. Edit this if the app clock should follow another region.
    'timezone' => 'Africa/Johannesburg',
    'password_min' => 6,
    'due_grace_minutes' => 60,
    'debug' => false,
];
