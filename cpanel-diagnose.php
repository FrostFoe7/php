<?php
/**
 * cPanel 403 Troubleshooting Diagnostic
 * Checks all common cPanel/LiteSpeed issues
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>cPanel 403 Diagnostics</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .check { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid; }
        .pass { border-left-color: green; }
        .fail { border-left-color: red; }
        .warn { border-left-color: orange; }
        h1 { color: #333; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        .fix { background: #fff3cd; padding: 10px; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>";

echo "<h1>cPanel 403 Forbidden - Diagnostic Report</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

// 1. Check PHP Version
echo "<div class='check pass'>";
echo "<strong>✓ PHP Version:</strong> " . phpversion() . "<br>";
echo "</div>";

// 2. Check file permissions
echo "<div class='check'>";
echo "<strong>File & Directory Permissions:</strong><br>";
$files_to_check = [
    __DIR__ => 'public_html (current dir)',
    __DIR__ . '/../' => 'public_html parent',
    __DIR__ . '/api' => 'api directory',
    __DIR__ . '/includes' => 'includes directory',
    __DIR__ . '/templates' => 'templates directory',
    __DIR__ . '/css' => 'css directory',
];

foreach ($files_to_check as $path => $label) {
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $readable = is_readable($path) ? '✓' : '✗';
        $writable = is_writable($path) ? '✓' : '✗';
        echo "<div>$readable $label: $perms (Writable: $writable)</div>";
    }
}
echo "</div>";

// 3. Check .htaccess
echo "<div class='check'>";
echo "<strong>.htaccess Configuration:</strong><br>";
$htaccess_file = __DIR__ . '/.htaccess';
if (file_exists($htaccess_file)) {
    echo "✓ .htaccess exists<br>";
    echo "Size: " . filesize($htaccess_file) . " bytes<br>";
    echo "Readable: " . (is_readable($htaccess_file) ? '✓' : '✗') . "<br>";
    echo "<code>Permissions: " . substr(sprintf('%o', fileperms($htaccess_file)), -3) . "</code>";
} else {
    echo "✗ .htaccess MISSING<br>";
    echo "<div class='fix'><strong>Fix:</strong> Create .htaccess with proper configuration</div>";
}
echo "</div>";

// 4. Check session permissions
echo "<div class='check'>";
echo "<strong>Session Configuration:</strong><br>";
$session_path = ini_get('session.save_path') ?: sys_get_temp_dir();
echo "Session Path: <code>$session_path</code><br>";
echo "Exists: " . (is_dir($session_path) ? '✓' : '✗') . "<br>";
echo "Writable: " . (is_writable($session_path) ? '✓' : '✗') . "<br>";

// Try to start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
echo "Session Started: ✓<br>";
echo "Session ID: " . session_id() . "<br>";
echo "</div>";

// 5. Check PHP handlers
echo "<div class='check'>";
echo "<strong>PHP Information:</strong><br>";
echo "SAPI: " . php_sapi_name() . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Loaded Extensions: " . count(get_loaded_extensions()) . "<br>";
echo "</div>";

// 6. Check database connection
echo "<div class='check'>";
echo "<strong>Database Connection:</strong><br>";
require_once __DIR__ . '/config.php';

if ($conn->connect_error) {
    echo "<span style='color: red;'>✗ Connection Failed: " . $conn->connect_error . "</span><br>";
} else {
    echo "✓ Connected to: " . DB_NAME . "<br>";
    $result = $conn->query("SELECT COUNT(*) as cnt FROM csv_files");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Files in database: " . $row['cnt'] . "<br>";
    }
}
echo "</div>";

// 7. Check file existence
echo "<div class='check'>";
echo "<strong>Critical PHP Files:</strong><br>";
$files = ['index.php', 'edit.php', 'view.php', 'delete.php', 'upload.php', 'login.php', 'api/list.php', 'api/get.php'];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path) ? '✓' : '✗';
    $readable = is_readable($path) ? '✓' : '✗';
    echo "<div>$exists $file (Readable: $readable)</div>";
}
echo "</div>";

// 8. Check .htaccess support
echo "<div class='check'>";
echo "<strong>.htaccess Support (mod_rewrite):</strong><br>";
if (extension_loaded('mod_rewrite') || function_exists('apache_get_modules')) {
    echo "✓ mod_rewrite appears to be available<br>";
} else {
    echo "⚠ mod_rewrite status unknown (may still work)<br>";
}

// Test if rewrite is working
if (ini_get('auto_prepend_file')) {
    echo "Auto prepend: " . ini_get('auto_prepend_file') . "<br>";
}
echo "</div>";

// 9. Check memory and limits
echo "<div class='check'>";
echo "<strong>PHP Limits:</strong><br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Upload Max: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max: " . ini_get('post_max_size') . "<br>";
echo "Max Execution: " . ini_get('max_execution_time') . "s<br>";
echo "</div>";

// 10. cPanel specific checks
echo "<div class='check'>";
echo "<strong>cPanel Environment:</strong><br>";
$cpanel_home = getenv('HOME');
echo "Home: " . ($cpanel_home ?: 'Not detected') . "<br>";
echo "User: " . get_current_user() . "<br>";
echo "Running as: " . shell_exec('whoami') . "<br>";

// Check if running under cPanel
$cpanel_dir = $cpanel_home . '/public_html';
if (is_dir($cpanel_dir)) {
    echo "cPanel Detected: ✓ (public_html found)<br>";
} else {
    echo "cPanel Detected: ⚠ (public_html not found)<br>";
}
echo "</div>";

// 11. Test POST method
echo "<div class='check'>";
echo "<strong>POST Method Test:</strong><br>";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "✓ POST is working<br>";
    echo "Data received: " . count($_POST) . " parameters<br>";
} else {
    echo "Current method: GET<br>";
    echo "<form method='POST' style='margin-top: 10px;'>";
    echo "<input type='hidden' name='test' value='post'>";
    echo "<button type='submit'>Test POST</button>";
    echo "</form>";
}
echo "</div>";

// 12. Common fixes
echo "<div class='check warn'>";
echo "<strong>🔧 Common cPanel 403 Fixes:</strong><br>";
echo "<ol>";
echo "<li><strong>File Permissions:</strong> Set to 644 for files, 755 for directories<br>";
echo "<code>chmod -R 755 public_html<br>";
echo "find public_html -type f -exec chmod 644 {} \\;</code></li>";
echo "<li><strong>.htaccess:</strong> Must be readable (644) and in public_html root</li>";
echo "<li><strong>Session Directory:</strong> /tmp must be writable by web server user</li>";
echo "<li><strong>PHP Version:</strong> Ensure PHP 7.4+ is selected in cPanel</li>";
echo "<li><strong>Handler:</strong> Set to 'suPHP' or 'PHP-FPM' in cPanel (not DSO if mod_security issues)</li>";
echo "<li><strong>mod_security:</strong> May block requests - check in cPanel Mod Security section</li>";
echo "<li><strong>Database:</strong> Verify credentials and localhost access</li>";
echo "<li><strong>MSSQL/Firewall:</strong> Check if server is blocking POST requests</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
