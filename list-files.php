<?php
/**
 * LIST ALL FILES WITH CORRECT ACCESS URLS
 */

require_once __DIR__ . '/includes/config.php';

echo "=== AVAILABLE FILES ===\n\n";

$result = $conn->query("SELECT id, filename, row_count, file_uuid FROM csv_files ORDER BY id DESC");

if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " files:\n\n";
    
    while ($file = $result->fetch_assoc()) {
        echo "📄 " . htmlspecialchars($file['filename']) . "\n";
        echo "   ID: " . $file['id'] . "\n";
        echo "   UUID: " . ($file['file_uuid'] ?? 'NOT SET') . "\n";
        echo "   Questions: " . $file['row_count'] . "\n";
        echo "\n   🔗 EDIT URL:\n";
        echo "   https://csv.mnr.world/edit.php?id=" . $file['id'] . "\n";
        echo "\n   🔗 VIEW URL:\n";
        echo "   https://csv.mnr.world/view.php?id=" . $file['id'] . "\n";
        echo "\n   🔗 DELETE URL:\n";
        echo "   https://csv.mnr.world/delete.php?id=" . $file['id'] . "\n";
        echo "\n";
    }
} else {
    echo "❌ No files found in database\n";
    echo "\n📤 To add files:\n";
    echo "1. Visit: https://csv.mnr.world/upload.php\n";
    echo "2. Upload a CSV file\n";
    echo "3. Then try editing\n";
}

echo "=== TO TEST EDIT PAGE ===\n";
echo "1. Copy the EDIT URL above\n";
echo "2. Visit the URL in your browser\n";
echo "3. If you see 403, report the exact URL you used\n";
?>
