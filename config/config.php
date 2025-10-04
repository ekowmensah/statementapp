<?php
/**
 * Daily Statement App - Local Configuration
 * This file contains your local environment settings
 */

return [
    // Database Configuration
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'accounts',
        'username' => 'root',
        'password' => '', // Default XAMPP MySQL password
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
        'base_path' => '/accountstatement/public',
        'timezone' => 'America/New_York',
        'debug' => true,
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

    // File Upload
    'upload' => [
        'max_size' => 5242880, // 5MB in bytes
        'allowed_types' => ['csv', 'xlsx', 'pdf'],
        'upload_path' => __DIR__ . '/../storage/uploads/',
    ],

    // Email
    'mail' => [
        'driver' => 'smtp',
        'host' => 'localhost',
        'port' => 1025,
        'username' => '',
        'password' => '',
        'encryption' => null,
        'from' => [
            'address' => 'noreply@localhost',
            'name' => 'Daily Statement App'
        ]
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'path' => __DIR__ . '/../storage/logs/',
    ]
];
