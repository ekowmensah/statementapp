<?php
/**
 * Application Routes Configuration
 * Maps URL patterns to Controller actions
 */

return [
    // Authentication routes
    'GET|POST /login' => 'AuthController@login',
    'POST /logout' => 'AuthController@logout',
    'GET /logout' => 'AuthController@logoutGet',

    // Dashboard
    'GET /' => 'DashboardController@index',
    'GET /dashboard' => 'DashboardController@index',

    // Daily Transactions
    'GET /daily' => 'DailyTxnController@index',
    'GET /daily/create' => 'DailyTxnController@create',
    'POST /daily/create' => 'DailyTxnController@store',
    'GET /daily/edit' => 'DailyTxnController@edit',
    'POST /daily/edit' => 'DailyTxnController@update',
    'GET /daily/show' => 'DailyTxnController@show',
    'POST /daily/delete' => 'DailyTxnController@delete',


    // Statement Views
    'GET /statement' => 'StatementController@index',
    'GET /statement/generate' => 'StatementController@generate',
    'GET /statement/test' => 'StatementController@test',
    'GET /statement/monthly' => 'StatementController@monthly',

    // Reports
    'GET /reports' => 'ReportsController@index',
    'GET /reports/data' => 'ReportsController@getData',
    'GET /reports/test' => 'ReportsController@test',
    'GET /reports/api-test' => 'ReportsController@apiTest',

    // Month Locks
    'GET /locks' => 'MonthLockController@index',
    'POST /locks/lock' => 'MonthLockController@lock',
    'POST /locks/unlock' => 'MonthLockController@unlock',

    // Export functionality
    'GET /export/csv' => 'ExportController@csv',
    'GET /export/pdf' => 'ExportController@pdf',

    // User Management
    'GET /users' => 'UserController@index',
    'GET /users/create' => 'UserController@create',
    'POST /users/store' => 'UserController@store',
    'GET /users/edit' => 'UserController@edit',
    'POST /users/update' => 'UserController@update',
    'POST /users/delete' => 'UserController@delete',
    'POST /users/toggle-active' => 'UserController@toggleActive',
    'GET /users/profile' => 'UserController@profile',
    'POST /users/update-profile' => 'UserController@updateProfile',

    // API endpoints
    'POST /api/preview' => 'DailyTxnController@preview',
    'GET /api/rates/effective' => 'RatesController@getEffectiveRate',
    'GET /api/dashboard/kpis' => 'DashboardController@getKpis',
    'GET /api/dashboard/chart' => 'DashboardController@getChartData',
    'GET /api/user/check-updates' => 'UserController@checkUpdates',

    // Error pages
    'GET /403' => 'ErrorController@forbidden',
    'GET /404' => 'ErrorController@notFound',
    'GET /500' => 'ErrorController@serverError',
];
