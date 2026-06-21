<?php

declare(strict_types=1);

return [
    'installed' => false,
    'app' => [
        'name' => 'LedgerFlow',
        'url' => 'https://example.com',
        'environment' => 'production',
        'debug' => false,
        'timezone' => 'UTC',
        'key' => 'replace-with-installer-generated-key',
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => '',
        'user' => '',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'transport' => 'mail',
        'host' => '',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_email' => 'billing@example.com',
        'from_name' => 'LedgerFlow',
    ],
    'security' => [
        'session_name' => 'ledgerflow_session',
        'login_attempts' => 5,
        'login_decay_minutes' => 15,
        'max_upload_mb' => 2,
    ],
];
