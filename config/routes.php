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
    'GET /daily/show-by-date' => 'DailyTxnController@showByDate',
    'POST /daily/delete' => 'DailyTxnController@delete',

    // Companies
    'GET /companies' => 'CompanyController@index',
    'GET /companies/create' => 'CompanyController@create',
    'POST /companies/create' => 'CompanyController@store',
    'GET /companies/edit' => 'CompanyController@edit',
    'POST /companies/edit' => 'CompanyController@update',
    'GET /companies/show' => 'CompanyController@show',
    'POST /companies/delete' => 'CompanyController@delete',
    'POST /companies/toggle-active' => 'CompanyController@toggleActive',

    // Statement Views
    'GET /statement' => 'StatementController@index',
    'GET /statement/generate' => 'StatementController@generate',
    'GET /statement/test' => 'StatementController@test',
    'GET /statement/monthly' => 'StatementController@monthly',

    // Reports
    'GET /reports' => 'ReportsController@index',
    'GET /reports/consolidated' => 'ReportsController@consolidated',
    'GET /reports/export-consolidated' => 'ReportsController@exportConsolidated',
    'GET /reports/data' => 'ReportsController@getData',
    'GET /reports/test' => 'ReportsController@test',
    'GET /reports/api-test' => 'ReportsController@apiTest',

    // Month Locks
    'GET /locks' => 'MonthLockController@index',
    'POST /locks/lock' => 'MonthLockController@lock',
    // Export functionality
    'GET /export/csv' => 'ExportController@csv',
    'GET /export/pdf' => 'ExportController@pdf',

    // User management routes
    'GET /users' => 'UserController@index',
    'GET /users/create' => 'UserController@create',
    'POST /users/store' => 'UserController@store',
    'GET /users/edit' => 'UserController@edit',
    'POST /users/update' => 'UserController@update',
    'POST /users/delete' => 'UserController@delete',
    'POST /users/toggle-active' => 'UserController@toggleActive',
    'GET /users/profile' => 'UserController@profile',
    'POST /users/update-profile' => 'UserController@updateProfile',

    // Role management routes
    'GET /roles' => 'RoleController@index',
    'GET /roles/create' => 'RoleController@create',
    'POST /roles/store' => 'RoleController@store',
    'GET /roles/edit' => 'RoleController@edit',
    'POST /roles/update' => 'RoleController@update',
    'POST /roles/delete' => 'RoleController@delete',
    'GET /roles/edit-permissions' => 'RoleController@editPermissions',
    'POST /roles/update-permissions' => 'RoleController@updatePermissions',

    // API endpoints
    'POST /api/preview' => 'DailyTxnController@preview',
    'GET /api/rates/effective' => 'RatesController@getEffectiveRate',
    'GET /api/dashboard/kpis' => 'DashboardController@getKpis',
    'GET /api/dashboard/chart' => 'DashboardController@getChartData',
    'GET /api/user/check-updates' => 'UserController@checkUpdates',
    'GET /api/companies' => 'CompanyController@api',
    'GET /api/companies/{id}/usage' => 'CompanyController@show',
    'GET /api/companies/{id}/transactions' => 'DailyTxnController@getByDateRange',

    // Error pages
    'GET /403' => 'ErrorController@forbidden',
    'GET /404' => 'ErrorController@notFound',
    'GET /500' => 'ErrorController@serverError',
];
