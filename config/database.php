<?php
return [
    'media' => [
        'driver'         => 'mysql',
        'url'            => env('DATABASE_URL'),
        'host'           => 'media.haxibiao.com',
        'port'           => '3306',
        'database'       => 'media',
        'username'       => 'root',
        'password'       => env('DB_PASSWORD_MEDIA', env('DB_PASSWORD')),
        'unix_socket'    => env('DB_SOCKET', ''),
        'charset'        => 'utf8mb4',
        'collation'      => 'utf8mb4_unicode_ci',
        'prefix'         => '',
        'prefix_indexes' => true,
        'strict'         => false,
        'engine'         => null,
        'options'        => [
            PDO::ATTR_PERSISTENT => true,
        ],
    ],
];
