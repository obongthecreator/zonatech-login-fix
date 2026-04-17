<?php
/**
 * Database Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Pending Users Table (for email verification)
        // Using direct SQL for more reliable table creation
        $table_pending = esc_sql($wpdb->prefix . 'zonatech_pending_users');
        $sql_pending = "CREATE TABLE IF NOT EXISTS `$table_pending` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `first_name` varchar(100) NOT NULL,
            `last_name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `phone` varchar(20) DEFAULT '',
            `password` varchar(255) NOT NULL,
            `verification_code` varchar(6) NOT NULL,
            `expires_at` datetime NOT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
        ) $charset_collate;";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query($sql_pending);
        
        // Past Questions Table
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        $sql_questions = "CREATE TABLE $table_questions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            exam_type varchar(20) NOT NULL,
            subject varchar(100) NOT NULL,
            year int(4) NOT NULL,
            passage_id bigint(20) DEFAULT NULL,
            question_text longtext NOT NULL,
            option_a text NOT NULL,
            option_b text NOT NULL,
            option_c text NOT NULL,
            option_d text NOT NULL,
            correct_answer char(1) NOT NULL,
            explanation longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exam_type (exam_type),
            KEY subject (subject),
            KEY year (year),
            KEY passage_id (passage_id)
        ) $charset_collate;";
        dbDelta($sql_questions);
        
        // Passages Table (for comprehension passages in English language questions)
        $table_passages = $wpdb->prefix . 'zonatech_passages';
        $sql_passages = "CREATE TABLE $table_passages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            exam_type varchar(20) NOT NULL,
            subject varchar(100) NOT NULL,
            year int(4) NOT NULL,
            passage_title varchar(255) DEFAULT '',
            passage_text longtext NOT NULL,
            passage_number int(11) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exam_type (exam_type),
            KEY subject (subject),
            KEY year (year)
        ) $charset_collate;";
        dbDelta($sql_passages);
        
        // User Purchases Table
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        $sql_purchases = "CREATE TABLE $table_purchases (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            purchase_type varchar(50) NOT NULL,
            item_name varchar(255) NOT NULL,
            amount decimal(10,2) NOT NULL,
            reference varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            meta_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY reference (reference),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_purchases);
        
        // Quiz Results Table
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        $sql_quiz = "CREATE TABLE $table_quiz (
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
        dbDelta($sql_quiz);
        
        // Activity Log Table
        $table_activity = $wpdb->prefix . 'zonatech_activity_log';
        $sql_activity = "CREATE TABLE $table_activity (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            activity_type varchar(50) NOT NULL,
            description text NOT NULL,
            meta_data longtext,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_activity);
        
        // Scratch Cards Table
        $table_cards = $wpdb->prefix . 'zonatech_scratch_cards';
        $sql_cards = "CREATE TABLE $table_cards (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            card_type varchar(20) NOT NULL,
            pin varchar(50) NOT NULL,
            serial_number varchar(50) NOT NULL,
            file_url varchar(500) DEFAULT NULL,
            status varchar(20) DEFAULT 'available',
            user_id bigint(20),
            purchase_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            sold_at datetime,
            PRIMARY KEY (id),
            KEY card_type (card_type),
            KEY status (status),
            UNIQUE KEY pin (pin)
        ) $charset_collate;";
        dbDelta($sql_cards);
        
        // NIN Requests Table
        $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
        $sql_nin = "CREATE TABLE $table_nin (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            nin_number varchar(20) NOT NULL,
            service_type varchar(50) DEFAULT 'nin_slip',
            form_data longtext,
            status varchar(20) DEFAULT 'pending',
            slip_url varchar(255),
            file_url varchar(255),
            admin_notes text,
            purchase_id bigint(20),
            api_response longtext,
            api_verified_at datetime,
            fulfilled_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY nin_number (nin_number),
            KEY service_type (service_type),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_nin);
        
        // User Subject Access Table (supports both individual subjects and categories)
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        $sql_access = "CREATE TABLE $table_access (
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
        dbDelta($sql_access);
        
        // Downloaded Documents Table
        $table_downloads = $wpdb->prefix . 'zonatech_downloads';
        $sql_downloads = "CREATE TABLE $table_downloads (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            document_type varchar(50) NOT NULL,
            document_name varchar(255) NOT NULL,
            file_url varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_downloads);
        
        // Feedback Table
        $table_feedback = $wpdb->prefix . 'zonatech_feedback';
        $sql_feedback = "CREATE TABLE $table_feedback (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            subject varchar(255) DEFAULT '',
            message text NOT NULL,
            rating int(1) DEFAULT 0,
            status varchar(20) DEFAULT 'unread',
            admin_response text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_feedback);
        
        // Run migrations to add new columns to existing tables
        self::run_migrations();
    }
    
    /**
     * Run database migrations for existing tables
     * This adds new columns that may not exist on older installations
     */
    public static function run_migrations() {
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        // Check if passage_id column exists, if not add it
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_questions LIKE 'passage_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_questions ADD COLUMN passage_id bigint(20) DEFAULT NULL AFTER year");
            $wpdb->query("ALTER TABLE $table_questions ADD KEY passage_id (passage_id)");
        }
    }
    
    public static function seed_sample_data() {
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_questions");
        if ($count > 0) {
            return;
        }
        
        // Comprehensive subjects for each exam type
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
        
        $exam_subjects = array(
            'jamb' => $jamb_subjects,
            'waec' => $waec_subjects,
            'neco' => $neco_subjects
        );
        
        $sample_questions = array(
            array(
                'question_text' => 'Which of the following is the correct definition of photosynthesis?',
                'option_a' => 'The process by which plants break down glucose',
                'option_b' => 'The process by which plants convert light energy to chemical energy',
                'option_c' => 'The process by which animals produce energy',
                'option_d' => 'The process of cellular respiration',
                'correct_answer' => 'B',
                'explanation' => 'Photosynthesis is the process by which green plants and some other organisms use sunlight to synthesize foods with the help of chlorophyll.'
            ),
            array(
                'question_text' => 'Solve for x: 2x + 5 = 15',
                'option_a' => 'x = 5',
                'option_b' => 'x = 10',
                'option_c' => 'x = 7.5',
                'option_d' => 'x = 20',
                'correct_answer' => 'A',
                'explanation' => '2x + 5 = 15, 2x = 15 - 5, 2x = 10, x = 5'
            ),
            array(
                'question_text' => 'The SI unit of force is?',
                'option_a' => 'Joule',
                'option_b' => 'Watt',
                'option_c' => 'Newton',
                'option_d' => 'Pascal',
                'correct_answer' => 'C',
                'explanation' => 'The SI unit of force is Newton (N), named after Sir Isaac Newton.'
            ),
            array(
                'question_text' => 'Which of the following is an element?',
                'option_a' => 'Water',
                'option_b' => 'Carbon dioxide',
                'option_c' => 'Sodium chloride',
                'option_d' => 'Oxygen',
                'correct_answer' => 'D',
                'explanation' => 'Oxygen is an element (O), while water (H2O), carbon dioxide (CO2), and sodium chloride (NaCl) are compounds.'
            ),
            array(
                'question_text' => 'What is the capital city of Nigeria?',
                'option_a' => 'Lagos',
                'option_b' => 'Abuja',
                'option_c' => 'Kano',
                'option_d' => 'Port Harcourt',
                'correct_answer' => 'B',
                'explanation' => 'Abuja became the capital city of Nigeria in 1991, replacing Lagos.'
            )
        );
        
        // Insert sample questions using batch inserts for better performance
        // Insert data for all subjects and years from 2010 to present
        $years = range(2010, date('Y'));
        
        $values = array();
        $placeholders = array();
        
        foreach ($exam_subjects as $exam_type => $subjects) {
            // Use 3 core subjects to keep data manageable while still having good coverage
            $core_subjects = array_slice($subjects, 0, 5);
            foreach ($core_subjects as $subject) {
                foreach ($years as $year) {
                    foreach ($sample_questions as $question) {
                        $values[] = $exam_type;
                        $values[] = $subject;
                        $values[] = $year;
                        $values[] = $question['question_text'];
                        $values[] = $question['option_a'];
                        $values[] = $question['option_b'];
                        $values[] = $question['option_c'];
                        $values[] = $question['option_d'];
                        $values[] = $question['correct_answer'];
                        $values[] = $question['explanation'];
                        $placeholders[] = "(%s, %s, %d, %s, %s, %s, %s, %s, %s, %s)";
                    }
                }
            }
        }
        
        // Batch insert all questions
        if (!empty($placeholders)) {
            $sql = "INSERT INTO $table_questions 
                    (exam_type, subject, year, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation) 
                    VALUES " . implode(', ', $placeholders);
            $wpdb->query($wpdb->prepare($sql, $values));
        }
        
        // Also add entries for all other subjects (with just 2024 year for demonstration)
        // This ensures all subjects appear in the dropdown
        $values = array();
        $placeholders = array();
        
        foreach ($exam_subjects as $exam_type => $subjects) {
            // Skip the first 5 subjects already added
            $remaining_subjects = array_slice($subjects, 5);
            foreach ($remaining_subjects as $subject) {
                // Add just one year (2024) with sample questions
                foreach ($sample_questions as $question) {
                    $values[] = $exam_type;
                    $values[] = $subject;
                    $values[] = 2024;
                    $values[] = $question['question_text'];
                    $values[] = $question['option_a'];
                    $values[] = $question['option_b'];
                    $values[] = $question['option_c'];
                    $values[] = $question['option_d'];
                    $values[] = $question['correct_answer'];
                    $values[] = $question['explanation'];
                    $placeholders[] = "(%s, %s, %d, %s, %s, %s, %s, %s, %s, %s)";
                }
            }
        }
        
        if (!empty($placeholders)) {
            $sql = "INSERT INTO $table_questions 
                    (exam_type, subject, year, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation) 
                    VALUES " . implode(', ', $placeholders);
            $wpdb->query($wpdb->prepare($sql, $values));
        }
        
        // Seed sample scratch cards with batch insert (reduced to 10 per type)
        $table_cards = $wpdb->prefix . 'zonatech_scratch_cards';
        $card_types = array('waec', 'neco', 'jamb');
        
        $card_values = array();
        $card_placeholders = array();
        
        foreach ($card_types as $card_type) {
            for ($i = 1; $i <= 10; $i++) {
                $card_values[] = $card_type;
                $card_values[] = strtoupper($card_type) . '-' . wp_generate_password(12, false, false);
                $card_values[] = strtoupper($card_type) . '-SN-' . wp_generate_password(8, false, false);
                $card_values[] = 'available';
                $card_placeholders[] = "(%s, %s, %s, %s)";
            }
        }
        
        if (!empty($card_placeholders)) {
            $sql = "INSERT INTO $table_cards (card_type, pin, serial_number, status) VALUES " . implode(', ', $card_placeholders);
            $wpdb->query($wpdb->prepare($sql, $card_values));
        }
    }
}