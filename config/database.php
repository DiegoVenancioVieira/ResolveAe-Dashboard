<?php
return [
    'host'     => '192.168.0.105',
    'port'     => '3306',
    'database' => 'dbase',
    'username' => 'dbase',
    'password' => 'dbase',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
