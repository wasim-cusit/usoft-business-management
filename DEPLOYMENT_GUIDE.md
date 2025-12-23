# Deployment Guide - Live Server Configuration

## ✅ Changes Made for Live Server Deployment

### 1. Dynamic BASE_URL Detection
The `BASE_URL` in `config/config.php` now automatically detects:
- **Protocol**: HTTP or HTTPS (based on server configuration)
- **Host**: Automatically uses the domain name (e.g., `yourdomain.com` or `localhost`)
- **Path**: Automatically detects the project folder path

### 2. Updated Files

#### `config/config.php`
- ✅ BASE_URL now auto-detects from server environment
- ✅ Works on both localhost and live server
- ✅ No manual configuration needed

#### `config/language.php`
- ✅ Language redirects updated to work with any server path
- ✅ Uses relative paths for better compatibility

#### `.htaccess`
- ✅ RewriteBase commented out (auto-detected)
- ✅ Error pages commented out (can be enabled if needed)

## 🚀 How It Works

### On Localhost:
- URL: `http://localhost/usoft/`
- BASE_URL automatically detected as: `http://localhost/usoft/`

### On Live Server:
- URL: `https://yourdomain.com/` (if in root)
- BASE_URL automatically detected as: `https://yourdomain.com/`

- URL: `https://yourdomain.com/usoft/` (if in subfolder)
- BASE_URL automatically detected as: `https://yourdomain.com/usoft/`

## 📝 Before Uploading to Live Server

### 1. Database Configuration
Update `config/database.php` if your database is on a different server:
```php
define('DB_HOST', 'localhost'); // Change if database is on different server
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_database_name');
```

### 2. Error Reporting (Optional)
In `config/config.php`, you may want to disable error display for production:
```php
error_reporting(0);
ini_set('display_errors', 0);
```

### 3. .htaccess Configuration
- The `.htaccess` file is now flexible and should work on most servers
- If you encounter rewrite issues, you can uncomment and set `RewriteBase` manually

## ✅ Testing After Upload

1. **Test Login**: Visit your login page and verify redirects work
2. **Test Navigation**: Click through different pages to ensure all redirects work
3. **Test Language Switch**: Try switching between Urdu and English
4. **Test Forms**: Submit forms and verify redirects after submission

## 🔧 Troubleshooting

### If redirects still go to localhost:
1. Clear browser cache
2. Check that `config/config.php` was uploaded correctly
3. Verify PHP version supports `__FILE__` and `dirname()`

### If BASE_URL is incorrect:
1. Check server logs for errors
2. Verify `$_SERVER['DOCUMENT_ROOT']` is set correctly
3. You can temporarily add this to see detected BASE_URL:
   ```php
   echo BASE_URL; // Remove after testing
   ```

## 📌 Notes

- All redirects now use `BASE_URL` constant
- No hardcoded URLs remain in the codebase
- Works automatically on any server configuration
- Supports both HTTP and HTTPS
- Works in root directory or subfolder

