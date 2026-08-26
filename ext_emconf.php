<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Database Migrations',
    'description' => 'Versioned database content migrations via doctrine/migrations, runnable in deployments',
    'category' => 'misc',
    'author' => 'Michael Straschek',
    'author_email' => 'm@straschek.io',
    'state' => 'alpha',
    'version' => '0.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-13.4.99',
        ],
    ],
];
