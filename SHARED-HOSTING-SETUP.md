# Shared Hosting Setup Guide

## Quick Setup Steps

### 1. Upload Files
Upload all files to your shared hosting account:
- **cPanel**: Use File Manager or FTP
- **Upload to**: `public_html/` or your domain's document root

### 2. Configure Domain Settings

**Option A: Using .env file (Recommended)**
1. Copy `.env.shared-hosting` to `.env`
2. Update these values in `.env`:

```env
# For root domain (yourdomain.com)
APP_URL=https://yourdomain.com
APP_BASE_PATH=

# For subdirectory (yourdomain.com/statements)
APP_URL=https://yourdomain.com/statements
APP_BASE_PATH=/statements
```

**Option B: Direct config edit**
Edit `config/config.php` and change the fallback values:
```php
'url' => $_ENV['APP_URL'] ?? 'https://yourdomain.com',
'base_path' => $_ENV['APP_BASE_PATH'] ?? '',
```

### 3. Database Setup
1. Create MySQL database in cPanel
2. Update `.env` with database credentials:
```env
DB_HOST=localhost
DB_NAME=your_db_name
DB_USER=your_db_user
DB_PASS=your_db_password
```

### 4. Import Database
1. Go to phpMyAdmin in cPanel
2. Import `sql/schema.sql`
3. Import `sql/insert_sample_data.sql` (optional)

### 5. Set File Permissions
Set these permissions via File Manager:
- `storage/` folder: 755
- `storage/logs/` folder: 755
- `storage/uploads/` folder: 755

### 6. Test Your Setup
Visit your domain - it should redirect properly and show the login page.

## Common Shared Hosting Scenarios

### Scenario 1: Root Domain
**Setup:**
- Domain points to your account root
- App files in `public_html/`
- Access: `https://yourdomain.com`

**Configuration:**
```env
APP_URL=https://yourdomain.com
APP_BASE_PATH=
```

### Scenario 2: Subdirectory
**Setup:**
- App files in `public_html/statements/`
- Access: `https://yourdomain.com/statements`

**Configuration:**
```env
APP_URL=https://yourdomain.com/statements
APP_BASE_PATH=/statements
```

### Scenario 3: Subdomain
**Setup:**
- Create subdomain `statements.yourdomain.com`
- Point subdomain to app folder
- Access: `https://statements.yourdomain.com`

**Configuration:**
```env
APP_URL=https://statements.yourdomain.com
APP_BASE_PATH=
```

## Troubleshooting

### Issue: 404 errors
**Solution:** Check that `.htaccess` files are uploaded and working

### Issue: Database connection errors
**Solution:** Verify database credentials in `.env`

### Issue: Wrong URLs in links
**Solution:** Verify `APP_URL` and `APP_BASE_PATH` in `.env`

### Issue: Permission denied
**Solution:** Set proper file permissions (755 for folders, 644 for files)

## Security Notes

1. **Hide .env file:** Most shared hosts automatically protect `.env` files
2. **Use HTTPS:** Always use `https://` in `APP_URL`
3. **Set debug to false:** `APP_DEBUG=false` in production
4. **Strong database password:** Use a secure database password

## Support

If you need help:
1. Check your hosting provider's documentation
2. Contact your hosting support for server-specific issues
3. Use the test script: `yourdomain.com/test-redirect.php`
