<?php
/**
 * Daily Statement App - Configuration Template
 * Copy this file to config.php and update with your settings
 */

return [
    // Database Configuration
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'daily_statement_app',
        'username' => 'your_db_user',
        'password' => 'your_db_password',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],

    // Application Settings
    'app' => [
        'name' => 'Daily Statement App',
        'url' => 'http://localhost/accountstatement/public',
        'timezone' => 'America/New_York',
        'debug' => true, // Set to false in production
    ],

    // Security Settings
    'security' => [
        'csrf_token_name' => '_token',
        'session_name' => 'daily_statement_session',
        'session_lifetime' => 7200, // 2 hours in seconds
        'password_min_length' => 8,
    ],

    // Pagination
    'pagination' => [
        'per_page' => 25,
        'max_per_page' => 100,
    ],

    // File Upload (for future features)
    'upload' => [
        'max_size' => 5242880, // 5MB in bytes
        'allowed_types' => ['csv', 'xlsx', 'pdf'],
        'upload_path' => __DIR__ . '/../storage/uploads/',
    ],

    // Email (for future notifications)
    'mail' => [
        'driver' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'your_email@example.com',
        'password' => 'your_email_password',
        'encryption' => 'tls',
        'from' => [
            'address' => 'noreply@example.com',
            'name' => 'Daily Statement App'
        ]
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'path' => __DIR__ . '/../storage/logs/',
    ]
];
