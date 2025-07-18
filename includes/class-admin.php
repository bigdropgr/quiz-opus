<?php
/**
 * Admin Class
 * 
 * Handles the admin interface, dashboard, analytics, and settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_notices', [$this, 'showAdminNotices']);
        add_action('wp_ajax_elearning_export_quiz_data', [$this, 'handleQuizDataExport']);
        add_action('wp_ajax_elearning_cleanup_old_data', [$this, 'handleDataCleanup']);
        add_filter('post_row_actions', [$this, 'addQuizPreviewAction'], 10, 2);
        add_filter('post_row_actions', [$this, 'addLessonPreviewAction'], 10, 2);
    }
    
    /**
     * Add admin menus
     */
    public function addAdminMenus(): void {
        // Main menu page
        add_menu_page(
            __('E-Learning System', 'elearning-quiz'),
            __('E-Learning', 'elearning-quiz'),
            'view_elearning_analytics',
            'elearning-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-welcome-learn-more',
            30
        );
        
        // Dashboard submenu (same as main page)
        add_submenu_page(
            'elearning-dashboard',
            __('Dashboard', 'elearning-quiz'),
            __('Dashboard', 'elearning-quiz'),
            'view_elearning_analytics',
            'elearning-dashboard',
            [$this, 'renderDashboard']
        );
        
        // Analytics submenu
        add_submenu_page(
            'elearning-dashboard',
            __('Analytics', 'elearning-quiz'),
            __('Analytics', 'elearning-quiz'),
            'view_elearning_analytics',
            'elearning-analytics',
            [$this, 'renderAnalytics']
        );
        
        // Settings submenu (admin only)
        add_submenu_page(
            'elearning-dashboard',
            __('Settings', 'elearning-quiz'),
            __('Settings', 'elearning-quiz'),
            'manage_elearning_settings',
            'elearning-settings',
            [$this, 'renderSettings']
        );
        
        // Import/Export submenu
        add_submenu_page(
            'elearning-dashboard',
            __('Import/Export', 'elearning-quiz'),
            __('Import/Export', 'elearning-quiz'),
            'export_elearning_data',
            'elearning-import-export',
            [$this, 'renderImportExport']
        );
    }
    
    /**
     * Register plugin settings
     */
    public function registerSettings(): void {
        register_setting('elearning_quiz_settings', 'elearning_quiz_settings', [
            'sanitize_callback' => [$this, 'sanitizeSettings']
        ]);
        
        // General Settings Section
        add_settings_section(
            'elearning_general_settings',
            __('General Settings', 'elearning-quiz'),
            [$this, 'renderGeneralSettingsSection'],
            'elearning_quiz_settings'
        );
        
        // Data Retention Field
        add_settings_field(
            'data_retention_days',
            __('Data Retention (Days)', 'elearning-quiz'),
            [$this, 'renderDataRetentionField'],
            'elearning_quiz_settings',
            'elearning_general_settings'
        );
        
        // Default Passing Score Field
        add_settings_field(
            'default_passing_score',
            __('Default Passing Score (%)', 'elearning-quiz'),
            [$this, 'renderDefaultPassingScoreField'],
            'elearning_quiz_settings',
            'elearning_general_settings'
        );
        
        // Questions Per Quiz Field
        add_settings_field(
            'questions_per_quiz',
            __('Default Questions Per Quiz', 'elearning-quiz'),
            [$this, 'renderQuestionsPerQuizField'],
            'elearning_quiz_settings',
            'elearning_general_settings'
        );
        
        // Show Correct Answers Field
        add_settings_field(
            'show_correct_answers',
            __('Show Correct Answers', 'elearning-quiz'),
            [$this, 'renderShowCorrectAnswersField'],
            'elearning_quiz_settings',
            'elearning_general_settings'
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitizeSettings($settings): array {
        $sanitized = [];
        
        $sanitized['data_retention_days'] = absint($settings['data_retention_days'] ?? 365);
        $sanitized['default_passing_score'] = max(0, min(100, absint($settings['default_passing_score'] ?? 70)));
        $sanitized['questions_per_quiz'] = max(1, absint($settings['questions_per_quiz'] ?? 10));
        $sanitized['show_correct_answers'] = !empty($settings['show_correct_answers']);
        $sanitized['enable_progress_tracking'] = !empty($settings['enable_progress_tracking']);
        $sanitized['enable_quiz_retakes'] = !empty($settings['enable_quiz_retakes']);
        
        return $sanitized;
    }
    
    /**
     * Render dashboard page
     */
    public function renderDashboard(): void {
        $global_stats = ELearning_Database::getGlobalStatistics();
        ?>
        <div class="wrap">
            <h1><?php _e('E-Learning System Dashboard', 'elearning-quiz'); ?></h1>
            
            <div class="elearning-dashboard-widgets">
                <!-- Overview Stats -->
                <div class="elearning-stat-cards">
                    <div class="stat-card">
                        <h3><?php _e('Total Quiz Attempts', 'elearning-quiz'); ?></h3>
                        <div class="stat-number"><?php echo number_format($global_stats['total_quiz_attempts'] ?? 0); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php _e('Completed Quizzes', 'elearning-quiz'); ?></h3>
                        <div class="stat-number"><?php echo number_format($global_stats['completed_quiz_attempts'] ?? 0); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php _e('Passed Quizzes', 'elearning-quiz'); ?></h3>
                        <div class="stat-number"><?php echo number_format($global_stats['passed_quiz_attempts'] ?? 0); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php _e('Unique Users', 'elearning-quiz'); ?></h3>
                        <div class="stat-number"><?php echo number_format($global_stats['unique_users'] ?? 0); ?></div>
                    </div>
                </div>
                
                <!-- Language Stats -->
                <div class="elearning-language-stats">
                    <h2><?php _e('Usage by Language', 'elearning-quiz'); ?></h2>
                    <div class="language-stat-row">
                        <div class="language-stat">
                            <span class="language-label"><?php _e('English', 'elearning-quiz'); ?>:</span>
                            <span class="language-count"><?php echo number_format($global_stats['english_total'] ?? 0); ?></span>
                        </div>
                        <div class="language-stat">
                            <span class="language-label"><?php _e('Greek', 'elearning-quiz'); ?>:</span>
                            <span class="language-count"><?php echo number_format($global_stats['greek_total'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Average Score -->
                <?php if (!empty($global_stats['global_average_score'])): ?>
                <div class="elearning-average-score">
                    <h2><?php _e('Global Average Score', 'elearning-quiz'); ?></h2>
                    <div class="average-score-display">
                        <?php echo number_format($global_stats['global_average_score'], 1); ?>%
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <div class="elearning-quick-actions">
                    <h2><?php _e('Quick Actions', 'elearning-quiz'); ?></h2>
                    <div class="quick-action-buttons">
                        <a href="<?php echo admin_url('post-new.php?post_type=elearning_lesson'); ?>" class="button button-primary">
                            <?php _e('Create New Lesson', 'elearning-quiz'); ?>
                        </a>
                        <a href="<?php echo admin_url('post-new.php?post_type=elearning_quiz'); ?>" class="button button-primary">
                            <?php _e('Create New Quiz', 'elearning-quiz'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=elearning-analytics'); ?>" class="button">
                            <?php _e('View Analytics', 'elearning-quiz'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=elearning-import-export'); ?>" class="button">
                            <?php _e('Import/Export', 'elearning-quiz'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .elearning-dashboard-widgets {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        
        .elearning-stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .elearning-language-stats,
        .elearning-average-score,
        .elearning-quick-actions {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .language-stat-row {
            display: flex;
            gap: 40px;
        }
        
        .language-stat {
            font-size: 16px;
        }
        
        .language-count {
            font-weight: bold;
            color: #0073aa;
        }
        
        .average-score-display {
            font-size: 48px;
            font-weight: bold;
            color: #0073aa;
            text-align: center;
            margin-top: 10px;
        }
        
        .quick-action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        </style>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function renderAnalytics(): void {
        // Get all quizzes for the dropdown
        $quizzes = get_posts([
            'post_type' => 'elearning_quiz',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $selected_quiz = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
        $selected_quiz_stats = [];
        
        if ($selected_quiz) {
            $selected_quiz_stats = ELearning_Database::getQuizStatistics($selected_quiz);
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics', 'elearning-quiz'); ?></h1>
            
            <!-- Quiz Selection -->
            <div class="elearning-analytics-filter">
                <form method="get" action="">
                    <input type="hidden" name="page" value="elearning-analytics">
                    <label for="quiz_id"><?php _e('Select Quiz:', 'elearning-quiz'); ?></label>
                    <select name="quiz_id" id="quiz_id" onchange="this.form.submit()">
                        <option value=""><?php _e('All Quizzes', 'elearning-quiz'); ?></option>
                        <?php foreach ($quizzes as $quiz): ?>
                            <option value="<?php echo $quiz->ID; ?>" <?php selected($selected_quiz, $quiz->ID); ?>>
                                <?php echo esc_html($quiz->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <?php if ($selected_quiz && !empty($selected_quiz_stats)): ?>
                <!-- Quiz-specific analytics -->
                <div class="elearning-quiz-analytics">
                    <h2><?php echo esc_html(get_the_title($selected_quiz)); ?> - <?php _e('Analytics', 'elearning-quiz'); ?></h2>
                    
                    <div class="elearning-stat-cards">
                        <div class="stat-card">
                            <h3><?php _e('Total Attempts', 'elearning-quiz'); ?></h3>
                            <div class="stat-number"><?php echo number_format($selected_quiz_stats['total_attempts']); ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3><?php _e('Completed', 'elearning-quiz'); ?></h3>
                            <div class="stat-number"><?php echo number_format($selected_quiz_stats['completed_attempts']); ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3><?php _e('Passed', 'elearning-quiz'); ?></h3>
                            <div class="stat-number"><?php echo number_format($selected_quiz_stats['passed_attempts']); ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3><?php _e('Failed', 'elearning-quiz'); ?></h3>
                            <div class="stat-number"><?php echo number_format($selected_quiz_stats['failed_attempts']); ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3><?php _e('Average Score', 'elearning-quiz'); ?></h3>
                            <div class="stat-number"><?php echo number_format($selected_quiz_stats['average_score'], 1); ?>%</div>
                        </div>
                        
                        <div class="stat-card">
                            <h3><?php _e('Highest Score', 'elearning-quiz'); ?></h3>
                            <div class="stat-number"><?php echo number_format($selected_quiz_stats['highest_score'], 1); ?>%</div>
                        </div>
                    </div>
                    
                    <!-- Language breakdown -->
                    <div class="elearning-language-breakdown">
                        <h3><?php _e('Language Breakdown', 'elearning-quiz'); ?></h3>
                        <div class="language-stat-row">
                            <div class="language-stat">
                                <span class="language-label"><?php _e('English Attempts', 'elearning-quiz'); ?>:</span>
                                <span class="language-count"><?php echo number_format($selected_quiz_stats['english_attempts']); ?></span>
                            </div>
                            <div class="language-stat">
                                <span class="language-label"><?php _e('Greek Attempts', 'elearning-quiz'); ?>:</span>
                                <span class="language-count"><?php echo number_format($selected_quiz_stats['greek_attempts']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Button -->
                    <div class="elearning-export-section">
                        <h3><?php _e('Export Data', 'elearning-quiz'); ?></h3>
                        <button type="button" class="button button-primary" onclick="exportQuizData(<?php echo $selected_quiz; ?>)">
                            <?php _e('Export Quiz Data to CSV', 'elearning-quiz'); ?>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><?php _e('Select a quiz to view detailed analytics.', 'elearning-quiz'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        function exportQuizData(quizId) {
            const url = ajaxurl + '?action=elearning_export_quiz_data&quiz_id=' + quizId + '&nonce=' + '<?php echo wp_create_nonce('elearning_export_nonce'); ?>';
            window.open(url, '_blank');
        }
        </script>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function renderSettings(): void {
        ?>
        <div class="wrap">
            <h1><?php _e('E-Learning Settings', 'elearning-quiz'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('elearning_quiz_settings');
                do_settings_sections('elearning_quiz_settings');
                submit_button();
                ?>
            </form>
            
            <!-- Data Cleanup Section -->
            <div class="elearning-data-cleanup">
                <h2><?php _e('Data Management', 'elearning-quiz'); ?></h2>
                <p><?php _e('Clean up old quiz and lesson data based on your retention settings.', 'elearning-quiz'); ?></p>
                <button type="button" class="button button-secondary" onclick="cleanupOldData()">
                    <?php _e('Cleanup Old Data Now', 'elearning-quiz'); ?>
                </button>
                <div id="cleanup-result" style="margin-top: 10px;"></div>
            </div>
        </div>
        
        <script>
        function cleanupOldData() {
            if (!confirm('<?php _e('Are you sure you want to cleanup old data? This action cannot be undone.', 'elearning-quiz'); ?>')) {
                return;
            }
            
            const button = event.target;
            const resultDiv = document.getElementById('cleanup-result');
            
            button.disabled = true;
            button.textContent = '<?php _e('Cleaning up...', 'elearning-quiz'); ?>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=elearning_cleanup_old_data&nonce=<?php echo wp_create_nonce('elearning_cleanup_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="notice notice-success"><p>' + data.data.message + '</p></div>';
                } else {
                    resultDiv.innerHTML = '<div class="notice notice-error"><p>' + data.data + '</p></div>';
                }
                button.disabled = false;
                button.textContent = '<?php _e('Cleanup Old Data Now', 'elearning-quiz'); ?>';
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="notice notice-error"><p><?php _e('An error occurred during cleanup.', 'elearning-quiz'); ?></p></div>';
                button.disabled = false;
                button.textContent = '<?php _e('Cleanup Old Data Now', 'elearning-quiz'); ?>';
            });
        }
        </script>
        <?php
    }
    
    /**
     * Render import/export page
     */
    public function renderImportExport(): void {
        ?>
        <div class="wrap">
            <h1><?php _e('Import/Export', 'elearning-quiz'); ?></h1>
            
            <div class="elearning-import-export-sections">
                <!-- Export Section -->
                <div class="export-section">
                    <h2><?php _e('Export Quiz Data', 'elearning-quiz'); ?></h2>
                    <p><?php _e('Export quiz attempts and results to CSV format for analysis.', 'elearning-quiz'); ?></p>
                    
                    <div class="export-form">
                        <label for="export-quiz-select"><?php _e('Select Quiz:', 'elearning-quiz'); ?></label>
                        <select id="export-quiz-select">
                            <option value=""><?php _e('Select a quiz...', 'elearning-quiz'); ?></option>
                            <?php
                            $quizzes = get_posts([
                                'post_type' => 'elearning_quiz',
                                'posts_per_page' => -1,
                                'post_status' => 'publish',
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ]);
                            foreach ($quizzes as $quiz):
                            ?>
                                <option value="<?php echo $quiz->ID; ?>"><?php echo esc_html($quiz->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div class="export-options">
                            <label>
                                <input type="radio" name="export_type" value="attempts" checked>
                                <?php _e('Export Quiz Attempts', 'elearning-quiz'); ?>
                            </label>
                            <label>
                                <input type="radio" name="export_type" value="questions">
                                <?php _e('Export Quiz Questions', 'elearning-quiz'); ?>
                            </label>
                        </div>
                        
                        <button type="button" class="button button-primary" onclick="exportSelectedQuiz()">
                            <?php _e('Export to CSV', 'elearning-quiz'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Import Section -->
                <div class="import-section">
                    <h2><?php _e('Import Quiz Questions', 'elearning-quiz'); ?></h2>
                    <p><?php _e('Import quiz questions from CSV format.', 'elearning-quiz'); ?></p>
                    
                    <div class="import-info">
                        <h4><?php _e('CSV Format Requirements:', 'elearning-quiz'); ?></h4>
                        <ul>
                            <li><?php _e('Column headers (required): Type, Question', 'elearning-quiz'); ?></li>
                            <li><?php _e('Multiple Choice: Option 1-5, Correct Answer(s)', 'elearning-quiz'); ?></li>
                            <li><?php _e('True/False: Correct Answer (true/false)', 'elearning-quiz'); ?></li>
                            <li><?php _e('Fill in Blanks: Text with Blanks (use {{blank}}), Word Bank', 'elearning-quiz'); ?></li>
                            <li><?php _e('Matching: Left 1-5, Right 1-5, Matches (format: 1-2,2-1)', 'elearning-quiz'); ?></li>
                        </ul>
                        
                        <a href="<?php echo ELEARNING_QUIZ_PLUGIN_URL; ?>templates/quiz-import-template.csv" download class="button button-secondary">
                            <?php _e('Download Sample CSV', 'elearning-quiz'); ?>
                        </a>
                    </div>
                    
                    <div class="import-form">
                        <p><?php _e('To import questions, edit a quiz and use the Import Questions button.', 'elearning-quiz'); ?></p>
                        <a href="<?php echo admin_url('edit.php?post_type=elearning_quiz'); ?>" class="button">
                            <?php _e('Go to Quizzes', 'elearning-quiz'); ?>
                        </a>
                    </div>
                </div>
                
                <!-- Bulk Operations Section -->
                <div class="bulk-section">
                    <h2><?php _e('Bulk Operations', 'elearning-quiz'); ?></h2>
                    
                    <div class="bulk-export">
                        <h3><?php _e('Export All Data', 'elearning-quiz'); ?></h3>
                        <p><?php _e('Export all quiz attempts from all quizzes.', 'elearning-quiz'); ?></p>
                        <button type="button" class="button" onclick="exportAllData()">
                            <?php _e('Export All Attempts', 'elearning-quiz'); ?>
                        </button>
                    </div>
                    
                    <div class="bulk-cleanup">
                        <h3><?php _e('Data Cleanup', 'elearning-quiz'); ?></h3>
                        <p><?php _e('Remove abandoned quiz attempts older than 7 days.', 'elearning-quiz'); ?></p>
                        <button type="button" class="button" onclick="cleanupAbandonedAttempts()">
                            <?php _e('Cleanup Abandoned Attempts', 'elearning-quiz'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function exportSelectedQuiz() {
            const select = document.getElementById('export-quiz-select');
            const quizId = select.value;
            const exportType = document.querySelector('input[name="export_type"]:checked').value;
            
            if (!quizId) {
                alert('<?php _e('Please select a quiz to export.', 'elearning-quiz'); ?>');
                return;
            }
            
            let url;
            if (exportType === 'questions') {
                url = ajaxurl + '?action=elearning_export_questions&quiz_id=' + quizId + '&nonce=' + '<?php echo wp_create_nonce('elearning_export_nonce'); ?>';
            } else {
                url = ajaxurl + '?action=elearning_export_quiz_data&quiz_id=' + quizId + '&nonce=' + '<?php echo wp_create_nonce('elearning_export_nonce'); ?>';
            }
            
            window.open(url, '_blank');
        }
        
        function exportAllData() {
            if (!confirm('<?php _e('This will export all quiz attempts. Continue?', 'elearning-quiz'); ?>')) {
                return;
            }
            
            const url = ajaxurl + '?action=elearning_export_quiz_data&export_all=1&nonce=' + '<?php echo wp_create_nonce('elearning_export_nonce'); ?>';
            window.open(url, '_blank');
        }
        
        function cleanupAbandonedAttempts() {
            if (!confirm('<?php _e('This will remove all abandoned attempts older than 7 days. Continue?', 'elearning-quiz'); ?>')) {
                return;
            }
            
            jQuery.post(ajaxurl, {
                action: 'elearning_cleanup_abandoned',
                nonce: '<?php echo wp_create_nonce('elearning_cleanup_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data || '<?php _e('Cleanup failed', 'elearning-quiz'); ?>');
                }
            });
        }
        </script>
        
        <style>
        .elearning-import-export-sections {
            display: grid;
            gap: 30px;
            margin-top: 20px;
        }
        
        .export-section,
        .import-section,
        .bulk-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .export-form,
        .import-form {
            margin-top: 15px;
        }
        
        .export-form select,
        .import-form input[type="file"] {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .export-options {
            margin: 15px 0;
        }
        
        .export-options label {
            display: block;
            margin-bottom: 5px;
        }
        
        .import-info {
            background: #f0f0f1;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        
        .import-info h4 {
            margin-top: 0;
        }
        
        .import-info ul {
            margin-bottom: 15px;
        }
        
        .bulk-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .bulk-export,
        .bulk-cleanup {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .bulk-export h3,
        .bulk-cleanup h3 {
            margin-top: 0;
        }
        </style>
        <?php
    }
    
    /**
     * Render settings sections and fields
     */
    public function renderGeneralSettingsSection(): void {
        echo '<p>' . __('Configure general settings for the e-learning system.', 'elearning-quiz') . '</p>';
    }
    
    public function renderDataRetentionField(): void {
        $settings = get_option('elearning_quiz_settings', []);
        $value = $settings['data_retention_days'] ?? 365;
        ?>
        <input type="number" name="elearning_quiz_settings[data_retention_days]" value="<?php echo esc_attr($value); ?>" min="0" />
        <p class="description"><?php _e('Number of days to keep quiz and lesson data. Set to 0 to keep data indefinitely.', 'elearning-quiz'); ?></p>
        <?php
    }
    
    public function renderDefaultPassingScoreField(): void {
        $settings = get_option('elearning_quiz_settings', []);
        $value = $settings['default_passing_score'] ?? 70;
        ?>
        <input type="number" name="elearning_quiz_settings[default_passing_score]" value="<?php echo esc_attr($value); ?>" min="0" max="100" />
        <p class="description"><?php _e('Default passing score percentage for new quizzes.', 'elearning-quiz'); ?></p>
        <?php
    }
    
    public function renderQuestionsPerQuizField(): void {
        $settings = get_option('elearning_quiz_settings', []);
        $value = $settings['questions_per_quiz'] ?? 10;
        ?>
        <input type="number" name="elearning_quiz_settings[questions_per_quiz]" value="<?php echo esc_attr($value); ?>" min="1" />
        <p class="description"><?php _e('Default number of questions to show per quiz.', 'elearning-quiz'); ?></p>
        <?php
    }
    
    public function renderShowCorrectAnswersField(): void {
        $settings = get_option('elearning_quiz_settings', []);
        $value = !empty($settings['show_correct_answers']);
        ?>
        <label>
            <input type="checkbox" name="elearning_quiz_settings[show_correct_answers]" value="1" <?php checked($value); ?> />
            <?php _e('Show correct answers after quiz completion', 'elearning-quiz'); ?>
        </label>
        <?php
    }
    
    /**
     * Handle quiz data export
     */
    public function handleQuizDataExport(): void {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'elearning_export_nonce')) {
            wp_die(__('Security check failed', 'elearning-quiz'));
        }
        
        if (!current_user_can('export_elearning_data')) {
            wp_die(__('You do not have permission to export data', 'elearning-quiz'));
        }
        
        $quiz_id = intval($_GET['quiz_id'] ?? 0);
        if (!$quiz_id) {
            wp_die(__('Invalid quiz ID', 'elearning-quiz'));
        }
        
        $csv_data = ELearning_Database::exportQuizData($quiz_id);
        
        if (empty($csv_data)) {
            wp_die(__('No data found for this quiz', 'elearning-quiz'));
        }
        
        $quiz_title = get_the_title($quiz_id);
        $filename = sanitize_file_name($quiz_title . '_export_' . date('Y-m-d') . '.csv');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $csv_data;
        exit;
    }
    
    /**
     * Handle data cleanup
     */
    public function handleDataCleanup(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'elearning_cleanup_nonce')) {
            wp_send_json_error(__('Security check failed', 'elearning-quiz'));
        }
        
        if (!current_user_can('manage_elearning_settings')) {
            wp_send_json_error(__('You do not have permission to cleanup data', 'elearning-quiz'));
        }
        
        $deleted_count = ELearning_Database::cleanupOldData();
        
        wp_send_json_success([
            'message' => sprintf(__('Successfully cleaned up %d old records.', 'elearning-quiz'), $deleted_count)
        ]);
    }
    
    /**
     * Add quiz preview action
     */
    public function addQuizPreviewAction($actions, $post): array {
        if ($post->post_type === 'elearning_quiz' && $post->post_status === 'publish') {
            $preview_url = get_permalink($post->ID);
            $actions['quiz_preview'] = '<a href="' . esc_url($preview_url) . '" target="_blank">' . __('Preview Quiz', 'elearning-quiz') . '</a>';
        }
        return $actions;
    }
    
    /**
     * Add lesson preview action
     */
    public function addLessonPreviewAction($actions, $post): array {
        if ($post->post_type === 'elearning_lesson' && $post->post_status === 'publish') {
            $preview_url = get_permalink($post->ID);
            $actions['lesson_preview'] = '<a href="' . esc_url($preview_url) . '" target="_blank">' . __('Preview Lesson', 'elearning-quiz') . '</a>';
        }
        return $actions;
    }
    
    /**
     * Show admin notices
     */
    public function showAdminNotices(): void {
        // Check if database tables exist
        global $wpdb;
        $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            echo '<div class="notice notice-error"><p>';
            echo __('E-Learning Quiz System: Database tables are missing. Please deactivate and reactivate the plugin.', 'elearning-quiz');
            echo '</p></div>';
        }
    }
}