<?php
/**
 * SIMPLE TEST - Test edit.php prerequisites
 * This mimics what edit.php needs to run
 */

session_start();
echo "SESSION TEST\n";
echo "============\n\n";

// 1. Check if session started
echo "1. Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? "ACTIVE ✓" : "NOT ACTIVE ✗") . "\n";
echo "   Session ID: " . session_id() . "\n\n";

// 2. Simulate login check
echo "2. Login Check:\n";
if (!isset($_SESSION['user_id'])) {
    echo "   User NOT logged in (expected if not authenticated)\n";
    echo "   To test: Visit login.php and log in first\n";
} else {
    echo "   User IS logged in ✓\n";
    echo "   User ID: " . $_SESSION['user_id'] . "\n";
}
echo "\n";

// 3. Database connection test
echo "3. Database Connection:\n";
require_once __DIR__ . '/includes/config.php';

if ($conn->connect_error) {
    echo "   FAILED: " . $conn->connect_error . " ✗\n";
} else {
    echo "   Connected ✓\n";
    
    // Try to fetch a file
    $result = $conn->query("SELECT COUNT(*) as total FROM csv_files LIMIT 1");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "   Files available: " . $row['total'] . "\n";
    }
}
echo "\n";

// 4. GET parameter test
echo "4. GET Parameters:\n";
$file_id = $_GET['id'] ?? null;
echo "   file_id parameter: " . ($file_id ? htmlspecialchars($file_id) : "NOT PROVIDED") . "\n";
if ($file_id && !is_numeric($file_id)) {
    echo "   ERROR: file_id must be numeric ✗\n";
} elseif ($file_id) {
    echo "   file_id is valid ✓\n";
}
echo "\n";

// 5. Try to fetch file
if ($file_id && is_numeric($file_id)) {
    echo "5. Fetching File with ID=$file_id:\n";
    
    $stmt = $conn->prepare("SELECT id, filename, json_text, file_uuid FROM csv_files WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "   File NOT found ✗\n";
    } else {
        $file = $result->fetch_assoc();
        echo "   File found ✓\n";
        echo "   Filename: " . htmlspecialchars($file['filename']) . "\n";
        echo "   UUID: " . ($file['file_uuid'] ?? 'N/A') . "\n";
        
        $questions = json_decode($file['json_text'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "   Questions: " . count($questions) . "\n";
            echo "   JSON parse: SUCCESS ✓\n";
        } else {
            echo "   JSON parse: FAILED - " . json_last_error_msg() . " ✗\n";
        }
    }
    $stmt->close();
}
echo "\n";

// 6. Check if can write (for form submission)
echo "6. Form Submission Capability:\n";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "   POST method: WORKING ✓\n";
    $questions = $_POST['questions'] ?? [];
    echo "   Form data received: " . count($questions) . " questions\n";
} else {
    echo "   POST method: Not tested yet\n";
    echo "   To test: Submit the form below\n";
    echo "   \n   <form method='POST'>\n";
    echo "   <input type='hidden' name='questions[0][question]' value='test'>\n";
    echo "   <button type='submit'>Test POST</button>\n";
    echo "   </form>\n";
}
echo "\n";

echo "============\n";
echo "RESULT: If all tests above show ✓, edit.php should work\n";
echo "If seeing ✗, check the 403_TROUBLESHOOTING.md guide\n";
?>
