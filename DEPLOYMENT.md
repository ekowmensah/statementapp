# Daily Statement App - Deployment Guide

This guide will help you deploy the Daily Statement App to a production server.

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependency management if needed)

## Deployment Steps

### 1. Upload Files

Upload all application files to your web server. The structure should be:

```
your-domain/
├── public/           # Web root (point your domain here)
│   ├── index.php
│   ├── .htaccess
│   └── assets/
├── app/
├── config/
├── storage/
└── sql/
```

### 2. Configure Web Server

#### Apache Configuration

Point your domain's document root to the `public/` directory.

Example Apache virtual host:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/your/app/public
    
    <Directory /path/to/your/app/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. Database Setup

1. Create a MySQL database for the application
2. Import the database schema:
   ```bash
   mysql -u username -p database_name < sql/schema.sql
   ```
3. Optionally import sample data:
   ```bash
   mysql -u username -p database_name < sql/insert_sample_data.sql
   ```

### 4. Configuration

#### Option A: Using Environment Variables (Recommended)

1. Copy `.env.production` to `.env`
2. Update the values in `.env` with your production settings:

```env
# Application Settings
APP_URL=https://yourdomain.com
APP_BASE_PATH=
APP_DEBUG=false

# Database Configuration
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
```

#### Option B: Direct Configuration

1. Copy `config/config.production.php` to `config/config.php`
2. Update the configuration values directly in the file

### 5. Hosting Scenarios

#### Scenario 1: Root Domain (yourdomain.com)
```php
'app' => [
    'url' => 'https://yourdomain.com',
    'base_path' => '',
]
```

#### Scenario 2: Subdomain (statements.yourdomain.com)
```php
'app' => [
    'url' => 'https://statements.yourdomain.com',
    'base_path' => '',
]
```

#### Scenario 3: Subdirectory (yourdomain.com/statements)
```php
'app' => [
    'url' => 'https://yourdomain.com/statements',
    'base_path' => '/statements',
]
```

### 6. File Permissions

Set proper permissions for storage directories:

```bash
chmod -R 755 storage/
chmod -R 755 storage/logs/
chmod -R 755 storage/uploads/
```

### 7. Security Considerations

1. **Environment File**: Ensure `.env` is not accessible via web
2. **Debug Mode**: Set `APP_DEBUG=false` in production
3. **Database**: Use a dedicated database user with minimal privileges
4. **HTTPS**: Always use HTTPS in production
5. **File Permissions**: Restrict file permissions appropriately

### 8. Testing the Deployment

1. Visit your domain in a web browser
2. You should see the login page
3. Test login functionality
4. Check that all pages load correctly
5. Verify database connections work
6. Test file uploads if applicable

### 9. Common Issues and Solutions

#### Issue: "Page not found" errors
**Solution**: Check that your web server is configured to route all requests through `public/index.php`

#### Issue: Database connection errors
**Solution**: Verify database credentials and ensure the database server is accessible

#### Issue: CSS/JS not loading
**Solution**: Check that the `APP_URL` and `APP_BASE_PATH` are configured correctly

#### Issue: Permission denied errors
**Solution**: Check file permissions on storage directories

### 10. Maintenance

#### Backup
- Regularly backup your database
- Backup uploaded files in `storage/uploads/`
- Backup configuration files

#### Updates
- Always test updates in a staging environment first
- Backup before applying updates
- Check logs after updates

#### Monitoring
- Monitor error logs in `storage/logs/`
- Set up database monitoring
- Monitor disk space usage

## Support

If you encounter issues during deployment, check:

1. Web server error logs
2. Application logs in `storage/logs/`
3. PHP error logs
4. Database connection status

For additional support, refer to the application documentation or contact your system administrator.
