<?php
/**
 * Frontend Admin Dashboard Template
 * Allows admins to view analytics without accessing WordPress admin
 */

if (!defined('ABSPATH')) exit;

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_redirect(site_url('/zonatech-dashboard/'));
    exit;
}

// Ensure the Question Importer class is loaded
if (!class_exists('ZonaTech_Question_Importer')) {
    $importer_file = dirname(__DIR__) . '/includes/class-question-importer.php';
    if (file_exists($importer_file)) {
        require_once $importer_file;
    }
}

global $wpdb;

// Handle form submissions
$message = '';
$message_type = '';

// Handle bulk delete questions by subject (no year required)
if (isset($_POST['delete_subject_year']) && wp_verify_nonce($_POST['delete_subject_year_nonce'], 'zonatech_delete_subject_year')) {
    $exam_type = sanitize_text_field($_POST['bulk_delete_exam_type']);
    $subject = sanitize_text_field($_POST['bulk_delete_subject']);
    
    if (empty($exam_type) || empty($subject)) {
        $message = 'Please select valid exam type and subject.';
        $message_type = 'error';
    } else {
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        // Count how many will be deleted
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_questions WHERE exam_type = %s AND subject = %s",
            $exam_type, $subject
        ));
        
        if ($count > 0) {
            $result = $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_questions WHERE exam_type = %s AND subject = %s",
                $exam_type, $subject
            ));
            
            if ($result !== false) {
                $message = "Successfully deleted $count " . strtoupper($exam_type) . " $subject questions!";
                $message_type = 'success';
            } else {
                $message = 'Failed to delete questions. Database error.';
                $message_type = 'error';
            }
        } else {
            $message = "No questions found for " . strtoupper($exam_type) . " $subject.";
            $message_type = 'warning';
        }
    }
}

// Handle question deletion
if (isset($_POST['delete_question']) && wp_verify_nonce($_POST['delete_question_nonce'], 'zonatech_delete_question')) {
    $question_id = intval($_POST['question_id']);
    if ($question_id > 0) {
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        // Check if question exists first
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_questions WHERE id = %d", $question_id));
        if ($exists) {
            $result = $wpdb->delete($table_questions, array('id' => $question_id), array('%d'));
            if ($result) {
                $message = 'Question deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to delete question.';
                $message_type = 'error';
            }
        } else {
            $message = 'Question not found.';
            $message_type = 'error';
        }
    }
}

// Handle question edit/update
if (isset($_POST['edit_question']) && wp_verify_nonce($_POST['edit_question_nonce'], 'zonatech_edit_question')) {
    $question_id = intval($_POST['question_id']);
    $correct_answer = strtoupper(sanitize_text_field($_POST['correct_answer']));
    
    // Validate correct_answer is A, B, C, D, or E
    if (!in_array($correct_answer, array('A', 'B', 'C', 'D', 'E'))) {
        $message = 'Invalid correct answer. Must be A, B, C, D, or E.';
        $message_type = 'error';
    } elseif ($question_id > 0) {
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        // Check if question exists first
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_questions WHERE id = %d", $question_id));
        if ($exists) {
            $result = $wpdb->update(
                $table_questions,
                array(
                    'exam_type' => sanitize_text_field($_POST['exam_type']),
                    'subject' => sanitize_text_field($_POST['subject']),
                    'question_text' => sanitize_textarea_field($_POST['question_text']),
                    'option_a' => sanitize_text_field($_POST['option_a']),
                    'option_b' => sanitize_text_field($_POST['option_b']),
                    'option_c' => sanitize_text_field($_POST['option_c']),
                    'option_d' => sanitize_text_field($_POST['option_d']),
                    'correct_answer' => $correct_answer,
                    'explanation' => sanitize_textarea_field($_POST['explanation'])
                ),
                array('id' => $question_id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            if ($result !== false) {
                $message = 'Question updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update question.';
                $message_type = 'error';
            }
        } else {
            $message = 'Question not found.';
            $message_type = 'error';
        }
    }
}

// Handle single question addition
if (isset($_POST['add_single_question']) && wp_verify_nonce($_POST['question_nonce'], 'zonatech_add_question')) {
    $exam_type = sanitize_text_field($_POST['exam_type']);
    $subject = sanitize_text_field($_POST['subject']);
    $question_text = sanitize_textarea_field($_POST['question_text']);
    $option_a = sanitize_text_field($_POST['option_a']);
    $option_b = sanitize_text_field($_POST['option_b']);
    $option_c = sanitize_text_field($_POST['option_c']);
    $option_d = sanitize_text_field($_POST['option_d']);
    $correct_answer = sanitize_text_field($_POST['correct_answer']);
    $explanation = sanitize_textarea_field($_POST['explanation']);
    
    $table_questions = $wpdb->prefix . 'zonatech_questions';
    
    $result = $wpdb->insert($table_questions, array(
        'exam_type' => $exam_type,
        'subject' => $subject,
        'year' => 0, // Year not required - questions are merged
        'question_text' => $question_text,
        'option_a' => $option_a,
        'option_b' => $option_b,
        'option_c' => $option_c,
        'option_d' => $option_d,
        'correct_answer' => $correct_answer,
        'explanation' => $explanation,
        'created_at' => current_time('mysql')
    ));
    
    if ($result) {
        $message = 'Question added successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to add question. Please try again.';
        $message_type = 'error';
    }
}

/**
 * Extract text content from a Word document (.docx)
 * DOCX files are ZIP archives containing XML content
 * 
 * @param string $file_path Path to the DOCX file
 * @return string|false Extracted text content or false on failure
 */
function zonatech_extract_docx_text($file_path) {
    // Check if ZipArchive is available
    if (!class_exists('ZipArchive')) {
        return false;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($file_path) !== true) {
        return false;
    }
    
    // Read the main document content
    $xml_content = $zip->getFromName('word/document.xml');
    $zip->close();
    
    if ($xml_content === false) {
        return false;
    }
    
    // Parse XML and extract text
    $text = '';
    
    // Use DOMDocument to parse XML properly
    $dom = new DOMDocument();
    @$dom->loadXML($xml_content, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
    
    // Find all text elements (w:t tags contain text)
    $paragraphs = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p');
    
    foreach ($paragraphs as $paragraph) {
        $paragraph_text = '';
        $text_nodes = $paragraph->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't');
        
        foreach ($text_nodes as $text_node) {
            $paragraph_text .= $text_node->textContent;
        }
        
        if (!empty(trim($paragraph_text))) {
            $text .= $paragraph_text . "\n";
        }
    }
    
    return $text;
}

// Handle bulk file upload (CSV or DOCX)
if (isset($_POST['bulk_upload_questions']) && wp_verify_nonce($_POST['bulk_nonce'], 'zonatech_bulk_upload')) {
    if (!empty($_FILES['csv_file']['tmp_name'])) {
        try {
            $file = $_FILES['csv_file']['tmp_name'];
            $file_name = isset($_FILES['csv_file']['name']) ? $_FILES['csv_file']['name'] : '';
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allow_without_answers = isset($_POST['import_without_answers']) && $_POST['import_without_answers'] === '1';
            $allow_missing_options = isset($_POST['import_with_missing_options']) && $_POST['import_with_missing_options'] === '1';
            
            // Handle DOCX file differently
            if ($file_extension === 'docx') {
                $full_content = zonatech_extract_docx_text($file);
                if ($full_content === false) {
                    $message = 'Could not read the Word document. Make sure it\'s a valid .docx file and the ZipArchive extension is enabled.';
                    $message_type = 'error';
                    $full_content = ''; // Prevent further processing
                }
            } else {
                // Read CSV or text file content
                $full_content = file_get_contents($file);
            }
            
            if ($full_content === false) {
                $message = 'Could not read the uploaded file.';
                $message_type = 'error';
            } else {
                // Check if this is a document-style CSV (text content, not structured data)
                // Document-style CSVs can have various formats:
                // 1. Numbered questions like "1. Question text" with options "A. text"
                // 2. Multi-column format with headers like "Question,Option A,Option B..."
                // 3. Answer keys at the end like "1. A", "2. D"
                
                // Check for structured CSV with our specific required columns
                $first_line = strtok($full_content, "\n");
                $first_line_lower = strtolower($first_line);
                $has_structured_headers = (
                    strpos($first_line_lower, 'exam_type') !== false && 
                    strpos($first_line_lower, 'subject') !== false && 
                    strpos($first_line_lower, 'question_text') !== false
                );
                
                // Check for multi-column question format (like "Question,Option A,Option B,Option C,Option D")
                $has_question_option_headers = (
                    preg_match('/question/i', $first_line) && 
                    preg_match('/option\s*[a-d]/i', $first_line)
                );
                
                // Check for numbered questions anywhere in the content
                $has_numbered_questions = preg_match('/\d+\.\s+[A-Za-z]/m', $full_content);
                
                // Check for answer keys in the content (e.g., "1. A", "2. D" standalone patterns)
                // Match lines that are just answer patterns with optional whitespace
                $has_answer_keys = preg_match('/^\s*\d+\.\s*[A-E]\s*$/mi', $full_content);
                
                // Determine if document-style parsing is needed
                $is_document_style = ($has_numbered_questions || $has_question_option_headers) && !$has_structured_headers;
                
                if ($is_document_style) {
                    // Parse as document-style CSV (text with questions and options)
                    // For multi-column CSVs, we need to join columns properly
                    $lines = explode("\n", $full_content);
                    $clean_lines = array();
                    $skip_header = $has_question_option_headers; // Skip the header row if detected
                    
                    foreach ($lines as $line_index => $line) {
                        // Skip header line for multi-column format
                        if ($skip_header && $line_index === 0) continue;
                        
                        $line = trim($line);
                        if (empty($line) || strpos($line, '#') === 0) continue;
                        
                        // Skip download/watermark lines
                        if (stripos($line, 'myschoolgist') !== false || stripos($line, 'Download') !== false) continue;
                        
                        // For multi-column CSV, parse as CSV row and join columns
                        if ($has_question_option_headers && strpos($line, ',') !== false) {
                            // Parse CSV row
                            $columns = str_getcsv($line);
                            // Clean and join columns with spaces
                            $joined = implode(' ', array_map('trim', $columns));
                            // Remove multiple spaces
                            $joined = preg_replace('/\s+/', ' ', $joined);
                            $clean_lines[] = trim($joined);
                        } else {
                            // Regular text line - just clean it
                            $line = rtrim($line, ',');
                            $line = trim($line, '"');
                            $clean_lines[] = $line;
                        }
                    }
                    $content = implode("\n", $clean_lines);
                    
                    // Try to detect exam type, subject, and year from content
                    $detected_exam = 'jamb';
                    $detected_subject = 'Use of English';
                    $detected_year = intval(date('Y'));
                    
                    // Look for patterns like "USE OF ENGLISH 1978" or "JAMB 2020 Mathematics"
                    if (preg_match('/\b(JAMB|WAEC|NECO)\b/i', $content, $exam_match)) {
                        $detected_exam = strtolower($exam_match[1]);
                    }
                    if (preg_match('/\b(19[7-9]\d|20[0-2]\d)\b/', $content, $year_match)) {
                        $detected_year = intval($year_match[1]);
                    }
                    
                    // Detect subject
                    $subject_patterns = array(
                        'USE OF ENGLISH' => 'Use of English',
                        'ENGLISH LANGUAGE' => 'English Language',
                        'MATHEMATICS' => 'Mathematics',
                        'PHYSICS' => 'Physics',
                        'CHEMISTRY' => 'Chemistry',
                        'BIOLOGY' => 'Biology',
                        'ECONOMICS' => 'Economics',
                        'GOVERNMENT' => 'Government',
                        'LITERATURE' => 'Literature in English',
                        'GEOGRAPHY' => 'Geography',
                        'ACCOUNTING' => 'Accounting',
                        'COMMERCE' => 'Commerce',
                        'CIVIC' => 'Civic Education',
                        'AGRICULTURAL' => 'Agricultural Science',
                        'COMPUTER' => 'Computer Studies',
                        'HISTORY' => 'History',
                    );
                    foreach ($subject_patterns as $pattern => $subject_name) {
                        if (stripos($content, $pattern) !== false) {
                            $detected_subject = $subject_name;
                            break;
                        }
                    }
                    
                    // Check if the importer class exists
                    if (!class_exists('ZonaTech_Question_Importer')) {
                        $message = "Question importer class not found. Please ensure the plugin is properly installed.";
                        $message_type = 'error';
                    } else {
                        // Use the question importer to parse questions
                        $importer = ZonaTech_Question_Importer::get_instance();
                        
                        // Get exam type and subject from form (required now that year is removed)
                        $form_exam_type = isset($_POST['bulk_exam_type']) ? sanitize_text_field($_POST['bulk_exam_type']) : '';
                        $form_subject = isset($_POST['bulk_subject']) ? sanitize_text_field($_POST['bulk_subject']) : '';
                        
                        // Validate that exam type and subject are provided
                        if (empty($form_exam_type) || empty($form_subject)) {
                            $message = "Please select an Exam Type and Subject before uploading.";
                            $message_type = 'error';
                        } else {
                            // Check for passage-based content (English language style with inline answers)
                            $has_passages = preg_match('/^(?:#+\s*)?PASSAGE\s*\d+\s*:/im', $content);
                            $has_inline_answers = preg_match('/^Answer:?\s*[A-Ea-e]/im', $content);
                            
                            if ($has_passages || $has_inline_answers) {
                                // Use the passage-aware parser for English language style documents
                                $parsed = $importer->parse_passages_and_questions($content);
                                $questions = $parsed['questions'];
                                $passages = isset($parsed['passages']) ? $parsed['passages'] : array();
                            } else {
                                // Parse questions directly from content (no year sections required)
                                $questions = $importer->parse_questions($content);
                                $answers = $importer->parse_answers($content);
                                if (!empty($answers)) {
                                    $questions = $importer->merge_questions_with_answers($questions, $answers);
                                }
                                $passages = array();
                            }
                            
                            if (!empty($questions)) {
                                // Import passages first if we have any
                                $passage_map = array();
                                if (!empty($passages)) {
                                    $passage_map = $importer->import_passages($passages, $form_exam_type, $form_subject, intval(date('Y')));
                                    
                                    // Update question passage_ids with actual database IDs
                                    foreach ($questions as $q_key => $question) {
                                        if (!empty($question['passage_id']) && isset($passage_map[$question['passage_id']])) {
                                            $questions[$q_key]['passage_id'] = $passage_map[$question['passage_id']];
                                        } else {
                                            $questions[$q_key]['passage_id'] = null;
                                        }
                                    }
                                }
                                
                                // Use form-provided exam type and subject
                                $result = $importer->import_to_database($questions, $form_exam_type, $form_subject, intval(date('Y')), $allow_without_answers, $allow_missing_options);
                                
                                if ($result['success_count'] > 0) {
                                    $passages_count = count($passage_map);
                                    $message = "Import completed: {$result['success_count']} questions imported!";
                                    if ($passages_count > 0) {
                                        $message .= " ($passages_count passages imported)";
                                    }
                                    $message .= " (" . strtoupper($form_exam_type) . " - $form_subject)";
                                    if ($result['skipped'] > 0) {
                                        $message .= " ({$result['skipped']} duplicates skipped)";
                                    }
                                    $missing = isset($result['missing_answers']) ? $result['missing_answers'] : 0;
                                    $missing_opts = isset($result['missing_options']) ? $result['missing_options'] : 0;
                                    if ($allow_without_answers && $missing > 0) {
                                        $message .= " WARNING: {$missing} questions imported without answer keys - please review!";
                                        $message_type = 'warning';
                                    } elseif ($allow_missing_options && $missing_opts > 0) {
                                        $message .= " WARNING: {$missing_opts} questions imported with placeholder options - please review!";
                                        $message_type = 'warning';
                                    } else {
                                        $message_type = 'success';
                                    }
                                } else {
                                    $error_details = !empty($result['errors']) ? ' First 3 errors: ' . implode('; ', array_slice($result['errors'], 0, 3)) : '';
                                    $total_parsed = isset($result['total_parsed']) ? $result['total_parsed'] : 0;
                                    $missing = isset($result['missing_answers']) ? $result['missing_answers'] : 0;
                                    
                                    // Check for missing options errors
                                    $has_missing_options_errors = false;
                                    if (!empty($result['errors'])) {
                                        foreach ($result['errors'] as $error) {
                                            if (strpos($error, 'Missing one or more options') !== false) {
                                                $has_missing_options_errors = true;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    if ($missing > 0 && !$allow_without_answers) {
                                        $message = "Found {$total_parsed} questions but {$missing} are missing answer keys. Check the 'Import without answer keys' option to import anyway.";
                                    } elseif ($has_missing_options_errors && !$allow_missing_options) {
                                        $message = "Questions were found but some have missing options.$error_details Check the 'Import with missing options' box to import anyway.";
                                    } else {
                                        $message = "Questions were found but could not be imported.$error_details";
                                    }
                                    $message_type = 'error';
                                }
                            } else {
                                $message = "Could not parse questions. Make sure your document has numbered questions (1., 2., etc.) with options (A., B., C., D.).";
                                $message_type = 'error';
                            }
                        }
                    }
                } else {
                    // Parse as structured CSV with column headers
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle); // Get header row
            
            // Normalize header names to lowercase for matching
            $header_map = array();
            if ($header) {
                foreach ($header as $index => $col_name) {
                    $header_map[strtolower(trim($col_name))] = $index;
                }
            }
            
            // Map common column name variations
            $column_aliases = array(
                'exam_type' => array('exam_type', 'examtype', 'exam', 'type'),
                'subject' => array('subject', 'course', 'subject_name'),
                'year' => array('year', 'exam_year', 'yr'),
                'question_text' => array('question_text', 'question', 'questiontext', 'questions'),
                'option_a' => array('option_a', 'optiona', 'a', 'option a', 'opt_a'),
                'option_b' => array('option_b', 'optionb', 'b', 'option b', 'opt_b'),
                'option_c' => array('option_c', 'optionc', 'c', 'option c', 'opt_c'),
                'option_d' => array('option_d', 'optiond', 'd', 'option d', 'opt_d'),
                'option_e' => array('option_e', 'optione', 'e', 'option e', 'opt_e'),
                'options' => array('options', 'opts', 'choices', 'answers'),
                'correct_answer' => array('correct_answer', 'correctanswer', 'answer', 'correct', 'ans', 'correct_option', 'correct answer'),
                'explanation' => array('explanation', 'explain', 'solution', 'note')
            );
            
            // Find column indices
            $columns = array();
            foreach ($column_aliases as $field => $aliases) {
                $columns[$field] = null;
                foreach ($aliases as $alias) {
                    if (isset($header_map[$alias])) {
                        $columns[$field] = $header_map[$alias];
                        break;
                    }
                }
            }
            
            // Check if we have the required columns (year is optional now)
            $required = array('exam_type', 'subject', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer');
            $missing_columns = array();
            foreach ($required as $field) {
                if ($columns[$field] === null) {
                    $missing_columns[] = $field;
                }
            }
            
            // Check for simple Question,Answer format
            $is_simple_format = false;
            if (!empty($missing_columns) && isset($header_map['question']) && isset($header_map['answer'])) {
                $is_simple_format = true;
                $missing_columns = array(); // Clear missing columns - we'll handle this format
            }
            
            // Check for Question,Options,Correct Answer,Explanation format (with options as array string)
            $is_options_array_format = false;
            if (!empty($missing_columns) && !$is_simple_format && isset($header_map['question']) && $columns['options'] !== null) {
                $is_options_array_format = true;
                $missing_columns = array(); // Clear missing columns - we'll handle this format
            }
            
            if (!empty($missing_columns) && !$is_simple_format && !$is_options_array_format) {
                fclose($handle);
                $message = 'CSV is missing required columns: ' . implode(', ', $missing_columns) . '. Required columns: exam_type, subject, question_text, option_a, option_b, option_c, option_d, correct_answer. Or use a simple "Question,Answer" format, or "Question,Options,Correct Answer,Explanation" format, or a document-style CSV with numbered questions.';
                $message_type = 'error';
            } else if ($is_options_array_format) {
                // Handle Question,Options,Correct Answer,Explanation format
                // Options are in array format like: ['A. Elisha', 'B. Ezekiel', 'C. Elijah', 'D. Obadiah', 'E. Nehemiah']
                $form_exam_type = isset($_POST['exam_type']) ? sanitize_text_field($_POST['exam_type']) : 'jamb';
                $form_subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : 'Christian Religious Studies';
                
                $table_questions = $wpdb->prefix . 'zonatech_questions';
                $success_count = 0;
                $error_count = 0;
                $row_num = 1;
                
                while (($row = fgetcsv($handle)) !== false) {
                    $row_num++;
                    
                    $question_text = isset($row[$header_map['question']]) ? sanitize_textarea_field(trim($row[$header_map['question']])) : '';
                    $options_string = isset($row[$columns['options']]) ? trim($row[$columns['options']]) : '';
                    $correct_answer = ($columns['correct_answer'] !== null && isset($row[$columns['correct_answer']])) ? strtoupper(sanitize_text_field(trim($row[$columns['correct_answer']]))) : '';
                    $explanation = ($columns['explanation'] !== null && isset($row[$columns['explanation']])) ? sanitize_textarea_field(trim($row[$columns['explanation']])) : '';
                    
                    // Skip empty rows
                    if (empty($question_text)) {
                        continue;
                    }
                    
                    // Parse options from array string like: ['A. Elisha', 'B. Ezekiel', ...]
                    $option_a = '';
                    $option_b = '';
                    $option_c = '';
                    $option_d = '';
                    $option_e = '';
                    
                    // Remove brackets and split by comma
                    $options_string = trim($options_string, "[]");
                    // Match individual options in quotes
                    preg_match_all("/['\"]([^'\"]+)['\"]/", $options_string, $option_matches);
                    
                    if (!empty($option_matches[1])) {
                        foreach ($option_matches[1] as $opt) {
                            $opt = trim($opt);
                            // Check for A., B., C., D., E. prefixes
                            if (preg_match('/^A\.\s*(.+)$/i', $opt, $m)) {
                                $option_a = trim($m[1]);
                            } elseif (preg_match('/^B\.\s*(.+)$/i', $opt, $m)) {
                                $option_b = trim($m[1]);
                            } elseif (preg_match('/^C\.\s*(.+)$/i', $opt, $m)) {
                                $option_c = trim($m[1]);
                            } elseif (preg_match('/^D\.\s*(.+)$/i', $opt, $m)) {
                                $option_d = trim($m[1]);
                            } elseif (preg_match('/^E\.\s*(.+)$/i', $opt, $m)) {
                                $option_e = trim($m[1]);
                            }
                        }
                    }
                    
                    // Validate correct_answer is A, B, C, D, or E
                    if (!in_array($correct_answer, array('A', 'B', 'C', 'D', 'E'))) {
                        $correct_answer = 'A'; // Default to A if invalid
                    }
                    
                    // Check for missing options - handle bypass if checkbox is checked
                    $has_missing = empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d);
                    if ($has_missing) {
                        if ($allow_missing_options) {
                            // Fill in placeholders for missing options
                            if (empty($option_a)) $option_a = '[Option A - to be added]';
                            if (empty($option_b)) $option_b = '[Option B - to be added]';
                            if (empty($option_c)) $option_c = '[Option C - to be added]';
                            if (empty($option_d)) $option_d = '[Option D - to be added]';
                        } else {
                            // Skip this question - will count as error
                            $error_count++;
                            continue;
                        }
                    }
                    
                    // Store option E in explanation if it exists (DB only has A-D columns)
                    $full_explanation = $explanation;
                    if (!empty($option_e)) {
                        $full_explanation = "[OPTION_E]: $option_e\n\n$explanation";
                    }
                    
                    $result = $wpdb->insert($table_questions, array(
                        'exam_type' => $form_exam_type,
                        'subject' => $form_subject,
                        'year' => 0, // No year for merged questions
                        'question_text' => $question_text,
                        'option_a' => $option_a,
                        'option_b' => $option_b,
                        'option_c' => $option_c,
                        'option_d' => $option_d,
                        'correct_answer' => $correct_answer,
                        'explanation' => $full_explanation,
                        'created_at' => current_time('mysql')
                    ));
                    
                    if ($result) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                
                fclose($handle);
                
                if ($success_count > 0) {
                    $message = "CSV import completed: {$success_count} questions imported for " . strtoupper($form_exam_type) . " {$form_subject}!";
                    if ($error_count > 0) {
                        $message .= " ({$error_count} questions skipped - missing options)";
                    }
                    $message_type = 'success';
                } else {
                    if ($error_count > 0 && !$allow_missing_options) {
                        $message = "No questions imported. {$error_count} questions have missing options. Check the 'Import with missing options' checkbox to import them with placeholders.";
                    } else {
                        $message = 'No questions were imported. Check your CSV format.';
                    }
                    $message_type = 'error';
                }
            } else if ($is_simple_format) {
                // Handle simple Question,Answer format
                // Get exam_type and subject from the form selection
                $form_exam_type = isset($_POST['exam_type']) ? sanitize_text_field($_POST['exam_type']) : 'jamb';
                $form_subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : 'Christian Religious Studies';
                
                $table_questions = $wpdb->prefix . 'zonatech_questions';
                $success_count = 0;
                $error_count = 0;
                $row_num = 1;
                
                while (($row = fgetcsv($handle)) !== false) {
                    $row_num++;
                    
                    $question_text = isset($row[$header_map['question']]) ? sanitize_textarea_field(trim($row[$header_map['question']])) : '';
                    $answer_text = isset($row[$header_map['answer']]) ? sanitize_text_field(trim($row[$header_map['answer']])) : '';
                    
                    // Skip empty rows
                    if (empty($question_text)) {
                        continue;
                    }
                    
                    // Extract the correct answer from format like "Correct: Elijah"
                    $correct_answer_text = '';
                    if (preg_match('/^Correct:\s*(.+)$/i', $answer_text, $matches)) {
                        $correct_answer_text = trim($matches[1]);
                    } else {
                        $correct_answer_text = $answer_text;
                    }
                    
                    // For simple format, we store the answer text as the correct answer
                    // and leave options empty (or set option_a as the correct answer)
                    $result = $wpdb->insert($table_questions, array(
                        'exam_type' => $form_exam_type,
                        'subject' => $form_subject,
                        'year' => 0, // No year for merged questions
                        'question_text' => $question_text,
                        'option_a' => $correct_answer_text,
                        'option_b' => '',
                        'option_c' => '',
                        'option_d' => '',
                        'correct_answer' => 'A', // Since we put the answer in option_a
                        'explanation' => 'Answer: ' . $correct_answer_text,
                        'created_at' => current_time('mysql')
                    ));
                    
                    if ($result) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                
                fclose($handle);
                
                if ($success_count > 0) {
                    $message = "CSV import completed: {$success_count} questions imported for " . strtoupper($form_exam_type) . " {$form_subject}!";
                    if ($error_count > 0) {
                        $message .= " ({$error_count} errors)";
                    }
                    $message_type = 'success';
                } else {
                    $message = 'No questions were imported. Check your CSV format.';
                    $message_type = 'error';
                }
            } else {
                $table_questions = $wpdb->prefix . 'zonatech_questions';
                $success_count = 0;
                $error_count = 0;
                $row_num = 1;
                $skipped_rows = array();
                
                while (($row = fgetcsv($handle)) !== false) {
                    $row_num++;
                    
                    // Extract values using detected column positions
                    $exam_type = isset($row[$columns['exam_type']]) ? sanitize_text_field(trim($row[$columns['exam_type']])) : '';
                    $subject = isset($row[$columns['subject']]) ? sanitize_text_field(trim($row[$columns['subject']])) : '';
                    $year = isset($row[$columns['year']]) ? intval(trim($row[$columns['year']])) : 0;
                    $question_text = isset($row[$columns['question_text']]) ? sanitize_textarea_field(trim($row[$columns['question_text']])) : '';
                    $option_a = isset($row[$columns['option_a']]) ? sanitize_text_field(trim($row[$columns['option_a']])) : '';
                    $option_b = isset($row[$columns['option_b']]) ? sanitize_text_field(trim($row[$columns['option_b']])) : '';
                    $option_c = isset($row[$columns['option_c']]) ? sanitize_text_field(trim($row[$columns['option_c']])) : '';
                    $option_d = isset($row[$columns['option_d']]) ? sanitize_text_field(trim($row[$columns['option_d']])) : '';
                    $option_e = ($columns['option_e'] !== null && isset($row[$columns['option_e']])) ? sanitize_text_field(trim($row[$columns['option_e']])) : '';
                    $correct_answer = isset($row[$columns['correct_answer']]) ? strtoupper(sanitize_text_field(trim($row[$columns['correct_answer']]))) : '';
                    $explanation = ($columns['explanation'] !== null && isset($row[$columns['explanation']])) ? sanitize_textarea_field(trim($row[$columns['explanation']])) : '';
                    
                    // Validate required data - exam_type, subject, question_text are always required
                    if (empty($exam_type) || empty($subject) || empty($question_text)) {
                        $skipped_rows[] = $row_num;
                        continue;
                    }
                    
                    // Check for missing options A-D (Option E is optional)
                    $has_missing_opts = empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d);
                    if ($has_missing_opts) {
                        if ($allow_missing_options) {
                            // Fill in placeholders for missing options
                            if (empty($option_a)) $option_a = '[Option A - to be added]';
                            if (empty($option_b)) $option_b = '[Option B - to be added]';
                            if (empty($option_c)) $option_c = '[Option C - to be added]';
                            if (empty($option_d)) $option_d = '[Option D - to be added]';
                        } else {
                            $skipped_rows[] = $row_num;
                            continue;
                        }
                    }
                    
                    // Normalize exam type - skip row if invalid
                    $exam_type = strtolower($exam_type);
                    if (!in_array($exam_type, array('jamb', 'waec', 'neco'))) {
                        $skipped_rows[] = $row_num;
                        continue;
                    }
                    
                    // Validate correct answer - skip row if invalid
                    if (!in_array($correct_answer, array('A', 'B', 'C', 'D', 'E'))) {
                        $skipped_rows[] = $row_num;
                        continue;
                    }
                    
                    // Store Option E in explanation if present (DB only has A-D columns)
                    $full_explanation = $explanation;
                    if (!empty($option_e)) {
                        $full_explanation = "[OPTION_E]: " . $option_e . "\n\n" . $explanation;
                    }
                    
                    // Insert into database (year is optional - use 0 for merged questions)
                    $result = $wpdb->insert($table_questions, array(
                        'exam_type' => $exam_type,
                        'subject' => $subject,
                        'year' => $year > 0 ? $year : 0,
                        'question_text' => $question_text,
                        'option_a' => $option_a,
                        'option_b' => $option_b,
                        'option_c' => $option_c,
                        'option_d' => $option_d,
                        'correct_answer' => $correct_answer,
                        'explanation' => $full_explanation,
                        'created_at' => current_time('mysql')
                    ));
                    
                    if ($result) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                fclose($handle);
                
                $message = "Bulk upload completed: $success_count questions added successfully";
                if ($error_count > 0) {
                    $message .= ", $error_count database errors";
                }
                if (!empty($skipped_rows)) {
                    $skip_count = count($skipped_rows);
                    $message .= ", $skip_count rows skipped";
                    if (!$allow_missing_options) {
                        $message .= " (missing data - try checking 'Import with missing options')";
                    }
                }
                $message .= ".";
                
                if ($success_count > 0) {
                    $message_type = ($error_count > 0 || !empty($skipped_rows)) ? 'warning' : 'success';
                } else {
                    if (!empty($skipped_rows) && !$allow_missing_options) {
                        $message = "No questions imported. " . count($skipped_rows) . " rows have missing data. Check the 'Import with missing options' checkbox to import with placeholders.";
                    }
                    $message_type = 'error';
                }
            }
        }
        }
        } catch (Exception $e) {
            $message = 'An error occurred while processing the CSV file: ' . esc_html($e->getMessage());
            $message_type = 'error';
        } catch (Error $e) {
            $message = 'A critical error occurred: ' . esc_html($e->getMessage());
            $message_type = 'error';
        }
    } else {
        $message = 'Please select a CSV or Word document (.docx) file to upload.';
        $message_type = 'error';
    }
}

// Handle scratch card generation
if (isset($_POST['generate_cards']) && wp_verify_nonce($_POST['cards_nonce'], 'zonatech_generate_cards')) {
    $card_type = sanitize_text_field($_POST['card_type']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0 && $quantity <= 100) {
        $table_cards = $wpdb->prefix . 'zonatech_scratch_cards';
        $generated = 0;
        
        for ($i = 0; $i < $quantity; $i++) {
            $pin = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
            $serial = 'ZT' . date('Ymd') . strtoupper(substr(md5(mt_rand()), 0, 6));
            
            $result = $wpdb->insert($table_cards, array(
                'card_type' => $card_type,
                'serial_number' => $serial,
                'pin' => $pin,
                'status' => 'available',
                'created_at' => current_time('mysql')
            ));
            
            if ($result) $generated++;
        }
        
        $message = "$generated $card_type scratch cards generated successfully!";
        $message_type = 'success';
    } else {
        $message = 'Please enter a valid quantity (1-100).';
        $message_type = 'error';
    }
}

// Handle scratch card CSV upload
if (isset($_POST['upload_scratch_cards']) && wp_verify_nonce($_POST['upload_cards_nonce'], 'zonatech_upload_scratch_cards')) {
    if (!empty($_FILES['cards_csv_file']['tmp_name'])) {
        $card_type = sanitize_text_field($_POST['upload_card_type']);
        $valid_types = array('waec', 'neco', 'jamb');
        
        if (!in_array($card_type, $valid_types)) {
            $message = 'Invalid card type selected.';
            $message_type = 'error';
        } else {
            $file = $_FILES['cards_csv_file']['tmp_name'];
            $file_name = isset($_FILES['cards_csv_file']['name']) ? $_FILES['cards_csv_file']['name'] : '';
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Determine if this is a text-based file (CSV/TXT) or an image/PDF file
            $text_extensions = array('csv', 'txt');
            $file_extensions = array('jpg', 'jpeg', 'png', 'pdf');
            
            if (in_array($file_ext, $text_extensions)) {
                // Process CSV/TXT text file (existing logic)
                $file_content = file_get_contents($file);
                
                if ($file_content === false) {
                    $message = 'Could not read the uploaded file.';
                    $message_type = 'error';
                } else {
                    $table_cards = $wpdb->prefix . 'zonatech_scratch_cards';
                    $lines = explode("\n", $file_content);
                    $uploaded = 0;
                    $skipped = 0;
                    $is_first_line = true;
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;
                        
                        // Skip header row if it contains column names
                        if ($is_first_line) {
                            $is_first_line = false;
                            $lower_line = strtolower($line);
                            if (strpos($lower_line, 'pin') !== false || strpos($lower_line, 'serial') !== false || strpos($lower_line, 'card') !== false) {
                                continue;
                            }
                        }
                        
                        // Parse CSV line: expected format is PIN,Serial Number (or just PIN)
                        $parts = str_getcsv($line);
                        $pin = isset($parts[0]) ? sanitize_text_field(trim($parts[0])) : '';
                        $serial = isset($parts[1]) ? sanitize_text_field(trim($parts[1])) : '';
                        
                        if (empty($pin)) {
                            $skipped++;
                            continue;
                        }
                        
                        // Auto-generate serial number if not provided
                        if (empty($serial)) {
                            $serial = strtoupper($card_type) . '-SN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                        }
                        
                        // Check for duplicate PIN
                        $existing = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM $table_cards WHERE pin = %s",
                            $pin
                        ));
                        
                        if ($existing) {
                            $skipped++;
                            continue;
                        }
                        
                        $result = $wpdb->insert($table_cards, array(
                            'card_type' => $card_type,
                            'serial_number' => $serial,
                            'pin' => $pin,
                            'status' => 'available',
                            'created_at' => current_time('mysql')
                        ));
                        
                        if ($result) $uploaded++;
                        else $skipped++;
                    }
                    
                    if ($uploaded > 0) {
                        $message = "$uploaded " . strtoupper($card_type) . " scratch cards uploaded successfully!";
                        if ($skipped > 0) $message .= " ($skipped skipped - duplicate or invalid)";
                        $message_type = 'success';
                    } else {
                        $message = 'No cards were uploaded. Please check the file format (PIN,Serial per line).';
                        if ($skipped > 0) $message .= " $skipped rows were skipped (duplicate or invalid).";
                        $message_type = 'error';
                    }
                }
            } elseif (in_array($file_ext, $file_extensions)) {
                // Process image/PDF file - upload to WordPress media library and create card record
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_id = media_handle_upload('cards_csv_file', 0);
                
                if (is_wp_error($attachment_id)) {
                    $message = 'File upload failed: ' . $attachment_id->get_error_message();
                    $message_type = 'error';
                } else {
                    $file_url = wp_get_attachment_url($attachment_id);
                    $table_cards = $wpdb->prefix . 'zonatech_scratch_cards';
                    
                    // Auto-generate PIN and serial for image/PDF card uploads
                    $pin = strtoupper($card_type) . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
                    $serial = strtoupper($card_type) . '-SN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                    
                    $result = $wpdb->insert($table_cards, array(
                        'card_type' => $card_type,
                        'serial_number' => $serial,
                        'pin' => $pin,
                        'file_url' => esc_url_raw($file_url),
                        'status' => 'available',
                        'created_at' => current_time('mysql')
                    ));
                    
                    if ($result) {
                        $message = strtoupper($card_type) . " scratch card uploaded successfully as " . strtoupper($file_ext) . " file!";
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to save scratch card record to database.';
                        $message_type = 'error';
                    }
                }
            } else {
                $message = 'Unsupported file type. Please upload CSV, TXT, JPG, PNG, or PDF files.';
                $message_type = 'error';
            }
        }
    } else {
        $message = 'Please select a file to upload.';
        $message_type = 'error';
    }
}

// Handle site branding update
if (isset($_POST['save_branding_settings']) && wp_verify_nonce($_POST['branding_nonce'], 'zonatech_save_branding')) {
    $site_name = sanitize_text_field($_POST['zonatech_site_name'] ?? '');
    if (!empty($site_name)) {
        update_option('zonatech_site_name', $site_name);
    }
    
    // Handle logo upload
    if (!empty($_FILES['zonatech_logo_file']['tmp_name'])) {
        $allowed_types = array('image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml');
        $file_type = wp_check_filetype($_FILES['zonatech_logo_file']['name']);
        $mime = isset($_FILES['zonatech_logo_file']['type']) ? $_FILES['zonatech_logo_file']['type'] : '';
        
        if (in_array($mime, $allowed_types) || in_array('image/' . $file_type['ext'], $allowed_types)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('zonatech_logo_file', 0);
            if (!is_wp_error($attachment_id)) {
                update_option('zonatech_site_logo', esc_url_raw(wp_get_attachment_url($attachment_id)));
                $message = 'Branding settings updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Logo upload failed: ' . $attachment_id->get_error_message();
                $message_type = 'error';
            }
        } else {
            $message = 'Invalid file type. Please upload a PNG, JPG, GIF, WebP or SVG image.';
            $message_type = 'error';
        }
    }
    
    if (empty($message)) {
        $message = 'Branding settings updated successfully!';
        $message_type = 'success';
    }
}

// Handle Paystack settings update
if (isset($_POST['save_paystack_settings']) && wp_verify_nonce($_POST['paystack_nonce'], 'zonatech_save_paystack')) {
    $public_key = sanitize_text_field($_POST['paystack_public_key']);
    $secret_key = sanitize_text_field($_POST['paystack_secret_key']);
    
    // Don't update secret key if it's the masked placeholder
    $skip_secret_update = ($secret_key === '••••••••••••••••' || empty($secret_key));
    
    // Validate key format
    $valid = true;
    if (!empty($public_key) && !preg_match('/^pk_(test|live)_[a-zA-Z0-9]+$/', $public_key)) {
        $message = 'Invalid public key format. Key should start with pk_test_ or pk_live_ followed by alphanumeric characters';
        $message_type = 'error';
        $valid = false;
    }
    if (!$skip_secret_update && !empty($secret_key) && !preg_match('/^sk_(test|live)_[a-zA-Z0-9]+$/', $secret_key)) {
        $message = 'Invalid secret key format. Key should start with sk_test_ or sk_live_ followed by alphanumeric characters';
        $message_type = 'error';
        $valid = false;
    }
    
    if ($valid) {
        update_option('zonatech_paystack_public_key', $public_key);
        if (!$skip_secret_update) {
            update_option('zonatech_paystack_secret_key', $secret_key);
        }
        $message = 'Paystack API keys updated successfully!';
        $message_type = 'success';
    }
}

// Handle OtaPay settings update
if (isset($_POST['save_otapay_settings']) && wp_verify_nonce($_POST['otapay_nonce'], 'zonatech_save_otapay')) {
    $otapay_api_key = sanitize_text_field($_POST['otapay_api_key']);
    $otapay_enabled = isset($_POST['otapay_enabled']) ? '1' : '0';
    
    // Don't update API key if it's the masked placeholder
    $skip_key_update = ($otapay_api_key === '••••••••••••••••' || empty($otapay_api_key));
    
    if (!$skip_key_update) {
        update_option('zonatech_otapay_api_key', $otapay_api_key);
    }
    update_option('zonatech_otapay_enabled', $otapay_enabled);
    
    $message = 'OtaPay settings updated successfully!';
    $message_type = 'success';
}

// Get current Paystack settings
$paystack_public_key = get_option('zonatech_paystack_public_key', '');
$paystack_secret_key = get_option('zonatech_paystack_secret_key', '');
$is_test_mode = strpos($paystack_public_key, 'pk_test_') === 0;

// Get current OtaPay settings
$otapay_api_key = get_option('zonatech_otapay_api_key', '');
$otapay_enabled = get_option('zonatech_otapay_enabled', '0');

// Get statistics
$table_purchases = $wpdb->prefix . 'zonatech_purchases';
$table_questions = $wpdb->prefix . 'zonatech_questions';
$table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
$table_cards = $wpdb->prefix . 'zonatech_scratch_cards';
$table_nin = $wpdb->prefix . 'zonatech_nin_requests';
$table_feedback = $wpdb->prefix . 'zonatech_feedback';
$table_activity = $wpdb->prefix . 'zonatech_activity_log';

// Revenue statistics
$today_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM $table_purchases WHERE status = 'completed' AND DATE(created_at) = %s",
    date('Y-m-d')
)) ?? 0;

$this_week_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM $table_purchases WHERE status = 'completed' AND created_at >= %s",
    date('Y-m-d', strtotime('-7 days'))
)) ?? 0;

$this_month_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM $table_purchases WHERE status = 'completed' AND MONTH(created_at) = %d AND YEAR(created_at) = %d",
    date('n'), date('Y')
)) ?? 0;

$total_revenue = $wpdb->get_var(
    "SELECT COALESCE(SUM(amount), 0) FROM $table_purchases WHERE status = 'completed'"
) ?? 0;

// User statistics
$total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}") ?? 0;
$new_users_today = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->users} WHERE DATE(user_registered) = %s",
    date('Y-m-d')
)) ?? 0;
$new_users_week = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= %s",
    date('Y-m-d', strtotime('-7 days'))
)) ?? 0;

// Questions count
$total_questions = $wpdb->get_var("SELECT COUNT(*) FROM $table_questions") ?? 0;
$question_stats = $wpdb->get_results("SELECT exam_type, COUNT(*) as count FROM $table_questions GROUP BY exam_type");

// Quizzes taken
$total_quizzes = $wpdb->get_var("SELECT COUNT(*) FROM $table_quiz") ?? 0;
$quizzes_today = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_quiz WHERE DATE(created_at) = %s",
    date('Y-m-d')
)) ?? 0;

// Pending items
$pending_purchases = $wpdb->get_var("SELECT COUNT(*) FROM $table_purchases WHERE status = 'pending'") ?? 0;

// Recent purchases
$recent_purchases = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, u.display_name, u.user_email 
     FROM $table_purchases p 
     LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
     ORDER BY p.created_at DESC 
     LIMIT %d",
    10
));

// Top subjects
$top_subjects = $wpdb->get_results(
    "SELECT item_name, COUNT(*) as count, SUM(amount) as revenue 
     FROM $table_purchases 
     WHERE status = 'completed' AND purchase_type = 'subject'
     GROUP BY item_name 
     ORDER BY count DESC 
     LIMIT 5"
);

// Get uploaded subjects with question counts (grouped by exam_type and subject)
$uploaded_subjects = $wpdb->get_results(
    "SELECT exam_type, subject, COUNT(*) as question_count 
     FROM $table_questions 
     GROUP BY exam_type, subject 
     ORDER BY exam_type, subject"
);

// Create a lookup array for quick access
$uploaded_subjects_lookup = array();
foreach ($uploaded_subjects as $row) {
    $key = strtolower($row->exam_type) . '_' . strtolower($row->subject);
    $uploaded_subjects_lookup[$key] = (int) $row->question_count;
}

// Get all available subjects from the categories
$all_subjects = array();
if (class_exists('ZonaTech_Past_Questions')) {
    $categories = ZonaTech_Past_Questions::get_subject_categories();
    foreach ($categories as $cat_key => $category) {
        foreach ($category['subjects'] as $subject) {
            if (!in_array($subject, $all_subjects)) {
                $all_subjects[] = $subject;
            }
        }
    }
    sort($all_subjects);
}

// Available scratch cards
$available_cards = $wpdb->get_results(
    "SELECT card_type, COUNT(*) as count FROM $table_cards WHERE status = 'available' GROUP BY card_type"
);

// Recent users
$recent_users = $wpdb->get_results($wpdb->prepare(
    "SELECT ID, display_name, user_email, user_registered FROM {$wpdb->users} ORDER BY user_registered DESC LIMIT %d",
    10
));

// Recent feedback (check if table exists first)
$recent_feedback = array();
if ($wpdb->get_var("SHOW TABLES LIKE '$table_feedback'") == $table_feedback) {
    $recent_feedback = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_feedback ORDER BY created_at DESC LIMIT %d",
        10
    ));
}

// Activity log
$recent_activities = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, u.display_name FROM $table_activity a 
     LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
     ORDER BY a.created_at DESC LIMIT %d",
    10
));

$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ZonaTech NG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/css/main.css">
    <link rel="stylesheet" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/css/dashboard.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --primary-light: #a78bfa;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            --dark: #1f2937;
            --light: #f9fafb;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #ffffff;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(139, 92, 246, 0.2);
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .admin-logo a {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .admin-logo img {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            object-fit: contain;
            border: 2px solid rgba(139, 92, 246, 0.5);
            padding: 4px;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
            transition: all 0.3s ease;
        }
        
        .admin-logo img:hover {
            border-color: rgba(139, 92, 246, 0.8);
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
        }
        
        /* Logo fallback when image is missing */
        .admin-logo-icon {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #ffffff;
            border: 2px solid rgba(139, 92, 246, 0.3);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .admin-logo-icon:hover {
            border-color: rgba(139, 92, 246, 0.8);
            transform: scale(1.05);
        }
        
        .admin-logo-text {
            flex: 1;
            min-width: 0;
        }
        
        .admin-logo h2 {
            font-size: 16px;
            font-weight: 700;
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            line-height: 1.3;
        }
        
        .admin-logo span {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
            display: block;
            margin-top: 2px;
        }
        
        .admin-nav {
            list-style: none;
        }
        
        .admin-nav li {
            margin-bottom: 5px;
        }
        
        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .admin-nav a:hover, .admin-nav a.active {
            background: rgba(139, 92, 246, 0.2);
            color: #ffffff;
        }
        
        .admin-nav a.active {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(167, 139, 250, 0.2));
            border-left: 3px solid #8b5cf6;
        }
        
        .admin-nav i {
            width: 20px;
            text-align: center;
        }
        
        .nav-divider {
            height: 1px;
            background: rgba(139, 92, 246, 0.2);
            margin: 20px 0;
        }
        
        .admin-user {
            margin-top: auto;
            padding: 15px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 12px;
            margin-top: 30px;
        }
        
        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .admin-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .admin-user-details h4 {
            font-size: 14px;
            font-weight: 600;
        }
        
        .admin-user-details span {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            padding-right: 40px;
            max-width: calc(100vw - 280px);
            overflow-x: hidden;
            box-sizing: border-box;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .admin-header h1 {
            font-size: 28px;
            font-weight: 700;
        }
        
        .admin-header-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn-admin {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none !important;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-admin:hover {
            text-decoration: none !important;
        }
        
        .btn-admin-primary {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            background-size: 200% 200%;
            color: #ffffff;
            animation: btn-glow 3s ease-in-out infinite;
        }
        
        .btn-admin-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(139, 92, 246, 0.4);
            animation: btn-shimmer 1.5s ease-in-out infinite, btn-glow 1.5s ease-in-out infinite;
        }
        
        @keyframes btn-shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        
        @keyframes btn-glow {
            0%, 100% { box-shadow: 0 0 15px rgba(139, 92, 246, 0.3); }
            50% { box-shadow: 0 0 30px rgba(139, 92, 246, 0.6); }
        }
        
        .btn-admin-outline {
            background: transparent;
            border: 1px solid rgba(139, 92, 246, 0.5);
            color: #ffffff;
        }
        
        .btn-admin-outline:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: rgba(139, 92, 246, 0.8);
        }
        
        /* Remove all underlines from links and buttons */
        a, a:hover, a:focus, a:active,
        .admin-nav a, .admin-nav a:hover,
        .btn-admin, .btn-admin:hover,
        button, button:hover {
            text-decoration: none !important;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 10px 40px rgba(139, 92, 246, 0.2);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-card-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-card-icon.revenue { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .stat-card-icon.users { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .stat-card-icon.questions { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .stat-card-icon.pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        
        .stat-card-change {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 20px;
        }
        
        .stat-card-change.positive {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .stat-card-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* Section */
        .admin-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-header h2 i {
            color: #8b5cf6;
        }
        
        /* Tables */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th {
            text-align: left;
            padding: 12px 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .admin-table td {
            padding: 15px;
            font-size: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .admin-table tr:hover td {
            background: rgba(139, 92, 246, 0.05);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.completed {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .status-badge.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        
        .status-badge.failed {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        /* Grid Layouts */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .three-columns {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        /* Cards Info */
        .card-info {
            background: rgba(139, 92, 246, 0.1);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }
        
        .card-info h4 {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .card-info .value {
            font-size: 24px;
            font-weight: 700;
            color: #8b5cf6;
        }
        
        /* Feedback Card */
        .feedback-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .feedback-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .feedback-rating {
            color: #f59e0b;
        }
        
        .feedback-message {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.5;
        }
        
        .feedback-meta {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 10px;
        }
        
        /* User Avatar */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }
        
        .user-details {
            font-size: 14px;
        }
        
        .user-details small {
            display: block;
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
        }
        
        /* Hamburger Menu for Mobile */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 200;
            width: 45px;
            height: 45px;
            background: rgba(139, 92, 246, 0.2);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            cursor: pointer;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .mobile-menu-toggle span {
            width: 20px;
            height: 2px;
            background: #ffffff;
            transition: all 0.3s ease;
        }
        
        /* Responsive */
        @media (max-width: 1400px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .two-columns {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }
            
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-sidebar.open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
                padding: 80px 15px 30px;
                max-width: 100vw;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .three-columns {
                grid-template-columns: 1fr;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .admin-header h1 {
                font-size: 22px;
            }
        }
        
        /* Modal Styles */
        .admin-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            overflow-y: auto;
            padding: 40px 20px;
        }
        
        .admin-modal.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        
        .admin-modal-content {
            background: linear-gradient(135deg, rgba(30, 30, 50, 0.98), rgba(20, 20, 35, 0.98));
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 20px;
            padding: 30px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .admin-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .admin-modal-header h2 {
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
        }
        
        .admin-modal-header h2 i {
            color: #8b5cf6;
        }
        
        .admin-modal-close {
            width: 40px;
            height: 40px;
            border: none;
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .admin-modal-close:hover {
            background: rgba(239, 68, 68, 0.4);
        }
        
        /* Form Styles */
        .admin-form-group {
            margin-bottom: 20px;
        }
        
        .admin-form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
        }
        
        .admin-form-group input,
        .admin-form-group select,
        .admin-form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 10px;
            color: #ffffff;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .admin-form-group input:focus,
        .admin-form-group select:focus,
        .admin-form-group textarea:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }
        
        .admin-form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .admin-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .admin-form-row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        @media (max-width: 600px) {
            .admin-form-row, .admin-form-row-3 {
                grid-template-columns: 1fr;
            }
        }
        
        .admin-form-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            border: none;
            border-radius: 10px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .admin-form-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
        }
        
        /* Tabs */
        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .admin-tab {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .admin-tab:hover, .admin-tab.active {
            background: rgba(139, 92, 246, 0.2);
            border-color: rgba(139, 92, 246, 0.5);
            color: #ffffff;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* File Upload */
        .file-upload-area {
            border: 2px dashed rgba(139, 92, 246, 0.4);
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .file-upload-area:hover {
            border-color: #8b5cf6;
            background: rgba(139, 92, 246, 0.1);
        }
        
        .file-upload-area i {
            font-size: 40px;
            color: #8b5cf6;
            margin-bottom: 15px;
        }
        
        .file-upload-area p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
        }
        
        .file-upload-area small {
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
        }
        
        .file-upload-area input[type="file"] {
            display: none;
        }
        
        /* Message Alert */
        .admin-alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .admin-alert.success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #10b981;
        }
        
        .admin-alert.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #ef4444;
        }
        
        .admin-alert.warning {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #f59e0b;
        }
        
        /* Download Link */
        .download-template {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            border-radius: 8px;
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .download-template:hover {
            background: rgba(59, 130, 246, 0.3);
        }
        
        /* Section Containers */
        .admin-section-container {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-logo">
                <a href="<?php echo site_url(); ?>">
                    <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo-medium.png" alt="ZonaTech NG" class="admin-logo-img">
                </a>
                <div class="admin-logo-text">
                    <h2>ZonaTech NG</h2>
                    <span>Admin Dashboard</span>
                </div>
            </div>
            
            <ul class="admin-nav">
                <li><a href="#dashboard" class="active" onclick="switchSection('dashboard', this); return false;"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="#users" onclick="switchSection('users', this); return false;"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="#purchases" onclick="switchSection('purchases', this); return false;"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="#questions" onclick="switchSection('questions', this); return false;"><i class="fas fa-book"></i> Questions</a></li>
                <li><a href="#nin-requests" onclick="switchSection('nin-requests', this); return false;"><i class="fas fa-id-card"></i> NIN Requests</a></li>
                <li><a href="#feedback" onclick="switchSection('feedback', this); return false;"><i class="fas fa-comments"></i> Feedback</a></li>
                
                <div class="nav-divider"></div>
                
                <li><a href="#" onclick="openModal('addQuestionModal'); return false;"><i class="fas fa-plus-circle"></i> Add Questions</a></li>
                <li><a href="#" onclick="openModal('manageCardsModal'); return false;"><i class="fas fa-ticket-alt"></i> Manage Cards</a></li>
                <li><a href="#" onclick="openModal('userAccessModal'); return false;"><i class="fas fa-user-shield"></i> User Access</a></li>
                <li><a href="#" onclick="openModal('pricingModal'); return false;"><i class="fas fa-money-bill-wave"></i> Pricing</a></li>
                <li><a href="#" onclick="openModal('settingsModal'); return false;"><i class="fas fa-cog"></i> Settings</a></li>
                
                <div class="nav-divider"></div>
                
                <li><a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-user"></i> User Dashboard</a></li>
                <li><a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> View Site</a></li>
                <li><a href="<?php echo wp_logout_url(site_url()); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            
            <div class="admin-user">
                <div class="admin-user-info">
                    <div class="admin-user-avatar">
                        <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                    </div>
                    <div class="admin-user-details">
                        <h4><?php echo esc_html($current_user->display_name); ?></h4>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <?php if (!empty($message)): ?>
            <div class="admin-alert <?php echo esc_attr($message_type); ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                <?php echo esc_html($message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="admin-section-container">
            <div class="admin-header">
                <h1><i class="fas fa-chart-line"></i> Dashboard Overview</h1>
                <div class="admin-header-actions">
                    <button onclick="openModal('addQuestionModal')" class="btn-admin btn-admin-primary">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                    <button onclick="openModal('manageCardsModal')" class="btn-admin btn-admin-outline">
                        <i class="fas fa-ticket-alt"></i> Add Cards
                    </button>
                </div>
            </div>
            
            <!-- Revenue Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon revenue"><i class="fas fa-calendar-day"></i></div>
                        <span class="stat-card-change positive">Today</span>
                    </div>
                    <div class="stat-card-value">₦<?php echo number_format($today_revenue); ?></div>
                    <div class="stat-card-label">Today's Revenue</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon revenue"><i class="fas fa-calendar-week"></i></div>
                        <span class="stat-card-change positive">7 Days</span>
                    </div>
                    <div class="stat-card-value">₦<?php echo number_format($this_week_revenue); ?></div>
                    <div class="stat-card-label">This Week</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon revenue"><i class="fas fa-calendar-alt"></i></div>
                        <span class="stat-card-change positive">Month</span>
                    </div>
                    <div class="stat-card-value">₦<?php echo number_format($this_month_revenue); ?></div>
                    <div class="stat-card-label">This Month</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon revenue"><i class="fas fa-vault"></i></div>
                    </div>
                    <div class="stat-card-value">₦<?php echo number_format($total_revenue); ?></div>
                    <div class="stat-card-label">Total Revenue</div>
                </div>
            </div>
            
            <!-- Platform Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon users"><i class="fas fa-users"></i></div>
                        <span class="stat-card-change positive">+<?php echo $new_users_today; ?> today</span>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($total_users); ?></div>
                    <div class="stat-card-label">Total Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon questions"><i class="fas fa-book"></i></div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($total_questions); ?></div>
                    <div class="stat-card-label">Total Questions</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon questions"><i class="fas fa-clipboard-check"></i></div>
                        <span class="stat-card-change positive">+<?php echo $quizzes_today; ?> today</span>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($total_quizzes); ?></div>
                    <div class="stat-card-label">Quizzes Taken</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon pending"><i class="fas fa-clock"></i></div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($pending_purchases); ?></div>
                    <div class="stat-card-label">Pending Payments</div>
                </div>
            </div>
            
            <!-- Questions by Exam Type -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-book-open"></i> Questions by Exam Type</h2>
                </div>
                <div class="three-columns">
                    <?php 
                    $exam_colors = array('jamb' => '#8b5cf6', 'waec' => '#10b981', 'neco' => '#f59e0b');
                    foreach ($question_stats as $stat): 
                        $color = $exam_colors[strtolower($stat->exam_type)] ?? '#6b7280';
                    ?>
                    <div class="card-info" style="border-left: 4px solid <?php echo $color; ?>;">
                        <h4><?php echo strtoupper(esc_html($stat->exam_type)); ?></h4>
                        <div class="value"><?php echo number_format($stat->count); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Subject Upload Status Section -->
            <div class="admin-section" id="subject-upload-status">
                <div class="section-header">
                    <h2><i class="fas fa-check-circle"></i> Subject Upload Status</h2>
                    <span class="btn-admin btn-admin-outline" style="opacity: 0.7;">
                        <?php 
                        $uploaded_count = 0;
                        $total_subject_count = count($all_subjects) * 3; // 3 exam types
                        foreach (array('jamb', 'waec', 'neco') as $exam) {
                            foreach ($all_subjects as $subject) {
                                $key = $exam . '_' . strtolower($subject);
                                if (isset($uploaded_subjects_lookup[$key]) && $uploaded_subjects_lookup[$key] > 0) {
                                    $uploaded_count++;
                                }
                            }
                        }
                        echo $uploaded_count . ' of ' . $total_subject_count . ' uploaded';
                        ?>
                    </span>
                </div>
                
                <div class="subject-status-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                    <button class="btn-admin exam-tab active" onclick="showExamSubjects('jamb')" style="background: #8b5cf6;">JAMB</button>
                    <button class="btn-admin exam-tab" onclick="showExamSubjects('waec')" style="background: rgba(16, 185, 129, 0.3); border: 1px solid #10b981; color: #10b981;">WAEC</button>
                    <button class="btn-admin exam-tab" onclick="showExamSubjects('neco')" style="background: rgba(245, 158, 11, 0.3); border: 1px solid #f59e0b; color: #f59e0b;">NECO</button>
                </div>
                
                <?php foreach (array('jamb', 'waec', 'neco') as $exam_type): ?>
                <div class="exam-subjects-grid" id="subjects-<?php echo $exam_type; ?>" style="<?php echo $exam_type !== 'jamb' ? 'display: none;' : ''; ?>">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                        <?php 
                        $exam_uploaded_count = 0;
                        foreach ($all_subjects as $subject): 
                            $key = $exam_type . '_' . strtolower($subject);
                            $question_count = $uploaded_subjects_lookup[$key] ?? 0;
                            $is_uploaded = $question_count > 0;
                            if ($is_uploaded) $exam_uploaded_count++;
                        ?>
                        <div class="subject-status-card" style="
                            background: <?php echo $is_uploaded ? 'linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(16, 185, 129, 0.1) 100%)' : 'linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%)'; ?>;
                            border: 1px solid <?php echo $is_uploaded ? 'rgba(34, 197, 94, 0.3)' : 'rgba(239, 68, 68, 0.2)'; ?>;
                            border-radius: 12px;
                            padding: 15px;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            transition: all 0.3s ease;
                        ">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 10px;
                                    background: <?php echo $is_uploaded ? 'rgba(34, 197, 94, 0.2)' : 'rgba(239, 68, 68, 0.15)'; ?>;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: <?php echo $is_uploaded ? '#22c55e' : '#ef4444'; ?>;
                                    font-size: 18px;
                                ">
                                    <i class="fas <?php echo $is_uploaded ? 'fa-check' : 'fa-times'; ?>"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #fff; font-size: 14px;"><?php echo esc_html($subject); ?></div>
                                    <div style="font-size: 12px; color: <?php echo $is_uploaded ? '#22c55e' : 'rgba(239, 68, 68, 0.8)'; ?>;">
                                        <?php echo $is_uploaded ? number_format($question_count) . ' questions' : 'Not uploaded'; ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($is_uploaded): ?>
                            <div style="
                                background: rgba(34, 197, 94, 0.2);
                                color: #22c55e;
                                padding: 4px 10px;
                                border-radius: 20px;
                                font-size: 11px;
                                font-weight: 600;
                            ">UPLOADED</div>
                            <?php else: ?>
                            <div style="
                                background: rgba(239, 68, 68, 0.15);
                                color: #ef4444;
                                padding: 4px 10px;
                                border-radius: 20px;
                                font-size: 11px;
                                font-weight: 600;
                            ">PENDING</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 15px; padding: 15px; background: rgba(139, 92, 246, 0.1); border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.2);">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <span style="color: rgba(255,255,255,0.7);">
                                <strong style="color: #8b5cf6;"><?php echo strtoupper($exam_type); ?></strong> Progress: 
                                <strong style="color: #22c55e;"><?php echo $exam_uploaded_count; ?></strong> / <?php echo count($all_subjects); ?> subjects
                            </span>
                            <div style="background: rgba(255,255,255,0.1); border-radius: 20px; width: 200px; height: 8px; overflow: hidden;">
                                <div style="
                                    width: <?php echo count($all_subjects) > 0 ? ($exam_uploaded_count / count($all_subjects) * 100) : 0; ?>%;
                                    height: 100%;
                                    background: linear-gradient(90deg, #22c55e, #10b981);
                                    border-radius: 20px;
                                    transition: width 0.5s ease;
                                "></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Two Column Layout -->
            <div class="two-columns">
                <!-- Recent Purchases -->
                <div class="admin-section" id="purchases">
                    <div class="section-header">
                        <h2><i class="fas fa-shopping-cart"></i> Recent Purchases</h2>
                        <span class="btn-admin btn-admin-outline" style="opacity: 0.7;">Showing Latest 10</span>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Item</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_purchases)): ?>
                                <?php foreach ($recent_purchases as $purchase): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar"><?php echo strtoupper(substr($purchase->display_name ?? 'U', 0, 1)); ?></div>
                                            <div class="user-details">
                                                <?php echo esc_html($purchase->display_name ?? 'Unknown'); ?>
                                                <small><?php echo date('M j', strtotime($purchase->created_at)); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($purchase->item_name); ?></td>
                                    <td>₦<?php echo number_format($purchase->amount); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo esc_attr($purchase->status); ?>">
                                            <?php echo ucfirst($purchase->status); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; color: rgba(255,255,255,0.5);">No purchases yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Recent Users -->
                <div class="admin-section" id="users">
                    <div class="section-header">
                        <h2><i class="fas fa-users"></i> Recent Users</h2>
                        <span class="btn-admin btn-admin-outline" style="opacity: 0.7;">Showing Latest 10</span>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_users)): ?>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar"><?php echo strtoupper(substr($user->display_name, 0, 1)); ?></div>
                                            <span><?php echo esc_html($user->display_name); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($user->user_registered)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align: center; color: rgba(255,255,255,0.5);">No users yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- More Stats -->
            <div class="two-columns">
                <!-- Top Selling Subjects -->
                <div class="admin-section">
                    <div class="section-header">
                        <h2><i class="fas fa-star"></i> Top Selling Subjects</h2>
                    </div>
                    <?php if (!empty($top_subjects)): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Sales</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_subjects as $subject): ?>
                            <tr>
                                <td><?php echo esc_html($subject->item_name); ?></td>
                                <td><?php echo number_format($subject->count); ?></td>
                                <td>₦<?php echo number_format($subject->revenue); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: rgba(255,255,255,0.5);">No subject sales yet</p>
                    <?php endif; ?>
                </div>
                
                <!-- Scratch Cards -->
                <div class="admin-section">
                    <div class="section-header">
                        <h2><i class="fas fa-ticket-alt"></i> Available Scratch Cards</h2>
                        <button onclick="openModal('manageCardsModal')" class="btn-admin btn-admin-outline">Add More</button>
                    </div>
                    <?php if (!empty($available_cards)): ?>
                    <div class="three-columns">
                        <?php foreach ($available_cards as $card): ?>
                        <div class="card-info">
                            <h4><?php echo strtoupper(esc_html($card->card_type)); ?></h4>
                            <div class="value"><?php echo number_format($card->count); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: rgba(255,255,255,0.5);">No scratch cards available. <a href="#" onclick="openModal('manageCardsModal'); return false;" style="color: #8b5cf6;">Add some</a></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Feedback -->
            <div class="admin-section" id="feedback">
                <div class="section-header">
                    <h2><i class="fas fa-comments"></i> Recent Feedback</h2>
                    <span class="btn-admin btn-admin-outline" style="opacity: 0.7;">Showing Latest 5</span>
                </div>
                <?php if (!empty($recent_feedback)): ?>
                    <?php foreach ($recent_feedback as $fb): ?>
                    <div class="feedback-card">
                        <div class="feedback-card-header">
                            <div class="user-info">
                                <div class="user-avatar"><?php echo strtoupper(substr($fb->name, 0, 1)); ?></div>
                                <div class="user-details">
                                    <?php echo esc_html($fb->name); ?>
                                    <small><?php echo esc_html($fb->subject); ?></small>
                                </div>
                            </div>
                            <div class="feedback-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa<?php echo $i <= $fb->rating ? 's' : 'r'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="feedback-message"><?php echo esc_html(substr($fb->message, 0, 200)); ?><?php echo strlen($fb->message) > 200 ? '...' : ''; ?></p>
                        <div class="feedback-meta">
                            <i class="fas fa-envelope"></i> <?php echo esc_html($fb->email); ?> &bull; 
                            <i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($fb->created_at)); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: rgba(255,255,255,0.5);">No feedback yet</p>
                <?php endif; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Activity</h2>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Activity</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?php echo strtoupper(substr($activity->display_name ?? 'U', 0, 1)); ?></div>
                                        <span><?php echo esc_html($activity->display_name ?? 'Unknown'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($activity->action); ?></td>
                                <td><?php echo date('M j, g:i A', strtotime($activity->created_at)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; color: rgba(255,255,255,0.5);">No recent activity</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div><!-- End Dashboard Section -->
            
            <!-- Users Section -->
            <div id="users-section" class="admin-section-container" style="display: none;">
                <div class="admin-header">
                    <h1><i class="fas fa-users"></i> Users Management</h1>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon users"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($total_users); ?></div>
                        <div class="stat-card-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon revenue"><i class="fas fa-user-plus"></i></div>
                            <span class="stat-card-change positive">Today</span>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($new_users_today); ?></div>
                        <div class="stat-card-label">New Today</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon questions"><i class="fas fa-user-check"></i></div>
                            <span class="stat-card-change positive">7 Days</span>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($new_users_week); ?></div>
                        <div class="stat-card-label">New This Week</div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <div class="section-header">
                        <h2><i class="fas fa-list"></i> All Users</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Purchases</th>
                                <th>Quizzes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_users = $wpdb->get_results($wpdb->prepare(
                                "SELECT ID, display_name, user_email, user_registered FROM {$wpdb->users} ORDER BY user_registered DESC LIMIT %d",
                                50
                            ));
                            foreach ($all_users as $u): 
                                $u_purchases = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_purchases WHERE user_id = %d AND status = 'completed'", $u->ID));
                                $u_quizzes = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_quiz WHERE user_id = %d", $u->ID));
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?php echo strtoupper(substr($u->display_name, 0, 1)); ?></div>
                                        <div class="user-details"><?php echo esc_html($u->display_name); ?></div>
                                    </div>
                                </td>
                                <td><?php echo esc_html($u->user_email); ?></td>
                                <td><?php echo date('M j, Y', strtotime($u->user_registered)); ?></td>
                                <td><?php echo number_format($u_purchases); ?></td>
                                <td><?php echo number_format($u_quizzes); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- End Users Section -->
            
            <!-- Purchases Section -->
            <div id="purchases-section" class="admin-section-container" style="display: none;">
                <div class="admin-header">
                    <h1><i class="fas fa-shopping-cart"></i> Purchases Management</h1>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon revenue"><i class="fas fa-money-bill"></i></div>
                        </div>
                        <div class="stat-card-value">₦<?php echo number_format($total_revenue); ?></div>
                        <div class="stat-card-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon pending"><i class="fas fa-clock"></i></div>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($pending_purchases); ?></div>
                        <div class="stat-card-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon questions"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <?php $completed_purchases = $wpdb->get_var("SELECT COUNT(*) FROM $table_purchases WHERE status = 'completed'") ?? 0; ?>
                        <div class="stat-card-value"><?php echo number_format($completed_purchases); ?></div>
                        <div class="stat-card-label">Completed</div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <div class="section-header">
                        <h2><i class="fas fa-list"></i> All Purchases</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Item</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_purchases = $wpdb->get_results($wpdb->prepare(
                                "SELECT p.*, u.display_name, u.user_email 
                                 FROM $table_purchases p 
                                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                                 ORDER BY p.created_at DESC 
                                 LIMIT %d",
                                100
                            ));
                            foreach ($all_purchases as $p): 
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?php echo strtoupper(substr($p->display_name ?? 'U', 0, 1)); ?></div>
                                        <div class="user-details"><?php echo esc_html($p->display_name ?? 'Unknown'); ?></div>
                                    </div>
                                </td>
                                <td><?php echo esc_html($p->item_name); ?></td>
                                <td>₦<?php echo number_format($p->amount); ?></td>
                                <td><code style="font-size: 11px;"><?php echo esc_html($p->reference); ?></code></td>
                                <td>
                                    <?php if ($p->status === 'completed'): ?>
                                        <span class="status-badge success">Completed</span>
                                    <?php elseif ($p->status === 'pending'): ?>
                                        <span class="status-badge warning">Pending</span>
                                    <?php else: ?>
                                        <span class="status-badge error"><?php echo esc_html($p->status); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($p->created_at)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- End Purchases Section -->
            
            <!-- Questions Section -->
            <div id="questions-section" class="admin-section-container" style="display: none;">
                <div class="admin-header">
                    <h1><i class="fas fa-book"></i> Questions Management</h1>
                    <div class="admin-header-actions">
                        <button onclick="openModal('addQuestionModal')" class="btn-admin btn-admin-primary">
                            <i class="fas fa-plus"></i> Add Question
                        </button>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon questions"><i class="fas fa-book-open"></i></div>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($total_questions); ?></div>
                        <div class="stat-card-label">Total Questions</div>
                    </div>
                    <?php foreach ($question_stats as $stat): 
                        $color = $exam_colors[strtolower($stat->exam_type)] ?? '#6b7280';
                    ?>
                    <div class="stat-card" style="border-left: 4px solid <?php echo $color; ?>;">
                        <div class="stat-card-header">
                            <div class="stat-card-icon" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;"><i class="fas fa-graduation-cap"></i></div>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($stat->count); ?></div>
                        <div class="stat-card-label"><?php echo strtoupper(esc_html($stat->exam_type)); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Bulk Delete by Subject -->
                <div class="admin-section" style="margin-bottom: 25px;">
                    <div class="section-header">
                        <h2><i class="fas fa-trash-alt"></i> Delete Questions by Subject</h2>
                    </div>
                    <form method="POST" action="" onsubmit="return confirmBulkDelete();">
                        <?php wp_nonce_field('zonatech_delete_subject_year', 'delete_subject_year_nonce'); ?>
                        <div class="admin-form-row-3">
                            <div class="admin-form-group">
                                <label>Exam Type *</label>
                                <select name="bulk_delete_exam_type" id="bulk_delete_exam_type" required>
                                    <option value="">Select Exam</option>
                                    <option value="jamb">JAMB</option>
                                    <option value="waec">WAEC</option>
                                    <option value="neco">NECO</option>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label>Subject *</label>
                                <select name="bulk_delete_subject" id="bulk_delete_subject" required>
                                    <option value="">Select Subject</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <button type="submit" name="delete_subject_year" class="btn-admin" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                <i class="fas fa-trash"></i> Delete All Questions
                            </button>
                            <span id="bulk_delete_count" style="font-size: 14px; color: rgba(255,255,255,0.6);"></span>
                        </div>
                    </form>
                </div>
                
                <div class="admin-section">
                    <div class="section-header">
                        <h2><i class="fas fa-list"></i> Recent Questions</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Subject</th>
                                <th>Year</th>
                                <th>Question</th>
                                <th>Answer</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_questions = $wpdb->get_results($wpdb->prepare(
                                "SELECT * FROM $table_questions ORDER BY created_at DESC LIMIT %d",
                                50
                            ));
                            foreach ($all_questions as $q): 
                            ?>
                            <tr>
                                <td><span class="status-badge" style="background: <?php echo $exam_colors[strtolower($q->exam_type)] ?? '#6b7280'; ?>20; color: <?php echo $exam_colors[strtolower($q->exam_type)] ?? '#6b7280'; ?>;"><?php echo strtoupper(esc_html($q->exam_type)); ?></span></td>
                                <td><?php echo esc_html($q->subject); ?></td>
                                <td><?php echo esc_html($q->year); ?></td>
                                <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html(substr($q->question_text, 0, 80)); ?>...</td>
                                <td><span class="status-badge success"><?php echo esc_html($q->correct_answer); ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button onclick='viewQuestion(<?php echo wp_json_encode(array(
                                            "id" => $q->id,
                                            "exam_type" => $q->exam_type,
                                            "subject" => $q->subject,
                                            "year" => $q->year,
                                            "question_text" => $q->question_text,
                                            "option_a" => $q->option_a,
                                            "option_b" => $q->option_b,
                                            "option_c" => $q->option_c,
                                            "option_d" => $q->option_d,
                                            "correct_answer" => $q->correct_answer,
                                            "explanation" => $q->explanation ?? ""
                                        )); ?>)' class="btn-admin btn-admin-outline" style="padding: 5px 10px; font-size: 12px;" title="View/Edit">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="confirmDeleteQuestion(<?php echo intval($q->id); ?>, '<?php echo esc_js(substr($q->question_text, 0, 50)); ?>...')" class="btn-admin" style="padding: 5px 10px; font-size: 12px; background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- End Questions Section -->
            
            <!-- Feedback Section -->
            <div id="feedback-section" class="admin-section-container" style="display: none;">
                <div class="admin-header">
                    <h1><i class="fas fa-comments"></i> Feedback Management</h1>
                </div>
                
                <?php 
                $all_feedback = $wpdb->get_results($wpdb->prepare(
                    "SELECT f.*, u.display_name, u.user_email 
                     FROM $table_feedback f 
                     LEFT JOIN {$wpdb->users} u ON f.user_id = u.ID 
                     ORDER BY f.created_at DESC 
                     LIMIT %d",
                    50
                ));
                $feedback_count = count($all_feedback);
                ?>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon questions"><i class="fas fa-comments"></i></div>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($feedback_count); ?></div>
                        <div class="stat-card-label">Total Feedback</div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <div class="section-header">
                        <h2><i class="fas fa-list"></i> All Feedback</h2>
                    </div>
                    <?php if (!empty($all_feedback)): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_feedback as $fb): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?php echo strtoupper(substr($fb->display_name ?? 'U', 0, 1)); ?></div>
                                        <div class="user-details"><?php echo esc_html($fb->display_name ?? 'Unknown'); ?></div>
                                    </div>
                                </td>
                                <td><span class="status-badge"><?php echo esc_html($fb->feedback_type ?? 'General'); ?></span></td>
                                <td style="max-width: 400px;"><?php echo esc_html(substr($fb->message ?? '', 0, 100)); ?>...</td>
                                <td><?php echo date('M j, Y g:i A', strtotime($fb->created_at)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                        <i class="fas fa-comments" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <p>No feedback received yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div><!-- End Feedback Section -->
            
            <!-- NIN Requests Section -->
            <div id="nin-requests-section" class="admin-section-container" style="display: none;">
                <div class="admin-header">
                    <h1><i class="fas fa-id-card"></i> NIN Requests Management</h1>
                </div>
                
                <?php 
                // Get NIN requests
                $nin_requests = $wpdb->get_results(
                    "SELECT n.*, u.display_name, u.user_email, p.amount 
                     FROM $table_nin n 
                     LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID 
                     LEFT JOIN $table_purchases p ON n.purchase_id = p.id
                     ORDER BY n.created_at DESC 
                     LIMIT 100"
                );
                $pending_nin = $wpdb->get_var("SELECT COUNT(*) FROM $table_nin WHERE status = 'paid'") ?? 0;
                $fulfilled_nin = $wpdb->get_var("SELECT COUNT(*) FROM $table_nin WHERE status = 'fulfilled'") ?? 0;
                
                // Check if GVerifyer API is configured
                $gverifyer_configured = !empty(get_option('zonatech_gverifyer_api_key', ''));
                ?>
                
                <?php if ($gverifyer_configured): ?>
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #22c55e; font-size: 14px;">
                        <i class="fas fa-bolt"></i> <strong>GVerifyer API Connected</strong> - Click "Auto Verify" on pending requests to automatically verify NIN using the GVerifyer API.
                    </p>
                </div>
                <?php else: ?>
                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #f59e0b; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> <strong>Enable Auto-Verification:</strong> Configure your GVerifyer API key in <a href="#" onclick="openModal('settingsModal'); return false;" style="color: #8b5cf6; font-weight: 600;">Settings</a> to automatically verify NIN requests.
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon pending"><i class="fas fa-clock"></i></div>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($pending_nin); ?></div>
                        <div class="stat-card-label">Pending Fulfillment</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon users"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($fulfilled_nin); ?></div>
                        <div class="stat-card-label">Fulfilled</div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <div class="section-header">
                        <h2><i class="fas fa-list"></i> All NIN Requests</h2>
                    </div>
                    <?php if (!empty($nin_requests)): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Service</th>
                                <th>NIN</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nin_requests as $req): 
                                $form_data = json_decode($req->form_data ?? '{}', true);
                                $user_phone = $form_data['phone'] ?? $form_data['phone_nin'] ?? '';
                                $service_names = array(
                                    'nin_slip_download' => 'Slip Download',
                                    'nin_modification' => 'Data Modification',
                                    'nin_dob_correction' => 'DOB Correction',
                                    'nin_slip' => 'Premium Slip',
                                    'nin_standard_slip' => 'Standard Slip',
                                    'nin_verification' => 'NIN Verification',
                                    'nin_validation' => 'NIN Validation'
                                );
                                $service_type = $req->service_type ?? 'nin_slip';
                                $service_name = $service_names[$service_type] ?? $service_type;
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?php echo strtoupper(substr($req->display_name ?? 'U', 0, 1)); ?></div>
                                        <div class="user-details">
                                            <?php echo esc_html($req->display_name ?? 'Unknown'); ?>
                                            <small><?php echo esc_html($req->user_email ?? ''); ?></small>
                                            <?php if ($user_phone): ?>
                                                <small style="display: block; color: #22c55e;"><i class="fas fa-phone"></i> <?php echo esc_html($user_phone); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: rgba(139, 92, 246, 0.2); color: #a78bfa;">
                                        <?php echo esc_html($service_name); ?>
                                    </span>
                                </td>
                                <td><code style="font-size: 12px;"><?php echo esc_html($req->nin_number); ?></code></td>
                                <td>₦<?php echo number_format($req->amount ?? 0); ?></td>
                                <td>
                                    <?php if ($req->status === 'fulfilled'): ?>
                                        <span class="status-badge completed"><i class="fas fa-check"></i> Fulfilled</span>
                                    <?php elseif ($req->status === 'paid'): ?>
                                        <span class="status-badge pending"><i class="fas fa-clock"></i> Pending</span>
                                    <?php else: ?>
                                        <span class="status-badge"><?php echo esc_html($req->status); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($req->created_at)); ?></td>
                                <td>
                                    <?php if ($req->status === 'paid'): ?>
                                        <?php if ($gverifyer_configured): ?>
                                        <button onclick="autoVerifyNIN(<?php echo $req->id; ?>, '<?php echo esc_js($req->display_name); ?>', '<?php echo esc_js($form_data['verification_method'] ?? 'nin_number'); ?>')" class="btn-admin" style="padding: 6px 12px; font-size: 12px; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; margin-right: 5px;">
                                            <i class="fas fa-bolt"></i> Auto Verify
                                        </button>
                                        <?php endif; ?>
                                        <button onclick="openFulfillModal(<?php echo $req->id; ?>, '<?php echo esc_js($req->display_name); ?>', '<?php echo esc_js($req->user_email); ?>', '<?php echo esc_js($req->nin_number); ?>', '<?php echo esc_js($service_name); ?>', '<?php echo esc_js($user_phone); ?>')" class="btn-admin btn-admin-primary" style="padding: 6px 12px; font-size: 12px;">
                                            <i class="fas fa-upload"></i> Manual
                                        </button>
                                    <?php else: ?>
                                        <button onclick="openFulfillModal(<?php echo $req->id; ?>, '<?php echo esc_js($req->display_name); ?>', '<?php echo esc_js($req->user_email); ?>', '<?php echo esc_js($req->nin_number); ?>', '<?php echo esc_js($service_name); ?>', '<?php echo esc_js($user_phone); ?>')" class="btn-admin btn-admin-outline" style="padding: 6px 12px; font-size: 12px;">
                                            <i class="fas fa-reply"></i> Reply
                                        </button>
                                    <?php endif; ?>
                                    <button onclick='viewRequestDetails(<?php echo wp_json_encode($form_data); ?>)' class="btn-admin btn-admin-outline" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                        <i class="fas fa-id-card" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <p>No NIN requests yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div><!-- End NIN Requests Section -->
            
        </main>
    </div>
    
    <!-- Fulfill NIN Request Modal -->
    <div class="admin-modal" id="fulfillNINModal">
        <div class="admin-modal-content" style="max-width: 600px;">
            <div class="admin-modal-header">
                <h2><i class="fas fa-upload"></i> Fulfill NIN Request</h2>
                <button class="admin-modal-close" onclick="closeModal('fulfillNINModal')">&times;</button>
            </div>
            
            <div id="fulfill-request-info" style="background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                <p style="margin: 0;"><strong>User:</strong> <span id="fulfill-user-name"></span></p>
                <p style="margin: 5px 0;"><strong>Email:</strong> <span id="fulfill-user-email"></span></p>
                <p style="margin: 5px 0;"><strong>NIN:</strong> <span id="fulfill-nin"></span></p>
                <p style="margin: 0;"><strong>Service:</strong> <span id="fulfill-service"></span></p>
            </div>
            
            <form id="fulfill-nin-form" method="POST" enctype="multipart/form-data" onsubmit="return submitFulfillment(event)">
                <input type="hidden" name="request_id" id="fulfill-request-id">
                <?php wp_nonce_field('zonatech_fulfill_nin', 'fulfill_nonce'); ?>
                
                <div class="admin-form-group">
                    <label><i class="fas fa-file"></i> Upload Document (PDF/Image)</label>
                    <input type="file" name="nin_document" id="nin-document-file" accept=".pdf,.jpg,.jpeg,.png" style="padding: 12px;">
                    <small style="color: rgba(255,255,255,0.5);">Upload the NIN slip or document to send to the user</small>
                </div>
                
                <div class="admin-form-group">
                    <label><i class="fas fa-sticky-note"></i> Admin Notes (Optional)</label>
                    <textarea name="admin_notes" rows="2" placeholder="Any notes about this request"></textarea>
                </div>
                
                <div class="admin-form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="send_email" value="1" checked style="width: 18px; height: 18px; accent-color: #8b5cf6;">
                        <span>Send email notification to user with document</span>
                    </label>
                </div>
                
                <div class="admin-form-group">
                    <label><i class="fas fa-reply"></i> Custom Message to User (Optional)</label>
                    <textarea name="custom_message" id="custom_message" rows="3" placeholder="Add a personalized message to include in the email..."></textarea>
                </div>
                
                <div class="admin-form-group" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" onclick="openWhatsApp()" class="btn-admin" style="background: linear-gradient(135deg, #22c55e, #16a34a); padding: 12px 20px;">
                        <i class="fab fa-whatsapp"></i> Reply via WhatsApp
                    </button>
                    <button type="button" onclick="copyWhatsAppMessage()" class="btn-admin btn-admin-outline" style="padding: 12px 20px;">
                        <i class="fas fa-copy"></i> Copy WhatsApp Message
                    </button>
                </div>
                
                <button type="submit" class="admin-form-submit" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); margin-top: 10px;">
                    <i class="fas fa-paper-plane"></i> Fulfill & Send Email to User
                </button>
            </form>
        </div>
    </div>
    
    <!-- View Request Details Modal -->
    <div class="admin-modal" id="viewDetailsModal">
        <div class="admin-modal-content" style="max-width: 500px;">
            <div class="admin-modal-header">
                <h2><i class="fas fa-info-circle"></i> Request Details</h2>
                <button class="admin-modal-close" onclick="closeModal('viewDetailsModal')">&times;</button>
            </div>
            <div id="request-details-content" style="padding: 10px;"></div>
        </div>
    </div>
    
    <!-- Add Question Modal -->
    <div class="admin-modal" id="addQuestionModal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add Questions</h2>
                <button class="admin-modal-close" onclick="closeModal('addQuestionModal')">&times;</button>
            </div>
            
            <div class="admin-tabs">
                <button class="admin-tab active" onclick="switchTab('singleQuestion', this)">Single Question</button>
                <button class="admin-tab" onclick="switchTab('bulkUpload', this)">Bulk Upload</button>
            </div>
            
            <!-- Single Question Form -->
            <div class="tab-content active" id="singleQuestion">
                <form method="POST" action="">
                    <?php wp_nonce_field('zonatech_add_question', 'question_nonce'); ?>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Exam Type *</label>
                            <select name="exam_type" required>
                                <option value="">Select Exam</option>
                                <option value="jamb">JAMB</option>
                                <option value="waec">WAEC</option>
                                <option value="neco">NECO</option>
                            </select>
                        </div>
                        <div class="admin-form-group">
                            <label>Subject *</label>
                            <select name="subject" id="modalSubject" required>
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="admin-form-group">
                        <label>Question Text *</label>
                        <textarea name="question_text" placeholder="Enter the question..." required></textarea>
                    </div>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Option A *</label>
                            <input type="text" name="option_a" placeholder="First option" required>
                        </div>
                        <div class="admin-form-group">
                            <label>Option B *</label>
                            <input type="text" name="option_b" placeholder="Second option" required>
                        </div>
                    </div>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Option C *</label>
                            <input type="text" name="option_c" placeholder="Third option" required>
                        </div>
                        <div class="admin-form-group">
                            <label>Option D *</label>
                            <input type="text" name="option_d" placeholder="Fourth option" required>
                        </div>
                    </div>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Correct Answer *</label>
                            <select name="correct_answer" required>
                                <option value="">Select Answer</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="admin-form-group">
                            <label>Explanation (Optional)</label>
                            <input type="text" name="explanation" placeholder="Why this answer is correct">
                        </div>
                    </div>
                    
                    <button type="submit" name="add_single_question" class="admin-form-submit">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                </form>
            </div>
            
            <!-- Bulk Upload Form -->
            <div class="tab-content" id="bulkUpload">
                <a href="#" onclick="downloadCSVTemplate(); return false;" class="download-template">
                    <i class="fas fa-download"></i> Download CSV Template
                </a>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('zonatech_bulk_upload', 'bulk_nonce'); ?>
                    
                    <div class="admin-form-row" style="margin-bottom: 20px;">
                        <div class="admin-form-group">
                            <label><i class="fas fa-graduation-cap"></i> Exam Type *</label>
                            <select name="bulk_exam_type" id="bulkExamType" required onchange="updateBulkSubjects()">
                                <option value="">Select Exam Type</option>
                                <option value="jamb">JAMB</option>
                                <option value="waec">WAEC</option>
                                <option value="neco">NECO</option>
                            </select>
                        </div>
                        <div class="admin-form-group">
                            <label><i class="fas fa-book"></i> Subject *</label>
                            <select name="bulk_subject" id="bulkSubject" required>
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="file-upload-area" onclick="document.getElementById('csvFile').click();">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload CSV or Word Document</p>
                        <small>Supports: CSV files OR Word documents (.docx) with questions and options</small>
                        <input type="file" name="csv_file" id="csvFile" accept=".csv,.docx" onchange="handleFileSelect(this)">
                    </div>
                    
                    <p id="selectedFile" style="text-align: center; color: #8b5cf6; margin-bottom: 15px;"></p>
                    
                    <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="import_without_answers" id="import_without_answers" value="1">
                        <label for="import_without_answers" style="font-size: 14px; color: rgba(255,255,255,0.8); cursor: pointer;">
                            Import questions even without answer keys (answers default to 'A' - must be edited later)
                        </label>
                    </div>
                    
                    <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="import_with_missing_options" id="import_with_missing_options" value="1">
                        <label for="import_with_missing_options" style="font-size: 14px; color: rgba(255,255,255,0.8); cursor: pointer;">
                            Import questions even with missing options (placeholders will be added - must be edited later)
                        </label>
                    </div>
                    
                    <button type="submit" name="bulk_upload_questions" class="admin-form-submit">
                        <i class="fas fa-upload"></i> Upload Questions
                    </button>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: rgba(59, 130, 246, 0.1); border-radius: 10px;">
                    <h4 style="margin-bottom: 10px; color: #3b82f6;"><i class="fas fa-info-circle"></i> Supported File Formats</h4>
                    <p style="font-size: 13px; color: rgba(255,255,255,0.7); line-height: 1.6;">
                        <strong style="color: #10b981;"><i class="fas fa-file-word"></i> Word Documents (.docx):</strong> Upload Word documents with numbered questions and options (A., B., C., D., E.)<br><br>
                        <strong style="color: #8b5cf6;"><i class="fas fa-file-csv"></i> CSV - Structured:</strong> Each row with columns: exam_type, subject, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation<br><br>
                        <strong style="color: #8b5cf6;"><i class="fas fa-file-csv"></i> CSV - Document-style:</strong> Numbered questions (1., 2., etc.) with options (A., B., C., D., E.) and answer keys at the end
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Manage Cards Modal -->
    <div class="admin-modal" id="manageCardsModal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h2><i class="fas fa-ticket-alt"></i> Generate Scratch Cards</h2>
                <button class="admin-modal-close" onclick="closeModal('manageCardsModal')">&times;</button>
            </div>
            
            <form method="POST" action="">
                <?php wp_nonce_field('zonatech_generate_cards', 'cards_nonce'); ?>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label>Card Type *</label>
                        <select name="card_type" required>
                            <option value="">Select Type</option>
                            <option value="waec">WAEC Result Checker</option>
                            <option value="neco">NECO Result Checker</option>
                            <option value="jamb">JAMB Profile Code</option>
                        </select>
                    </div>
                    <div class="admin-form-group">
                        <label>Quantity (1-100) *</label>
                        <input type="number" name="quantity" min="1" max="100" placeholder="Number of cards" required>
                    </div>
                </div>
                
                <button type="submit" name="generate_cards" class="admin-form-submit">
                    <i class="fas fa-magic"></i> Generate Cards
                </button>
            </form>
            
            <!-- Upload Scratch Cards from CSV -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <h3 style="font-size: 16px; margin-bottom: 15px; color: rgba(255,255,255,0.8);"><i class="fas fa-upload"></i> Upload Scratch Cards from File</h3>
                <div style="padding: 12px 15px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; margin-bottom: 15px;">
                    <p style="font-size: 12px; color: rgba(255,255,255,0.8); margin: 0;">
                        <i class="fas fa-info-circle" style="color: #3b82f6;"></i> 
                        Upload scratch card data in multiple formats:<br>
                        <strong>CSV/TXT:</strong> Text file with PIN,Serial Number per line (serial optional, header auto-skipped).<br>
                        <strong>JPG/PNG/PDF:</strong> Image or PDF file of a scratch card. A unique PIN and serial number will be auto-generated.
                    </p>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('zonatech_upload_scratch_cards', 'upload_cards_nonce'); ?>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Card Type *</label>
                            <select name="upload_card_type" required>
                                <option value="">Select Type</option>
                                <option value="waec">WAEC Result Checker</option>
                                <option value="neco">NECO Result Checker</option>
                                <option value="jamb">JAMB Profile Code</option>
                            </select>
                        </div>
                        <div class="admin-form-group">
                            <label>File (CSV, TXT, JPG, PNG, PDF) *</label>
                            <input type="file" name="cards_csv_file" accept=".csv,.txt,.jpg,.jpeg,.png,.pdf" required style="padding: 8px;">
                        </div>
                    </div>
                    
                    <button type="submit" name="upload_scratch_cards" class="admin-form-submit" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                        <i class="fas fa-upload"></i> Upload Cards
                    </button>
                </form>
            </div>
            
            <div style="margin-top: 25px;">
                <h3 style="font-size: 16px; margin-bottom: 15px; color: rgba(255,255,255,0.8);"><i class="fas fa-list"></i> Current Stock</h3>
                <?php if (!empty($available_cards)): ?>
                <div class="three-columns">
                    <?php foreach ($available_cards as $card): ?>
                    <div class="card-info">
                        <h4><?php echo strtoupper(esc_html($card->card_type)); ?></h4>
                        <div class="value"><?php echo number_format($card->count); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="text-align: center; color: rgba(255,255,255,0.5);">No scratch cards in stock</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- User Access Management Modal -->
    <div class="admin-modal" id="userAccessModal">
        <div class="admin-modal-content" style="max-width: 800px;">
            <div class="admin-modal-header">
                <h2><i class="fas fa-user-shield"></i> User Access Management</h2>
                <button class="admin-modal-close" onclick="closeModal('userAccessModal')">&times;</button>
            </div>
            
            <div style="padding: 12px 15px; background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 8px; margin-bottom: 20px;">
                <p style="font-size: 13px; color: rgba(255,255,255,0.8); margin: 0;">
                    <i class="fas fa-info-circle" style="color: #8b5cf6;"></i> 
                    Search for a user, then grant or revoke access to past questions, scratch cards, and NIN services.
                </p>
            </div>
            
            <!-- Search User -->
            <div style="margin-bottom: 20px;">
                <h3 style="font-size: 16px; margin-bottom: 12px; color: rgba(255,255,255,0.8);"><i class="fas fa-search"></i> Search User</h3>
                <div class="admin-form-group">
                    <input type="text" id="ua-user-search" placeholder="Search by email, username, or name..." style="width: 100%;" autocomplete="off">
                </div>
                <div id="ua-search-results" style="display: none; max-height: 180px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; margin-top: 8px;"></div>
                <div id="ua-selected-user" style="display: none; margin-top: 10px; padding: 12px; background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <strong id="ua-selected-name" style="color: #fff;"></strong>
                            <span id="ua-selected-email" style="color: rgba(255,255,255,0.6); font-size: 12px; margin-left: 8px;"></span>
                        </div>
                        <button type="button" onclick="clearUserAccessSelection()" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 12px;">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                    <input type="hidden" id="ua-selected-user-id" value="">
                </div>
            </div>
            
            <!-- Grant Access Form -->
            <div style="margin-bottom: 25px; padding: 20px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px;">
                <h3 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.8);">
                    <i class="fas fa-plus-circle" style="color: #10b981;"></i> Grant Access
                </h3>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label>Access Type *</label>
                        <select id="ua-access-type" onchange="toggleAccessTypeFields()">
                            <option value="">Select Access Type</option>
                            <option value="past_questions">Past Questions</option>
                            <option value="scratch_cards">Scratch Cards</option>
                            <option value="nin_service">NIN Service</option>
                        </select>
                    </div>
                    <div class="admin-form-group" id="ua-exam-type-group">
                        <label>Exam Type *</label>
                        <select id="ua-exam-type">
                            <option value="">Select Exam Type</option>
                            <option value="jamb">JAMB</option>
                            <option value="waec">WAEC</option>
                            <option value="neco">NECO</option>
                        </select>
                    </div>
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group" id="ua-category-group">
                        <label>Category</label>
                        <select id="ua-category">
                            <option value="">Select Category</option>
                            <option value="all">All Categories (Science + Arts + Business)</option>
                            <option value="science">Science</option>
                            <option value="arts">Arts</option>
                            <option value="business">Business/Commercial</option>
                        </select>
                    </div>
                    <div class="admin-form-group">
                        <label>Duration *</label>
                        <select id="ua-duration">
                            <option value="monthly">1 Month</option>
                            <option value="sixmonth">6 Months</option>
                            <option value="lifetime">Lifetime (Never Expires)</option>
                        </select>
                    </div>
                </div>
                
                <button type="button" id="ua-grant-btn" onclick="grantUserAccess()" class="admin-form-submit" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-check-circle"></i> Grant Access
                </button>
                
                <div id="ua-grant-message" style="display: none; margin-top: 12px; padding: 10px 15px; border-radius: 8px; font-size: 13px;"></div>
            </div>
            
            <!-- Current Access Records -->
            <div>
                <h3 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.8);">
                    <i class="fas fa-list" style="color: #8b5cf6;"></i> Current Access Records
                    <button type="button" onclick="loadUserAccessRecords()" style="background: rgba(139, 92, 246, 0.2); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3); padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 11px; margin-left: auto;">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </h3>
                <div id="ua-access-records" style="max-height: 300px; overflow-y: auto;">
                    <p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">Select a user above to view their access records.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Settings Modal -->
    <div class="admin-modal" id="settingsModal">
        <div class="admin-modal-content" style="max-width: 700px;">
            <div class="admin-modal-header">
                <h2><i class="fas fa-cog"></i> Settings</h2>
                <button class="admin-modal-close" onclick="closeModal('settingsModal')">&times;</button>
            </div>
            
            <!-- Site Branding Section -->
            <div style="margin-bottom: 30px; padding: 20px; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px;">
                <h3 style="font-size: 18px; display: flex; align-items: center; gap: 10px; margin: 0 0 15px 0;">
                    <i class="fas fa-palette" style="color: #f59e0b;"></i> Site Branding
                </h3>
                <div style="padding: 12px 15px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; margin-bottom: 15px;">
                    <p style="font-size: 13px; color: rgba(255,255,255,0.8); margin: 0;">
                        <i class="fas fa-info-circle" style="color: #f59e0b;"></i> 
                        Customize your site name and logo. These will appear in the header, footer, and other areas of the site.
                    </p>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('zonatech_save_branding', 'branding_nonce'); ?>
                    
                    <div class="admin-form-group" style="margin-bottom: 15px;">
                        <label><i class="fas fa-heading"></i> Site Name</label>
                        <input type="text" name="zonatech_site_name" value="<?php echo esc_attr(get_option('zonatech_site_name', 'ZonaTech NG')); ?>" placeholder="e.g., ZonaTech NG">
                        <small style="color: rgba(255,255,255,0.5); font-size: 11px; display: block; margin-top: 5px;">The name displayed across the site</small>
                    </div>
                    
                    <div class="admin-form-group" style="margin-bottom: 15px;">
                        <label><i class="fas fa-image"></i> Site Logo</label>
                        <?php $current_logo = get_option('zonatech_site_logo', ''); ?>
                        <?php if (!empty($current_logo)): ?>
                        <div style="margin-bottom: 10px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px; text-align: center;">
                            <img src="<?php echo esc_url($current_logo); ?>" alt="Current Logo" style="max-height: 60px; max-width: 200px; border-radius: 4px;">
                            <p style="font-size: 11px; color: rgba(255,255,255,0.5); margin: 5px 0 0 0;">Current logo</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="zonatech_logo_file" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml" style="padding: 8px;">
                        <small style="color: rgba(255,255,255,0.5); font-size: 11px; display: block; margin-top: 5px;">Upload a new logo (PNG, JPG, GIF, WebP, or SVG). Leave empty to keep the current logo.</small>
                    </div>
                    
                    <button type="submit" name="save_branding_settings" class="admin-form-submit" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="fas fa-save"></i> Save Branding
                    </button>
                </form>
            </div>
            
            <!-- Paystack API Keys Section -->
            <div style="margin-bottom: 30px; padding: 20px; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                    <h3 style="font-size: 18px; display: flex; align-items: center; gap: 10px; margin: 0;">
                        <i class="fas fa-credit-card" style="color: #8b5cf6;"></i> Paystack API Keys
                    </h3>
                    <?php if ($is_test_mode): ?>
                    <span style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-flask"></i> TEST MODE
                    </span>
                    <?php elseif (!empty($paystack_public_key)): ?>
                    <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> LIVE MODE
                    </span>
                    <?php else: ?>
                    <span style="background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-exclamation-circle"></i> NOT CONFIGURED
                    </span>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 12px 15px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; margin-bottom: 15px;">
                    <p style="font-size: 13px; color: rgba(255,255,255,0.8); margin: 0;">
                        <i class="fas fa-info-circle" style="color: #3b82f6;"></i> 
                        Get your API keys from <a href="https://dashboard.paystack.com/#/settings/developer" target="_blank" style="color: #8b5cf6; font-weight: 600;">Paystack Dashboard</a>
                    </p>
                </div>
                
                <!-- Key Type Instructions -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <div style="padding: 10px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px;">
                        <p style="font-size: 12px; color: #f59e0b; margin: 0; font-weight: 600;"><i class="fas fa-flask"></i> Test Keys</p>
                        <p style="font-size: 11px; color: rgba(255,255,255,0.6); margin: 5px 0 0 0;">pk_test_xxx / sk_test_xxx<br>For testing payments</p>
                    </div>
                    <div style="padding: 10px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px;">
                        <p style="font-size: 12px; color: #10b981; margin: 0; font-weight: 600;"><i class="fas fa-check-circle"></i> Live Keys</p>
                        <p style="font-size: 11px; color: rgba(255,255,255,0.6); margin: 5px 0 0 0;">pk_live_xxx / sk_live_xxx<br>For real payments</p>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <?php wp_nonce_field('zonatech_save_paystack', 'paystack_nonce'); ?>
                    
                    <div class="admin-form-group">
                        <label><i class="fas fa-key"></i> Public Key <span style="color: <?php echo $is_test_mode ? '#f59e0b' : (!empty($paystack_public_key) ? '#10b981' : '#ef4444'); ?>;">(<?php echo $is_test_mode ? 'Test' : (!empty($paystack_public_key) ? 'Live' : 'Not Set'); ?>)</span></label>
                        <input type="text" name="paystack_public_key" value="<?php echo esc_attr($paystack_public_key); ?>" placeholder="pk_test_xxxxx or pk_live_xxxxx" style="font-family: monospace;">
                    </div>
                    
                    <div class="admin-form-group">
                        <label><i class="fas fa-lock"></i> Secret Key <?php echo !empty($paystack_secret_key) ? '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Set</span>' : '<span style="color: #ef4444;">Not Set</span>'; ?></label>
                        <input type="password" name="paystack_secret_key" value="<?php echo !empty($paystack_secret_key) ? '••••••••••••••••' : ''; ?>" placeholder="sk_test_xxxxx or sk_live_xxxxx" style="font-family: monospace;" <?php echo !empty($paystack_secret_key) ? 'onfocus="if(this.value===\'••••••••••••••••\')this.value=\'\';"' : ''; ?>>
                    </div>
                    
                    <button type="submit" name="save_paystack_settings" class="admin-form-submit" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="fas fa-save"></i> Save Paystack Keys
                    </button>
                </form>
                
                <!-- Webhook URL -->
                <div style="margin-top: 15px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                    <p style="font-size: 12px; color: rgba(255,255,255,0.6); margin: 0 0 8px 0;"><i class="fas fa-link"></i> Webhook URL (add to Paystack):</p>
                    <code style="display: block; font-size: 10px; word-break: break-all; color: #a78bfa; background: rgba(0,0,0,0.3); padding: 8px; border-radius: 6px;">
                        <?php echo home_url('/wp-admin/admin-ajax.php?action=zonatech_paystack_webhook'); ?>
                    </code>
                </div>
            </div>
            
            <!-- OtaPay API Section -->
            <div style="margin-bottom: 30px; padding: 20px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                    <h3 style="font-size: 18px; display: flex; align-items: center; gap: 10px; margin: 0;">
                        <i class="fas fa-ticket-alt" style="color: #10b981;"></i> OtaPay.ng API (Scratch Cards)
                    </h3>
                    <?php if ($otapay_enabled === '1' && !empty($otapay_api_key)): ?>
                    <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> ENABLED
                    </span>
                    <?php else: ?>
                    <span style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-pause-circle"></i> DISABLED
                    </span>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 12px 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; margin-bottom: 15px;">
                    <p style="font-size: 13px; color: rgba(255,255,255,0.8); margin: 0;">
                        <i class="fas fa-info-circle" style="color: #10b981;"></i> 
                        OtaPay enables automatic purchase of <strong>WAEC</strong> and <strong>NECO</strong> scratch cards. Get your API key from <a href="https://app.otapay.ng" target="_blank" style="color: #10b981; font-weight: 600;">OtaPay.ng</a>
                    </p>
                </div>
                
                <form method="POST" action="">
                    <?php wp_nonce_field('zonatech_save_otapay', 'otapay_nonce'); ?>
                    
                    <div class="admin-form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="otapay_enabled" value="1" <?php checked($otapay_enabled, '1'); ?> style="width: 18px; height: 18px; accent-color: #10b981;">
                            <span>Enable OtaPay Auto-Purchase</span>
                        </label>
                        <small style="color: rgba(255,255,255,0.5); font-size: 11px; display: block; margin-top: 5px;">When enabled, WAEC/NECO cards will be purchased automatically from OtaPay after payment</small>
                    </div>
                    
                    <div class="admin-form-group">
                        <label><i class="fas fa-key"></i> OtaPay API Key <?php echo !empty($otapay_api_key) ? '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Set</span>' : '<span style="color: #ef4444;">Not Set</span>'; ?></label>
                        <input type="password" name="otapay_api_key" value="<?php echo !empty($otapay_api_key) ? '••••••••••••••••' : ''; ?>" placeholder="Your OtaPay API key" style="font-family: monospace;" <?php echo !empty($otapay_api_key) ? 'onfocus="if(this.value===\'••••••••••••••••\')this.value=\'\';"' : ''; ?>>
                        <small style="color: rgba(255,255,255,0.5); font-size: 11px; display: block; margin-top: 5px;">Your secret API key from OtaPay dashboard</small>
                    </div>
                    
                    <button type="submit" name="save_otapay_settings" class="admin-form-submit" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-save"></i> Save OtaPay Settings
                    </button>
                </form>
                
                <!-- OtaPay Provider Info -->
                <div style="margin-top: 15px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                    <p style="font-size: 12px; color: rgba(255,255,255,0.6); margin: 0 0 8px 0;"><i class="fas fa-tags"></i> Supported Providers & Prices:</p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <span style="background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 6px 12px; border-radius: 6px; font-size: 12px;"><strong>WAEC</strong> - ₦3,850</span>
                        <span style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 6px 12px; border-radius: 6px; font-size: 12px;"><strong>NECO</strong> - ₦2,550</span>
                    </div>
                </div>
            </div>
            
            <!-- GVerifyer API Section -->
            <div style="margin-bottom: 30px; padding: 20px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px;">
                <?php 
                $gverifyer_api_key = get_option('zonatech_gverifyer_api_key', '');
                $gverifyer_configured = !empty($gverifyer_api_key);
                ?>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                    <h3 style="font-size: 18px; display: flex; align-items: center; gap: 10px; margin: 0;">
                        <i class="fas fa-id-card" style="color: #3b82f6;"></i> GVerifyer API (NIN Auto-Verification)
                    </h3>
                    <?php if ($gverifyer_configured): ?>
                    <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> CONFIGURED
                    </span>
                    <?php else: ?>
                    <span style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-exclamation-circle"></i> NOT CONFIGURED
                    </span>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 12px 15px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; margin-bottom: 15px;">
                    <p style="font-size: 13px; color: rgba(255,255,255,0.8); margin: 0;">
                        <i class="fas fa-info-circle" style="color: #3b82f6;"></i> 
                        GVerifyer enables <strong>automatic NIN verification</strong> after payment. Get your API key from <a href="https://gverifyer.com" target="_blank" style="color: #3b82f6; font-weight: 600;">gverifyer.com</a>
                    </p>
                </div>
                
                <!-- Supported Verification Methods -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px;">
                    <div style="padding: 10px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 8px; text-align: center;">
                        <p style="font-size: 12px; color: #22c55e; margin: 0; font-weight: 600;"><i class="fas fa-id-badge"></i> NIN Number</p>
                        <p style="font-size: 11px; color: rgba(255,255,255,0.6); margin: 5px 0 0 0;">Verify by 11-digit NIN</p>
                    </div>
                    <div style="padding: 10px; background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 8px; text-align: center;">
                        <p style="font-size: 12px; color: #a78bfa; margin: 0; font-weight: 600;"><i class="fas fa-phone"></i> Phone Number</p>
                        <p style="font-size: 11px; color: rgba(255,255,255,0.6); margin: 5px 0 0 0;">Verify by phone</p>
                    </div>
                    <div style="padding: 10px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; text-align: center;">
                        <p style="font-size: 12px; color: #f59e0b; margin: 0; font-weight: 600;"><i class="fas fa-user"></i> Demographics</p>
                        <p style="font-size: 11px; color: rgba(255,255,255,0.6); margin: 5px 0 0 0;">Name, DOB, Gender</p>
                    </div>
                </div>
                
                <div id="gverifyer-form">
                    <div class="admin-form-group">
                        <label><i class="fas fa-key"></i> GVerifyer API Key <?php echo $gverifyer_configured ? '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Set</span>' : '<span style="color: #ef4444;">Not Set</span>'; ?></label>
                        <input type="password" id="gverifyer_api_key" value="<?php echo $gverifyer_configured ? '••••••••••••••••' : ''; ?>" placeholder="Your GVerifyer API key" style="font-family: monospace;" <?php echo $gverifyer_configured ? 'onfocus="if(this.value===\'••••••••••••••••\')this.value=\'\';"' : ''; ?>>
                        <small style="color: rgba(255,255,255,0.5); font-size: 11px; display: block; margin-top: 5px;">Your API key from GVerifyer dashboard</small>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="button" onclick="saveGVerifyerSettings()" class="admin-form-submit" style="background: linear-gradient(135deg, #3b82f6, #2563eb); flex: 1;">
                            <i class="fas fa-save"></i> Save GVerifyer Key
                        </button>
                        <button type="button" onclick="testGVerifyerConnection()" class="admin-form-submit" style="background: linear-gradient(135deg, #22c55e, #16a34a); flex: 1;">
                            <i class="fas fa-plug"></i> Test Connection
                        </button>
                    </div>
                </div>
                
                <div id="gverifyer-result" style="margin-top: 15px; display: none;"></div>
                
                <!-- API Endpoints Info -->
                <div style="margin-top: 15px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                    <p style="font-size: 12px; color: rgba(255,255,255,0.6); margin: 0 0 8px 0;"><i class="fas fa-link"></i> API Endpoints:</p>
                    <div style="font-size: 10px; color: #a78bfa; font-family: monospace;">
                        <p style="margin: 3px 0;">POST https://gverifyer.com/api/verification/nin_by_nin.php</p>
                        <p style="margin: 3px 0;">POST https://gverifyer.com/api/verification/nin_by_phone.php</p>
                        <p style="margin: 3px 0;">POST https://gverifyer.com/api/verification/nin_by_demo.php</p>
                    </div>
                </div>
            </div>
            
            <!-- UltraMsg WhatsApp API Section -->
            <div style="padding: 20px; background: rgba(37, 211, 102, 0.1); border: 1px solid rgba(37, 211, 102, 0.3); border-radius: 12px; margin-bottom: 20px;">
                <?php
                $ultramsg_instance_id = get_option('zonatech_ultramsg_instance_id', '');
                $ultramsg_token = get_option('zonatech_ultramsg_token', '');
                $admin_whatsapp = get_option('zonatech_admin_whatsapp', '');
                $ultramsg_configured = !empty($ultramsg_instance_id) && !empty($ultramsg_token);
                ?>
                <h3 style="font-size: 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <span>
                        <i class="fab fa-whatsapp" style="color: #25d366;"></i> UltraMsg WhatsApp API (Auto-Notifications)
                    </span>
                    <?php if ($ultramsg_configured): ?>
                        <span style="font-size: 11px; background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-check-circle"></i> Configured
                        </span>
                    <?php endif; ?>
                </h3>
                
                <div style="background: rgba(37, 211, 102, 0.08); padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; color: rgba(255,255,255,0.8);">
                    <i class="fas fa-info-circle" style="color: #25d366;"></i>
                    UltraMsg enables <strong>automatic WhatsApp notifications</strong> to your phone for NIN service payments. Free tier includes 500 messages/month. 
                    <a href="https://ultramsg.com" target="_blank" style="color: #25d366; font-weight: 600;">Get started at ultramsg.com</a>
                </div>
                
                <!-- Setup Guide -->
                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 12px;">
                    <h4 style="color: #25d366; margin-bottom: 10px; font-size: 13px;"><i class="fas fa-book"></i> Setup Guide</h4>
                    <ol style="padding-left: 20px; margin: 0; color: rgba(255,255,255,0.7); line-height: 1.8;">
                        <li>Go to <a href="https://ultramsg.com" target="_blank" style="color: #25d366;">ultramsg.com</a> and create a free account</li>
                        <li>Click "Create New Instance" in your dashboard</li>
                        <li>Scan the QR code with your WhatsApp to link your phone</li>
                        <li>Copy your <strong>Instance ID</strong> and <strong>Token</strong> from the instance page</li>
                        <li>Paste them below and save</li>
                    </ol>
                </div>
                
                <div id="ultramsg-form">
                    <?php $ultramsg_api_url = get_option('zonatech_ultramsg_api_url', ''); ?>
                    <div class="admin-form-group" style="margin-bottom: 15px;">
                        <label><i class="fas fa-link"></i> API URL (from your UltraMsg dashboard)</label>
                        <input type="text" id="ultramsg_api_url" value="<?php echo esc_attr($ultramsg_api_url); ?>" placeholder="e.g., https://api.ultramsg.com/instance12345/" style="font-family: monospace;">
                        <small style="color: rgba(255,255,255,0.5); font-size: 11px; display: block; margin-top: 5px;">Copy the full API URL from your UltraMsg instance page (includes your Instance ID)</small>
                    </div>
                    
                    <div class="admin-form-group" style="margin-bottom: 15px;">
                        <label><i class="fas fa-hashtag"></i> Instance ID <?php echo $ultramsg_configured ? '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Set</span>' : '<span style="color: #ef4444;">Not Set</span>'; ?></label>
                        <input type="text" id="ultramsg_instance_id" value="<?php echo esc_attr($ultramsg_instance_id); ?>" placeholder="Your UltraMsg Instance ID (e.g., instance12345)" style="font-family: monospace;">
                        <small style="color: rgba(255,255,255,0.5); font-size: 11px; display: block; margin-top: 5px;">Found on your UltraMsg instance page (or extracted from API URL)</small>
                    </div>
                    
                    <div class="admin-form-group" style="margin-bottom: 15px;">
                        <label><i class="fas fa-key"></i> Token <?php echo $ultramsg_configured ? '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Set</span>' : '<span style="color: #ef4444;">Not Set</span>'; ?></label>
                        <input type="password" id="ultramsg_token" value="<?php echo $ultramsg_configured ? '••••••••••••••••' : ''; ?>" placeholder="Your UltraMsg Token" style="font-family: monospace;" <?php echo $ultramsg_configured ? 'onfocus="if(this.value===\'••••••••••••••••\')this.value=\'\';"' : ''; ?>>
                        <small style="color: rgba(255,255,255,0.5); font-size: 11px; display: block; margin-top: 5px;">Your API token from UltraMsg</small>
                    </div>
                    
                    <div class="admin-form-group" style="margin-bottom: 15px;">
                        <label><i class="fas fa-phone"></i> Admin WhatsApp Number</label>
                        <input type="text" id="admin_whatsapp" value="<?php echo esc_attr($admin_whatsapp); ?>" placeholder="e.g., 08012345678">
                        <small style="color: rgba(255,255,255,0.5); font-size: 11px; display: block; margin-top: 5px;">Your WhatsApp number to receive notifications (Nigerian format)</small>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button type="button" onclick="saveUltraMsgSettings()" class="admin-form-submit" style="background: linear-gradient(135deg, #25d366, #128c7e); flex: 1;">
                            <i class="fas fa-save"></i> Save UltraMsg Settings
                        </button>
                        <button type="button" onclick="testUltraMsgConnection()" class="admin-form-submit" style="background: linear-gradient(135deg, #10b981, #059669); flex: 1;">
                            <i class="fas fa-paper-plane"></i> Test Connection
                        </button>
                    </div>
                </div>
                
                <div id="ultramsg-result" style="margin-top: 15px; display: none;"></div>
            </div>
            
            <!-- Pricing Info -->
            <div style="padding: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px;">
                <h3 style="font-size: 16px; margin-bottom: 15px;"><i class="fas fa-sliders-h"></i> Pricing Info</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
                    <div class="card-info">
                        <h4>Subject</h4>
                        <div class="value">₦5,000</div>
                    </div>
                    <div class="card-info" style="border-left: 3px solid #22c55e;">
                        <h4>WAEC Card</h4>
                        <div class="value" style="color: #22c55e;">₦3,850</div>
                    </div>
                    <div class="card-info" style="border-left: 3px solid #f59e0b;">
                        <h4>NECO Card</h4>
                        <div class="value" style="color: #f59e0b;">₦2,550</div>
                    </div>
                    <div class="card-info">
                        <h4>NIN Slip</h4>
                        <div class="value">₦1-2K</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pricing Management Modal -->
    <div class="admin-modal" id="pricingModal">
        <div class="admin-modal-content" style="max-width: 800px;">
            <div class="admin-modal-header">
                <h2><i class="fas fa-money-bill-wave"></i> Pricing Management</h2>
                <button class="admin-modal-close" onclick="closeModal('pricingModal')">&times;</button>
            </div>
            
            <div style="padding: 12px 15px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; margin-bottom: 20px;">
                <p style="font-size: 13px; color: rgba(255,255,255,0.8); margin: 0;">
                    <i class="fas fa-info-circle" style="color: #3b82f6;"></i> 
                    All prices are in Nigerian Naira (₦). Changes take effect immediately.
                </p>
            </div>
            
            <div id="pricing-form-container">
                <!-- Past Questions & Subscriptions Section -->
                <div style="margin-bottom: 25px; padding: 20px; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px;">
                    <h3 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-book" style="color: #8b5cf6;"></i> Past Questions & Subscriptions
                    </h3>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label><i class="fas fa-tag"></i> Single Subject Price (₦)</label>
                            <input type="number" id="pricing_subject_price" min="0" step="100" placeholder="e.g., 5000">
                            <small style="color: rgba(255,255,255,0.5);">Price per subject access</small>
                        </div>
                        <div class="admin-form-group">
                            <label><i class="fas fa-calendar"></i> Monthly Subscription (₦)</label>
                            <input type="number" id="pricing_monthly_price" min="0" step="100" placeholder="e.g., 3000">
                            <small style="color: rgba(255,255,255,0.5);">Monthly access to all subjects</small>
                        </div>
                    </div>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label><i class="fas fa-calendar-alt"></i> 6-Month Subscription (₦)</label>
                            <input type="number" id="pricing_6month_price" min="0" step="100" placeholder="e.g., 15000">
                            <small style="color: rgba(255,255,255,0.5);">6-month access to all subjects</small>
                        </div>
                        <div class="admin-form-group">
                            <label><i class="fas fa-gift"></i> Free Questions Limit</label>
                            <input type="number" id="pricing_free_questions_limit" min="0" step="1" placeholder="e.g., 10">
                            <small style="color: rgba(255,255,255,0.5);">Number of free questions before payment</small>
                        </div>
                    </div>
                </div>
                
                <!-- Scratch Cards Section -->
                <div style="margin-bottom: 25px; padding: 20px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px;">
                    <h3 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-ticket-alt" style="color: #10b981;"></i> Scratch Cards
                    </h3>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label><i class="fas fa-graduation-cap"></i> WAEC Card Price (₦)</label>
                            <input type="number" id="pricing_waec_card_price" min="0" step="50" placeholder="e.g., 3850">
                            <small style="color: rgba(255,255,255,0.5);">WAEC result checker card</small>
                        </div>
                        <div class="admin-form-group">
                            <label><i class="fas fa-graduation-cap"></i> NECO Card Price (₦)</label>
                            <input type="number" id="pricing_neco_card_price" min="0" step="50" placeholder="e.g., 2550">
                            <small style="color: rgba(255,255,255,0.5);">NECO result checker card</small>
                        </div>
                    </div>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label><i class="fas fa-graduation-cap"></i> JAMB Card Price (₦)</label>
                            <input type="number" id="pricing_jamb_card_price" min="0" step="50" placeholder="e.g., 5000">
                            <small style="color: rgba(255,255,255,0.5);">JAMB profile/result card</small>
                        </div>
                        <div class="admin-form-group">
                            <label><i class="fas fa-credit-card"></i> Default Card Price (₦)</label>
                            <input type="number" id="pricing_scratch_card_price" min="0" step="50" placeholder="e.g., 3500">
                            <small style="color: rgba(255,255,255,0.5);">Fallback for other card types</small>
                        </div>
                    </div>
                </div>
                
                <!-- NIN Services Section -->
                <div style="margin-bottom: 25px; padding: 20px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px;">
                    <h3 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-id-card" style="color: #3b82f6;"></i> NIN Services
                    </h3>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label><i class="fas fa-file-alt"></i> Premium NIN Slip (₦)</label>
                            <input type="number" id="pricing_nin_slip_price" min="0" step="100" placeholder="e.g., 2000">
                            <small style="color: rgba(255,255,255,0.5);">Premium slip with full details</small>
                        </div>
                        <div class="admin-form-group">
                            <label><i class="fas fa-file"></i> Standard NIN Slip (₦)</label>
                            <input type="number" id="pricing_nin_standard_slip_price" min="0" step="100" placeholder="e.g., 1000">
                            <small style="color: rgba(255,255,255,0.5);">Standard slip with basic details</small>
                        </div>
                    </div>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label><i class="fas fa-download"></i> NIN Slip Download (₦)</label>
                            <input type="number" id="pricing_nin_slip_download_price" min="0" step="100" placeholder="e.g., 500">
                            <small style="color: rgba(255,255,255,0.5);">Direct NIN slip download</small>
                        </div>
                        <div class="admin-form-group">
                            <label><i class="fas fa-edit"></i> NIN Modification (₦)</label>
                            <input type="number" id="pricing_nin_modification_price" min="0" step="100" placeholder="e.g., 3000">
                            <small style="color: rgba(255,255,255,0.5);">NIN data modification service</small>
                        </div>
                    </div>
                    
                    <div class="admin-form-group">
                        <label><i class="fas fa-calendar-check"></i> DOB Correction (₦)</label>
                        <input type="number" id="pricing_nin_dob_correction_price" min="0" step="100" placeholder="e.g., 5000">
                        <small style="color: rgba(255,255,255,0.5);">Date of birth correction service</small>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="savePricing()" class="admin-form-submit" style="flex: 1; background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="fas fa-save"></i> Save All Prices
                    </button>
                    <button type="button" onclick="loadPricing()" class="admin-form-submit" style="flex: 0.5; background: linear-gradient(135deg, #6b7280, #4b5563);">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                
                <div id="pricing-result" style="margin-top: 15px; display: none;"></div>
            </div>
        </div>
    </div>
    
    <!-- View/Edit Question Modal -->
    <div class="admin-modal" id="viewQuestionModal">
        <div class="admin-modal-content" style="max-width: 700px;">
            <div class="admin-modal-header">
                <h2><i class="fas fa-edit"></i> View/Edit Question</h2>
                <button class="admin-modal-close" onclick="closeModal('viewQuestionModal')">&times;</button>
            </div>
            
            <form method="POST" action="">
                <?php wp_nonce_field('zonatech_edit_question', 'edit_question_nonce'); ?>
                <input type="hidden" name="question_id" id="edit_question_id">
                
                <div class="admin-form-row-3">
                    <div class="admin-form-group">
                        <label>Exam Type *</label>
                        <select name="exam_type" id="edit_exam_type" required>
                            <option value="jamb">JAMB</option>
                            <option value="waec">WAEC</option>
                            <option value="neco">NECO</option>
                        </select>
                    </div>
                    <div class="admin-form-group">
                        <label>Subject *</label>
                        <select name="subject" id="edit_subject" required>
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                </div>
                
                <div class="admin-form-group">
                    <label>Question Text *</label>
                    <textarea name="question_text" id="edit_question_text" required></textarea>
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label>Option A *</label>
                        <input type="text" name="option_a" id="edit_option_a" required>
                    </div>
                    <div class="admin-form-group">
                        <label>Option B *</label>
                        <input type="text" name="option_b" id="edit_option_b" required>
                    </div>
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label>Option C *</label>
                        <input type="text" name="option_c" id="edit_option_c" required>
                    </div>
                    <div class="admin-form-group">
                        <label>Option D *</label>
                        <input type="text" name="option_d" id="edit_option_d" required>
                    </div>
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label>Correct Answer *</label>
                        <select name="correct_answer" id="edit_correct_answer" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <div class="admin-form-group">
                        <label>Explanation (Optional)</label>
                        <input type="text" name="explanation" id="edit_explanation">
                    </div>
                </div>
                
                <button type="submit" name="edit_question" class="admin-form-submit">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
    
    <!-- Delete Question Hidden Form -->
    <form id="deleteQuestionForm" method="POST" action="" style="display: none;">
        <?php wp_nonce_field('zonatech_delete_question', 'delete_question_nonce'); ?>
        <input type="hidden" name="question_id" id="delete_question_id">
        <input type="hidden" name="delete_question" value="1">
    </form>
    
    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
        }
        
        // Toggle exam subjects display in Subject Upload Status section
        function showExamSubjects(examType) {
            // Hide all exam subject grids
            document.querySelectorAll('.exam-subjects-grid').forEach(grid => {
                grid.style.display = 'none';
            });
            
            // Show the selected exam type grid
            document.getElementById('subjects-' + examType).style.display = 'block';
            
            // Update tab active states
            document.querySelectorAll('.exam-tab').forEach(tab => {
                tab.classList.remove('active');
                tab.style.background = '';
                if (tab.textContent.toLowerCase().includes(examType)) {
                    tab.classList.add('active');
                    const colors = {
                        'jamb': '#8b5cf6',
                        'waec': '#10b981',
                        'neco': '#f59e0b'
                    };
                    tab.style.background = colors[examType];
                    tab.style.color = '#fff';
                    tab.style.border = 'none';
                } else {
                    // Reset inactive tabs to their original style
                    const tabExam = tab.textContent.toLowerCase().trim();
                    const inactiveColors = {
                        'jamb': 'rgba(139, 92, 246, 0.3)',
                        'waec': 'rgba(16, 185, 129, 0.3)',
                        'neco': 'rgba(245, 158, 11, 0.3)'
                    };
                    const borderColors = {
                        'jamb': '#8b5cf6',
                        'waec': '#10b981',
                        'neco': '#f59e0b'
                    };
                    tab.style.background = inactiveColors[tabExam];
                    tab.style.border = '1px solid ' + borderColors[tabExam];
                    tab.style.color = borderColors[tabExam];
                }
            });
        }
        
        // Section switching for admin navigation
        function switchSection(sectionId, link) {
            // Hide all section containers
            document.querySelectorAll('.admin-section-container').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show the selected section
            const targetSection = document.getElementById(sectionId + '-section');
            if (targetSection) {
                targetSection.style.display = 'block';
            }
            
            // Update active state in navigation
            document.querySelectorAll('.admin-nav a').forEach(navLink => {
                navLink.classList.remove('active');
            });
            link.classList.add('active');
            
            // Close mobile sidebar
            if (window.innerWidth <= 768) {
                document.getElementById('adminSidebar').classList.remove('open');
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('adminSidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Close modal on backdrop click
        document.querySelectorAll('.admin-modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Tab switching
        function switchTab(tabId, button) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.admin-tab').forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            button.classList.add('active');
        }
        
        // File upload handling
        function handleFileSelect(input) {
            const fileName = input.files[0]?.name;
            if (fileName) {
                document.getElementById('selectedFile').textContent = 'Selected: ' + fileName;
            }
        }
        
        // Download CSV template
        function downloadCSVTemplate() {
            const headers = 'exam_type,subject,question_text,option_a,option_b,option_c,option_d,correct_answer,explanation\n';
            const example = 'jamb,Mathematics,"What is 2 + 2?",3,4,5,6,B,"2 + 2 equals 4"\n';
            const simpleFormat = '\n# OR use simple Question,Answer format:\n# Question,Answer\n# "Who said this?","Correct: Elijah"\n';
            const blob = new Blob([headers + example + simpleFormat], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'zonatech_questions_template.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        // Subject dropdown population based on exam type
        const subjects = {
            jamb: ['Use of English', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Agricultural Science', 'Economics', 'Commerce', 'Accounting', 'Government', 'Geography', 'Literature in English', 'Christian Religious Studies', 'Islamic Religious Studies', 'History', 'Civic Education', 'Home Economics', 'Food & Nutrition', 'Fine Arts', 'Music', 'French', 'Arabic', 'Hausa', 'Igbo', 'Yoruba', 'Physical Education'],
            waec: ['English Language', 'Mathematics', 'Civic Education', 'Physics', 'Chemistry', 'Biology', 'Agricultural Science', 'Further Mathematics', 'Health Education', 'Economics', 'Commerce', 'Financial Accounting', 'Literature in English', 'Government', 'History', 'Christian Religious Studies', 'Islamic Religious Studies', 'Geography', 'Fine Arts', 'Music', 'French', 'Arabic', 'Hausa', 'Igbo', 'Yoruba', 'Data Processing', 'Computer Studies', 'Animal Husbandry', 'Technical Drawing'],
            neco: ['English Language', 'Mathematics', 'Civic Education', 'Physics', 'Chemistry', 'Biology', 'Agricultural Science', 'Further Mathematics', 'Health Science', 'Economics', 'Commerce', 'Financial Accounting', 'Literature in English', 'Government', 'History', 'Christian Religious Studies', 'Islamic Religious Studies', 'Geography', 'Fine Arts', 'Music', 'French', 'Arabic', 'Hausa', 'Igbo', 'Yoruba', 'Computer Studies', 'Data Processing', 'Marketing', 'Home Economics', 'Animal Husbandry', 'Technical Drawing']
        };
        
        // Update subjects for single question form
        document.querySelector('select[name="exam_type"]')?.addEventListener('change', function() {
            const subjectSelect = document.getElementById('modalSubject');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            
            const examSubjects = subjects[this.value] || [];
            examSubjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                subjectSelect.appendChild(option);
            });
        });
        
        // Update subjects for bulk upload form
        function updateBulkSubjects() {
            const examTypeSelect = document.getElementById('bulkExamType');
            const subjectSelect = document.getElementById('bulkSubject');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            
            const examSubjects = subjects[examTypeSelect.value] || [];
            examSubjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                subjectSelect.appendChild(option);
            });
        }
        
        // NIN Fulfillment Functions
        var currentUserPhone = '';
        var currentUserEmail = '';
        var currentUserNin = '';
        var currentServiceName = '';
        var currentUserName = '';
        
        function openFulfillModal(requestId, userName, userEmail, nin, service, phone) {
            document.getElementById('fulfill-request-id').value = requestId;
            document.getElementById('fulfill-user-name').textContent = userName;
            document.getElementById('fulfill-user-email').textContent = userEmail;
            document.getElementById('fulfill-nin').textContent = nin;
            document.getElementById('fulfill-service').textContent = service;
            
            currentUserPhone = phone || '';
            currentUserEmail = userEmail;
            currentUserNin = nin;
            currentServiceName = service;
            currentUserName = userName;
            
            openModal('fulfillNINModal');
        }
        
        function openWhatsApp() {
            var message = buildWhatsAppMessage();
            var phone = currentUserPhone || prompt('Enter user phone number (e.g., 08012345678):');
            if (!phone) return;
            
            // Format phone for WhatsApp (remove leading 0 and add 234)
            phone = phone.replace(/\s/g, '');
            if (phone.startsWith('0')) {
                phone = '234' + phone.substring(1);
            } else if (!phone.startsWith('234')) {
                phone = '234' + phone;
            }
            
            window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(message), '_blank');
        }
        
        function buildWhatsAppMessage() {
            var customMsg = document.getElementById('custom_message')?.value || '';
            var message = '🎉 *ZonaTech NG - NIN Service Update*\n\n';
            message += 'Hello ' + currentUserName + ',\n\n';
            message += 'Your *' + currentServiceName + '* request has been processed successfully!\n\n';
            message += '📝 *NIN:* ' + currentUserNin + '\n';
            if (customMsg) {
                message += '\n💬 *Message:* ' + customMsg + '\n';
            }
            message += '\n✅ Please check your email for the document.\n\n';
            message += 'If you have any questions, feel free to reply to this message.\n\n';
            message += 'Best regards,\n*ZonaTech NG Team*';
            return message;
        }
        
        function copyWhatsAppMessage() {
            var message = buildWhatsAppMessage();
            navigator.clipboard.writeText(message).then(function() {
                alert('WhatsApp message copied to clipboard!');
            }).catch(function() {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = message;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('WhatsApp message copied to clipboard!');
            });
        }
        
        function viewRequestDetails(formData) {
            let html = '<div style="background: rgba(0,0,0,0.2); border-radius: 10px; padding: 15px;">';
            for (let key in formData) {
                if (formData.hasOwnProperty(key) && formData[key]) {
                    let label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    html += '<p style="margin: 8px 0;"><strong style="color: #a78bfa;">' + label + ':</strong> ' + formData[key] + '</p>';
                }
            }
            html += '</div>';
            document.getElementById('request-details-content').innerHTML = html;
            openModal('viewDetailsModal');
        }
        
        function submitFulfillment(event) {
            event.preventDefault();
            const form = document.getElementById('fulfill-nin-form');
            const formData = new FormData(form);
            formData.append('action', 'zonatech_fulfill_nin_request');
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Request fulfilled successfully! Email sent to user.');
                    closeModal('fulfillNINModal');
                    location.reload();
                } else {
                    alert(data.data?.message || 'Failed to fulfill request');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
            
            return false;
        }
        
        // View/Edit Question Function
        function viewQuestion(question) {
            document.getElementById('edit_question_id').value = question.id;
            document.getElementById('edit_exam_type').value = question.exam_type.toLowerCase();
            document.getElementById('edit_question_text').value = question.question_text;
            document.getElementById('edit_option_a').value = question.option_a;
            document.getElementById('edit_option_b').value = question.option_b;
            document.getElementById('edit_option_c').value = question.option_c;
            document.getElementById('edit_option_d').value = question.option_d;
            document.getElementById('edit_correct_answer').value = question.correct_answer;
            document.getElementById('edit_explanation').value = question.explanation || '';
            
            // Populate subjects dropdown for the selected exam type
            const examType = question.exam_type.toLowerCase();
            const subjectSelect = document.getElementById('edit_subject');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            
            const examSubjects = subjects[examType] || [];
            examSubjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                if (subject === question.subject) {
                    option.selected = true;
                }
                subjectSelect.appendChild(option);
            });
            
            // If the subject isn't in the list, add it anyway
            if (!examSubjects.includes(question.subject)) {
                const option = document.createElement('option');
                option.value = question.subject;
                option.textContent = question.subject;
                option.selected = true;
                subjectSelect.appendChild(option);
            }
            
            openModal('viewQuestionModal');
        }
        
        // Update edit form subject dropdown when exam type changes
        document.getElementById('edit_exam_type')?.addEventListener('change', function() {
            const subjectSelect = document.getElementById('edit_subject');
            const currentValue = subjectSelect.value;
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            
            const examSubjects = subjects[this.value] || [];
            examSubjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                if (subject === currentValue) {
                    option.selected = true;
                }
                subjectSelect.appendChild(option);
            });
        });
        
        // Confirm and delete question
        function confirmDeleteQuestion(questionId, questionPreview) {
            // Sanitize the question preview to prevent XSS in confirm dialog
            const sanitizedPreview = String(questionPreview).replace(/[<>'"&]/g, function(char) {
                const entities = {'<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;', '&': '&amp;'};
                return entities[char] || char;
            });
            if (confirm('Are you sure you want to delete this question?\n\n"' + sanitizedPreview + '"\n\nThis action cannot be undone.')) {
                document.getElementById('delete_question_id').value = questionId;
                document.getElementById('deleteQuestionForm').submit();
            }
        }
        
        // Bulk delete subject dropdown population
        document.getElementById('bulk_delete_exam_type')?.addEventListener('change', function() {
            const subjectSelect = document.getElementById('bulk_delete_subject');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            
            const examSubjects = subjects[this.value] || [];
            examSubjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                subjectSelect.appendChild(option);
            });
        });
        
        // Confirm bulk delete
        function confirmBulkDelete() {
            const examType = document.getElementById('bulk_delete_exam_type').value;
            const subject = document.getElementById('bulk_delete_subject').value;
            
            if (!examType || !subject) {
                alert('Please select exam type and subject.');
                return false;
            }
            
            return confirm('Are you sure you want to delete ALL ' + examType.toUpperCase() + ' ' + subject + ' questions?\n\nThis action cannot be undone!');
        }
        
        // GVerifyer API Functions
        function saveGVerifyerSettings() {
            var apiKey = document.getElementById('gverifyer_api_key').value;
            
            if (!apiKey || apiKey === '••••••••••••••••') {
                showGVerifyerResult('Please enter your API key.', 'error');
                return;
            }
            
            showGVerifyerResult('<i class="fas fa-spinner fa-spin"></i> Saving...', 'info');
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'zonatech_save_gverifyer_settings',
                    nonce: '<?php echo wp_create_nonce('zonatech_gverifyer_nonce'); ?>',
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        showGVerifyerResult('<i class="fas fa-check-circle"></i> ' + response.data.message, 'success');
                        document.getElementById('gverifyer_api_key').value = '••••••••••••••••';
                    } else {
                        showGVerifyerResult('<i class="fas fa-exclamation-circle"></i> ' + (response.data.message || 'Failed to save settings.'), 'error');
                    }
                },
                error: function() {
                    showGVerifyerResult('<i class="fas fa-exclamation-circle"></i> Network error. Please try again.', 'error');
                }
            });
        }
        
        function testGVerifyerConnection() {
            showGVerifyerResult('<i class="fas fa-spinner fa-spin"></i> Testing connection...', 'info');
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'zonatech_test_gverifyer_api',
                    nonce: '<?php echo wp_create_nonce('zonatech_gverifyer_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        showGVerifyerResult('<i class="fas fa-check-circle"></i> ' + response.data.message, 'success');
                    } else {
                        showGVerifyerResult('<i class="fas fa-exclamation-circle"></i> ' + (response.data.message || 'Connection failed.'), 'error');
                    }
                },
                error: function() {
                    showGVerifyerResult('<i class="fas fa-exclamation-circle"></i> Network error. Please try again.', 'error');
                }
            });
        }
        
        function showGVerifyerResult(message, type) {
            var resultDiv = document.getElementById('gverifyer-result');
            var bgColor = type === 'success' ? 'rgba(34, 197, 94, 0.2)' : 
                         type === 'error' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.2)';
            var textColor = type === 'success' ? '#22c55e' : 
                           type === 'error' ? '#ef4444' : '#3b82f6';
            
            resultDiv.innerHTML = '<div style="padding: 12px; background: ' + bgColor + '; border-radius: 8px; color: ' + textColor + ';">' + message + '</div>';
            resultDiv.style.display = 'block';
        }
        
        // Auto Verify NIN using GVerifyer API
        function autoVerifyNIN(requestId, userName, verificationMethod) {
            if (!confirm('Auto verify NIN for ' + userName + ' using GVerifyer API?\n\nThis will call the GVerifyer API and send the result to the user.')) {
                return;
            }
            
            // Show loading state
            var btn = event.target.closest('button');
            var originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            btn.disabled = true;
            
            // Map verification method
            var method = 'nin';
            if (verificationMethod === 'phone_number') method = 'phone';
            else if (verificationMethod === 'demographic') method = 'demographic';
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'zonatech_gverifyer_verify',
                    nonce: '<?php echo wp_create_nonce('zonatech_gverifyer_nonce'); ?>',
                    request_id: requestId,
                    verification_method: method
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ Verification Successful!\n\n' + JSON.stringify(response.data.data, null, 2));
                        location.reload();
                    } else {
                        alert('❌ Verification Failed:\n\n' + (response.data.message || 'Unknown error'));
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                },
                error: function() {
                    alert('❌ Network error. Please try again.');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            });
        }
        
        // UltraMsg API Functions
        function saveUltraMsgSettings() {
            var apiUrl = document.getElementById('ultramsg_api_url').value;
            var instanceId = document.getElementById('ultramsg_instance_id').value;
            var token = document.getElementById('ultramsg_token').value;
            var adminWhatsapp = document.getElementById('admin_whatsapp').value;
            
            // If API URL is provided, extract instance ID from it
            if (apiUrl && !instanceId) {
                var match = apiUrl.match(/ultramsg\.com\/([^\/]+)/);
                if (match) {
                    instanceId = match[1];
                    document.getElementById('ultramsg_instance_id').value = instanceId;
                }
            }
            
            if (!instanceId && !apiUrl) {
                showUltraMsgResult('Please enter your API URL or Instance ID.', 'error');
                return;
            }
            
            if (!token || token === '••••••••••••••••') {
                showUltraMsgResult('Please enter your Token.', 'error');
                return;
            }
            
            if (!adminWhatsapp) {
                showUltraMsgResult('Please enter your Admin WhatsApp number.', 'error');
                return;
            }
            
            showUltraMsgResult('<i class="fas fa-spinner fa-spin"></i> Saving settings...', 'info');
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'zonatech_save_ultramsg_settings',
                    nonce: '<?php echo wp_create_nonce('zonatech_ultramsg_nonce'); ?>',
                    api_url: apiUrl,
                    instance_id: instanceId,
                    token: token,
                    admin_whatsapp: adminWhatsapp
                },
                success: function(response) {
                    if (response.success) {
                        showUltraMsgResult('<i class="fas fa-check-circle"></i> ' + response.data.message, 'success');
                        document.getElementById('ultramsg_token').value = '••••••••••••••••';
                    } else {
                        showUltraMsgResult('<i class="fas fa-exclamation-circle"></i> ' + (response.data.message || 'Failed to save settings.'), 'error');
                    }
                },
                error: function() {
                    showUltraMsgResult('<i class="fas fa-exclamation-circle"></i> Network error. Please try again.', 'error');
                }
            });
        }
        
        function testUltraMsgConnection() {
            showUltraMsgResult('<i class="fas fa-spinner fa-spin"></i> Testing connection and sending test message...', 'info');
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'zonatech_test_ultramsg',
                    nonce: '<?php echo wp_create_nonce('zonatech_ultramsg_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        showUltraMsgResult('<i class="fas fa-check-circle"></i> ' + response.data.message, 'success');
                    } else {
                        showUltraMsgResult('<i class="fas fa-exclamation-circle"></i> ' + (response.data.message || 'Connection failed.'), 'error');
                    }
                },
                error: function() {
                    showUltraMsgResult('<i class="fas fa-exclamation-circle"></i> Network error. Please try again.', 'error');
                }
            });
        }
        
        function showUltraMsgResult(message, type) {
            var resultDiv = document.getElementById('ultramsg-result');
            var bgColor = type === 'success' ? 'rgba(34, 197, 94, 0.2)' : 
                         type === 'error' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(37, 211, 102, 0.2)';
            var textColor = type === 'success' ? '#22c55e' : 
                           type === 'error' ? '#ef4444' : '#25d366';
            
            resultDiv.innerHTML = '<div style="padding: 12px; background: ' + bgColor + '; border-radius: 8px; color: ' + textColor + ';">' + message + '</div>';
            resultDiv.style.display = 'block';
        }
        
        // Pricing Management Functions
        function loadPricing() {
            showPricingResult('<i class="fas fa-spinner fa-spin"></i> Loading prices...', 'info');
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'zonatech_get_pricing',
                    nonce: '<?php echo wp_create_nonce('zonatech_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        document.getElementById('pricing_subject_price').value = data.subject_price || '';
                        document.getElementById('pricing_monthly_price').value = data.monthly_price || '';
                        document.getElementById('pricing_6month_price').value = data['6month_price'] || '';
                        document.getElementById('pricing_free_questions_limit').value = data.free_questions_limit || '';
                        document.getElementById('pricing_scratch_card_price').value = data.scratch_card_price || '';
                        document.getElementById('pricing_waec_card_price').value = data.waec_card_price || '';
                        document.getElementById('pricing_neco_card_price').value = data.neco_card_price || '';
                        document.getElementById('pricing_jamb_card_price').value = data.jamb_card_price || '';
                        document.getElementById('pricing_nin_slip_price').value = data.nin_slip_price || '';
                        document.getElementById('pricing_nin_standard_slip_price').value = data.nin_standard_slip_price || '';
                        document.getElementById('pricing_nin_slip_download_price').value = data.nin_slip_download_price || '';
                        document.getElementById('pricing_nin_modification_price').value = data.nin_modification_price || '';
                        document.getElementById('pricing_nin_dob_correction_price').value = data.nin_dob_correction_price || '';
                        
                        showPricingResult('<i class="fas fa-check-circle"></i> Prices loaded successfully!', 'success');
                        setTimeout(function() {
                            document.getElementById('pricing-result').style.display = 'none';
                        }, 2000);
                    } else {
                        showPricingResult('<i class="fas fa-exclamation-circle"></i> ' + (response.data.message || 'Failed to load prices.'), 'error');
                    }
                },
                error: function() {
                    showPricingResult('<i class="fas fa-exclamation-circle"></i> Network error. Please try again.', 'error');
                }
            });
        }
        
        function savePricing() {
            showPricingResult('<i class="fas fa-spinner fa-spin"></i> Saving prices...', 'info');
            
            var pricingData = {
                action: 'zonatech_save_pricing',
                nonce: '<?php echo wp_create_nonce('zonatech_nonce'); ?>',
                zonatech_subject_price: document.getElementById('pricing_subject_price').value,
                zonatech_monthly_price: document.getElementById('pricing_monthly_price').value,
                zonatech_6month_price: document.getElementById('pricing_6month_price').value,
                zonatech_free_questions_limit: document.getElementById('pricing_free_questions_limit').value,
                zonatech_scratch_card_price: document.getElementById('pricing_scratch_card_price').value,
                zonatech_waec_card_price: document.getElementById('pricing_waec_card_price').value,
                zonatech_neco_card_price: document.getElementById('pricing_neco_card_price').value,
                zonatech_jamb_card_price: document.getElementById('pricing_jamb_card_price').value,
                zonatech_nin_slip_price: document.getElementById('pricing_nin_slip_price').value,
                zonatech_nin_standard_slip_price: document.getElementById('pricing_nin_standard_slip_price').value,
                zonatech_nin_slip_download_price: document.getElementById('pricing_nin_slip_download_price').value,
                zonatech_nin_modification_price: document.getElementById('pricing_nin_modification_price').value,
                zonatech_nin_dob_correction_price: document.getElementById('pricing_nin_dob_correction_price').value
            };
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: pricingData,
                success: function(response) {
                    if (response.success) {
                        showPricingResult('<i class="fas fa-check-circle"></i> ' + response.data.message, 'success');
                    } else {
                        showPricingResult('<i class="fas fa-exclamation-circle"></i> ' + (response.data.message || 'Failed to save prices.'), 'error');
                    }
                },
                error: function() {
                    showPricingResult('<i class="fas fa-exclamation-circle"></i> Network error. Please try again.', 'error');
                }
            });
        }
        
        function showPricingResult(message, type) {
            var resultDiv = document.getElementById('pricing-result');
            var bgColor = type === 'success' ? 'rgba(34, 197, 94, 0.2)' : 
                         type === 'error' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.2)';
            var textColor = type === 'success' ? '#22c55e' : 
                           type === 'error' ? '#ef4444' : '#3b82f6';
            
            resultDiv.innerHTML = '<div style="padding: 12px; background: ' + bgColor + '; border-radius: 8px; color: ' + textColor + ';">' + message + '</div>';
            resultDiv.style.display = 'block';
        }
        
        // Load pricing when modal opens
        var originalOpenModal = openModal;
        openModal = function(modalId) {
            originalOpenModal(modalId);
            if (modalId === 'pricingModal') {
                loadPricing();
            }
        };
        
        // ===== User Access Management =====
        var uaSearchTimer = null;
        var uaNonce = '<?php echo wp_create_nonce('zonatech_nonce'); ?>';
        var uaAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        // User search
        document.getElementById('ua-user-search').addEventListener('input', function() {
            var query = this.value.trim();
            clearTimeout(uaSearchTimer);
            
            if (query.length < 2) {
                document.getElementById('ua-search-results').style.display = 'none';
                document.getElementById('ua-search-results').innerHTML = '';
                return;
            }
            
            uaSearchTimer = setTimeout(function() {
                jQuery.ajax({
                    url: uaAjaxUrl,
                    type: 'POST',
                    data: {
                        action: 'zonatech_search_users',
                        nonce: uaNonce,
                        search: query
                    },
                    success: function(response) {
                        var resultsDiv = document.getElementById('ua-search-results');
                        if (response.success && response.data.users.length > 0) {
                            var html = '';
                            response.data.users.forEach(function(user) {
                                html += '<div onclick="selectUserForAccess(' + parseInt(user.id) + ', \'' + escapeHtmlAttr(user.display_name) + '\', \'' + escapeHtmlAttr(user.email) + '\')" style="padding: 10px 14px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; transition: background 0.2s;"';
                                html += ' onmouseover="this.style.background=\'rgba(139,92,246,0.1)\'" onmouseout="this.style.background=\'transparent\'">';
                                html += '<div><strong style="color: #fff;">' + escapeHtmlContent(user.display_name) + '</strong> <small style="color: rgba(255,255,255,0.4);">@' + escapeHtmlContent(user.username) + '</small></div>';
                                html += '<div style="color: rgba(255,255,255,0.5); font-size: 12px;">' + escapeHtmlContent(user.email) + '</div>';
                                html += '</div>';
                            });
                            resultsDiv.innerHTML = html;
                            resultsDiv.style.display = 'block';
                        } else {
                            resultsDiv.innerHTML = '<div style="padding: 15px; color: rgba(255,255,255,0.5); text-align: center;">No users found.</div>';
                            resultsDiv.style.display = 'block';
                        }
                    }
                });
            }, 300);
        });
        
        function escapeHtmlContent(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
        
        function escapeHtmlAttr(text) {
            return String(text).replace(/&/g, '&amp;').replace(/'/g, '&#39;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        
        function selectUserForAccess(userId, name, email) {
            document.getElementById('ua-selected-user-id').value = userId;
            document.getElementById('ua-selected-name').textContent = name;
            document.getElementById('ua-selected-email').textContent = email;
            document.getElementById('ua-selected-user').style.display = 'block';
            document.getElementById('ua-user-search').value = '';
            document.getElementById('ua-user-search').style.display = 'none';
            document.getElementById('ua-search-results').style.display = 'none';
            
            // Load access records for this user
            loadUserAccessRecords();
        }
        
        function clearUserAccessSelection() {
            document.getElementById('ua-selected-user-id').value = '';
            document.getElementById('ua-selected-user').style.display = 'none';
            document.getElementById('ua-user-search').style.display = '';
            document.getElementById('ua-user-search').value = '';
            document.getElementById('ua-user-search').focus();
            document.getElementById('ua-access-records').innerHTML = '<p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">Select a user above to view their access records.</p>';
        }
        
        function toggleAccessTypeFields() {
            var accessType = document.getElementById('ua-access-type').value;
            var examTypeGroup = document.getElementById('ua-exam-type-group');
            var categoryGroup = document.getElementById('ua-category-group');
            
            if (accessType === 'past_questions') {
                examTypeGroup.style.display = '';
                categoryGroup.style.display = '';
            } else if (accessType === 'scratch_cards') {
                examTypeGroup.style.display = '';
                categoryGroup.style.display = 'none';
                document.getElementById('ua-category').value = '';
            } else if (accessType === 'nin_service') {
                examTypeGroup.style.display = 'none';
                categoryGroup.style.display = 'none';
                document.getElementById('ua-exam-type').value = '';
                document.getElementById('ua-category').value = '';
            } else {
                examTypeGroup.style.display = '';
                categoryGroup.style.display = '';
            }
        }
        
        function grantUserAccess() {
            var userId = document.getElementById('ua-selected-user-id').value;
            var accessType = document.getElementById('ua-access-type').value;
            var examType = document.getElementById('ua-exam-type').value;
            var category = document.getElementById('ua-category').value;
            var duration = document.getElementById('ua-duration').value;
            
            if (!userId) {
                showUaMessage('Please search and select a user first.', 'error');
                return;
            }
            if (!accessType) {
                showUaMessage('Please select an access type.', 'error');
                return;
            }
            
            // For past_questions, exam type and category are required
            if (accessType === 'past_questions') {
                if (!examType) {
                    showUaMessage('Please select an exam type.', 'error');
                    return;
                }
                if (!category) {
                    showUaMessage('Please select a category.', 'error');
                    return;
                }
            }
            
            // For scratch_cards, exam type is the card type
            if (accessType === 'scratch_cards' && !examType) {
                showUaMessage('Please select a card type (JAMB, WAEC, or NECO).', 'error');
                return;
            }
            
            // For nin_service, set exam_type to 'nin'
            var grantExamType = accessType === 'nin_service' ? 'nin' : examType;
            var grantCategory = category || accessType;
            
            var btn = document.getElementById('ua-grant-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Granting...';
            
            jQuery.ajax({
                url: uaAjaxUrl,
                type: 'POST',
                data: {
                    action: 'zonatech_grant_access',
                    nonce: uaNonce,
                    user_id: userId,
                    exam_type: grantExamType,
                    category: grantCategory,
                    duration: duration
                },
                success: function(response) {
                    if (response.success) {
                        showUaMessage(response.data.message, 'success');
                        loadUserAccessRecords();
                        // Reset form
                        document.getElementById('ua-access-type').value = '';
                        document.getElementById('ua-exam-type').value = '';
                        document.getElementById('ua-category').value = '';
                        document.getElementById('ua-duration').value = 'monthly';
                        toggleAccessTypeFields();
                    } else {
                        showUaMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    showUaMessage('Network error. Please try again.', 'error');
                },
                complete: function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle"></i> Grant Access';
                }
            });
        }
        
        function loadUserAccessRecords() {
            var userId = document.getElementById('ua-selected-user-id').value;
            if (!userId) return;
            
            var container = document.getElementById('ua-access-records');
            container.innerHTML = '<p style="text-align: center; padding: 15px;"><i class="fas fa-spinner fa-spin" style="color: #8b5cf6;"></i> Loading...</p>';
            
            jQuery.ajax({
                url: uaAjaxUrl,
                type: 'POST',
                data: {
                    action: 'zonatech_get_user_access',
                    nonce: uaNonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        var records = response.data.records;
                        if (records.length === 0) {
                            container.innerHTML = '<p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">No access records found for this user.</p>';
                            return;
                        }
                        
                        var html = '<table style="width: 100%; border-collapse: collapse; font-size: 13px;">';
                        html += '<thead><tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">';
                        html += '<th style="padding: 8px; text-align: left; color: rgba(255,255,255,0.6);">Type</th>';
                        html += '<th style="padding: 8px; text-align: left; color: rgba(255,255,255,0.6);">Category</th>';
                        html += '<th style="padding: 8px; text-align: left; color: rgba(255,255,255,0.6);">Status</th>';
                        html += '<th style="padding: 8px; text-align: left; color: rgba(255,255,255,0.6);">Expires</th>';
                        html += '<th style="padding: 8px; text-align: right; color: rgba(255,255,255,0.6);">Action</th>';
                        html += '</tr></thead><tbody>';
                        
                        records.forEach(function(record) {
                            var statusColor = record.status === 'active' ? '#22c55e' : '#ef4444';
                            var statusBg = record.status === 'active' ? 'rgba(34,197,94,0.2)' : 'rgba(239,68,68,0.2)';
                            
                            html += '<tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">';
                            html += '<td style="padding: 8px; color: #fff;"><span style="background: rgba(139,92,246,0.3); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">' + escapeHtmlContent(record.exam_type) + '</span></td>';
                            html += '<td style="padding: 8px; color: rgba(255,255,255,0.8);">' + escapeHtmlContent(record.category || record.subject || '—') + '</td>';
                            html += '<td style="padding: 8px;"><span style="background: ' + statusBg + '; color: ' + statusColor + '; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">' + record.status.toUpperCase() + '</span></td>';
                            html += '<td style="padding: 8px; color: rgba(255,255,255,0.7); font-size: 12px;">' + escapeHtmlContent(record.expires_at) + '</td>';
                            html += '<td style="padding: 8px; text-align: right;">';
                            html += '<button onclick="revokeUserAccess(' + parseInt(record.id) + ')" style="background: rgba(239,68,68,0.2); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 11px;"><i class="fas fa-times"></i> Revoke</button>';
                            html += '</td></tr>';
                        });
                        
                        html += '</tbody></table>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<p style="text-align: center; color: rgba(239,68,68,0.8); padding: 15px;">' + (response.data.message || 'Failed to load records.') + '</p>';
                    }
                },
                error: function() {
                    container.innerHTML = '<p style="text-align: center; color: rgba(239,68,68,0.8); padding: 15px;">Network error. Please try again.</p>';
                }
            });
        }
        
        function revokeUserAccess(accessId) {
            if (!confirm('Are you sure you want to revoke this access? This cannot be undone.')) return;
            
            jQuery.ajax({
                url: uaAjaxUrl,
                type: 'POST',
                data: {
                    action: 'zonatech_revoke_access',
                    nonce: uaNonce,
                    access_id: accessId
                },
                success: function(response) {
                    if (response.success) {
                        showUaMessage('Access revoked successfully.', 'success');
                        loadUserAccessRecords();
                    } else {
                        showUaMessage(response.data.message || 'Failed to revoke access.', 'error');
                    }
                },
                error: function() {
                    showUaMessage('Network error. Please try again.', 'error');
                }
            });
        }
        
        function showUaMessage(message, type) {
            var msgDiv = document.getElementById('ua-grant-message');
            var bgColor = type === 'success' ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.15)';
            var borderColor = type === 'success' ? 'rgba(34,197,94,0.3)' : 'rgba(239,68,68,0.3)';
            var textColor = type === 'success' ? '#22c55e' : '#ef4444';
            var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            msgDiv.innerHTML = '<i class="fas ' + icon + '"></i> ' + message;
            msgDiv.style.background = bgColor;
            msgDiv.style.border = '1px solid ' + borderColor;
            msgDiv.style.color = textColor;
            msgDiv.style.display = 'block';
            
            setTimeout(function() { msgDiv.style.display = 'none'; }, 5000);
        }
    </script>
</body>
</html>