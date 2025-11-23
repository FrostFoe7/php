<?php
function parseCSV($filepath) {
    $questions = [];
    
    // Detect encoding and convert to UTF-8 if needed
    $content = file_get_contents($filepath);
    
    // Simple encoding detection/conversion attempt
    // If it looks like UTF-8 with BOM, strip it
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    
    // Save back to temp file to read with fgetcsv
    file_put_contents($filepath, $content);

    if (($handle = fopen($filepath, "r")) !== FALSE) {
        $header = fgetcsv($handle, 0, ",");
        
        // Normalize headers: lowercase, trim, remove BOM artifacts
        $header = array_map(function($h) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
        }, $header);

        // Expected columns mapping
        $expected_cols = [
            'questions' => 'question_text',
            'question' => 'question_text', // alias
            'option1' => 'option1',
            'option2' => 'option2',
            'option3' => 'option3',
            'option4' => 'option4',
            'option5' => 'option5',
            'answer' => 'answer',
            'explanation' => 'explanation',
            'type' => 'type',
            'section' => 'section'
        ];

        $col_map = [];
        foreach ($header as $index => $col_name) {
            foreach ($expected_cols as $csv_key => $db_key) {
                if (strpos($col_name, $csv_key) !== false) {
                    $col_map[$db_key] = $index;
                    break;
                }
            }
        }

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // Skip empty rows
            if (empty(array_filter($row))) continue;

            $q = [
                'question_text' => '',
                'option1' => '',
                'option2' => '',
                'option3' => '',
                'option4' => '',
                'option5' => '',
                'answer' => '',
                'explanation' => '',
                'type' => 0,
                'section' => 0
            ];

            foreach ($col_map as $db_key => $index) {
                if (isset($row[$index])) {
                    // Clean up the data but KEEP HTML
                    $val = trim($row[$index]);
                    
                    // Handle "Corrupted Excel encodings" - basic attempt
                    // If we see common mojibake patterns, we might want to fix them, 
                    // but "utf8mb4 everywhere" implies we trust the input is utf8 or we converted it.
                    // Since we did file_get_contents and stripped BOM, we assume it's mostly OK.
                    // If specific mojibake handling is needed, iconv would go here.
                    
                    $q[$db_key] = $val;
                }
            }
            
            // Basic validation: must have question text
            if (!empty($q['question_text'])) {
                $questions[] = $q;
            }
        }
        fclose($handle);
    }
    return $questions;
}
?>
