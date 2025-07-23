<?php
/**
 * Database Class
 * 
 * Handles custom database tables creation and management
 * Updated with security fixes and performance improvements
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_Database {
    
    public function __construct() {
        add_action('init', [$this, 'checkDatabaseVersion']);
    }
    
    /**
     * Check database version and update if needed
     */
    public function checkDatabaseVersion(): void {
        $installed_version = get_option('elearning_quiz_db_version', '0');
        
        if (version_compare($installed_version, ELEARNING_QUIZ_VERSION, '<')) {
            self::createTables();
            update_option('elearning_quiz_db_version', ELEARNING_QUIZ_VERSION);
        }
    }
    
    /**
     * Create custom database tables with improved indexes
     */
    public static function createTables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Quiz attempts table with improved indexes
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            attempt_id varchar(20) NOT NULL,
            quiz_id bigint(20) NOT NULL,
            user_session varchar(255) DEFAULT NULL,
            language varchar(5) DEFAULT 'en',
            start_time datetime NOT NULL,
            end_time datetime DEFAULT NULL,
            time_spent int(11) DEFAULT NULL,
            status enum('started', 'completed', 'abandoned') DEFAULT 'started',
            score decimal(5,2) DEFAULT NULL,
            total_questions int(11) DEFAULT NULL,
            correct_answers int(11) DEFAULT NULL,
            passed tinyint(1) DEFAULT 0,
            questions_shown text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY attempt_id (attempt_id),
            KEY quiz_id (quiz_id),
            KEY user_session (user_session),
            KEY status (status),
            KEY language (language),
            KEY passed (passed),
            KEY quiz_user_date (quiz_id, user_session, created_at),
            KEY user_quiz_status (user_session, quiz_id, status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Quiz answers table with time tracking
        $table_name = $wpdb->prefix . 'elearning_quiz_answers';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            attempt_id varchar(20) NOT NULL,
            question_index int(11) NOT NULL,
            question_type varchar(50) NOT NULL,
            question_text text NOT NULL,
            user_answer text DEFAULT NULL,
            correct_answer text DEFAULT NULL,
            is_correct tinyint(1) DEFAULT 0,
            time_spent int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY attempt_id (attempt_id),
            KEY question_type (question_type),
            KEY is_correct (is_correct),
            KEY attempt_question (attempt_id, question_index)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Lesson progress table
        $table_name = $wpdb->prefix . 'elearning_lesson_progress';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lesson_id bigint(20) NOT NULL,
            user_session varchar(255) NOT NULL,
            section_index int(11) NOT NULL,
            completed tinyint(1) DEFAULT 0,
            scroll_completed tinyint(1) DEFAULT 0,
            button_completed tinyint(1) DEFAULT 0,
            time_spent int(11) DEFAULT NULL,
            scroll_percentage decimal(5,2) DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY lesson_user_section (lesson_id, user_session, section_index),
            KEY lesson_id (lesson_id),
            KEY user_session (user_session),
            KEY completed (completed),
            KEY lesson_user_completed (lesson_id, user_session, completed)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Drop custom database tables
     */
    public static function dropTables(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'elearning_quiz_attempts',
            $wpdb->prefix . 'elearning_quiz_answers',
            $wpdb->prefix . 'elearning_lesson_progress'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Generate unique attempt ID
     */
    public static function generateAttemptId(): string {
        global $wpdb;
        
        do {
            $attempt_id = 'tst' . wp_generate_password(10, false, false);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}elearning_quiz_attempts WHERE attempt_id = %s",
                $attempt_id
            ));
        } while ($exists > 0);
        
        return $attempt_id;
    }
    
    /**
     * Get or create user session using cookies instead of PHP sessions
     */
    public static function getOrCreateUserSession(): string {
        $session_id = isset($_COOKIE['elearning_user_session']) ? sanitize_text_field($_COOKIE['elearning_user_session']) : '';
        
        if (empty($session_id)) {
            $session_id = 'user_' . wp_generate_password(16, false, false) . '_' . time();
            setcookie('elearning_user_session', $session_id, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }
        
        return $session_id;
    }
    
    /**
     * Start a new quiz attempt with rate limiting
     */
    public static function startQuizAttempt($quiz_id, $questions_shown = []): string {
        global $wpdb;
        
        $user_session = self::getOrCreateUserSession();
        
        // Rate limiting check
        $attempts_key = 'quiz_attempts_' . $user_session;
        $attempts_in_last_minute = get_transient($attempts_key) ?: 0;
        
        if ($attempts_in_last_minute > 5) {
            return false; // Too many attempts
        }
        
        set_transient($attempts_key, $attempts_in_last_minute + 1, 60);
        
        $attempt_id = self::generateAttemptId();
        $language = self::getCurrentLanguage();
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        try {
            $result = $wpdb->insert($table_name, [
                'attempt_id' => $attempt_id,
                'quiz_id' => $quiz_id,
                'user_session' => $user_session,
                'language' => $language,
                'start_time' => current_time('mysql'),
                'status' => 'started',
                'questions_shown' => wp_json_encode($questions_shown)
            ]);
            
            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }
        } catch (Exception $e) {
            error_log('E-Learning Quiz Error: ' . $e->getMessage());
            return false;
        }
        
        return $attempt_id;
    }
    
    /**
     * Complete a quiz attempt with time tracking
     */
    public static function completeQuizAttempt($attempt_id, $score, $total_questions, $correct_answers, $passing_score): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        // Calculate time spent
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT start_time FROM $table_name WHERE attempt_id = %s",
            $attempt_id
        ));
        
        $time_spent = null;
        if ($attempt) {
            $start = strtotime($attempt->start_time);
            $end = time();
            $time_spent = $end - $start;
        }
        
        $passed = ($score >= $passing_score) ? 1 : 0;
        
        try {
            $result = $wpdb->update(
                $table_name,
                [
                    'end_time' => current_time('mysql'),
                    'time_spent' => $time_spent,
                    'status' => 'completed',
                    'score' => $score,
                    'total_questions' => $total_questions,
                    'correct_answers' => $correct_answers,
                    'passed' => $passed
                ],
                ['attempt_id' => $attempt_id]
            );
            
            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }
            
            // Clear statistics cache
            $quiz_id = $wpdb->get_var($wpdb->prepare(
                "SELECT quiz_id FROM $table_name WHERE attempt_id = %s",
                $attempt_id
            ));
            
            if ($quiz_id) {
                delete_transient('quiz_stats_' . $quiz_id);
                delete_transient('global_quiz_stats');
            }
            
        } catch (Exception $e) {
            error_log('E-Learning Quiz Error: ' . $e->getMessage());
            return false;
        }
        
        return $result !== false;
    }
    
    /**
     * Save quiz answer with error handling
     */
    public static function saveQuizAnswer($attempt_id, $question_index, $question_type, $question_text, $user_answer, $correct_answer, $is_correct, $time_spent = null): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_answers';
        
        // Convert arrays to JSON for storage
        if (is_array($user_answer)) {
            $user_answer = wp_json_encode($user_answer);
        }
        if (is_array($correct_answer)) {
            $correct_answer = wp_json_encode($correct_answer);
        }
        
        try {
            $result = $wpdb->insert($table_name, [
                'attempt_id' => $attempt_id,
                'question_index' => $question_index,
                'question_type' => $question_type,
                'question_text' => wp_strip_all_tags($question_text),
                'user_answer' => $user_answer,
                'correct_answer' => $correct_answer,
                'is_correct' => $is_correct ? 1 : 0,
                'time_spent' => $time_spent
            ]);
            
            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }
        } catch (Exception $e) {
            error_log('E-Learning Quiz Error: ' . $e->getMessage());
            return false;
        }
        
        return $result !== false;
    }
    
    /**
     * Update lesson progress with better tracking
     */
    public static function updateLessonProgress($lesson_id, $section_index, $completed = false, $time_spent = null, $scroll_percentage = null): bool {
        global $wpdb;
        
        $user_session = self::getOrCreateUserSession();
        $table_name = $wpdb->prefix . 'elearning_lesson_progress';
        
        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE lesson_id = %d AND user_session = %s AND section_index = %d",
            $lesson_id, $user_session, $section_index
        ));
        
        $data = [
            'time_spent' => $time_spent,
            'scroll_percentage' => $scroll_percentage
        ];
        
        // Auto-complete if scrolled to 90%+ and spent reasonable time
        if ($scroll_percentage >= 90 && $time_spent >= 30) {
            $data['scroll_completed'] = 1;
        }
        
        if ($completed) {
            $data['button_completed'] = 1;
            $data['completed'] = 1;
            $data['completed_at'] = current_time('mysql');
        }
        
        try {
            if ($existing) {
                // Update existing record
                $result = $wpdb->update(
                    $table_name,
                    $data,
                    [
                        'lesson_id' => $lesson_id,
                        'user_session' => $user_session,
                        'section_index' => $section_index
                    ]
                );
            } else {
                // Insert new record
                $data = array_merge($data, [
                    'lesson_id' => $lesson_id,
                    'user_session' => $user_session,
                    'section_index' => $section_index,
                    'completed' => $completed ? 1 : 0
                ]);
                
                if ($completed) {
                    $data['completed_at'] = current_time('mysql');
                }
                
                $result = $wpdb->insert($table_name, $data);
            }
            
            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }
        } catch (Exception $e) {
            error_log('E-Learning Lesson Progress Error: ' . $e->getMessage());
            return false;
        }
        
        return $result !== false;
    }
    
    /**
     * Get lesson progress
     */
    public static function getLessonProgress($lesson_id, $user_session = null): array {
        global $wpdb;
        
        if (!$user_session) {
            $user_session = self::getOrCreateUserSession();
        }
        
        $table_name = $wpdb->prefix . 'elearning_lesson_progress';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE lesson_id = %d AND user_session = %s ORDER BY section_index",
            $lesson_id, $user_session
        ), ARRAY_A);
        
        return $results ?: [];
    }
    
    /**
     * Get previously used questions for a user session
     */
    public static function getPreviouslyUsedQuestions($quiz_id, $user_session = null): array {
        global $wpdb;
        
        if (!$user_session) {
            $user_session = self::getOrCreateUserSession();
        }
        
        $attempts_table = $wpdb->prefix . 'elearning_quiz_attempts';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT questions_shown FROM $attempts_table 
             WHERE quiz_id = %d AND user_session = %s AND status = 'completed'
             ORDER BY created_at DESC",
            $quiz_id, $user_session
        ), ARRAY_A);
        
        $used_questions = [];
        foreach ($results as $row) {
            $questions = json_decode($row['questions_shown'], true);
            if (is_array($questions)) {
                $used_questions = array_merge($used_questions, $questions);
            }
        }
        
        return array_unique($used_questions);
    }
    
    /**
     * Get quiz statistics with caching
     */
    public static function getQuizStatistics($quiz_id): array {
        global $wpdb;
        
        // Check cache first
        $cache_key = 'quiz_stats_' . $quiz_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_attempts,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_attempts,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as passed_attempts,
                COUNT(CASE WHEN passed = 0 AND status = 'completed' THEN 1 END) as failed_attempts,
                AVG(CASE WHEN status = 'completed' THEN score END) as average_score,
                MAX(score) as highest_score,
                MIN(CASE WHEN status = 'completed' THEN score END) as lowest_score,
                COUNT(CASE WHEN language = 'en' THEN 1 END) as english_attempts,
                COUNT(CASE WHEN language = 'gr' THEN 1 END) as greek_attempts,
                AVG(CASE WHEN status = 'completed' AND time_spent IS NOT NULL THEN time_spent END) as avg_time_spent
             FROM $table_name 
             WHERE quiz_id = %d",
            $quiz_id
        ), ARRAY_A);
        
        if (!$stats) {
            $stats = [];
        }
        
        // Cache for 1 hour
        set_transient($cache_key, $stats, HOUR_IN_SECONDS);
        
        return $stats;
    }
    
    /**
     * Get global statistics with caching
     */
    public static function getGlobalStatistics(): array {
        global $wpdb;
        
        // Check cache first
        $cached = get_transient('global_quiz_stats');
        if ($cached !== false) {
            return $cached;
        }
        
        $attempts_table = $wpdb->prefix . 'elearning_quiz_attempts';
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_quiz_attempts,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_quiz_attempts,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as passed_quiz_attempts,
                COUNT(DISTINCT user_session) as unique_users,
                AVG(CASE WHEN status = 'completed' THEN score END) as global_average_score,
                COUNT(CASE WHEN language = 'en' THEN 1 END) as english_total,
                COUNT(CASE WHEN language = 'gr' THEN 1 END) as greek_total
             FROM $attempts_table",
            ARRAY_A
        );
        
        if (!$stats) {
            $stats = [];
        }
        
        // Cache for 30 minutes
        set_transient('global_quiz_stats', $stats, 30 * MINUTE_IN_SECONDS);
        
        return $stats;
    }
    
    /**
     * Get user quiz attempts
     */
    public static function getUserQuizAttempts($user_session, $quiz_id = null): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        $where_clause = "WHERE user_session = %s";
        $params = [$user_session];
        
        if ($quiz_id) {
            $where_clause .= " AND quiz_id = %d";
            $params[] = $quiz_id;
        }
        
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY start_time DESC";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
        
        return $results ?: [];
    }
    
    /**
     * Clean up old data (GDPR compliance)
     */
    public static function cleanupOldData(): int {
        global $wpdb;
        
        $settings = get_option('elearning_quiz_settings', []);
        $retention_days = $settings['data_retention_days'] ?? 365;
        
        if ($retention_days <= 0) {
            return 0; // Don't delete if set to 0 or negative
        }
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        $total_deleted = 0;
        
        try {
            // Clean up quiz attempts
            $attempts_table = $wpdb->prefix . 'elearning_quiz_attempts';
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM $attempts_table WHERE created_at < %s",
                $cutoff_date
            ));
            $total_deleted += $deleted;
            
            // Clean up quiz answers
            $answers_table = $wpdb->prefix . 'elearning_quiz_answers';
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM $answers_table WHERE created_at < %s",
                $cutoff_date
            ));
            $total_deleted += $deleted;
            
            // Clean up lesson progress
            $progress_table = $wpdb->prefix . 'elearning_lesson_progress';
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM $progress_table WHERE created_at < %s",
                $cutoff_date
            ));
            $total_deleted += $deleted;
            
            // Clear all caches after cleanup
            delete_transient('global_quiz_stats');
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_quiz_stats_%'");
            
        } catch (Exception $e) {
            error_log('E-Learning Data Cleanup Error: ' . $e->getMessage());
        }
        
        return $total_deleted;
    }
    
    /**
     * Get current language for WPML compatibility
     */
    private static function getCurrentLanguage(): string {
        // Check for WPML
        if (function_exists('icl_get_current_language')) {
            return icl_get_current_language();
        }
        
        // Fallback to WordPress locale
        $locale = get_locale();
        if (strpos($locale, 'el') === 0) {
            return 'gr';
        }
        
        return 'en';
    }
    
    /**
     * Export quiz data to CSV with improved format
     */
    public static function exportQuizData($quiz_id, $start_date = null, $end_date = null): string {
        global $wpdb;
        
        $attempts_table = $wpdb->prefix . 'elearning_quiz_attempts';
        $answers_table = $wpdb->prefix . 'elearning_quiz_answers';
        
        $where_clause = "WHERE a.quiz_id = %d";
        $params = [$quiz_id];
        
        if ($start_date) {
            $where_clause .= " AND a.start_time >= %s";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $where_clause .= " AND a.start_time <= %s";
            $params[] = $end_date;
        }
        
        // Get attempt data with question details
        $sql = "SELECT 
                    a.attempt_id,
                    a.user_session,
                    a.language,
                    a.start_time,
                    a.end_time,
                    a.time_spent,
                    a.status,
                    a.score,
                    a.total_questions,
                    a.correct_answers,
                    a.passed,
                    TIMESTAMPDIFF(MINUTE, a.start_time, a.end_time) as duration_minutes
                FROM $attempts_table a
                $where_clause
                ORDER BY a.start_time DESC";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
        
        if (empty($results)) {
            return '';
        }
        
        // Create CSV content
        $csv_content = '';
        
        // Add headers
        $headers = [
            'Attempt ID',
            'User Session',
            'Language',
            'Start Time',
            'End Time',
            'Time Spent (seconds)',
            'Status',
            'Score (%)',
            'Total Questions',
            'Correct Answers',
            'Passed',
            'Duration (minutes)'
        ];
        $csv_content .= implode(',', $headers) . "\n";
        
        // Add data rows
        foreach ($results as $row) {
            $csv_content .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value ?? '') . '"';
            }, $row)) . "\n";
        }
        
        return $csv_content;
    }
    
    /**
     * Get difficult questions (lowest success rate)
     */
    public static function getDifficultQuestions($quiz_id, $limit = 5): array {
        global $wpdb;
        
        $attempts_table = $wpdb->prefix . 'elearning_quiz_attempts';
        $answers_table = $wpdb->prefix . 'elearning_quiz_answers';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                a.question_index,
                a.question_type,
                LEFT(a.question_text, 100) as question_preview,
                COUNT(*) as total_answers,
                COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) as correct_answers,
                (COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) / COUNT(*)) * 100 as success_rate
             FROM $answers_table a
             INNER JOIN $attempts_table att ON a.attempt_id = att.attempt_id
             WHERE att.quiz_id = %d
             GROUP BY a.question_index, a.question_type, a.question_text
             ORDER BY success_rate ASC
             LIMIT %d",
            $quiz_id,
            $limit
        ), ARRAY_A);
        
        return $results ?: [];
    }
    
    /**
     * Track quiz abandonment
     */
    public static function trackQuizAbandonment(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        // Mark old started quizzes as abandoned (older than 2 hours)
        $result = $wpdb->query(
            "UPDATE $table_name 
             SET status = 'abandoned' 
             WHERE status = 'started' 
             AND start_time < DATE_SUB(NOW(), INTERVAL 2 HOUR)"
        );
        
        // Log for monitoring
        if ($result > 0) {
            error_log("E-Learning Quiz: Marked $result quizzes as abandoned");
        }
    }
}