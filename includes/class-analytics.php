<?php
/**
 * Analytics Class
 * 
 * Handles analytics data processing, reporting, and statistics
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_Analytics {
    
    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidget']);
        add_action('wp_ajax_elearning_get_chart_data', [$this, 'getChartData']);
        add_action('wp_ajax_elearning_get_quiz_performance', [$this, 'getQuizPerformance']);
        add_action('init', [$this, 'scheduleAnalyticsCleanup']);
    }
    
    /**
     * Add WordPress dashboard widget
     */
    public function addDashboardWidget(): void {
        if (current_user_can('view_elearning_analytics')) {
            wp_add_dashboard_widget(
                'elearning_dashboard_widget',
                __('E-Learning System Overview', 'elearning-quiz'),
                [$this, 'renderDashboardWidget']
            );
        }
    }
    
    /**
     * Render WordPress dashboard widget
     */
    public function renderDashboardWidget(): void {
        $stats = $this->getOverviewStats();
        ?>
        <div class="elearning-dashboard-widget">
            <div class="widget-stats">
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total Attempts', 'elearning-quiz'); ?></span>
                    <span class="stat-value"><?php echo number_format($stats['total_attempts']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Pass Rate', 'elearning-quiz'); ?></span>
                    <span class="stat-value"><?php echo number_format($stats['pass_rate'], 1); ?>%</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Avg Score', 'elearning-quiz'); ?></span>
                    <span class="stat-value"><?php echo number_format($stats['avg_score'], 1); ?>%</span>
                </div>
            </div>
            <div class="widget-actions">
                <a href="<?php echo admin_url('admin.php?page=elearning-analytics'); ?>" class="button button-small">
                    <?php _e('View Full Analytics', 'elearning-quiz'); ?>
                </a>
            </div>
        </div>
        
        <style>
        .elearning-dashboard-widget .widget-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .elearning-dashboard-widget .stat-item {
            text-align: center;
        }
        .elearning-dashboard-widget .stat-label {
            display: block;
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        .elearning-dashboard-widget .stat-value {
            display: block;
            font-size: 18px;
            font-weight: bold;
            color: #0073aa;
        }
        </style>
        <?php
    }
    
    /**
     * Get overview statistics
     */
    public function getOverviewStats(): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_attempts,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_attempts,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as passed_attempts,
                AVG(CASE WHEN status = 'completed' THEN score END) as avg_score
             FROM $table_name 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            ARRAY_A
        );
        
        if (!$stats) {
            return [
                'total_attempts' => 0,
                'completed_attempts' => 0,
                'passed_attempts' => 0,
                'pass_rate' => 0,
                'avg_score' => 0
            ];
        }
        
        $pass_rate = $stats['completed_attempts'] > 0 
            ? ($stats['passed_attempts'] / $stats['completed_attempts']) * 100 
            : 0;
        
        return [
            'total_attempts' => (int) $stats['total_attempts'],
            'completed_attempts' => (int) $stats['completed_attempts'],
            'passed_attempts' => (int) $stats['passed_attempts'],
            'pass_rate' => $pass_rate,
            'avg_score' => (float) $stats['avg_score'] ?: 0
        ];
    }
    
    /**
     * Get quiz performance data
     */
    public function getQuizPerformance(): void {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'elearning_analytics_nonce')) {
            wp_send_json_error(__('Security check failed', 'elearning-quiz'));
        }
        
        if (!current_user_can('view_elearning_analytics')) {
            wp_send_json_error(__('Access denied', 'elearning-quiz'));
        }
        
        $quiz_id = intval($_GET['quiz_id'] ?? 0);
        
        if (!$quiz_id) {
            wp_send_json_error(__('Invalid quiz ID', 'elearning-quiz'));
        }
        
        $performance_data = $this->getQuizPerformanceData($quiz_id);
        wp_send_json_success($performance_data);
    }
    
    /**
     * Get detailed quiz performance data
     */
    private function getQuizPerformanceData($quiz_id): array {
        global $wpdb;
        
        $attempts_table = $wpdb->prefix . 'elearning_quiz_attempts';
        $answers_table = $wpdb->prefix . 'elearning_quiz_answers';
        
        // Get basic quiz stats
        $basic_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_attempts,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_attempts,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as passed_attempts,
                AVG(CASE WHEN status = 'completed' THEN score END) as avg_score,
                MAX(score) as highest_score,
                MIN(CASE WHEN status = 'completed' THEN score END) as lowest_score,
                COUNT(CASE WHEN language = 'en' THEN 1 END) as english_attempts,
                COUNT(CASE WHEN language = 'gr' THEN 1 END) as greek_attempts
             FROM $attempts_table 
             WHERE quiz_id = %d",
            $quiz_id
        ), ARRAY_A);
        
        // Get daily attempts for the last 30 days
        $daily_attempts = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as attempts,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as passed
             FROM $attempts_table 
             WHERE quiz_id = %d 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $quiz_id
        ), ARRAY_A);
        
        // Get question performance
        $question_performance = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                a.question_index,
                a.question_type,
                COUNT(*) as total_answers,
                COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) as correct_answers,
                (COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) / COUNT(*)) * 100 as success_rate
             FROM $answers_table a
             INNER JOIN $attempts_table att ON a.attempt_id = att.attempt_id
             WHERE att.quiz_id = %d
             GROUP BY a.question_index, a.question_type
             ORDER BY a.question_index ASC",
            $quiz_id
        ), ARRAY_A);
        
        // Get user session retake patterns
        $retake_patterns = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                user_session,
                COUNT(*) as attempts,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as passed_attempts,
                MIN(created_at) as first_attempt,
                MAX(created_at) as last_attempt
             FROM $attempts_table 
             WHERE quiz_id = %d 
             AND status = 'completed'
             GROUP BY user_session
             HAVING COUNT(*) > 1
             ORDER BY attempts DESC
             LIMIT 10",
            $quiz_id
        ), ARRAY_A);
        
        return [
            'basic_stats' => $basic_stats ?: [],
            'daily_attempts' => $daily_attempts ?: [],
            'question_performance' => $question_performance ?: [],
            'retake_patterns' => $retake_patterns ?: []
        ];
    }
    
    /**
     * Get chart data for analytics visualizations
     */
    public function getChartData(): void {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'elearning_analytics_nonce')) {
            wp_send_json_error(__('Security check failed', 'elearning-quiz'));
        }
        
        if (!current_user_can('view_elearning_analytics')) {
            wp_send_json_error(__('Access denied', 'elearning-quiz'));
        }
        
        $chart_type = sanitize_text_field($_GET['chart_type'] ?? '');
        $quiz_id = intval($_GET['quiz_id'] ?? 0);
        
        switch ($chart_type) {
            case 'daily_attempts':
                $data = $this->getDailyAttemptsData($quiz_id);
                break;
            case 'score_distribution':
                $data = $this->getScoreDistributionData($quiz_id);
                break;
            case 'language_breakdown':
                $data = $this->getLanguageBreakdownData($quiz_id);
                break;
            case 'question_difficulty':
                $data = $this->getQuestionDifficultyData($quiz_id);
                break;
            default:
                wp_send_json_error(__('Invalid chart type', 'elearning-quiz'));
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get daily attempts data for charts
     */
    private function getDailyAttemptsData($quiz_id = 0): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        $where_clause = $quiz_id ? "AND quiz_id = $quiz_id" : "";
        
        $results = $wpdb->get_results(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as attempts,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as passed,
                COUNT(CASE WHEN passed = 0 AND status = 'completed' THEN 1 END) as failed
             FROM $table_name 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             $where_clause
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            ARRAY_A
        );
        
        return $results ?: [];
    }
    
    /**
     * Get score distribution data
     */
    private function getScoreDistributionData($quiz_id = 0): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        $where_clause = $quiz_id ? "AND quiz_id = $quiz_id" : "";
        
        $results = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN score >= 90 THEN '90-100%'
                    WHEN score >= 80 THEN '80-89%'
                    WHEN score >= 70 THEN '70-79%'
                    WHEN score >= 60 THEN '60-69%'
                    WHEN score >= 50 THEN '50-59%'
                    ELSE 'Below 50%'
                END as score_range,
                COUNT(*) as count
             FROM $table_name 
             WHERE status = 'completed' AND score IS NOT NULL
             $where_clause
             GROUP BY score_range
             ORDER BY score_range DESC",
            ARRAY_A
        );
        
        return $results ?: [];
    }
    
    /**
     * Get language breakdown data
     */
    private function getLanguageBreakdownData($quiz_id = 0): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        $where_clause = $quiz_id ? "AND quiz_id = $quiz_id" : "";
        
        $results = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN language = 'en' THEN 'English'
                    WHEN language = 'gr' THEN 'Greek'
                    ELSE 'Other'
                END as language_name,
                COUNT(*) as count,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as passed,
                AVG(CASE WHEN status = 'completed' THEN score END) as avg_score
             FROM $table_name 
             WHERE 1=1 $where_clause
             GROUP BY language
             ORDER BY count DESC",
            ARRAY_A
        );
        
        return $results ?: [];
    }
    
    /**
     * Get question difficulty data
     */
    private function getQuestionDifficultyData($quiz_id): array {
        if (!$quiz_id) {
            return [];
        }
        
        global $wpdb;
        
        $attempts_table = $wpdb->prefix . 'elearning_quiz_attempts';
        $answers_table = $wpdb->prefix . 'elearning_quiz_answers';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                a.question_index,
                a.question_type,
                LEFT(a.question_text, 50) as question_preview,
                COUNT(*) as total_answers,
                COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) as correct_answers,
                (COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) / COUNT(*)) * 100 as success_rate
             FROM $answers_table a
             INNER JOIN $attempts_table att ON a.attempt_id = att.attempt_id
             WHERE att.quiz_id = %d
             GROUP BY a.question_index, a.question_type, a.question_text
             ORDER BY success_rate ASC",
            $quiz_id
        ), ARRAY_A);
        
        return $results ?: [];
    }
    
    /**
     * Get most popular quizzes
     */
    public function getMostPopularQuizzes($limit = 10): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                quiz_id,
                COUNT(*) as attempt_count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completion_count,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as pass_count,
                AVG(CASE WHEN status = 'completed' THEN score END) as average_score,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_attempts
             FROM $table_name 
             GROUP BY quiz_id 
             ORDER BY attempt_count DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        // Get quiz titles
        foreach ($results as &$result) {
            $quiz = get_post($result['quiz_id']);
            $result['quiz_title'] = $quiz ? $quiz->post_title : __('Unknown Quiz', 'elearning-quiz');
            $result['quiz_url'] = $quiz ? get_edit_post_link($quiz->ID) : '';
        }
        
        return $results;
    }
    
    /**
     * Get struggling users (multiple failed attempts)
     */
    public function getStrugglingUsers($limit = 20): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                user_session,
                quiz_id,
                COUNT(*) as attempts,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as passed_attempts,
                AVG(score) as avg_score,
                MIN(created_at) as first_attempt,
                MAX(created_at) as last_attempt
             FROM $table_name 
             WHERE status = 'completed'
             GROUP BY user_session, quiz_id
             HAVING attempts >= 3 AND passed_attempts = 0
             ORDER BY attempts DESC, avg_score ASC
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        // Get quiz titles
        foreach ($results as &$result) {
            $quiz = get_post($result['quiz_id']);
            $result['quiz_title'] = $quiz ? $quiz->post_title : __('Unknown Quiz', 'elearning-quiz');
        }
        
        return $results;
    }
    
    /**
     * Get quiz completion trends
     */
    public function getCompletionTrends($days = 30): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as started,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'abandoned' THEN 1 END) as abandoned,
                (COUNT(CASE WHEN status = 'completed' THEN 1 END) / COUNT(*)) * 100 as completion_rate
             FROM $table_name 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $days
        ), ARRAY_A);
        
        return $results ?: [];
    }
    
    /**
     * Generate analytics report
     */
    public function generateAnalyticsReport($quiz_id = null, $start_date = null, $end_date = null): array {
        $report = [
            'generated_at' => current_time('mysql'),
            'quiz_id' => $quiz_id,
            'date_range' => [
                'start' => $start_date,
                'end' => $end_date
            ]
        ];
        
        if ($quiz_id) {
            $report['quiz_performance'] = $this->getQuizPerformanceData($quiz_id);
            $report['quiz_title'] = get_the_title($quiz_id);
        } else {
            $report['global_stats'] = ELearning_Database::getGlobalStatistics();
            $report['popular_quizzes'] = $this->getMostPopularQuizzes(5);
            $report['completion_trends'] = $this->getCompletionTrends();
        }
        
        $report['struggling_users'] = $this->getStrugglingUsers(10);
        
        return $report;
    }
    
    /**
     * Schedule analytics data cleanup
     */
    public function scheduleAnalyticsCleanup(): void {
        if (!wp_next_scheduled('elearning_analytics_cleanup')) {
            wp_schedule_event(time(), 'daily', 'elearning_analytics_cleanup');
        }
        
        add_action('elearning_analytics_cleanup', [$this, 'runAnalyticsCleanup']);
    }
    
    /**
     * Run analytics cleanup
     */
    public function runAnalyticsCleanup(): void {
        $settings = get_option('elearning_quiz_settings', []);
        $retention_days = $settings['data_retention_days'] ?? 365;
        
        if ($retention_days > 0) {
            ELearning_Database::cleanupOldData();
        }
    }
    
    /**
     * Get user progress statistics
     */
    public function getUserProgressStats($user_session): array {
        global $wpdb;
        
        $attempts_table = $wpdb->prefix . 'elearning_quiz_attempts';
        $progress_table = $wpdb->prefix . 'elearning_lesson_progress';
        
        // Get quiz stats for user
        $quiz_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_quizzes_attempted,
                COUNT(CASE WHEN passed = 1 THEN 1 END) as quizzes_passed,
                AVG(score) as average_score,
                COUNT(DISTINCT quiz_id) as unique_quizzes
             FROM $attempts_table 
             WHERE user_session = %s AND status = 'completed'",
            $user_session
        ), ARRAY_A);
        
        // Get lesson progress stats
        $lesson_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT lesson_id) as lessons_started,
                COUNT(CASE WHEN completed = 1 THEN 1 END) as sections_completed,
                COUNT(*) as total_sections_accessed
             FROM $progress_table 
             WHERE user_session = %s",
            $user_session
        ), ARRAY_A);
        
        return [
            'quiz_stats' => $quiz_stats ?: [],
            'lesson_stats' => $lesson_stats ?: []
        ];
    }
    
    /**
     * Track quiz abandonment
     */
    public function trackQuizAbandonment(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        
        // Mark old started quizzes as abandoned (older than 2 hours)
        $wpdb->query(
            "UPDATE $table_name 
             SET status = 'abandoned' 
             WHERE status = 'started' 
             AND start_time < DATE_SUB(NOW(), INTERVAL 2 HOUR)"
        );
    }
}