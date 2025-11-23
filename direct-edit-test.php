<?php
/**
 * DIRECT EDIT PAGE TEST
 * Access edit.php directly with all required session/auth
 */

session_start();

// Simulate authenticated session
$_SESSION['user_id'] = 'admin';
$_SESSION['username'] = 'admin';

require_once __DIR__ . '/includes/config.php';

echo "=== EDIT PAGE ACCESS TEST ===\n\n";

// 1. Verify session
echo "1. Session:\n";
echo "   Status: " . (session_status() == PHP_SESSION_ACTIVE ? "✓ ACTIVE" : "✗ INACTIVE") . "\n";
echo "   User ID: " . ($_SESSION['user_id'] ?? "NOT SET") . "\n\n";

// 2. Check if we can access edit.php file
echo "2. File Access:\n";
$edit_file = __DIR__ . '/edit.php';
if (file_exists($edit_file)) {
    echo "   File exists: ✓ Yes\n";
    echo "   Readable: " . (is_readable($edit_file) ? "✓ Yes" : "✗ No") . "\n";
    echo "   Size: " . filesize($edit_file) . " bytes\n";
} else {
    echo "   File exists: ✗ No\n";
}
echo "\n";

// 3. Check if file_id parameter is needed
echo "3. File ID Parameter Test:\n";
$file_id = $_GET['id'] ?? null;
if (!$file_id) {
    echo "   file_id NOT provided (will use default id=1)\n";
    $file_id = 1;
}
echo "   Using file_id: " . $file_id . "\n\n";

// 4. Try to fetch the file from database
echo "4. Database Query Test:\n";
$stmt = $conn->prepare("SELECT id, filename, json_text, file_uuid, row_count FROM csv_files WHERE id = ? LIMIT 1");
if (!$stmt) {
    echo "   ✗ Prepare failed: " . $conn->error . "\n";
} else {
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "   ✗ File not found with ID=$file_id\n";
        echo "   Trying to get any file:\n";
        $result2 = $conn->query("SELECT id, filename, row_count FROM csv_files LIMIT 1");
        if ($result2 && $result2->num_rows > 0) {
            $file = $result2->fetch_assoc();
            echo "   Found: ID=" . $file['id'] . ", Filename=" . $file['filename'] . "\n";
        }
    } else {
        $file = $result->fetch_assoc();
        echo "   ✓ File found:\n";
        echo "      ID: " . $file['id'] . "\n";
        echo "      Filename: " . htmlspecialchars($file['filename']) . "\n";
        echo "      Questions: " . $file['row_count'] . "\n";
        echo "      UUID: " . ($file['file_uuid'] ?? 'NOT SET') . "\n";
        
        // Parse JSON
        $questions = json_decode($file['json_text'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "      JSON Parse: ✓ Success (" . count($questions) . " questions)\n";
        } else {
            echo "      JSON Parse: ✗ Failed - " . json_last_error_msg() . "\n";
        }
    }
    $stmt->close();
}
echo "\n";

// 5. Simulate what edit.php does on load
echo "5. Edit Page Load Simulation:\n";
if (isset($file)) {
    echo "   Would render:\n";
    echo "      Title: Edit: " . substr($file['filename'], 0, 40) . "\n";
    echo "      Question count: " . count($questions) . "\n";
    echo "      Form action: edit.php?id=" . $file['id'] . "\n";
    echo "      Form method: POST\n";
    echo "      Button: Save All Changes\n";
}

// 6. Test form submission readiness
echo "\n6. Form Submission Test:\n";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "   POST method: ✓ Working\n";
    echo "   Questions received: " . count($_POST['questions'] ?? []) . "\n";
} else {
    echo "   Current method: GET\n";
    echo "   To test POST: Submit the form below\n\n";
    echo "   <form method='POST'>\n";
    echo "   <input type='hidden' name='questions[0][question]' value='test_question'>\n";
    echo "   <input type='hidden' name='questions[0][option1]' value='option_a'>\n";
    echo "   <button type='submit'>Test POST Submit</button>\n";
    echo "   </form>\n";
}

echo "\n=== END TEST ===\n";
?>
