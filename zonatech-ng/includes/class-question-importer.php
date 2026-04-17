<?php
/**
 * Question Importer Class
 * 
 * Handles importing past questions and answers from text/document files.
 * Supports formats like JAMB, WAEC, NECO past questions with separate answer keys.
 * Supports PDF, TXT, and DOCX file uploads with automatic text extraction.
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Question_Importer {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_zonatech_import_questions', array($this, 'ajax_import_questions'));
        add_action('wp_ajax_zonatech_preview_import', array($this, 'ajax_preview_import'));
        add_action('wp_ajax_zonatech_upload_file', array($this, 'ajax_upload_file'));
    }
    
    /**
     * Parse passages and questions from English language style content
     * Supports comprehension passages with linked questions
     * 
     * Format expected:
     * - PASSAGE 1: TITLE
     * - [Passage text content...]
     * - 1. Question text
     * - A. option, B. option, etc.
     * - Answer: X
     * - Explanation: ...
     * 
     * @param string $content The raw text content
     * @return array Array with 'passages' and 'questions' keys
     */
    public function parse_passages_and_questions($content) {
        $result = array(
            'passages' => array(),
            'questions' => array()
        );
        
        // Normalize line endings
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Pattern to detect passage headers: "PASSAGE 1:", "### PASSAGE 1:", "PASSAGE 1: TITLE"
        // Must have "PASSAGE" followed by a number
        $passage_pattern = '/^(?:#+\s*)?PASSAGE\s*(\d+)\s*:?\s*(.*)$/im';
        
        // If no passage markers found, just parse as regular questions
        if (!preg_match($passage_pattern, $content)) {
            $result['questions'] = $this->parse_questions($content);
            return $result;
        }
        
        // Split content by passage markers and capture the delimiter info
        $lines = explode("\n", $content);
        $line_count = count($lines);
        
        // Find all passage start positions
        $passage_starts = array();
        for ($i = 0; $i < $line_count; $i++) {
            $line = trim($lines[$i]);
            if (preg_match('/^(?:#+\s*)?PASSAGE\s*(\d+)\s*:?\s*(.*)$/i', $line, $matches)) {
                $passage_num = intval($matches[1]);
                $passage_starts[] = array(
                    'line' => $i,
                    'number' => $passage_num,
                    'title' => trim($matches[2])
                );
            }
        }
        
        // Process each passage section
        for ($p = 0; $p < count($passage_starts); $p++) {
            $start_line = $passage_starts[$p]['line'] + 1; // Skip the header line
            $end_line = ($p + 1 < count($passage_starts)) ? $passage_starts[$p + 1]['line'] : $line_count;
            
            $passage_num = $passage_starts[$p]['number'];
            $passage_title = $passage_starts[$p]['title'];
            
            // Collect passage text and questions for this section
            $passage_text = '';
            $questions_start_line = -1;
            
            for ($i = $start_line; $i < $end_line; $i++) {
                $line = trim($lines[$i]);
                
                // Check if this line starts a question (numbered line)
                if (preg_match('/^(\d{1,3})\s*[.\)]\s*(.+)$/i', $line, $q_match)) {
                    $potential_num = intval($q_match[1]);
                    $text_after = trim($q_match[2]);
                    
                    // Looks like a question if it has substantial text and isn't an option
                    if ($potential_num >= 1 && strlen($text_after) > 10 && !preg_match('/^[A-Ea-e]\s*[.\)]/i', $text_after)) {
                        $questions_start_line = $i;
                        break;
                    }
                }
                
                // Skip section headers like "## SECTION A: COMPREHENSION PASSAGES"
                if (preg_match('/^#+\s*SECTION\s+[A-Z]:/i', $line)) {
                    continue;
                }
                
                // Skip separator lines
                if (!preg_match('/^[-–—]{3,}$/', $line)) {
                    $passage_text .= $line . "\n";
                }
            }
            
            // Save passage if we have text
            $passage_text = trim($passage_text);
            if (!empty($passage_text)) {
                $result['passages'][$passage_num] = array(
                    'number' => $passage_num,
                    'title' => $passage_title,
                    'text' => $passage_text
                );
            }
            
            // Parse questions for this passage section
            if ($questions_start_line >= 0) {
                $questions_end_line = $end_line;
                $questions_content = implode("\n", array_slice($lines, $questions_start_line, $questions_end_line - $questions_start_line));
                $parsed_questions = $this->parse_questions_with_answers($questions_content);
                
                // Link questions to this passage
                foreach ($parsed_questions as $num => $q) {
                    $q['passage_id'] = $passage_num;
                    $result['questions'][$num] = $q;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Parse questions with inline answers and explanations (English language style)
     * 
     * @param string $content The text content with questions
     * @return array Parsed questions
     */
    public function parse_questions_with_answers($content) {
        $questions = array();
        
        // Normalize line endings
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Split by question separators (--- or blank lines before numbers)
        $blocks = preg_split('/\n\s*-{3,}\s*\n/s', $content);
        
        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;
            
            // Try to parse as a single question block
            $question = $this->parse_single_question_block($block);
            if ($question !== null && !empty($question['question_text'])) {
                $questions[$question['number']] = $question;
            }
        }
        
        // If block parsing didn't work well, fall back to line-by-line parsing
        if (empty($questions)) {
            $questions = $this->parse_questions($content);
            
            // Try to extract answers and explanations from the content
            $this->extract_answers_from_content($questions, $content);
        }
        
        return $questions;
    }
    
    /**
     * Parse a single question block (question + options + answer + explanation)
     * 
     * @param string $block Text block containing a single question
     * @return array|null Parsed question or null
     */
    private function parse_single_question_block($block) {
        $lines = explode("\n", $block);
        
        $question = array(
            'number' => 0,
            'question_text' => '',
            'option_a' => '',
            'option_b' => '',
            'option_c' => '',
            'option_d' => '',
            'option_e' => '',
            'correct_answer' => '',
            'explanation' => '',
            'passage_id' => null
        );
        
        $collecting = 'question';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check for question number at start
            if (preg_match('/^(\d{1,3})\s*[.\)]\s*(.+)$/i', $line, $matches) && $question['number'] === 0) {
                $question['number'] = intval($matches[1]);
                $question['question_text'] = trim($matches[2]);
                $collecting = 'question';
                continue;
            }
            
            // Check for options (A., B., C., D., E.)
            if (preg_match('/^([A-Ea-e])\s*[.\)]\s*(.*)$/i', $line, $matches)) {
                $letter = strtoupper($matches[1]);
                $text = trim($matches[2]);
                
                switch ($letter) {
                    case 'A': $question['option_a'] = $text; $collecting = 'option_a'; break;
                    case 'B': $question['option_b'] = $text; $collecting = 'option_b'; break;
                    case 'C': $question['option_c'] = $text; $collecting = 'option_c'; break;
                    case 'D': $question['option_d'] = $text; $collecting = 'option_d'; break;
                    case 'E': $question['option_e'] = $text; $collecting = 'option_e'; break;
                }
                continue;
            }
            
            // Check for Answer line
            if (preg_match('/^Answer:?\s*([A-Ea-e])/i', $line, $matches)) {
                $question['correct_answer'] = strtoupper($matches[1]);
                $collecting = 'answer';
                continue;
            }
            
            // Check for Explanation line
            if (preg_match('/^Explanation:?\s*(.*)$/i', $line, $matches)) {
                $question['explanation'] = trim($matches[1]);
                $collecting = 'explanation';
                continue;
            }
            
            // Continue collecting current field
            if ($collecting === 'question' && empty($question['option_a'])) {
                $question['question_text'] .= ' ' . $line;
            } elseif ($collecting === 'explanation') {
                $question['explanation'] .= ' ' . $line;
            } elseif ($collecting && isset($question[$collecting]) && is_string($question[$collecting])) {
                $question[$collecting] .= ' ' . $line;
            }
        }
        
        // Clean up text fields
        $question['question_text'] = trim($question['question_text']);
        $question['explanation'] = trim($question['explanation']);
        
        return $question['number'] > 0 ? $question : null;
    }
    
    /**
     * Extract answers and explanations from content and merge into questions
     * 
     * @param array &$questions Questions array (modified in place)
     * @param string $content Full content text
     */
    private function extract_answers_from_content(&$questions, $content) {
        // Look for Answer: X patterns and associate with questions
        if (preg_match_all('/(\d{1,3})\s*[.\)]\s*.*?Answer:?\s*([A-Ea-e])\s*(?:Explanation:?\s*([^\n]+(?:\n(?![A-E\d])[^\n]*)*))?/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $q_num = intval($match[1]);
                if (isset($questions[$q_num])) {
                    $questions[$q_num]['correct_answer'] = strtoupper($match[2]);
                    if (!empty($match[3])) {
                        $questions[$q_num]['explanation'] = trim(preg_replace('/\s+/', ' ', $match[3]));
                    }
                }
            }
        }
    }
    
    /**
     * Parse questions from text content
     * Format expected: 
     * - Numbered questions (e.g., "81. Question text here?")
     * - Options on separate lines (e.g., "A. option text" or "A option text")
     * 
     * @param string $content The raw text content
     * @return array Parsed questions
     */
    public function parse_questions($content) {
        $questions = array();
        
        // Normalize line endings
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Clean up common PDF artifacts
        $content = $this->clean_pdf_text($content);
        
        // Split into lines
        $lines = explode("\n", $content);
        
        $current_question = null;
        $current_question_number = null;
        $collecting_option = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Check if line starts with a question number (e.g., "81. " or "81 " or "81)")
            // Also handle cases where the number is at start of line possibly after whitespace
            if (preg_match('/^(\d{1,3})\s*[.\):\s]\s*(.*)$/i', $line, $matches)) {
                $potential_number = intval($matches[1]);
                $rest_of_line = trim($matches[2]);
                
                // Verify this looks like a question (has some text and is a reasonable number)
                // Questions usually start from 1 and shouldn't jump by more than 100
                $is_likely_question = (
                    strlen($rest_of_line) > 5 && 
                    $potential_number >= 1 && 
                    $potential_number <= 500 &&
                    // Make sure it's not just an option that starts with a number
                    !preg_match('/^[A-Ea-e]\s*[.\)]/i', $rest_of_line)
                );
                
                if ($is_likely_question) {
                    // Save previous question if exists
                    if ($current_question !== null && !empty($current_question['question_text'])) {
                        $questions[$current_question_number] = $current_question;
                    }
                    
                    $current_question_number = $potential_number;
                    
                    // Check if options are inline with the question (common in CSV format)
                    // Pattern: "Question text A. option1 B. option2 C. option3 D. option4"
                    $inline_options = $this->extract_inline_options($rest_of_line);
                    
                    if ($inline_options !== null) {
                        // Question has inline options
                        $current_question = array(
                            'number' => $current_question_number,
                            'question_text' => $inline_options['question_text'],
                            'option_a' => $inline_options['option_a'],
                            'option_b' => $inline_options['option_b'],
                            'option_c' => $inline_options['option_c'],
                            'option_d' => $inline_options['option_d'],
                            'option_e' => isset($inline_options['option_e']) ? $inline_options['option_e'] : '',
                            'correct_answer' => '',
                            'explanation' => ''
                        );
                        $collecting_option = null;
                    } else {
                        // Regular format - question on its own line
                        $question_text = $this->clean_question_text($rest_of_line);
                        
                        $current_question = array(
                            'number' => $current_question_number,
                            'question_text' => $question_text,
                            'option_a' => '',
                            'option_b' => '',
                            'option_c' => '',
                            'option_d' => '',
                            'option_e' => '',
                            'correct_answer' => '',
                            'explanation' => ''
                        );
                        $collecting_option = null;
                    }
                    continue;
                }
            }
            
            // Check if line is an option (A., B., C., D., E. or A), B), C), D), E))
            // Also handles formats like "- A." or "- A)" (with dash prefix)
            if ($current_question !== null) {
                // Match options like "A. text", "A) text", "A text", "- A. text", "- A) text"
                // Now including option E for 5-option questions
                // Also handle ✓ symbol for marking correct answers
                if (preg_match('/^[-–—]?\s*([A-Ea-e])\s*[.\)]\s*(.*)$/i', $line, $matches)) {
                    $option_letter = strtoupper($matches[1]);
                    $option_text = trim($matches[2]);
                    
                    // Check if this option is marked as correct with ✓ or other markers
                    $is_correct = false;
                    if (preg_match('/[✓✔√]/', $option_text)) {
                        $is_correct = true;
                        // Remove the check mark from the option text
                        $option_text = preg_replace('/\s*[✓✔√]\s*/', '', $option_text);
                        $option_text = trim($option_text);
                    }
                    
                    switch ($option_letter) {
                        case 'A':
                            $current_question['option_a'] = $option_text;
                            $collecting_option = 'option_a';
                            if ($is_correct) $current_question['correct_answer'] = 'A';
                            break;
                        case 'B':
                            $current_question['option_b'] = $option_text;
                            $collecting_option = 'option_b';
                            if ($is_correct) $current_question['correct_answer'] = 'B';
                            break;
                        case 'C':
                            $current_question['option_c'] = $option_text;
                            $collecting_option = 'option_c';
                            if ($is_correct) $current_question['correct_answer'] = 'C';
                            break;
                        case 'D':
                            $current_question['option_d'] = $option_text;
                            $collecting_option = 'option_d';
                            if ($is_correct) $current_question['correct_answer'] = 'D';
                            break;
                        case 'E':
                            $current_question['option_e'] = $option_text;
                            $collecting_option = 'option_e';
                            if ($is_correct) $current_question['correct_answer'] = 'E';
                            break;
                    }
                } else if ($current_question !== null && !empty($current_question['question_text'])) {
                    // Check for "Explanation:" lines
                    if (preg_match('/^Explanation:\s*(.*)$/i', $line, $exp_match)) {
                        if (!isset($current_question['explanation'])) {
                            $current_question['explanation'] = '';
                        }
                        $current_question['explanation'] .= trim($exp_match[1]);
                        $collecting_option = 'explanation';
                        continue;
                    }
                    
                    // Skip separator lines like "---"
                    if (preg_match('/^[-–—]{2,}$/', $line)) {
                        continue;
                    }
                    
                    // If it's not an option and we have a current question
                    if (empty($current_question['option_a'])) {
                        // Still collecting question text
                        $current_question['question_text'] .= ' ' . $line;
                    } else if ($collecting_option === 'explanation' && isset($current_question['explanation'])) {
                        // Continue collecting explanation
                        $current_question['explanation'] .= ' ' . $line;
                    } else if ($collecting_option !== null && !empty($current_question[$collecting_option])) {
                        // Continue collecting the current option (multi-line option)
                        $current_question[$collecting_option] .= ' ' . $line;
                    }
                }
            }
        }
        
        // Don't forget the last question
        if ($current_question !== null && !empty($current_question['question_text'])) {
            $questions[$current_question_number] = $current_question;
        }
        
        return $questions;
    }
    
    /**
     * Extract options that appear inline with question text
     * Handles format like: "Question text A. opt1 B. opt2 C. opt3 D. opt4"
     * 
     * @param string $text The text containing question and inline options
     * @return array|null Array with question_text and options, or null if no inline options found
     */
    private function extract_inline_options($text) {
        // Look for pattern where A., B., C., D. (and optionally E.) appear in the same text
        // Check if we have at least A, B, C options inline (D may be at end)
        if (!preg_match('/[Aa]\s*[.\)]/', $text) || 
            !preg_match('/[Bb]\s*[.\)]/', $text) || 
            !preg_match('/[Cc]\s*[.\)]/', $text)) {
            return null; // Not enough options found inline
        }
        
        // Extract using regex - find where each option starts
        // Pattern to match option A marker with space before it
        $pattern = '/\s+([Aa])\s*[.\)]\s*/';
        
        // Find the position of option A
        if (!preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        
        $question_text = trim(substr($text, 0, $match[0][1]));
        $options_text = substr($text, $match[0][1]);
        
        // Now parse individual options from the options text
        $options = array(
            'question_text' => $question_text,
            'option_a' => '',
            'option_b' => '',
            'option_c' => '',
            'option_d' => '',
            'option_e' => ''
        );
        
        // Extract each option using non-greedy matching
        // Pattern: letter followed by . or ) then text until next option letter or end
        if (preg_match('/[Aa]\s*[.\)]\s*(.*?)(?=\s+[Bb]\s*[.\)]|$)/is', $options_text, $m)) {
            $options['option_a'] = trim($m[1]);
        }
        if (preg_match('/[Bb]\s*[.\)]\s*(.*?)(?=\s+[Cc]\s*[.\)]|$)/is', $options_text, $m)) {
            $options['option_b'] = trim($m[1]);
        }
        if (preg_match('/[Cc]\s*[.\)]\s*(.*?)(?=\s+[Dd]\s*[.\)]|$)/is', $options_text, $m)) {
            $options['option_c'] = trim($m[1]);
        }
        if (preg_match('/[Dd]\s*[.\)]\s*(.*?)(?=\s+[Ee]\s*[.\)]|$)/is', $options_text, $m)) {
            $options['option_d'] = trim($m[1]);
        }
        if (preg_match('/[Ee]\s*[.\)]\s*(.*?)$/is', $options_text, $m)) {
            $options['option_e'] = trim($m[1]);
        }
        
        // Validate we have at least 3 options (A, B, C or A, B, D, etc.)
        $non_empty_count = 0;
        if (!empty($options['option_a'])) $non_empty_count++;
        if (!empty($options['option_b'])) $non_empty_count++;
        if (!empty($options['option_c'])) $non_empty_count++;
        if (!empty($options['option_d'])) $non_empty_count++;
        
        if ($non_empty_count < 2) {
            return null;
        }
        
        return $options;
    }
    
    /**
     * Parse answer key from text content
     * Format expected:
     * - Tabular format: "1. D    2. A    3. C    4. D" (multiple answers per line)
     * - Look for SOLUTIONS/ANSWERS sections before parsing
     * 
     * @param string $content The raw text content
     * @return array Answers indexed by question number
     */
    public function parse_answers($content) {
        $answers = array();
        
        // Normalize line endings and spaces
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Split into lines
        $lines = explode("\n", $content);
        
        // Track if we're in an answer key section
        $in_answer_section = false;
        $consecutive_answers = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if this line indicates we're in an answer key section
            if (preg_match('/\b(ANSWER\s*KEY|ANSWERS?|SOLUTIONS?|CORRECT\s*ANSWERS?)\s*:?\s*/i', $line)) {
                $in_answer_section = true;
                continue;
            }
            
            // Method 1: Check for multiple answers per line (tabular format)
            // e.g., "1. D    2. A    3. C    4. D    5. B    6. D"
            preg_match_all('/(\d{1,3})\.\s*([A-Ea-e])(?=\s|$|,)/i', $line, $matches, PREG_SET_ORDER);
            
            if (count($matches) >= 3) {
                // This is definitely an answer line with multiple answers
                $in_answer_section = true;
                foreach ($matches as $match) {
                    $question_number = intval($match[1]);
                    $answer = strtoupper($match[2]);
                    if ($question_number >= 1 && $question_number <= 500) {
                        $answers[$question_number] = $answer;
                    }
                }
                $consecutive_answers = 0;
                continue;
            }
            
            // Method 2: Check for single answer per line format
            // e.g., "1. A" or "1. D" on its own line
            // Only match if line is JUST the answer pattern (nothing else significant)
            if (preg_match('/^(\d{1,3})\.\s*([A-Ea-e])\s*$/i', $line, $match)) {
                $question_number = intval($match[1]);
                $answer = strtoupper($match[2]);
                
                // Validate question number is reasonable
                if ($question_number >= 1 && $question_number <= 500) {
                    // Check if this could be part of an answer key section
                    // by looking for consecutive answer patterns
                    if ($in_answer_section || isset($answers[$question_number - 1]) || $consecutive_answers > 0) {
                        $answers[$question_number] = $answer;
                        $consecutive_answers++;
                    } else if ($question_number <= 3) {
                        // If it's one of the first few questions, accept it as answer
                        $answers[$question_number] = $answer;
                        $consecutive_answers++;
                    }
                }
            } else {
                // Not an answer line - reset consecutive counter if not in dedicated section
                if (!$in_answer_section) {
                    $consecutive_answers = 0;
                }
            }
        }
        
        return $answers;
    }
    
    /**
     * Clean question text by removing trailing option markers
     * 
     * @param string $text Question text
     * @return string Cleaned question text
     */
    private function clean_question_text($text) {
        // Remove trailing option markers that might be on the same line
        $text = preg_replace('/\s+[A-D]\s*[.\)]\s*$/', '', $text);
        return trim($text);
    }
    
    /**
     * Parse questions and answers by year sections
     * Detects year headers like "USE OF ENGLISH 2010" or "2012 JAMB"
     * 
     * @param string $content Full document content
     * @param int $start_year Only include years >= this value (default 2010)
     * @return array Array of ['year' => int, 'subject' => string, 'exam_type' => string, 'questions' => array, 'answers' => array]
     */
    public function parse_by_year($content, $start_year = 2010) {
        $year_sections = array();
        
        // Normalize line endings
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Find all year section headers
        // Patterns like: "USE OF ENGLISH 2010" or "2012 TYPE YELLOW" or "JAMB 2011"
        $year_pattern = '/^.*?(USE OF ENGLISH|MATHEMATICS|PHYSICS|CHEMISTRY|BIOLOGY|ECONOMICS|GOVERNMENT|LITERATURE|GEOGRAPHY|ACCOUNTING|COMMERCE|CIVIC|AGRICULTURAL|COMPUTER|HISTORY|ENGLISH)\s*(19\d{2}|20[0-2]\d).*$/mi';
        $year_pattern2 = '/^.*?(19\d{2}|20[0-2]\d)\s*(USE OF ENGLISH|MATHEMATICS|PHYSICS|CHEMISTRY|BIOLOGY|ECONOMICS|GOVERNMENT|LITERATURE|GEOGRAPHY|ACCOUNTING|COMMERCE|CIVIC|AGRICULTURAL|COMPUTER|HISTORY|ENGLISH|JAMB|TYPE).*$/mi';
        
        // Split content by year markers
        preg_match_all($year_pattern, $content, $matches1, PREG_OFFSET_CAPTURE);
        preg_match_all($year_pattern2, $content, $matches2, PREG_OFFSET_CAPTURE);
        
        // Combine and sort by offset
        $year_markers = array();
        
        foreach ($matches1[0] as $i => $match) {
            $year = intval($matches1[2][$i][0]);
            $subject = $this->normalize_subject($matches1[1][$i][0]);
            if ($year >= $start_year) {
                $year_markers[] = array(
                    'offset' => $match[1],
                    'year' => $year,
                    'subject' => $subject,
                    'exam_type' => 'jamb'
                );
            }
        }
        
        foreach ($matches2[0] as $i => $match) {
            $year = intval($matches2[1][$i][0]);
            $subject_or_type = $matches2[2][$i][0];
            $subject = $this->normalize_subject($subject_or_type);
            if ($year >= $start_year) {
                // Check if already added at similar offset (avoid duplicates)
                $is_duplicate = false;
                foreach ($year_markers as $existing) {
                    if (abs($existing['offset'] - $match[1]) < 100 && $existing['year'] === $year) {
                        $is_duplicate = true;
                        break;
                    }
                }
                if (!$is_duplicate) {
                    $year_markers[] = array(
                        'offset' => $match[1],
                        'year' => $year,
                        'subject' => $subject,
                        'exam_type' => 'jamb'
                    );
                }
            }
        }
        
        // Sort by offset
        usort($year_markers, function($a, $b) {
            return $a['offset'] - $b['offset'];
        });
        
        // Extract content for each year section
        $count = count($year_markers);
        for ($i = 0; $i < $count; $i++) {
            $start = $year_markers[$i]['offset'];
            $end = ($i + 1 < $count) ? $year_markers[$i + 1]['offset'] : strlen($content);
            
            $section_content = substr($content, $start, $end - $start);
            
            // Parse questions and answers for this section
            $questions = $this->parse_questions($section_content);
            $answers = $this->parse_answers($section_content);
            
            // Merge answers into questions
            if (!empty($answers)) {
                $questions = $this->merge_questions_with_answers($questions, $answers);
            }
            
            $year_sections[] = array(
                'year' => $year_markers[$i]['year'],
                'subject' => $year_markers[$i]['subject'],
                'exam_type' => $year_markers[$i]['exam_type'],
                'questions' => $questions,
                'answers' => $answers
            );
        }
        
        return $year_sections;
    }
    
    /**
     * Normalize subject name to consistent format
     */
    private function normalize_subject($subject) {
        $subject = strtoupper(trim($subject));
        $mapping = array(
            'USE OF ENGLISH' => 'Use of English',
            'ENGLISH' => 'Use of English',
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
            'JAMB' => 'Use of English',
            'TYPE' => 'Use of English'
        );
        return isset($mapping[$subject]) ? $mapping[$subject] : 'Use of English';
    }
    
    /**
     * Merge questions with answers
     * 
     * @param array $questions Parsed questions
     * @param array $answers Parsed answers
     * @return array Questions with answers filled in
     */
    public function merge_questions_with_answers($questions, $answers) {
        foreach ($questions as $number => &$question) {
            if (isset($answers[$number])) {
                $question['correct_answer'] = $answers[$number];
            }
        }
        return $questions;
    }
    
    /**
     * Import questions into database
     * 
     * @param array $questions Parsed questions
     * @param string $exam_type Exam type (jamb, waec, neco)
     * @param string $subject Subject name
     * @param int $year Year
     * @param bool $allow_without_answers If true, import questions even without answers (answer set to '?')
     * @return array Result with success count and errors
     */
    public function import_to_database($questions, $exam_type, $subject, $year, $allow_without_answers = false, $allow_missing_options = false) {
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        $success_count = 0;
        $errors = array();
        $skipped = 0;
        $missing_answers = 0;
        $missing_options_count = 0;
        
        // Check if passage_id column exists (for backward compatibility)
        $passage_id_column_exists = !empty($wpdb->get_results("SHOW COLUMNS FROM $table_questions LIKE 'passage_id'"));
        
        foreach ($questions as $number => $question) {
            // Validate question has required fields
            if (empty($question['question_text'])) {
                $errors[] = "Question {$number}: Missing question text";
                continue;
            }
            
            // Check if we have at least options A-D (E is optional)
            $has_missing_options = empty($question['option_a']) || empty($question['option_b']) || 
                empty($question['option_c']) || empty($question['option_d']);
            
            if ($has_missing_options) {
                if (!$allow_missing_options) {
                    $errors[] = "Question {$number}: Missing one or more options (A, B, C, D required)";
                    continue;
                } else {
                    // Fill in placeholder text for missing options
                    $missing_options_count++;
                    if (empty($question['option_a'])) $question['option_a'] = '[Option A - to be added]';
                    if (empty($question['option_b'])) $question['option_b'] = '[Option B - to be added]';
                    if (empty($question['option_c'])) $question['option_c'] = '[Option C - to be added]';
                    if (empty($question['option_d'])) $question['option_d'] = '[Option D - to be added]';
                }
            }
            
            if (empty($question['correct_answer'])) {
                $missing_answers++;
                if (!$allow_without_answers) {
                    $errors[] = "Question {$number}: Missing correct answer (no answer key found)";
                    continue;
                } else {
                    // Set a placeholder answer that can be updated later
                    $question['correct_answer'] = 'A'; // Default to A, admin must review
                }
            }
            
            // Check if question already exists (by question text, exam type, subject, year)
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_questions 
                 WHERE exam_type = %s AND subject = %s AND year = %d 
                 AND question_text = %s",
                $exam_type,
                $subject,
                $year,
                $question['question_text']
            ));
            
            if ($exists > 0) {
                $skipped++;
                continue;
            }
            
            // Handle explanation - use parsed explanation, or option E as fallback
            $explanation = '';
            if (!empty($question['explanation'])) {
                $explanation = $question['explanation'];
            } else if (!empty($question['option_e'])) {
                $explanation = 'E. ' . $question['option_e'];
            }
            
            // Build insert data array
            $insert_data = array(
                'exam_type' => $exam_type,
                'subject' => $subject,
                'year' => $year,
                'question_text' => sanitize_textarea_field($question['question_text']),
                'option_a' => sanitize_text_field($question['option_a']),
                'option_b' => sanitize_text_field($question['option_b']),
                'option_c' => sanitize_text_field($question['option_c']),
                'option_d' => sanitize_text_field($question['option_d']),
                'correct_answer' => sanitize_text_field($question['correct_answer']),
                'explanation' => sanitize_textarea_field($explanation)
            );
            
            // Add passage_id if present and column exists
            if (!empty($question['passage_id']) && $passage_id_column_exists) {
                $insert_data['passage_id'] = intval($question['passage_id']);
            }
            
            // Insert question
            $result = $wpdb->insert($table_questions, $insert_data);
            
            if ($result) {
                $success_count++;
            } else {
                $errors[] = "Question {$number}: Database error - " . $wpdb->last_error;
            }
        }
        
        return array(
            'success_count' => $success_count,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_parsed' => count($questions),
            'missing_answers' => $missing_answers,
            'missing_options' => $missing_options_count
        );
    }
    
    /**
     * Import passages to database
     * 
     * @param array $passages Array of passages
     * @param string $exam_type Exam type
     * @param string $subject Subject name
     * @param int $year Year
     * @return array Map of passage number to database ID
     */
    public function import_passages($passages, $exam_type, $subject, $year) {
        global $wpdb;
        $table_passages = $wpdb->prefix . 'zonatech_passages';
        
        $passage_map = array(); // Maps passage number to database ID
        
        foreach ($passages as $num => $passage) {
            if (empty($passage['text'])) {
                continue;
            }
            
            // Check if passage already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_passages 
                 WHERE exam_type = %s AND subject = %s AND year = %d AND passage_number = %d",
                $exam_type, $subject, $year, $passage['number']
            ));
            
            if ($existing) {
                $passage_map[$num] = intval($existing);
                continue;
            }
            
            // Insert new passage
            $result = $wpdb->insert($table_passages, array(
                'exam_type' => $exam_type,
                'subject' => $subject,
                'year' => $year,
                'passage_title' => sanitize_text_field($passage['title'] ?? ''),
                'passage_text' => wp_kses_post($passage['text']),
                'passage_number' => intval($passage['number'])
            ));
            
            if ($result) {
                $passage_map[$num] = $wpdb->insert_id;
            }
        }
        
        return $passage_map;
    }
    
    /**
     * Import questions with passages (English language style)
     * 
     * @param string $content Full document content
     * @param string $exam_type Exam type
     * @param string $subject Subject name
     * @param int $year Year
     * @param bool $allow_without_answers Allow import without answers
     * @param bool $allow_missing_options Allow missing options
     * @return array Import result
     */
    public function import_with_passages($content, $exam_type, $subject, $year, $allow_without_answers = false, $allow_missing_options = false) {
        // Parse passages and questions
        $parsed = $this->parse_passages_and_questions($content);
        
        $result = array(
            'passages_imported' => 0,
            'questions_imported' => 0,
            'skipped' => 0,
            'errors' => array()
        );
        
        // Import passages first and get the ID mapping
        $passage_map = array();
        if (!empty($parsed['passages'])) {
            $passage_map = $this->import_passages($parsed['passages'], $exam_type, $subject, $year);
            $result['passages_imported'] = count($passage_map);
        }
        
        // Update question passage_ids with database IDs
        if (!empty($parsed['questions'])) {
            foreach ($parsed['questions'] as $q_key => $question) {
                if (!empty($question['passage_id']) && isset($passage_map[$question['passage_id']])) {
                    $parsed['questions'][$q_key]['passage_id'] = $passage_map[$question['passage_id']];
                } else {
                    $parsed['questions'][$q_key]['passage_id'] = null;
                }
            }
            
            // Import questions
            $import_result = $this->import_to_database(
                $parsed['questions'], 
                $exam_type, 
                $subject, 
                $year, 
                $allow_without_answers, 
                $allow_missing_options
            );
            
            $result['questions_imported'] = $import_result['success_count'];
            $result['skipped'] = $import_result['skipped'];
            $result['errors'] = $import_result['errors'];
        }
        
        return $result;
    }
    
    /**
     * AJAX handler for previewing import
     */
    public function ajax_preview_import() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $questions_text = isset($_POST['questions_text']) ? wp_unslash($_POST['questions_text']) : '';
        $answers_text = isset($_POST['answers_text']) ? wp_unslash($_POST['answers_text']) : '';
        
        if (empty($questions_text)) {
            wp_send_json_error(array('message' => 'Questions text is required.'));
        }
        
        $questions = $this->parse_questions($questions_text);
        
        if (!empty($answers_text)) {
            $answers = $this->parse_answers($answers_text);
            $questions = $this->merge_questions_with_answers($questions, $answers);
        }
        
        if (empty($questions)) {
            wp_send_json_error(array('message' => 'No questions could be parsed from the provided text.'));
        }
        
        // Convert to simple array for JSON
        $preview_questions = array_values($questions);
        
        wp_send_json_success(array(
            'questions' => $preview_questions,
            'count' => count($preview_questions),
            'message' => count($preview_questions) . ' questions parsed successfully.'
        ));
    }
    
    /**
     * AJAX handler for importing questions
     */
    public function ajax_import_questions() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $questions_text = isset($_POST['questions_text']) ? wp_unslash($_POST['questions_text']) : '';
        $answers_text = isset($_POST['answers_text']) ? wp_unslash($_POST['answers_text']) : '';
        $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $import_mode = sanitize_text_field($_POST['import_mode'] ?? 'standard'); // 'standard' or 'english_passages'
        
        // Validation
        if (empty($questions_text)) {
            wp_send_json_error(array('message' => 'Questions text is required.'));
        }
        
        if (empty($exam_type)) {
            wp_send_json_error(array('message' => 'Exam type is required.'));
        }
        
        if (empty($subject)) {
            wp_send_json_error(array('message' => 'Subject is required.'));
        }
        
        if ($year < 1990 || $year > intval(date('Y')) + 1) {
            wp_send_json_error(array('message' => 'Invalid year. Please enter a year between 1990 and ' . (date('Y') + 1) . '.'));
        }
        
        // Check if this looks like English language style with passages
        $has_passages = preg_match('/(?:PASSAGE|SECTION\s*[A-Z]?:?\s*COMPREHENSION)/i', $questions_text);
        $has_inline_answers = preg_match('/Answer:?\s*[A-E]/i', $questions_text);
        
        // Auto-detect or use specified mode
        if ($import_mode === 'english_passages' || ($has_passages && $has_inline_answers)) {
            // Use English language parser with passages support
            $result = $this->import_with_passages(
                $questions_text, 
                $exam_type, 
                $subject, 
                $year,
                true, // Allow questions without answers (we'll extract from inline)
                false
            );
            
            $message = sprintf(
                'Import complete: %d passages, %d questions imported, %d skipped (duplicates), %d errors.',
                $result['passages_imported'],
                $result['questions_imported'],
                $result['skipped'],
                count($result['errors'])
            );
            
            if ($result['questions_imported'] > 0 || $result['passages_imported'] > 0) {
                wp_send_json_success(array(
                    'message' => $message,
                    'details' => $result
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'No content was imported. ' . (!empty($result['errors']) ? implode('; ', array_slice($result['errors'], 0, 5)) : ''),
                    'details' => $result
                ));
            }
        } else {
            // Standard parsing
            $questions = $this->parse_questions_with_answers($questions_text);
            
            if (empty($questions)) {
                // Fall back to basic parser
                $questions = $this->parse_questions($questions_text);
            }
            
            if (empty($questions)) {
                wp_send_json_error(array('message' => 'No questions could be parsed from the provided text. Please check the format.'));
            }
            
            // Parse and merge answers if provided separately
            if (!empty($answers_text)) {
                $answers = $this->parse_answers($answers_text);
                $questions = $this->merge_questions_with_answers($questions, $answers);
            }
            
            // Import to database
            $result = $this->import_to_database($questions, $exam_type, $subject, $year, true, false);
            
            $message = sprintf(
                'Import complete: %d questions imported, %d skipped (duplicates), %d errors.',
                $result['success_count'],
                $result['skipped'],
                count($result['errors'])
            );
            
            if ($result['success_count'] > 0) {
                wp_send_json_success(array(
                    'message' => $message,
                    'details' => $result
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'No questions were imported. ' . (!empty($result['errors']) ? implode('; ', array_slice($result['errors'], 0, 5)) : ''),
                    'details' => $result
                ));
            }
        }
    }
    
    /**
     * AJAX handler for file upload
     */
    public function ajax_upload_file() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => 'No file uploaded.'));
        }
        
        $file = $_FILES['file'];
        $file_type = isset($_POST['file_type']) ? sanitize_text_field($_POST['file_type']) : 'questions';
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = array(
                UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
                UPLOAD_ERR_EXTENSION => 'File upload blocked by extension.',
            );
            $error_msg = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : 'Unknown upload error.';
            wp_send_json_error(array('message' => $error_msg));
        }
        
        // Extract text from file
        $result = $this->extract_text_from_file($file);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        $text_content = $result['content'];
        $detected_metadata = array();
        
        // Try to detect metadata from content
        $detected_metadata = $this->detect_metadata_from_content($text_content, $file['name']);
        
        wp_send_json_success(array(
            'content' => $text_content,
            'detected' => $detected_metadata,
            'file_type' => $file_type,
            'message' => 'File processed successfully.' . (!empty($detected_metadata['confidence']) ? ' ' . ucfirst($detected_metadata['confidence']) . ' confidence in detected metadata.' : '')
        ));
    }
    
    /**
     * Detect exam metadata from content and filename
     * 
     * @param string $content Text content
     * @param string $filename Original filename
     * @return array Detected metadata
     */
    public function detect_metadata_from_content($content, $filename = '') {
        $detected = array(
            'exam_type' => '',
            'subject' => '',
            'year' => '',
            'confidence' => 'low'
        );
        
        $text = strtolower($content . ' ' . $filename);
        $confidence_score = 0;
        
        // Detect exam type
        if (preg_match('/\bjamb\b/i', $text) || preg_match('/\butme\b/i', $text)) {
            $detected['exam_type'] = 'jamb';
            $confidence_score++;
        } else if (preg_match('/\bwaec\b/i', $text) || preg_match('/\bwassce\b/i', $text)) {
            $detected['exam_type'] = 'waec';
            $confidence_score++;
        } else if (preg_match('/\bneco\b/i', $text) || preg_match('/\bssce\b/i', $text)) {
            $detected['exam_type'] = 'neco';
            $confidence_score++;
        }
        
        // Detect year (look for 4-digit years between 1990 and current year)
        $current_year = intval(date('Y'));
        // Build dynamic regex pattern for years 1990-current year
        if (preg_match_all('/\b(19[9][0-9]|20[0-9]{2})\b/', $text, $year_matches)) {
            // Get the most likely year (prefer years in valid range)
            foreach ($year_matches[1] as $year) {
                $year_int = intval($year);
                if ($year_int >= 1990 && $year_int <= $current_year) {
                    $detected['year'] = $year;
                    $confidence_score++;
                    break;
                }
            }
        }
        
        // Detect subject
        $subjects_map = array(
            'use of english' => 'Use of English',
            'english language' => 'English Language',
            'mathematics' => 'Mathematics',
            'maths' => 'Mathematics',
            'further mathematics' => 'Further Mathematics',
            'further maths' => 'Further Mathematics',
            'physics' => 'Physics',
            'chemistry' => 'Chemistry',
            'biology' => 'Biology',
            'economics' => 'Economics',
            'government' => 'Government',
            'literature' => 'Literature in English',
            'literature in english' => 'Literature in English',
            'commerce' => 'Commerce',
            'accounting' => 'Accounting',
            'financial accounting' => 'Financial Accounting',
            'geography' => 'Geography',
            'agricultural science' => 'Agricultural Science',
            'agric' => 'Agricultural Science',
            'computer science' => 'Computer Science',
            'computer studies' => 'Computer Studies',
            'data processing' => 'Data Processing',
            'civic education' => 'Civic Education',
            'civics' => 'Civic Education',
            'history' => 'History',
            'christian religious studies' => 'Christian Religious Studies',
            'crs' => 'Christian Religious Studies',
            'islamic religious studies' => 'Islamic Religious Studies',
            'irs' => 'Islamic Religious Studies',
            'home economics' => 'Home Economics',
            'food and nutrition' => 'Food & Nutrition',
            'fine arts' => 'Fine Arts',
            'music' => 'Music',
            'french' => 'French',
            'arabic' => 'Arabic',
            'hausa' => 'Hausa',
            'igbo' => 'Igbo',
            'yoruba' => 'Yoruba',
            'physical education' => 'Physical Education',
            'health education' => 'Health Education',
            'health science' => 'Health Science',
            'technical drawing' => 'Technical Drawing'
        );
        
        foreach ($subjects_map as $pattern => $subject) {
            if (stripos($text, $pattern) !== false) {
                $detected['subject'] = $subject;
                $confidence_score++;
                break;
            }
        }
        
        // Set confidence level
        if ($confidence_score >= 3) {
            $detected['confidence'] = 'high';
        } else if ($confidence_score >= 2) {
            $detected['confidence'] = 'medium';
        } else {
            $detected['confidence'] = 'low';
        }
        
        return $detected;
    }
    
    /**
     * Extract text from uploaded file (PDF, TXT, DOCX)
     * 
     * @param array $file $_FILES array element
     * @return array|WP_Error Extracted text and metadata or error
     */
    public function extract_text_from_file($file) {
        $file_type = wp_check_filetype($file['name']);
        $ext = strtolower($file_type['ext']);
        $allowed_types = array('txt', 'text', 'pdf', 'docx', 'doc');
        
        if (!in_array($ext, $allowed_types)) {
            return new WP_Error('invalid_file_type', 'Unsupported file type. Allowed: PDF, TXT, DOCX');
        }
        
        $content = '';
        
        switch ($ext) {
            case 'txt':
            case 'text':
                $content = $this->extract_from_txt($file['tmp_name']);
                break;
            case 'pdf':
                $content = $this->extract_from_pdf($file['tmp_name']);
                break;
            case 'docx':
                $content = $this->extract_from_docx($file['tmp_name']);
                break;
            case 'doc':
                $content = $this->extract_from_doc($file['tmp_name']);
                break;
        }
        
        if (is_wp_error($content)) {
            return $content;
        }
        
        if (empty(trim($content))) {
            return new WP_Error('empty_content', 'Could not extract any text from the file. Please ensure the file contains readable text.');
        }
        
        // Convert encoding if needed
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        
        return array(
            'content' => $content,
            'file_type' => $ext
        );
    }
    
    /**
     * Extract text from TXT file
     */
    private function extract_from_txt($filepath) {
        $content = file_get_contents($filepath);
        if ($content === false) {
            return new WP_Error('read_error', 'Could not read text file.');
        }
        return $content;
    }
    
    /**
     * Extract text from PDF file
     * Tries pdftotext command first (best quality), falls back to PHP parsing
     */
    private function extract_from_pdf($filepath) {
        // Validate filepath is a real file
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return new WP_Error('read_error', 'Could not read PDF file.');
        }
        
        // First, try using pdftotext command-line tool if available (best quality)
        $pdftotext_path = $this->find_pdftotext();
        if ($pdftotext_path && is_executable($pdftotext_path)) {
            // pdftotext_path is already validated to be from safe paths
            $escaped_path = escapeshellarg($filepath);
            $escaped_pdftotext = escapeshellarg($pdftotext_path);
            $output = shell_exec("$escaped_pdftotext -layout $escaped_path - 2>/dev/null");
            if (!empty(trim($output))) {
                // Clean up common PDF watermarks and artifacts
                $output = $this->clean_pdf_text($output);
                return $output;
            }
        }
        
        // Fallback to PHP-based extraction
        $content = file_get_contents($filepath);
        if ($content === false) {
            return new WP_Error('read_error', 'Could not read PDF file.');
        }
        
        // Simple PDF text extraction
        $text = '';
        
        // Try to find text streams in PDF
        // Look for text between BT (begin text) and ET (end text) markers
        if (preg_match_all('/BT\s*(.+?)\s*ET/s', $content, $matches)) {
            foreach ($matches[1] as $text_block) {
                // Extract text from Tj and TJ operators
                if (preg_match_all('/\(([^)]+)\)\s*Tj/s', $text_block, $tj_matches)) {
                    $text .= implode(' ', $tj_matches[1]) . "\n";
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $text_block, $TJ_matches)) {
                    foreach ($TJ_matches[1] as $TJ_content) {
                        if (preg_match_all('/\(([^)]+)\)/', $TJ_content, $inner_matches)) {
                            $text .= implode('', $inner_matches[1]);
                        }
                    }
                    $text .= "\n";
                }
            }
        }
        
        // Also try to extract raw text patterns
        // This catches some PDFs that store text differently
        if (empty(trim($text))) {
            // Try to find readable text sequences
            if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $stream_matches)) {
                foreach ($stream_matches[1] as $stream) {
                    // Decompress if using FlateDecode
                    $decompressed = @gzuncompress($stream);
                    if ($decompressed !== false) {
                        $stream = $decompressed;
                    }
                    
                    // Extract text between parentheses
                    if (preg_match_all('/\(([^)]{2,})\)/', $stream, $paren_matches)) {
                        foreach ($paren_matches[1] as $match) {
                            // Filter to printable characters
                            $filtered = preg_replace('/[^\x20-\x7E\n\r]/', '', $match);
                            if (strlen($filtered) > 2) {
                                $text .= $filtered . ' ';
                            }
                        }
                    }
                }
            }
        }
        
        // Clean up the text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/([.?!])\s+/', "$1\n", $text);
        
        // If still no text, provide helpful message
        if (empty(trim($text))) {
            return new WP_Error(
                'pdf_extraction_failed', 
                'Could not extract text from this PDF. The PDF may use complex encoding. Please install poppler-utils on your server for better PDF support, or copy the text manually and paste it into the Questions Text field.'
            );
        }
        
        return $this->clean_pdf_text($text);
    }
    
    /**
     * Find pdftotext executable path
     */
    private function find_pdftotext() {
        // Only check known safe paths for pdftotext
        $safe_paths = array(
            '/usr/bin/pdftotext',
            '/usr/local/bin/pdftotext',
        );
        
        foreach ($safe_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Clean PDF text by removing common watermarks and artifacts
     */
    private function clean_pdf_text($text) {
        if (empty($text)) {
            return '';
        }
        
        // Remove common watermark patterns found in Nigerian exam PDFs
        $patterns = array(
            '/myschoolgist\.com/i',  // myschoolgist.com watermark
            '/ysc\s*ho\s*ol\s*gis\s*t/i',  // broken myschoolgist text
            '/ww\s*w\.m/i',  // www.m partial
            '/Download\s+MySchoolGist[^\n]*/i',  // Download links
            '/https?:\/\/[^\s\n]+/i',  // URLs
            '/\n\s*\n\s*\n+/',  // Multiple blank lines
        );
        
        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, "\n", $text);
        }
        
        // Clean up extra whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $text);
        
        return trim($text);
    }
    
    /**
     * Extract text from DOCX file
     */
    private function extract_from_docx($filepath) {
        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_extension', 'ZipArchive extension is required to process DOCX files. Please contact your server administrator.');
        }
        
        // DOCX is a ZIP file containing XML
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            return new WP_Error('read_error', 'Could not open DOCX file.');
        }
        
        // Get the main document content
        $xml_content = $zip->getFromName('word/document.xml');
        $zip->close();
        
        if ($xml_content === false) {
            return new WP_Error('read_error', 'Could not read DOCX content.');
        }
        
        // Parse XML and extract text
        $text = '';
        
        // Remove XML tags but preserve structure
        $xml_content = str_replace('</w:p>', "\n", $xml_content);
        $xml_content = str_replace('</w:t>', ' ', $xml_content);
        $text = strip_tags($xml_content);
        
        // Clean up whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        return trim($text);
    }
    
    /**
     * Extract text from DOC file (older format)
     */
    private function extract_from_doc($filepath) {
        $content = file_get_contents($filepath);
        if ($content === false) {
            return new WP_Error('read_error', 'Could not read DOC file.');
        }
        
        // DOC files are binary, try to extract readable text
        // This is a simple approach that works for many DOC files
        $text = '';
        
        // Try to find text patterns
        if (preg_match_all('/[\x20-\x7E]{4,}/', $content, $matches)) {
            $text = implode(' ', $matches[0]);
        }
        
        if (empty(trim($text))) {
            return new WP_Error(
                'doc_extraction_failed',
                'Could not extract text from this DOC file. Please save it as DOCX or copy the text manually.'
            );
        }
        
        return $text;
    }
}

// Initialize
ZonaTech_Question_Importer::get_instance();