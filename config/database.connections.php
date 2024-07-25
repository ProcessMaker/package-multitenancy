<?php

return [
    'landlord' => [
        'driver' => 'mysql',
        'url' => env('MULTITENANCY_DATABASE_URL'),
        'host' => env('MULTITENANCY_DB_HOST', '127.0.0.1'),
        'port' => env('MULTITENANCY_DB_PORT', '3306'),
        'database' => env('MULTITENANCY_DB_DATABASE', 'landlord'),
        'username' => env('MULTITENANCY_DB_USERNAME', 'root'),
        'password' => env('MULTITENANCY_DB_PASSWORD', ''),
        'unix_socket' => env('MULTITENANCY_DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
];
