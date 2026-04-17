<?php
/**
 * Quiz System Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Quiz_System {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_zonatech_start_quiz', array($this, 'start_quiz'));
        add_action('wp_ajax_zonatech_submit_quiz', array($this, 'submit_quiz'));
        add_action('wp_ajax_zonatech_get_corrections', array($this, 'get_corrections'));
        add_action('wp_ajax_zonatech_get_quiz_history', array($this, 'get_quiz_history'));
    }
    
    public function start_quiz() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to take quiz.'));
        }
        
        $user_id = get_current_user_id();
        $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $question_count = intval($_POST['question_count'] ?? 50); // Default 50 questions per quiz
        $time_minutes = intval($_POST['time_minutes'] ?? 0); // Custom time in minutes (0 = auto)
        
        // Validate required parameters (year is no longer required)
        if (empty($exam_type) || empty($subject)) {
            wp_send_json_error(array('message' => 'Invalid quiz parameters.'));
        }
        
        // Limit question count between 10 and 100
        $question_count = max(10, min(100, $question_count));
        
        // Check access - Quiz is only available for paid users
        $past_questions = ZonaTech_Past_Questions::get_instance();
        if (!$past_questions->user_has_access($user_id, $exam_type, $subject)) {
            $category = ZonaTech_Past_Questions::get_subject_category($subject);
            $categories = ZonaTech_Past_Questions::get_subject_categories();
            $cat_info = isset($categories[$category]) ? $categories[$category] : null;
            
            wp_send_json_error(array(
                'message' => 'Quiz mode requires a paid subscription. Subscribe to access unlimited questions and quizzes!',
                'require_payment' => true,
                'exam_type' => $exam_type,
                'subject' => $subject,
                'category' => $category,
                'category_name' => $cat_info ? $cat_info['name'] : ucfirst($category),
                'monthly_price' => defined('ZONATECH_MONTHLY_PRICE') ? ZONATECH_MONTHLY_PRICE : 5000,
                'sixmonth_price' => defined('ZONATECH_6MONTH_PRICE') ? ZONATECH_6MONTH_PRICE : 25000
            ));
        }
        
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        // Get random questions for this exam type and subject (from all years)
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, question_text, option_a, option_b, option_c, option_d 
             FROM $table_questions 
             WHERE exam_type = %s AND subject = %s 
             ORDER BY RAND()
             LIMIT %d",
            $exam_type,
            $subject,
            $question_count
        ));
        
        if (empty($questions)) {
            wp_send_json_error(array('message' => 'No questions available for this selection.'));
        }
        
        ZonaTech_Activity_Log::log(
            $user_id,
            'quiz_start',
            sprintf('Started %s %s quiz (%d questions)', strtoupper($exam_type), $subject, count($questions))
        );
        
        // Timer calculation:
        // If custom time is provided (in minutes), use that
        // Otherwise, auto-calculate: 12 seconds per question
        if ($time_minutes > 0) {
            // Custom time provided (convert minutes to seconds)
            $time_limit = $time_minutes * 60;
        } else {
            // Auto-calculate: 12 seconds per question
            // 50 questions = 600 seconds (10 min)
            // 100 questions = 1200 seconds (20 min)
            $time_limit = count($questions) * 12;
        }
        
        wp_send_json_success(array(
            'questions' => $questions,
            'total' => count($questions),
            'exam_type' => strtoupper($exam_type),
            'subject' => $subject,
            'time_limit' => $time_limit
        ));
    }
    
    public function submit_quiz() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_id = get_current_user_id();
        $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $answers = isset($_POST['answers']) ? json_decode(stripslashes($_POST['answers']), true) : array();
        $time_taken = intval($_POST['time_taken'] ?? 0);
        
        // Year is no longer required
        if (empty($exam_type) || empty($subject) || empty($answers)) {
            wp_send_json_error(array('message' => 'Invalid submission data.'));
        }
        
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        // Get correct answers - validate all question IDs are integers
        $question_ids = array_keys($answers);
        $validated_ids = array();
        
        foreach ($question_ids as $id) {
            $int_id = intval($id);
            if ($int_id > 0) {
                $validated_ids[] = $int_id;
            }
        }
        
        if (empty($validated_ids)) {
            wp_send_json_error(array('message' => 'Invalid question IDs.'));
        }
        
        $placeholders = implode(',', array_fill(0, count($validated_ids), '%d'));
        
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, correct_answer FROM $table_questions WHERE id IN ($placeholders)",
            $validated_ids
        ), OBJECT_K);
        
        $correct = 0;
        $wrong = 0;
        $results = array();
        
        foreach ($answers as $question_id => $user_answer) {
            $question_id = intval($question_id);
            if (isset($questions[$question_id])) {
                $is_correct = strtoupper($user_answer) === strtoupper($questions[$question_id]->correct_answer);
                if ($is_correct) {
                    $correct++;
                } else {
                    $wrong++;
                }
                $results[$question_id] = array(
                    'user_answer' => strtoupper($user_answer),
                    'correct_answer' => $questions[$question_id]->correct_answer,
                    'is_correct' => $is_correct
                );
            }
        }
        
        $total = count($answers);
        $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        
        // Save quiz result
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        
        // Quiz result data and format types (year defaults to 0 for combined quizzes)
        $quiz_data = array(
            'user_id' => $user_id,
            'exam_type' => $exam_type,
            'subject' => $subject,
            'year' => 0, // No year for combined questions
            'total_questions' => $total,
            'correct_answers' => $correct,
            'wrong_answers' => $wrong,
            'score' => $score,
            'answers_data' => wp_json_encode($results),
            'time_taken' => $time_taken
        );
        $quiz_format = array('%d', '%s', '%s', '%d', '%d', '%d', '%d', '%f', '%s', '%d');
        
        // Insert with explicit format types for better database compatibility
        $insert_result = $wpdb->insert($table_quiz, $quiz_data, $quiz_format);
        
        // Check if insert failed (table might not exist)
        if ($insert_result === false) {
            // Create only the quiz results table if it doesn't exist
            $this->ensure_quiz_table_exists();
            
            // Retry the insert
            $insert_result = $wpdb->insert($table_quiz, $quiz_data, $quiz_format);
        }
        
        $result_id = $wpdb->insert_id;
        
        ZonaTech_Activity_Log::log(
            $user_id,
            'quiz_complete',
            sprintf('Completed %s %s quiz with score: %.2f%%', strtoupper($exam_type), $subject, $score),
            array('result_id' => $result_id, 'score' => $score)
        );
        
        // Send quiz score email to user
        $this->send_quiz_score_email($user_id, strtoupper($exam_type), $subject, $score, $correct, $wrong, $total, $this->get_grade($score));
        
        wp_send_json_success(array(
            'result_id' => $result_id,
            'score' => $score,
            'correct' => $correct,
            'wrong' => $wrong,
            'total' => $total,
            'time_taken' => $time_taken,
            'grade' => $this->get_grade($score),
            'message' => $this->get_score_message($score)
        ));
    }
    
    public function get_corrections() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_id = get_current_user_id();
        $result_id = intval($_POST['result_id'] ?? 0);
        
        if ($result_id < 1) {
            wp_send_json_error(array('message' => 'Invalid result ID.'));
        }
        
        global $wpdb;
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_quiz WHERE id = %d AND user_id = %d",
            $result_id,
            $user_id
        ));
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Quiz result not found.'));
        }
        
        $answers_data = json_decode($result->answers_data, true);
        
        // Get wrong answers only
        $wrong_ids = array();
        foreach ($answers_data as $q_id => $data) {
            if (!$data['is_correct']) {
                $wrong_ids[] = intval($q_id);
            }
        }
        
        if (empty($wrong_ids)) {
            wp_send_json_success(array(
                'message' => 'Congratulations! You got all answers correct!',
                'corrections' => array()
            ));
        }
        
        $placeholders = implode(',', array_fill(0, count($wrong_ids), '%d'));
        
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation 
             FROM $table_questions 
             WHERE id IN ($placeholders)",
            $wrong_ids
        ));
        
        $corrections = array();
        foreach ($questions as $q) {
            $corrections[] = array(
                'id' => $q->id,
                'question' => $q->question_text,
                'options' => array(
                    'A' => $q->option_a,
                    'B' => $q->option_b,
                    'C' => $q->option_c,
                    'D' => $q->option_d
                ),
                'your_answer' => $answers_data[$q->id]['user_answer'],
                'correct_answer' => $q->correct_answer,
                'explanation' => $q->explanation
            );
        }
        
        ZonaTech_Activity_Log::log($user_id, 'view_corrections', 'Viewed quiz corrections', array('result_id' => $result_id));
        
        wp_send_json_success(array('corrections' => $corrections));
    }
    
    public function get_quiz_history() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_id = get_current_user_id();
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        
        global $wpdb;
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_quiz WHERE user_id = %d",
            $user_id
        ));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, exam_type, subject, year, total_questions, correct_answers, wrong_answers, score, time_taken, created_at 
             FROM $table_quiz 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id,
            $per_page,
            $offset
        ));
        
        foreach ($results as &$r) {
            $r->grade = $this->get_grade($r->score);
        }
        
        wp_send_json_success(array(
            'results' => $results,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    private function get_grade($score) {
        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 45) return 'D';
        if ($score >= 40) return 'E';
        return 'F';
    }
    
    private function get_score_message($score) {
        if ($score >= 90) return 'Outstanding! You\'re a genius!';
        if ($score >= 70) return 'Excellent performance! Keep it up!';
        if ($score >= 60) return 'Good job! You\'re doing well!';
        if ($score >= 50) return 'Fair performance. Keep studying!';
        if ($score >= 40) return 'You need more practice. Don\'t give up!';
        return 'Keep trying! Review the corrections and try again.';
    }
    
    private function send_quiz_score_email($user_id, $exam_type, $subject, $score, $correct, $wrong, $total, $grade) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return;
        
        $to = $user->user_email;
        $first_name = get_user_meta($user_id, 'first_name', true) ?: $user->display_name;
        
        $subject_line = "Your {$exam_type} {$subject} Quiz Results - ZonaTech NG";
        
        // Determine grade color
        $grade_color = '#ef4444'; // red
        if ($score >= 70) $grade_color = '#22c55e'; // green
        else if ($score >= 50) $grade_color = '#f59e0b'; // yellow
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #0a0a0a;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 20px; padding: 40px; margin: 20px 0;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <div style="font-size: 32px; color: #8b5cf6; margin-bottom: 10px;">🎓</div>
                        <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Quiz Results</h1>
                    </div>
                    
                    <p style="color: #ffffff; font-size: 18px; margin-bottom: 20px;">Hi ' . esc_html($first_name) . ',</p>
                    
                    <p style="color: #a1a1aa; font-size: 14px; line-height: 1.6;">
                        You have completed the <strong style="color: #ffffff;">' . esc_html($exam_type) . ' ' . esc_html($subject) . '</strong> practice quiz. Here are your results:
                    </p>
                    
                    <div style="background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 15px; padding: 30px; margin: 25px 0; text-align: center;">
                        <div style="font-size: 64px; font-weight: bold; color: ' . $grade_color . '; margin-bottom: 10px;">' . number_format($score, 1) . '%</div>
                        <div style="font-size: 24px; color: #ffffff; margin-bottom: 5px;">Grade: ' . esc_html($grade) . '</div>
                        <p style="color: #a1a1aa; margin: 10px 0 0 0;">' . esc_html($this->get_score_message($score)) . '</p>
                    </div>
                    
                    <div style="display: flex; justify-content: space-around; margin: 25px 0;">
                        <div style="text-align: center; flex: 1; padding: 15px; background: rgba(34, 197, 94, 0.1); border-radius: 10px; margin: 0 5px;">
                            <div style="font-size: 28px; font-weight: bold; color: #22c55e;">' . esc_html($correct) . '</div>
                            <div style="font-size: 12px; color: #a1a1aa;">Correct</div>
                        </div>
                        <div style="text-align: center; flex: 1; padding: 15px; background: rgba(239, 68, 68, 0.1); border-radius: 10px; margin: 0 5px;">
                            <div style="font-size: 28px; font-weight: bold; color: #ef4444;">' . esc_html($wrong) . '</div>
                            <div style="font-size: 12px; color: #a1a1aa;">Wrong</div>
                        </div>
                        <div style="text-align: center; flex: 1; padding: 15px; background: rgba(139, 92, 246, 0.1); border-radius: 10px; margin: 0 5px;">
                            <div style="font-size: 28px; font-weight: bold; color: #8b5cf6;">' . esc_html($total) . '</div>
                            <div style="font-size: 12px; color: #a1a1aa;">Total</div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="' . site_url('/zonatech-past-questions/') . '" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 14px;">
                            Take Another Quiz
                        </a>
                    </div>
                    
                    <p style="color: #71717a; font-size: 12px; text-align: center; margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                        Keep practicing to improve your scores!<br>
                        © ' . date('Y') . ' ZonaTech NG. All rights reserved.
                    </p>
                </div>
            </div>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <' . ZONATECH_SUPPORT_EMAIL . '>'
        );
        
        wp_mail($to, $subject_line, $message, $headers);
    }
    
    public static function get_user_quiz_stats($user_id) {
        global $wpdb;
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_quizzes,
                AVG(score) as average_score,
                MAX(score) as best_score,
                SUM(correct_answers) as total_correct,
                SUM(wrong_answers) as total_wrong
             FROM $table_quiz 
             WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Ensure the quiz results table exists
     */
    private function ensure_quiz_table_exists() {
        global $wpdb;
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $sql = "CREATE TABLE $table_quiz (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            exam_type varchar(20) NOT NULL,
            subject varchar(100) NOT NULL,
            year int(4) NOT NULL,
            total_questions int(11) NOT NULL,
            correct_answers int(11) NOT NULL,
            wrong_answers int(11) NOT NULL,
            score decimal(5,2) NOT NULL,
            answers_data longtext,
            time_taken int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
}