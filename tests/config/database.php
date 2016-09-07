<?php
return array(
    'default' => 'testing',
    'connections' => array(
        'testing' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'localhost'),
            'database'  => env('DB_DATABASE', 'follower_test'),
            'username'  => env('DB_USERNAME', 'travis'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],
    ),
);
