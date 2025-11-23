# 🔧 Fixing 403 Forbidden Error - Edit Page

## Problem
When trying to edit questions, you're getting a **403 Forbidden** error from LiteSpeed Web Server.

## Root Causes (In Order of Likelihood)

### 1. **Not Logged In** ⚠️ MOST COMMON
- The edit.php page requires authentication
- **Solution**: Log in at `https://csv.mnr.world/login.php` first
- Then navigate to Dashboard → select file → Edit

### 2. **Session Issues** (PHP Application)
If you ARE logged in but still get 403:

**Check your browser:**
- Open DevTools (F12)
- Go to Application > Cookies
- Look for `PHPSESSID` cookie
- If missing or invalid, log out and log back in

**Root Cause**: Server can't create session files
- Session save path is not writable
- Temporary directory permissions are restricted

**Quick Fix**:
```php
// Add to /public_html/includes/config.php (before session_start)
session_save_path(sys_get_temp_dir());
ini_set('session.save_path', sys_get_temp_dir());
```

### 3. **CSS File Not Loading** (Asset 403)
If page loads but styling looks broken:
- The CSS file `/css/style.css` is returning 403
- This was fixed in latest version (absolute path: `/css/style.css`)
- Clear browser cache: `Ctrl+Shift+Delete` and refresh

### 4. **POST Method Blocked by LiteSpeed**
LiteSpeed might have rules blocking POST to certain PHP files:

**Check in LiteSpeed WebConsole:**
- Admin Console: https://your-server:7080
- Under "Context" → Check if .php files can accept POST
- Enable "POST" method for PHP context

**Quick Test**:
1. Visit: `https://csv.mnr.world/diagnose.php`
2. Look for "Form Submission Test" section
3. If Submit button doesn't work → POST is blocked

### 5. **File Permissions** (OS Level)
Server can't read PHP files:

```bash
# SSH to server and check permissions
ls -la /path/to/public_html/edit.php
ls -la /path/to/public_html/

# Should show something like: -rw-r--r-- (644)
# If not readable by group, fix with:
chmod 644 /path/to/public_html/*.php
chmod 755 /path/to/public_html/
```

## Diagnostic Steps

### Step 1: Check Login Status
1. Go to `https://csv.mnr.world/login.php`
2. Log in with credentials
3. Try to access `https://csv.mnr.world/edit.php?id=1`

### Step 2: Run Diagnostics
1. Visit `https://csv.mnr.world/diagnose.php`
2. Check all sections (green ✓ = good, red ✗ = problem)
3. Note which checks fail

### Step 3: Check CSS Loading
1. Open DevTools (F12) → Network tab
2. Refresh page
3. Look for `/css/style.css` 
4. If red/failed → CSS permission issue

### Step 4: Test Form Submission
1. On diagnose.php page
2. Fill "Form Submission Test" and click Submit
3. If it shows "✓ POST Submission Works" → POST is OK
4. If nothing happens → POST is blocked

## Solutions by Issue Type

### If Sessions Aren't Working:
```php
// In /public_html/includes/config.php, BEFORE session_start():

if (!session_save_path() || !is_writable(session_save_path())) {
    $temp_dir = sys_get_temp_dir();
    ini_set('session.save_path', $temp_dir);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
```

### If CSS Returns 403:
- Already fixed in codebase (absolute paths)
- But if still broken, add to `header.php`:
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Remove local CSS or fix path -->
<!-- <link rel="stylesheet" href="/css/style.css"> -->
```

### If POST is Blocked:
Contact your hosting provider (WhiteServers) and ask to:
1. Enable POST method for PHP context in LiteSpeed
2. Check if there are any .htaccess restrictions
3. Verify PHP is allowed to process form data

### If File Permissions Are Wrong:
```bash
# Contact hosting to run (or SSH if available):
chmod -R 755 /path/to/public_html
chmod -R 644 /path/to/public_html/*.php
chown -R nobody:nobody /path/to/public_html  # or web server user
```

## Quick Troubleshooting Checklist

- [ ] I'm logged in (check PHPSESSID cookie)
- [ ] I visited `/diagnose.php` and all checks passed
- [ ] CSS is loading (no red entries in Network tab)
- [ ] Form submission test works on `/diagnose.php`
- [ ] Using absolute paths for all asset links

## If All Else Fails

1. **Email Hosting Support** (WhiteServers):
   - Subject: "403 Forbidden Error on CSV Editor - POST Requests to PHP"
   - Include output from `/diagnose.php`
   - Ask them to check LiteSpeed configuration for PHP context

2. **Check Server Logs**:
   ```bash
   # LiteSpeed error log location (usually):
   /usr/local/lsws/logs/error.log
   /usr/local/lsws/logs/access.log
   
   # PHP error log:
   /var/log/php-errors.log
   tail -f /var/log/php-errors.log
   ```

3. **Temporarily Disable Problematic Features**:
   - Try editing through API instead
   - Or manually insert questions via database
   - Contact us for guidance

---

**Last Updated**: 2025-11-23  
**Version**: Course MNR World v2.0  
**Affected File**: `/public_html/edit.php`  
**Impact**: Question editing functionality
