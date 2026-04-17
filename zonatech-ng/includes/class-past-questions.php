<?php
/**
 * Past Questions Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Past_Questions {
    
    private static $instance = null;
    private static $category_column_ensured = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_zonatech_get_subjects', array($this, 'get_subjects'));
        add_action('wp_ajax_nopriv_zonatech_get_subjects', array($this, 'get_subjects'));
        add_action('wp_ajax_zonatech_get_questions', array($this, 'get_questions'));
        add_action('wp_ajax_zonatech_search_questions', array($this, 'search_questions'));
        add_action('wp_ajax_zonatech_check_access', array($this, 'check_access'));
        add_action('wp_ajax_zonatech_check_category_access', array($this, 'check_category_access'));
        add_action('wp_ajax_zonatech_get_user_subjects', array($this, 'get_user_subjects'));
        add_action('wp_ajax_zonatech_get_categories', array($this, 'get_categories'));
        add_action('wp_ajax_nopriv_zonatech_get_categories', array($this, 'get_categories'));
    }
    
    /**
     * Get compulsory subjects that are included in ALL categories
     * These are available to anyone who purchases ANY category
     * Note: Mathematics and Further Mathematics are different subjects
     * - Mathematics is compulsory for ALL categories
     * - Further Mathematics is Science-only
     */
    public static function get_compulsory_subjects() {
        return array(
            'Mathematics',
            'English Language', 
            'Use of English'
        );
    }
    
    /**
     * Get subject categories with their subjects
     * Note: Mathematics and English are compulsory and included in ALL categories
     * Further Mathematics is a separate Science subject (not compulsory)
     */
    public static function get_subject_categories() {
        $compulsory = self::get_compulsory_subjects();
        
        return array(
            'science' => array(
                'name' => 'Science',
                'icon' => 'fas fa-flask',
                'color' => '#22c55e',
                'description' => 'Physics, Chemistry, Biology, Further Mathematics + Mathematics & English (compulsory)',
                'subjects' => array_merge($compulsory, array(
                    'Further Mathematics', 'Physics', 'Chemistry', 
                    'Biology', 'Agricultural Science', 'Computer Studies', 
                    'Data Processing', 'Technical Drawing', 'Animal Husbandry',
                    'Health Education', 'Health Science', 'Physical Education',
                    'Food & Nutrition', 'Home Economics'
                ))
            ),
            'arts' => array(
                'name' => 'Arts',
                'icon' => 'fas fa-palette',
                'color' => '#8b5cf6',
                'description' => 'Literature, Government, History + Mathematics & English (compulsory)',
                'subjects' => array_merge($compulsory, array(
                    'Literature in English',
                    'Government', 'History', 'Geography', 'Civic Education',
                    'Christian Religious Studies', 'Islamic Religious Studies',
                    'Fine Arts', 'Music', 'French', 'Arabic', 
                    'Hausa', 'Igbo', 'Yoruba'
                ))
            ),
            'business' => array(
                'name' => 'Business/Commercial',
                'icon' => 'fas fa-briefcase',
                'color' => '#f59e0b',
                'description' => 'Economics, Commerce, Accounting + Mathematics & English (compulsory)',
                'subjects' => array_merge($compulsory, array(
                    'Economics', 'Commerce', 'Accounting', 'Financial Accounting',
                    'Marketing', 'Office Practice', 'Insurance', 
                    'Business Studies', 'Principles of Accounts'
                ))
            )
        );
    }
    
    /**
     * Get category for a specific subject
     */
    public static function get_subject_category($subject) {
        $categories = self::get_subject_categories();
        $subject_lower = strtolower(trim($subject));
        
        foreach ($categories as $cat_key => $category) {
            foreach ($category['subjects'] as $cat_subject) {
                $cat_subject_lower = strtolower(trim($cat_subject));
                
                // Exact match first
                if ($subject_lower === $cat_subject_lower) {
                    return $cat_key;
                }
                
                // Check if subject contains the category subject name (for variations)
                // But only if the category subject is at least 5 chars to avoid false matches
                if (strlen($cat_subject_lower) >= 5) {
                    if (strpos($subject_lower, $cat_subject_lower) !== false) {
                        return $cat_key;
                    }
                }
            }
        }
        
        // Default to arts if not found (most general category)
        return 'arts';
    }
    
    /**
     * Get categories AJAX handler
     */
    public function get_categories() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        try {
            $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
            $user_id = is_user_logged_in() ? get_current_user_id() : 0;
            
            $categories = self::get_subject_categories();
            $formatted_categories = array();
            
            foreach ($categories as $cat_key => $category) {
                $has_access = false;
                
                // Only check access if user is logged in
                if ($user_id > 0) {
                    try {
                        $has_access = $this->user_has_category_access($user_id, $exam_type, $cat_key);
                    } catch (Exception $e) {
                        // Log error for debugging
                        error_log('ZonaTech: Error checking category access: ' . $e->getMessage());
                        $has_access = false;
                    }
                }
                
                $category_price = defined('ZONATECH_CATEGORY_PRICE') ? ZONATECH_CATEGORY_PRICE : 5000;
                
                $formatted_categories[$cat_key] = array(
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'description' => $category['description'],
                    'subjects' => $category['subjects'],
                    'subject_count' => count($category['subjects']),
                    'has_access' => $has_access,
                    'price' => $category_price
                );
            }
            
            wp_send_json_success(array('categories' => $formatted_categories));
        } catch (Exception $e) {
            // Log error for debugging
            error_log('ZonaTech: Error in get_categories: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error loading categories. Please try again.'));
        }
    }
    
    /**
     * Check if user has access to a category
     */
    public function check_category_access() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('has_access' => false));
        }
        
        $user_id = get_current_user_id();
        $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        $has_access = $this->user_has_category_access($user_id, $exam_type, $category);
        
        wp_send_json_success(array('has_access' => $has_access));
    }
    
    /**
     * Check if user has category access in database
     * Admins have free access to all categories
     */
    public function user_has_category_access($user_id, $exam_type, $category) {
        // Admins have free access to all categories
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Normalize exam_type to lowercase for consistent matching
        $exam_type = strtolower($exam_type);
        
        global $wpdb;
        
        // Check if the table exists first
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_access
        )) === $table_access;
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_access (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                exam_type varchar(20) NOT NULL,
                subject varchar(100) DEFAULT NULL,
                category varchar(50) DEFAULT NULL,
                purchase_id bigint(20),
                expires_at datetime,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY exam_subject (exam_type, subject),
                KEY exam_category (exam_type, category)
            ) $charset_collate;";
            dbDelta($sql);
            return false;
        }
        
        // Ensure category column exists (older installs may not have it)
        self::ensure_category_column();
        
        // Check for category-level access
        $access = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_access 
             WHERE user_id = %d AND exam_type = %s AND category = %s 
             AND (expires_at IS NULL OR expires_at > NOW())",
            $user_id,
            $exam_type,
            $category
        ));
        
        return (int) $access > 0;
    }
    
    public function get_user_subjects() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to view your subjects.'));
        }
        
        $user_id = get_current_user_id();
        $access_records = self::get_user_accessible_categories($user_id);
        
        $formatted_subjects = array();
        foreach ($access_records as $record) {
            // For category-based access, show the category info
            if (!empty($record->category)) {
                $categories = self::get_subject_categories();
                $cat_info = isset($categories[$record->category]) ? $categories[$record->category] : null;
                
                $formatted_subjects[] = array(
                    'exam_type' => $record->exam_type,
                    'category' => $record->category,
                    'category_name' => $cat_info ? $cat_info['name'] : ucfirst($record->category),
                    'subjects' => $cat_info ? $cat_info['subjects'] : array(),
                    'date' => date('M d, Y', strtotime($record->created_at)),
                    'expires' => $record->expires_at ? date('M d, Y', strtotime($record->expires_at)) : 'Never'
                );
            } else {
                // Legacy individual subject access
                $formatted_subjects[] = array(
                    'exam_type' => $record->exam_type,
                    'subject' => $record->subject,
                    'date' => date('M d, Y', strtotime($record->created_at)),
                    'expires' => $record->expires_at ? date('M d, Y', strtotime($record->expires_at)) : 'Never'
                );
            }
        }
        
        wp_send_json_success(array('subjects' => $formatted_subjects));
    }
    
    public function get_subjects() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
        
        if (empty($exam_type)) {
            wp_send_json_error(array('message' => 'Exam type is required.'));
        }
        
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        // First try to get subjects from database
        $subjects = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT subject FROM $table_questions WHERE exam_type = %s ORDER BY subject",
            $exam_type
        ));
        
        // If no subjects in database, use predefined list
        if (empty($subjects)) {
            $subjects = self::get_predefined_subjects($exam_type);
        }
        
        wp_send_json_success(array('subjects' => $subjects));
    }
    
    public static function get_predefined_subjects($exam_type) {
        $jamb_subjects = array(
            'Use of English',
            'Mathematics',
            'Physics',
            'Chemistry',
            'Biology',
            'Agricultural Science',
            'Economics',
            'Commerce',
            'Accounting',
            'Government',
            'Geography',
            'Literature in English',
            'Christian Religious Studies',
            'Islamic Religious Studies',
            'History',
            'Civic Education',
            'Home Economics',
            'Food & Nutrition',
            'Fine Arts',
            'Music',
            'French',
            'Arabic',
            'Hausa',
            'Igbo',
            'Yoruba',
            'Physical Education'
        );
        
        $waec_subjects = array(
            'English Language',
            'Mathematics',
            'Civic Education',
            'Physics',
            'Chemistry',
            'Biology',
            'Agricultural Science',
            'Further Mathematics',
            'Health Education',
            'Economics',
            'Commerce',
            'Financial Accounting',
            'Literature in English',
            'Government',
            'History',
            'Christian Religious Studies',
            'Islamic Religious Studies',
            'Geography',
            'Fine Arts',
            'Music',
            'French',
            'Arabic',
            'Hausa',
            'Igbo',
            'Yoruba',
            'Data Processing',
            'Computer Studies',
            'Animal Husbandry',
            'Technical Drawing'
        );
        
        $neco_subjects = array(
            'English Language',
            'Mathematics',
            'Civic Education',
            'Physics',
            'Chemistry',
            'Biology',
            'Agricultural Science',
            'Further Mathematics',
            'Health Science',
            'Economics',
            'Commerce',
            'Financial Accounting',
            'Literature in English',
            'Government',
            'History',
            'Christian Religious Studies',
            'Islamic Religious Studies',
            'Geography',
            'Fine Arts',
            'Music',
            'French',
            'Arabic',
            'Hausa',
            'Igbo',
            'Yoruba',
            'Computer Studies',
            'Data Processing',
            'Marketing',
            'Home Economics',
            'Animal Husbandry',
            'Technical Drawing'
        );
        
        $all_subjects = array(
            'jamb' => $jamb_subjects,
            'waec' => $waec_subjects,
            'neco' => $neco_subjects
        );
        
        return isset($all_subjects[$exam_type]) ? $all_subjects[$exam_type] : array();
    }
    
    public function get_questions() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to access questions.'));
        }
        
        $user_id = get_current_user_id();
        $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        
        if (empty($exam_type) || empty($subject)) {
            wp_send_json_error(array('message' => 'Invalid request parameters.'));
        }
        
        // Check if user has paid access
        $has_paid_access = $this->user_has_access($user_id, $exam_type, $subject);
        $free_limit = defined('ZONATECH_FREE_QUESTIONS_LIMIT') ? ZONATECH_FREE_QUESTIONS_LIMIT : 10;
        
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        $table_passages = $wpdb->prefix . 'zonatech_passages';
        
        // Check if passage_id column exists
        $has_passage_id = !empty($wpdb->get_results("SHOW COLUMNS FROM $table_questions LIKE 'passage_id'"));
        
        // Get all questions for this exam type and subject (from all years)
        if ($has_passage_id) {
            $questions = $wpdb->get_results($wpdb->prepare(
                "SELECT id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, passage_id 
                 FROM $table_questions 
                 WHERE exam_type = %s AND subject = %s 
                 ORDER BY id",
                $exam_type,
                $subject
            ));
        } else {
            $questions = $wpdb->get_results($wpdb->prepare(
                "SELECT id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation 
                 FROM $table_questions 
                 WHERE exam_type = %s AND subject = %s 
                 ORDER BY id",
                $exam_type,
                $subject
            ));
        }
        
        // Get passages for this subject (for comprehension questions)
        $passages = array();
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_passages'");
        if ($table_exists) {
            $passage_results = $wpdb->get_results($wpdb->prepare(
                "SELECT id, passage_title, passage_text, passage_number 
                 FROM $table_passages 
                 WHERE exam_type = %s AND subject = %s 
                 ORDER BY passage_number",
                $exam_type,
                $subject
            ));
            
            // Index passages by ID for easy lookup
            foreach ($passage_results as $passage) {
                $passages[$passage->id] = array(
                    'id' => $passage->id,
                    'title' => $passage->passage_title,
                    'text' => $passage->passage_text,
                    'number' => $passage->passage_number
                );
            }
        }
        
        // Log activity
        ZonaTech_Activity_Log::log(
            $user_id,
            'view_questions',
            sprintf('Accessed %s %s questions', strtoupper($exam_type), $subject)
        );
        
        // For free users, limit to first 10 questions
        $total_questions = count($questions);
        $is_limited = false;
        $category = self::get_subject_category($subject);
        $categories = self::get_subject_categories();
        $cat_info = isset($categories[$category]) ? $categories[$category] : null;
        
        if (!$has_paid_access && $total_questions > $free_limit) {
            $questions = array_slice($questions, 0, $free_limit);
            $is_limited = true;
        }
        
        wp_send_json_success(array(
            'questions' => $questions,
            'passages' => $passages,
            'total' => $total_questions,
            'shown' => count($questions),
            'exam_type' => strtoupper($exam_type),
            'subject' => $subject,
            'has_paid_access' => $has_paid_access,
            'is_limited' => $is_limited,
            'free_limit' => $free_limit,
            'require_payment' => $is_limited,
            'category' => $category,
            'category_name' => $cat_info ? $cat_info['name'] : ucfirst($category),
            'monthly_price' => defined('ZONATECH_MONTHLY_PRICE') ? ZONATECH_MONTHLY_PRICE : 5000,
            'sixmonth_price' => defined('ZONATECH_6MONTH_PRICE') ? ZONATECH_6MONTH_PRICE : 25000
        ));
    }
    
    public function search_questions() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to search questions.'));
        }
        
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = 20;
        
        if (empty($search_term) && empty($exam_type)) {
            wp_send_json_error(array('message' => 'Please provide search criteria.'));
        }
        
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        $where = array('1=1');
        $params = array();
        
        if (!empty($search_term)) {
            $where[] = 'question_text LIKE %s';
            $params[] = '%' . $wpdb->esc_like($search_term) . '%';
        }
        
        if (!empty($exam_type)) {
            $where[] = 'exam_type = %s';
            $params[] = $exam_type;
        }
        
        if (!empty($subject)) {
            $where[] = 'subject = %s';
            $params[] = $subject;
        }
        
        if ($year > 0) {
            $where[] = 'year = %d';
            $params[] = $year;
        }
        
        $where_clause = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM $table_questions WHERE $where_clause";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }
        $total = $wpdb->get_var($count_sql);
        
        // Get results
        $params[] = $per_page;
        $params[] = $offset;
        
        $sql = "SELECT id, exam_type, subject, year, question_text 
                FROM $table_questions 
                WHERE $where_clause 
                ORDER BY year DESC, subject 
                LIMIT %d OFFSET %d";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        wp_send_json_success(array(
            'questions' => $results,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    public function check_access() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('has_access' => false));
        }
        
        $user_id = get_current_user_id();
        $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        
        $has_access = $this->user_has_access($user_id, $exam_type, $subject);
        
        // Also return category info if they don't have access
        if (!$has_access) {
            $category = self::get_subject_category($subject);
            wp_send_json_success(array(
                'has_access' => $has_access,
                'category' => $category,
                'category_price' => defined('ZONATECH_CATEGORY_PRICE') ? ZONATECH_CATEGORY_PRICE : 5000
            ));
        }
        
        wp_send_json_success(array('has_access' => $has_access));
    }
    
    /**
     * Check if user has access to a subject (via category or legacy individual access)
     * Note: Compulsory subjects (Mathematics, English) are accessible if user has ANY category
     * Admins have free access to all subjects
     */
    public function user_has_access($user_id, $exam_type, $subject) {
        // Admins have free access to all subjects and quizzes
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Normalize exam_type to lowercase for consistent matching
        $exam_type = strtolower($exam_type);
        
        global $wpdb;
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        
        // First check for legacy individual subject access
        $individual_access = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_access 
             WHERE user_id = %d AND exam_type = %s AND subject = %s 
             AND (expires_at IS NULL OR expires_at > NOW())",
            $user_id,
            $exam_type,
            $subject
        ));
        
        if ((int) $individual_access > 0) {
            return true;
        }
        
        // Check if this is a compulsory subject (Mathematics or English)
        $compulsory_subjects = self::get_compulsory_subjects();
        $is_compulsory = false;
        $subject_lower = strtolower(trim($subject));
        
        foreach ($compulsory_subjects as $comp_subj) {
            if (strtolower(trim($comp_subj)) === $subject_lower || 
                strpos($subject_lower, strtolower($comp_subj)) !== false) {
                $is_compulsory = true;
                break;
            }
        }
        
        // If compulsory, check if user has ANY category access for this exam type
        if ($is_compulsory) {
            // Ensure category column exists before querying it
            self::ensure_category_column();
            
            $any_category_access = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_access 
                 WHERE user_id = %d AND exam_type = %s AND category IS NOT NULL AND category != ''
                 AND (expires_at IS NULL OR expires_at > NOW())",
                $user_id,
                $exam_type
            ));
            
            if ((int) $any_category_access > 0) {
                return true;
            }
        }
        
        // Check for category-based access
        $category = self::get_subject_category($subject);
        return $this->user_has_category_access($user_id, $exam_type, $category);
    }
    
    public static function get_exam_types() {
        return array(
            'jamb' => array(
                'name' => 'JAMB',
                'full_name' => 'Joint Admissions and Matriculation Board',
                'icon' => 'fas fa-graduation-cap',
                'color' => '#8b5cf6'
            ),
            'waec' => array(
                'name' => 'WAEC',
                'full_name' => 'West African Examinations Council',
                'icon' => 'fas fa-book-open',
                'color' => '#22c55e'
            ),
            'neco' => array(
                'name' => 'NECO',
                'full_name' => 'National Examinations Council',
                'icon' => 'fas fa-scroll',
                'color' => '#f59e0b'
            )
        );
    }
    
    public static function get_user_accessible_subjects($user_id) {
        global $wpdb;
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT exam_type, subject, created_at, expires_at 
             FROM $table_access 
             WHERE user_id = %d 
             ORDER BY exam_type, subject",
            $user_id
        ));
    }
    
    public static function get_user_accessible_categories($user_id) {
        global $wpdb;
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT exam_type, subject, category, created_at, expires_at 
             FROM $table_access 
             WHERE user_id = %d 
             ORDER BY exam_type, category, subject",
            $user_id
        ));
    }
    
    /**
     * Ensure the category column exists in zonatech_user_access table.
     * Older installations may have the table without this column.
     * Uses a static flag to only check once per request.
     */
    public static function ensure_category_column() {
        if (self::$category_column_ensured) {
            return;
        }
        self::$category_column_ensured = true;
        
        global $wpdb;
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_access));
        if ($table_exists !== $table_access) {
            return; // Table doesn't exist; will be created elsewhere
        }
        
        // Check if category column exists
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $col_check = $wpdb->get_results("SHOW COLUMNS FROM `$table_access` LIKE 'category'");
        if (empty($col_check)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query("ALTER TABLE `$table_access` ADD COLUMN `category` varchar(50) DEFAULT NULL AFTER `subject`");
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query("ALTER TABLE `$table_access` ADD KEY `exam_category` (`exam_type`, `category`)");
        }
        
        // Ensure subject column allows NULL (older installs may have NOT NULL)
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query("ALTER TABLE `$table_access` MODIFY COLUMN `subject` varchar(100) DEFAULT NULL");
        // Ensure purchase_id column allows NULL
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query("ALTER TABLE `$table_access` MODIFY COLUMN `purchase_id` bigint(20) DEFAULT NULL");
    }
}