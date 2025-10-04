<?php
/**
 * Daily Statement App - Production Configuration Template
 * Copy this file to config.php and update with your production settings
 */

return [
    // Database Configuration
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'database' => $_ENV['DB_NAME'] ?? 'accounts',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
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
        // For production, set these based on your hosting environment:
        // Example for subdomain: https://statements.yourdomain.com
        'url' => $_ENV['APP_URL'] ?? 'https://yourdomain.com',
        'base_path' => $_ENV['APP_BASE_PATH'] ?? '',
        
        // Example for subdirectory: https://yourdomain.com/statements
        // 'url' => 'https://yourdomain.com/statements',
        // 'base_path' => '/statements',
        
        // Example for root domain: https://yourdomain.com
        // 'url' => 'https://yourdomain.com',
        // 'base_path' => '',
        
        'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/New_York',
        'debug' => $_ENV['APP_DEBUG'] ?? false, // Set to false in production
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
        'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
        'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
        'port' => $_ENV['MAIL_PORT'] ?? 587,
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@yourdomain.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Daily Statement App'
        ]
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'level' => $_ENV['LOG_LEVEL'] ?? 'error', // Use 'error' in production
        'path' => __DIR__ . '/../storage/logs/',
    ]
];
