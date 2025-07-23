<?php
/**
 * Import/Export Class
 * 
 * Handles import and export functionality for quiz questions
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_Import_Export {
    
    public function __construct() {
        add_action('wp_ajax_elearning_import_questions', [$this, 'handleQuestionsImport']);
        add_action('wp_ajax_elearning_export_questions', [$this, 'handleQuestionsExport']);
        add_action('admin_footer', [$this, 'addImportModal']);
    }
    
    /**
     * Handle questions import via AJAX
     */
    public function handleQuestionsImport(): void {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'elearning_import_nonce')) {
            wp_send_json_error(__('Security check failed', 'elearning-quiz'));
        }
        
        if (!current_user_can('edit_elearning_quizzes')) {
            wp_send_json_error(__('You do not have permission to import questions', 'elearning-quiz'));
        }
        
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        if (!$quiz_id) {
            wp_send_json_error(__('Invalid quiz ID', 'elearning-quiz'));
        }
        
        // Check if file was uploaded
        if (empty($_FILES['import_file'])) {
            wp_send_json_error(__('No file uploaded', 'elearning-quiz'));
        }
        
        $file = $_FILES['import_file'];
        
        // Validate file type
        $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(__('Please upload a CSV file', 'elearning-quiz'));
        }
        
        // Parse CSV file
        $questions = $this->parseCSVFile($file['tmp_name']);
        
        if (empty($questions)) {
            wp_send_json_error(__('No valid questions found in the file', 'elearning-quiz'));
        }
        
        // Get existing questions
        $existing_questions = get_post_meta($quiz_id, '_quiz_questions', true) ?: [];
        
        // Merge or replace based on setting
        $import_mode = sanitize_text_field($_POST['import_mode'] ?? 'append');
        
        if ($import_mode === 'replace') {
            $final_questions = $questions;
        } else {
            $final_questions = array_merge($existing_questions, $questions);
        }
        
        // Save questions
        update_post_meta($quiz_id, '_quiz_questions', $final_questions);
        
        wp_send_json_success([
            'message' => sprintf(__('Successfully imported %d questions', 'elearning-quiz'), count($questions)),
            'imported_count' => count($questions),
            'total_count' => count($final_questions)
        ]);
    }
    
    /**
     * Handle questions export
     */
    public function handleQuestionsExport(): void {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'elearning_export_nonce')) {
            wp_die(__('Security check failed', 'elearning-quiz'));
        }
        
        if (!current_user_can('export_elearning_data')) {
            wp_die(__('You do not have permission to export questions', 'elearning-quiz'));
        }
        
        $quiz_id = intval($_GET['quiz_id'] ?? 0);
        if (!$quiz_id) {
            wp_die(__('Invalid quiz ID', 'elearning-quiz'));
        }
        
        // Get quiz questions
        $questions = get_post_meta($quiz_id, '_quiz_questions', true) ?: [];
        
        if (empty($questions)) {
            wp_die(__('No questions found in this quiz', 'elearning-quiz'));
        }
        
        // Generate CSV content
        $csv_content = $this->generateQuestionsCSV($questions);
        
        // Set headers for download
        $quiz_title = get_the_title($quiz_id);
        $filename = sanitize_file_name($quiz_title . '_questions_' . date('Y-m-d') . '.csv');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for Excel compatibility
        echo "\xEF\xBB\xBF";
        echo $csv_content;
        exit;
    }
    
    /**
     * Parse CSV file and extract questions
     */
    private function parseCSVFile($filepath): array {
        $questions = [];
        
        if (($handle = fopen($filepath, 'r')) !== FALSE) {
            // Read header row
            $headers = fgetcsv($handle, 0, ',');
            if (!$headers) {
                fclose($handle);
                return [];
            }
            
            // Map headers to indices
            $header_map = array_flip(array_map('strtolower', array_map('trim', $headers)));
            
            // Read data rows
            while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
                $question = $this->parseQuestionRow($data, $header_map);
                if ($question) {
                    $questions[] = $question;
                }
            }
            
            fclose($handle);
        }
        
        return $questions;
    }
    
    /**
     * Parse a single question row from CSV
     */
    private function parseQuestionRow($data, $header_map): ?array {
        // Required fields
        $type_index = $header_map['type'] ?? $header_map['question type'] ?? null;
        $question_index = $header_map['question'] ?? $header_map['question text'] ?? null;
        
        if ($type_index === null || $question_index === null) {
            return null;
        }
        
        $type = strtolower(trim($data[$type_index] ?? ''));
        $question_text = trim($data[$question_index] ?? '');
        
        if (empty($type) || empty($question_text)) {
            return null;
        }
        
        // Map CSV type to internal type
        $type_mapping = [
            'multiple choice' => 'multiple_choice',
            'multiple_choice' => 'multiple_choice',
            'true false' => 'true_false',
            'true/false' => 'true_false',
            'fill blanks' => 'fill_blanks',
            'fill in the blanks' => 'fill_blanks',
            'matching' => 'matching'
        ];
        
        $internal_type = $type_mapping[$type] ?? null;
        if (!$internal_type) {
            return null;
        }
        
        $question = [
            'type' => $internal_type,
            'question' => sanitize_textarea_field($question_text)
        ];
        
        // Parse type-specific data
        switch ($internal_type) {
            case 'multiple_choice':
                $question = $this->parseMultipleChoiceData($data, $header_map, $question);
                break;
                
            case 'true_false':
                $question = $this->parseTrueFalseData($data, $header_map, $question);
                break;
                
            case 'fill_blanks':
                $question = $this->parseFillBlanksData($data, $header_map, $question);
                break;
                
            case 'matching':
                $question = $this->parseMatchingData($data, $header_map, $question);
                break;
        }
        
        return $question;
    }
    
    /**
     * Parse multiple choice question data
     */
    private function parseMultipleChoiceData($data, $header_map, $question): array {
        $options = [];
        $correct_answers = [];
        
        // Look for option columns (Option 1, Option 2, etc.)
        for ($i = 1; $i <= 10; $i++) {
            $option_key = 'option ' . $i;
            $option_index = $header_map[$option_key] ?? $header_map['option' . $i] ?? null;
            
            if ($option_index !== null && !empty($data[$option_index])) {
                $options[] = sanitize_text_field(trim($data[$option_index]));
            }
        }
        
        // Get correct answers
        $correct_index = $header_map['correct'] ?? $header_map['correct answers'] ?? $header_map['correct answer'] ?? null;
        if ($correct_index !== null && !empty($data[$correct_index])) {
            $correct_str = trim($data[$correct_index]);
            
            // Handle multiple correct answers (comma-separated indices)
            if (strpos($correct_str, ',') !== false) {
                $indices = explode(',', $correct_str);
                foreach ($indices as $idx) {
                    $idx = intval(trim($idx)) - 1; // Convert to 0-based index
                    if ($idx >= 0 && $idx < count($options)) {
                        $correct_answers[] = $idx;
                    }
                }
            } else {
                // Single correct answer
                $idx = intval($correct_str) - 1; // Convert to 0-based index
                if ($idx >= 0 && $idx < count($options)) {
                    $correct_answers[] = $idx;
                }
            }
        }
        
        if (count($options) >= 2 && !empty($correct_answers)) {
            $question['options'] = $options;
            $question['correct_answers'] = $correct_answers;
        }
        
        return $question;
    }
    
    /**
     * Parse true/false question data
     */
    private function parseTrueFalseData($data, $header_map, $question): array {
        $correct_index = $header_map['correct'] ?? $header_map['correct answer'] ?? $header_map['answer'] ?? null;
        
        if ($correct_index !== null && !empty($data[$correct_index])) {
            $answer = strtolower(trim($data[$correct_index]));
            
            if (in_array($answer, ['true', 't', '1', 'yes'])) {
                $question['correct_answer'] = 'true';
            } elseif (in_array($answer, ['false', 'f', '0', 'no'])) {
                $question['correct_answer'] = 'false';
            } else {
                $question['correct_answer'] = 'true'; // Default
            }
        } else {
            $question['correct_answer'] = 'true'; // Default
        }
        
        return $question;
    }
    
    /**
     * Parse fill in the blanks question data
     */
    private function parseFillBlanksData($data, $header_map, $question): array {
        // Get text with blanks
        $text_index = $header_map['text with blanks'] ?? $header_map['blank text'] ?? null;
        if ($text_index !== null && !empty($data[$text_index])) {
            $question['text_with_blanks'] = sanitize_textarea_field(trim($data[$text_index]));
        } else {
            // Use question text and add blanks
            $question['text_with_blanks'] = $question['question'];
        }
        
        // Get word bank
        $word_bank = [];
        $bank_index = $header_map['word bank'] ?? $header_map['words'] ?? null;
        
        if ($bank_index !== null && !empty($data[$bank_index])) {
            // Words separated by comma or semicolon
            $words = preg_split('/[,;]/', $data[$bank_index]);
            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word)) {
                    $word_bank[] = sanitize_text_field($word);
                }
            }
        } else {
            // Look for individual word columns
            for ($i = 1; $i <= 10; $i++) {
                $word_key = 'word ' . $i;
                $word_index = $header_map[$word_key] ?? $header_map['word' . $i] ?? null;
                
                if ($word_index !== null && !empty($data[$word_index])) {
                    $word_bank[] = sanitize_text_field(trim($data[$word_index]));
                }
            }
        }
        
        if (!empty($word_bank)) {
            $question['word_bank'] = $word_bank;
        }
        
        return $question;
    }
    
    /**
     * Parse matching question data
     */
    private function parseMatchingData($data, $header_map, $question): array {
        $left_column = [];
        $right_column = [];
        $matches = [];
        
        // Get left column items
        for ($i = 1; $i <= 10; $i++) {
            $left_key = 'left ' . $i;
            $left_index = $header_map[$left_key] ?? $header_map['left' . $i] ?? null;
            
            if ($left_index !== null && !empty($data[$left_index])) {
                $left_column[] = sanitize_text_field(trim($data[$left_index]));
            }
        }
        
        // Get right column items
        for ($i = 1; $i <= 10; $i++) {
            $right_key = 'right ' . $i;
            $right_index = $header_map[$right_key] ?? $header_map['right' . $i] ?? null;
            
            if ($right_index !== null && !empty($data[$right_index])) {
                $right_column[] = sanitize_text_field(trim($data[$right_index]));
            }
        }
        
        // Get matches
        $matches_index = $header_map['matches'] ?? $header_map['correct matches'] ?? null;
        if ($matches_index !== null && !empty($data[$matches_index])) {
            // Format: "1-1,2-2,3-3" or "1:1;2:2;3:3"
            $matches_str = trim($data[$matches_index]);
            $match_pairs = preg_split('/[,;]/', $matches_str);
            
            foreach ($match_pairs as $pair) {
                if (preg_match('/(\d+)[-:](\d+)/', $pair, $match)) {
                    $left_idx = intval($match[1]) - 1;
                    $right_idx = intval($match[2]) - 1;
                    
                    if ($left_idx >= 0 && $left_idx < count($left_column) &&
                        $right_idx >= 0 && $right_idx < count($right_column)) {
                        $matches[] = [
                            'left' => $left_idx,
                            'right' => $right_idx
                        ];
                    }
                }
            }
        }
        
        if (count($left_column) >= 2 && count($right_column) >= 2 && !empty($matches)) {
            $question['left_column'] = $left_column;
            $question['right_column'] = $right_column;
            $question['matches'] = $matches;
        }
        
        return $question;
    }
    
    /**
     * Generate CSV content from questions
     */
    private function generateQuestionsCSV($questions): string {
        $csv_lines = [];
        
        // Header row
        $headers = [
            'Type',
            'Question',
            'Option 1',
            'Option 2',
            'Option 3',
            'Option 4',
            'Option 5',
            'Correct Answer(s)',
            'Text with Blanks',
            'Word Bank',
            'Left 1',
            'Left 2',
            'Left 3',
            'Left 4',
            'Left 5',
            'Right 1',
            'Right 2',
            'Right 3',
            'Right 4',
            'Right 5',
            'Matches'
        ];
        
        $csv_lines[] = $this->arrayToCSVLine($headers);
        
        // Question rows
        foreach ($questions as $question) {
            $row = $this->questionToCSVRow($question);
            $csv_lines[] = $this->arrayToCSVLine($row);
        }
        
        return implode("\n", $csv_lines);
    }
    
    /**
     * Convert question to CSV row
     */
    private function questionToCSVRow($question): array {
        $row = array_fill(0, 21, ''); // 21 columns
        
        $row[0] = $question['type'];
        $row[1] = $question['question'];
        
        switch ($question['type']) {
            case 'multiple_choice':
                // Add options
                if (isset($question['options'])) {
                    foreach ($question['options'] as $i => $option) {
                        if ($i < 5) {
                            $row[2 + $i] = $option;
                        }
                    }
                }
                
                // Add correct answers (1-based indices)
                if (isset($question['correct_answers'])) {
                    $correct = array_map(function($idx) {
                        return $idx + 1;
                    }, $question['correct_answers']);
                    $row[7] = implode(',', $correct);
                }
                break;
                
            case 'true_false':
                $row[7] = $question['correct_answer'] ?? 'true';
                break;
                
            case 'fill_blanks':
                $row[8] = $question['text_with_blanks'] ?? '';
                $row[9] = isset($question['word_bank']) ? implode(',', $question['word_bank']) : '';
                break;
                
            case 'matching':
                // Add left column
                if (isset($question['left_column'])) {
                    foreach ($question['left_column'] as $i => $item) {
                        if ($i < 5) {
                            $row[10 + $i] = $item;
                        }
                    }
                }
                
                // Add right column
                if (isset($question['right_column'])) {
                    foreach ($question['right_column'] as $i => $item) {
                        if ($i < 5) {
                            $row[15 + $i] = $item;
                        }
                    }
                }
                
                // Add matches (1-based indices)
                if (isset($question['matches'])) {
                    $matches_str = [];
                    foreach ($question['matches'] as $match) {
                        $matches_str[] = ($match['left'] + 1) . '-' . ($match['right'] + 1);
                    }
                    $row[20] = implode(',', $matches_str);
                }
                break;
        }
        
        return $row;
    }
    
    /**
     * Convert array to CSV line
     */
    private function arrayToCSVLine($array): string {
        return implode(',', array_map(function($value) {
            // Escape quotes and wrap in quotes if necessary
            if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
                return '"' . str_replace('"', '""', $value) . '"';
            }
            return $value;
        }, $array));
    }
    
    /**
     * Add import modal to admin footer
     */
    public function addImportModal(): void {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'elearning_quiz') {
            return;
        }
        ?>
        <div id="quiz-import-modal" class="elearning-modal" style="display: none;">
            <div class="modal-content">
                <h2><?php _e('Import Quiz Questions', 'elearning-quiz'); ?></h2>
                
                <form id="import-questions-form" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="import-file"><?php _e('Select CSV File:', 'elearning-quiz'); ?></label>
                        <input type="file" id="import-file" name="import_file" accept=".csv" required>
                    </div>
                    
                    <div class="form-group">
                        <label><?php _e('Import Mode:', 'elearning-quiz'); ?></label>
                        <label>
                            <input type="radio" name="import_mode" value="append" checked>
                            <?php _e('Append to existing questions', 'elearning-quiz'); ?>
                        </label>
                        <label>
                            <input type="radio" name="import_mode" value="replace">
                            <?php _e('Replace existing questions', 'elearning-quiz'); ?>
                        </label>
                    </div>
                    
                    <div class="modal-buttons">
                        <button type="button" class="button" onclick="closeImportModal()">
                            <?php _e('Cancel', 'elearning-quiz'); ?>
                        </button>
                        <button type="submit" class="button button-primary">
                            <?php _e('Import Questions', 'elearning-quiz'); ?>
                        </button>
                    </div>
                </form>
                
                <div id="import-result" style="margin-top: 20px; display: none;"></div>
            </div>
        </div>
        
        <style>
        .elearning-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
        }
        
        .elearning-modal .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .elearning-modal h2 {
            margin-top: 0;
        }
        
        .elearning-modal .form-group {
            margin-bottom: 20px;
        }
        
        .elearning-modal label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .elearning-modal input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .elearning-modal .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }
        </style>
        
        <script>
        function openImportModal() {
            document.getElementById('quiz-import-modal').style.display = 'flex';
        }
        
        function closeImportModal() {
            document.getElementById('quiz-import-modal').style.display = 'none';
            document.getElementById('import-questions-form').reset();
            document.getElementById('import-result').style.display = 'none';
        }
        
        jQuery(document).ready(function($) {
            $('#import-questions-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'elearning_import_questions');
                formData.append('quiz_id', <?php echo get_the_ID(); ?>);
                formData.append('nonce', '<?php echo wp_create_nonce('elearning_import_nonce'); ?>');
                
                const $submitBtn = $(this).find('[type="submit"]');
                const originalText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('<?php _e('Importing...', 'elearning-quiz'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const $result = $('#import-result');
                        
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                            
                            // Reload page after 2 seconds
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                        }
                        
                        $result.show();
                        $submitBtn.prop('disabled', false).text(originalText);
                    },
                    error: function() {
                        $('#import-result').html('<div class="notice notice-error"><p><?php _e('Import failed. Please try again.', 'elearning-quiz'); ?></p></div>').show();
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }
}