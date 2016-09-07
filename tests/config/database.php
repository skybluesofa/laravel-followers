<?php
return array(
    'default' => 'testing',
    'connections' => array(
        'testing' => [
            'driver'    => 'mysql',
            'host'      => env('DB_TEST_HOST', 'localhost'),
            'database'  => env('DB_TEST_DATABASE', 'follower_test'),
            'username'  => env('DB_TEST_USERNAME', 'travis'),
            'password'  => env('DB_TEST_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],
    ),
);
