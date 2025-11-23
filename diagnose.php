<?php
/**
 * DIAGNOSTIC PAGE - Debug 403 Forbidden Issues
 * Place at: /public_html/diagnose.php
 */

require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>Diagnosis Report</title>";
echo "<style>
  body { font-family: monospace; background: #f5f5f5; padding: 20px; }
  .check { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid; }
  .pass { border-left-color: green; }
  .fail { border-left-color: red; }
  .warn { border-left-color: orange; }
  h1 { color: #333; }
  .code { background: #f0f0f0; padding: 10px; border-radius: 5px; font-size: 12px; }
</style></head><body>";

echo "<h1>🔍 Course MNR World - Diagnostic Report</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

// 1. PHP Version
echo "<div class='check pass'>";
echo "<strong>✓ PHP Version:</strong> " . phpversion() . "<br>";
echo "</div>";

// 2. Session Configuration
echo "<div class='check " . (ini_get('session.save_path') ? 'pass' : 'warn') . "'>";
echo "<strong>Session Configuration:</strong><br>";
echo "Session Save Path: " . (ini_get('session.save_path') ?: 'default') . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Status: " . (session_status() == PHP_SESSION_NONE ? 'Not started' : (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Disabled')) . "<br>";
echo "Session ID: " . (session_id() ?: 'none') . "<br>";
echo "</div>";

// 3. $_SESSION Test
echo "<div class='check " . (isset($_SESSION) ? 'pass' : 'fail') . "'>";
echo "<strong>$_SESSION Variables:</strong><br>";
if (isset($_SESSION)) {
    echo "Session array accessible: Yes<br>";
    echo "Session contents: " . json_encode($_SESSION) . "<br>";
} else {
    echo "Session array accessible: No (ERROR)<br>";
}
echo "</div>";

// 4. File Permissions
echo "<div class='check'>";
echo "<strong>File Permissions:</strong><br>";
$paths = [
    __DIR__ => 'Current Directory',
    __DIR__ . '/includes' => 'includes/',
    __DIR__ . '/templates' => 'templates/',
    __DIR__ . '/css' => 'css/',
    __DIR__ . '/api' => 'api/',
];
foreach ($paths as $path => $label) {
    $status = is_dir($path) && is_readable($path) ? 'pass' : 'fail';
    $perms = is_dir($path) ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    echo "<div style='margin: 5px 0;'>";
    echo "<span style='display:inline-block; width: 30px; color: " . ($status == 'pass' ? 'green' : 'red') . "'>●</span>";
    echo "$label: $perms (Readable: " . (is_readable($path) ? 'Yes' : 'No') . ")<br>";
    echo "</div>";
}
echo "</div>";

// 5. Database Connection
echo "<div class='check " . ($conn->connect_error ? 'fail' : 'pass') . "'>";
echo "<strong>Database Connection:</strong><br>";
if ($conn->connect_error) {
    echo "Status: ❌ Connection Failed<br>";
    echo "Error: " . htmlspecialchars($conn->connect_error) . "<br>";
} else {
    echo "Status: ✓ Connected<br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
    
    // Check csv_files table
    $check = $conn->query("SELECT COUNT(*) as count FROM csv_files");
    if ($check) {
        $result = $check->fetch_assoc();
        echo "Files in database: " . $result['count'] . "<br>";
    } else {
        echo "Error reading csv_files: " . $conn->error . "<br>";
    }
}
echo "</div>";

// 6. Server Request Method
echo "<div class='check pass'>";
echo "<strong>Current Request:</strong><br>";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "URL: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "<br>";
echo "Script: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "IP: " . $_SERVER['REMOTE_ADDR'] . "<br>";
echo "</div>";

// 7. Forms Test
echo "<div class='check pass'>";
echo "<strong>Form Submission Test:</strong><br>";
echo "<form method='POST' style='background: #f9f9f9; padding: 10px; margin-top: 10px;'>";
echo "<input type='text' name='test_input' value='test_value' placeholder='Test input'>";
echo "<button type='submit' name='test_submit' value='1'>Submit Test</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_submit'])) {
    echo "<div style='background: #e8f5e9; padding: 10px; border: 1px solid green; margin-top: 10px;'>";
    echo "<strong>✓ POST Submission Works!</strong><br>";
    echo "Received: " . htmlspecialchars($_POST['test_input'] ?? '') . "<br>";
    echo "</div>";
}
echo "</div>";

// 8. API Key Check
echo "<div class='check'>";
echo "<strong>API Configuration:</strong><br>";
echo "API Key defined: " . (defined('API_KEY') ? 'Yes' : 'No') . "<br>";
echo "API Key: " . (defined('API_KEY') ? '***' . substr(API_KEY, -4) : 'N/A') . "<br>";
echo "</div>";

// 9. Session File Test
echo "<div class='check'>";
echo "<strong>Session File Storage:</strong><br>";
$session_save_path = ini_get('session.save_path') ?: sys_get_temp_dir();
echo "Save Path: " . $session_save_path . "<br>";
echo "Writable: " . (is_writable($session_save_path) ? 'Yes ✓' : 'No ✗') . "<br>";
if (is_dir($session_save_path)) {
    $files = count(scandir($session_save_path)) - 2;
    echo "Files in directory: " . $files . "<br>";
}
echo "</div>";

// 10. Test Edit Page Access
echo "<div class='check'>";
echo "<strong>Edit Page Test:</strong><br>";
$edit_test = fopen(__DIR__ . '/edit.php', 'r');
if ($edit_test) {
    fclose($edit_test);
    echo "Edit page readable: ✓ Yes<br>";
    echo "Edit page file size: " . filesize(__DIR__ . '/edit.php') . " bytes<br>";
} else {
    echo "Edit page readable: ✗ No (ERROR)<br>";
}
echo "</div>";

echo "<hr>";
echo "<p><small>To use this diagnostic page, visit: http://csv.mnr.world/diagnose.php</small></p>";
echo "</body></html>";
?>
