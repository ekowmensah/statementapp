# Daily Statement App

A production-ready web application for managing daily financial transactions with automated cascade calculations. Built with pure PHP 8.2+, MySQL 8, and CoreUI/Bootstrap 5.

## Features

- **Daily Transaction Management**: Create, edit, and view daily transactions (CA, GA, JE inputs)
- **Automated Calculations**: Real-time computation of AG1, AV1, AG2, AV2, RE, and FI values
- **Rate Management**: Manage effective rates (AG1%, AG2%) with date-based application
- **Month Locking**: Lock months to prevent accidental edits after closing periods
- **Statement Views**: Comprehensive monthly statement views with totals
- **Reports & Analytics**: Charts and reports with date range filtering
- **Export Functionality**: CSV and PDF export capabilities
- **Role-Based Access**: Admin, Accountant, and Viewer roles with appropriate permissions
- **Security Features**: CSRF protection, input validation, and secure authentication

## Technology Stack

- **Backend**: PHP 8.2+ (pure PHP, no frameworks)
- **Database**: MySQL 8.0+ with InnoDB engine
- **Frontend**: Bootstrap 5 + CoreUI for modern UI
- **Charts**: Chart.js for data visualization
- **Architecture**: MVC pattern with clean separation of concerns

## Installation

### Prerequisites

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx) or XAMPP for local development
- Composer (optional, for future enhancements)

### Step 1: Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE daily_statement_app
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u your_username -p daily_statement_app < sql/schema.sql
```

3. Import seed data (optional but recommended):
```bash
mysql -u your_username -p daily_statement_app < sql/seed.sql
```

### Step 2: Configuration

1. Copy the configuration template:
```bash
cp config/config.example.php config/config.php
```

2. Edit `config/config.php` with your database credentials:
```php
'database' => [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'daily_statement_app',
    'username' => 'your_db_user',
    'password' => 'your_db_password',
    // ... other settings
]
```

### Step 3: Web Server Configuration

#### For XAMPP (Development)
1. Place the project in `htdocs/accountstatement/`
2. Access via `http://localhost/accountstatement/public/`

#### For Apache (Production)
Configure your virtual host to point to the `public/` directory:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/project/public
    
    <Directory /path/to/project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### For Nginx (Production)
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Step 4: Set Permissions (Linux/Mac)
```bash
chmod -R 755 /path/to/project
chmod -R 777 /path/to/project/storage/logs  # If you create a logs directory
```

## Default Credentials

After importing the seed data, you can log in with:

- **Email**: `admin@example.com`
- **Password**: `admin123`

**⚠️ Important**: Change the default password immediately after first login!

## Usage Guide

### 1. Dashboard
- View monthly KPIs (MTD CA, FI, GA, JE)
- See daily FI trend chart
- Monitor recent transactions and alerts

### 2. Daily Transactions
- **Create**: Add new daily transactions with CA, GA, JE inputs
- **Live Preview**: See computed values (AG1, AV1, AG2, AV2, RE, FI) in real-time
- **Edit/Delete**: Modify existing transactions (if month not locked)
- **Validation**: Automatic validation and error handling

### 3. Rate Management
- **Create Rates**: Set AG1% and AG2% rates with effective dates
- **History**: View rate change history
- **Validation**: Ensure rates are between 0-100% and dates are unique

### 4. Statement View
- **Monthly View**: Complete statement with all computed columns
- **Totals**: Automatic calculation of monthly totals
- **Filtering**: Filter by month/year
- **Export**: CSV and PDF export options

### 5. Month Locking
- **Lock Months**: Prevent edits to closed periods (Admin only)
- **Protection**: Database triggers prevent data modification
- **Unlock**: Remove locks if needed (Admin only)

### 6. Reports
- **Date Range**: Custom date range reporting
- **Metrics**: Choose from CA, FI, JE, GA metrics
- **Grouping**: Group by day, week, or month
- **Charts**: Visual representation with Chart.js

## Business Logic

### Calculation Cascade
The application implements the following calculation sequence:

1. **AG1** = CA × AG1_Rate
2. **AV1** = CA - AG1
3. **AG2** = AV1 × AG2_Rate
4. **AV2** = AV1 - AG2
5. **RE** = AV2 - GA
6. **FI** = RE - JE

### Rate Application
- Rates are applied based on the transaction date
- The system uses the most recent rate where `effective_on <= transaction_date`
- Historical transactions automatically use the correct rate for their date

### Month Locking
- Locked months prevent INSERT, UPDATE, DELETE operations on transactions
- Implemented via MySQL triggers for data integrity
- Only users with 'admin' role can manage locks

## Security Features

- **Authentication**: Session-based authentication with role management
- **CSRF Protection**: All forms protected with CSRF tokens
- **Input Validation**: Server-side validation for all inputs
- **SQL Injection Prevention**: PDO prepared statements throughout
- **XSS Protection**: All output properly escaped
- **Password Security**: Bcrypt hashing for passwords
- **Role-Based Access**: Granular permissions system

## User Roles

### Admin
- Full access to all features
- User management (future feature)
- Month lock management
- System settings

### Accountant
- Create, edit, delete daily transactions
- Manage rates
- View all reports and statements
- Export data

### Viewer
- View-only access to transactions, statements, and reports
- Export capabilities
- No create/edit permissions

## API Endpoints

The application includes several API endpoints for AJAX functionality:

- `POST /api/preview` - Real-time calculation preview
- `GET /api/rates/effective` - Get effective rate for a date
- `GET /api/dashboard/kpis` - Dashboard KPI data
- `GET /api/dashboard/chart` - Chart data for dashboard

## File Structure

```
project/
├── public/
│   ├── index.php              # Front controller
│   └── assets/                # Static assets (CSS/JS/images)
├── app/
│   ├── Controllers/           # Application controllers
│   ├── Models/               # Data models
│   ├── Views/                # View templates
│   └── Helpers/              # Utility classes
├── config/
│   ├── config.php            # Application configuration
│   ├── db.php                # Database connection
│   └── routes.php            # Route definitions
├── sql/
│   ├── schema.sql            # Database schema
│   └── seed.sql              # Sample data
└── README.md
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/config.php`
   - Ensure MySQL service is running
   - Verify database exists and user has proper permissions

2. **Permission Denied Errors**
   - Check file permissions (755 for directories, 644 for files)
   - Ensure web server has read access to project files

3. **CSRF Token Errors**
   - Ensure sessions are working properly
   - Check that forms include CSRF tokens
   - Verify session storage is writable

4. **Calculation Errors**
   - Verify rates exist for transaction dates
   - Check that rates are within valid range (0-1)
   - Ensure proper decimal precision in database

### Debug Mode

Enable debug mode in `config/config.php`:
```php
'app' => [
    'debug' => true,
    // ...
]
```

This will show detailed error messages and stack traces.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is proprietary software. All rights reserved.

## Support

For support or questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Ensure all prerequisites are met

## Changelog

### Version 1.0.0
- Initial release
- Core transaction management functionality
- Rate management system
- Month locking feature
- Dashboard and reporting
- Export capabilities
- Role-based access control

---

**Note**: This application replaces an Excel-based workflow and provides a robust, web-based solution for daily statement management with automated calculations and proper data integrity controls.
