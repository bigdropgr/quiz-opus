<?php
/**
 * Plugin Name: E-Learning Quiz System for KEPKA
 * Plugin URI: https://BigDrop.gr
 * Description: A comprehensive e-learning system with lessons, quizzes, and analytics for WordPress.
 * Version: 1.0.0
 * Author: BigDrop
 * Author URI: https://bigdrop.gr
 * Text Domain: elearning-quiz
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ELEARNING_QUIZ_VERSION', '1.0.0');
define('ELEARNING_QUIZ_PLUGIN_FILE', __FILE__);
define('ELEARNING_QUIZ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ELEARNING_QUIZ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ELEARNING_QUIZ_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check PHP version
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        echo sprintf(
            esc_html__('E-Learning Quiz System requires PHP 8.0 or higher. You are running PHP %s.', 'elearning-quiz'),
            PHP_VERSION
        );
        echo '</p></div>';
    });
    return;
}

/**
 * Main plugin class
 */
class ELearningQuizSystem {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init(): void {
        // Load plugin dependencies
        $this->loadDependencies();
        
        // Initialize hooks
        $this->initHooks();
        
        // Initialize components
        $this->initComponents();
    }
    
    /**
     * Load plugin dependencies
     */
    private function loadDependencies(): void {
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-database.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-admin.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-ajax.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-user-roles.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-import-export.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-woodmart-integration.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-lesson-widget.php';
        require_once ELEARNING_QUIZ_PLUGIN_DIR . 'includes/class-quiz-widget.php';
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        register_activation_hook(ELEARNING_QUIZ_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(ELEARNING_QUIZ_PLUGIN_FILE, [$this, 'deactivate']);
        register_uninstall_hook(ELEARNING_QUIZ_PLUGIN_FILE, [__CLASS__, 'uninstall']);
        
        add_action('init', [$this, 'loadTextdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }
    
    /**
     * Initialize plugin components
     */
    private function initComponents(): void {
        // Initialize post types
        new ELearning_Post_Types();
        
        // Initialize database
        new ELearning_Database();
        
        // Initialize admin interface
        if (is_admin()) {
            new ELearning_Admin();
        }
        
        // Initialize frontend
        if (!is_admin()) {
            new ELearning_Frontend();
        }
        
        // Initialize AJAX handlers (both frontend and admin)
        new ELearning_Ajax();
        
        // Initialize user roles
        new ELearning_User_Roles();
        
        // Initialize analytics
        new ELearning_Analytics();
        
        // Initialize shortcodes
        new ELearning_Shortcodes();
        
        // Initialize import/export
        new ELearning_Import_Export();
        
        // Schedule cleanup cron job
        if (!wp_next_scheduled('elearning_cleanup_abandoned_quizzes')) {
            wp_schedule_event(time(), 'hourly', 'elearning_cleanup_abandoned_quizzes');
        }
        
        add_action('elearning_cleanup_abandoned_quizzes', [ELearning_Database::class, 'trackQuizAbandonment']);
    }
    
    /**
     * Plugin activation
     */
    public function activate(): void {
        // Create database tables
        ELearning_Database::createTables();
        
        // Add user roles and capabilities
        ELearning_User_Roles::addRoles();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set plugin version
        update_option('elearning_quiz_version', ELEARNING_QUIZ_VERSION);
        
        // Set default settings
        $this->setDefaultSettings();
    }
    
    /**
     * Set default plugin settings
     */
    private function setDefaultSettings(): void {
        $default_settings = [
            'data_retention_days' => 365,
            'enable_progress_tracking' => true,
            'default_passing_score' => 70,
            'enable_quiz_retakes' => true,
            'questions_per_quiz' => 10,
            'show_correct_answers' => true,
            'cookie_consent_integration' => false
        ];
        
        add_option('elearning_quiz_settings', $default_settings);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate(): void {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall(): void {
        // Remove database tables
        ELearning_Database::dropTables();
        
        // Remove user roles
        ELearning_User_Roles::removeRoles();
        
        // Remove plugin options
        delete_option('elearning_quiz_version');
        delete_option('elearning_quiz_settings');
        
        // Clean up any transients
        delete_transient('elearning_quiz_cache');
    }
    
    /**
     * Load plugin textdomain
     */
    public function loadTextdomain(): void {
        load_plugin_textdomain(
            'elearning-quiz',
            false,
            dirname(ELEARNING_QUIZ_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueueScripts(): void {
        // Only enqueue on relevant pages
        if (!is_singular(['elearning_lesson', 'elearning_quiz']) && !has_shortcode(get_post()->post_content ?? '', 'loan_calculator')) {
            return;
        }
        
        $css_version = $this->getFileVersion('assets/css/frontend.css');
        $js_version = $this->getFileVersion('assets/js/frontend.js');
        
        // Check if WoodMart is active
        $theme = wp_get_theme();
        $is_woodmart = $theme->get('Name') === 'WoodMart' || $theme->get('Template') === 'woodmart';
        
        if ($is_woodmart) {
            // Use the WoodMart integrated CSS file
            wp_enqueue_style(
                'elearning-quiz-frontend',
                ELEARNING_QUIZ_PLUGIN_URL . 'assets/css/woodmart-integrated-frontend.css',
                ['woodmart-style'], // Make it dependent on WoodMart's main style
                $css_version
            );
        } else {
            // Use the standard CSS file for other themes
            wp_enqueue_style(
                'elearning-quiz-frontend',
                ELEARNING_QUIZ_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                $css_version
            );
        }
        
        // Main frontend script with dependencies for drag & drop
        wp_enqueue_script(
            'elearning-quiz-frontend',
            ELEARNING_QUIZ_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'],
            $js_version,
            true
        );
        
        // Localize script for AJAX and strings - ENHANCED VERSION
        wp_localize_script('elearning-quiz-frontend', 'elearningQuiz', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('elearning_quiz_nonce'),
            'strings' => [
                'loading' => __('Loading...', 'elearning-quiz'),
                'error' => __('An error occurred. Please try again.', 'elearning-quiz'),
                'confirm_submit' => __('Are you sure you want to submit your answers?', 'elearning-quiz'),
                'congratulations' => __('Congratulations!', 'elearning-quiz'),
                'quiz_passed' => __('You have successfully passed this quiz.', 'elearning-quiz'),
                'try_again' => __('Try Again', 'elearning-quiz'),
                'quiz_failed' => __('You did not pass this quiz. Please review the material and try again.', 'elearning-quiz'),
                'correct_answers' => __('Correct Answers', 'elearning-quiz'),
                'passing_score' => __('Passing Score', 'elearning-quiz'),
                'retry_quiz' => __('Retry Quiz', 'elearning-quiz'),
                'next_section' => __('Next Section', 'elearning-quiz'),
                'previous_section' => __('Previous Section', 'elearning-quiz'),
                'mark_complete' => __('Mark Section Complete', 'elearning-quiz'),
                'section_completed' => __('Section completed!', 'elearning-quiz'),
                'time_remaining' => __('Time Remaining', 'elearning-quiz'),
                'time_up' => __('Time\'s Up!', 'elearning-quiz'),
                'submitting_quiz' => __('Your quiz is being submitted...', 'elearning-quiz'),
                'one_minute_warning' => __('One minute remaining', 'elearning-quiz'),
                'unanswered_questions' => __('You have unanswered questions', 'elearning-quiz'),
                'submit_anyway' => __('Submit anyway?', 'elearning-quiz'),
                'your_answer' => __('Your Answer', 'elearning-quiz'),
                'correct_answer' => __('Correct Answer', 'elearning-quiz'),
                'no_answer' => __('No answer provided', 'elearning-quiz'),
                'review_answers' => __('Review Your Answers', 'elearning-quiz'),
                'perfect_score' => __('Congratulations, you had 100% success!', 'elearning-quiz'),
                'congratulations_score' => __('Congratulations, you had %s% success!', 'elearning-quiz'),
                'sorry_failed' => __('Sorry, you had %s% success!', 'elearning-quiz'),
                'wrong_answers_count' => __('You had %s mistakes', 'elearning-quiz'),
                'your_answer_was' => __('Your answer was', 'elearning-quiz'),
                'correct_answer_is' => __('The correct answer is', 'elearning-quiz'),
                'question' => __('Question', 'elearning-quiz'),
                'drop_here' => __('Drop here', 'elearning-quiz'),
                'leave_warning' => __('You have unsaved progress. Are you sure you want to leave?', 'elearning-quiz'),
                'skip_to_quiz' => __('Skip to quiz content', 'elearning-quiz'),
            ]
        ]);
    }
    
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueueAdminScripts($hook): void {
        // Only load on our plugin pages and post edit screens
        $allowed_hooks = ['post.php', 'post-new.php', 'edit.php'];
        $allowed_post_types = ['elearning_lesson', 'elearning_quiz'];
        
        if (!in_array($hook, $allowed_hooks) && 
            strpos($hook, 'elearning-quiz') === false) {
            return;
        }
        
        // Check if we're editing our custom post types
        if (in_array($hook, ['post.php', 'post-new.php', 'edit.php'])) {
            $post_type = get_post_type();
            if (!in_array($post_type, $allowed_post_types)) {
                return;
            }
        }
        
        $admin_css_version = $this->getFileVersion('assets/css/admin.css');
        $admin_js_version = $this->getFileVersion('assets/js/admin.js');
        
        // Admin stylesheet
        wp_enqueue_style(
            'elearning-quiz-admin',
            ELEARNING_QUIZ_PLUGIN_URL . 'assets/css/admin.css',
            [],
            $admin_css_version
        );
        
        // Admin script
        wp_enqueue_script(
            'elearning-quiz-admin',
            ELEARNING_QUIZ_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable'],
            $admin_js_version,
            true
        );
        
        // Localize admin script
        wp_localize_script('elearning-quiz-admin', 'elearningQuizAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('elearning_quiz_admin_nonce'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this item?', 'elearning-quiz'),
                'add_question' => __('Add Question', 'elearning-quiz'),
                'add_option' => __('Add Option', 'elearning-quiz'),
                'add_word' => __('Add Word', 'elearning-quiz'),
                'remove' => __('Remove', 'elearning-quiz'),
                'option_text' => __('Option text', 'elearning-quiz'),
                'correct' => __('Correct', 'elearning-quiz'),
                'word' => __('Word', 'elearning-quiz'),
                'section' => __('Section', 'elearning-quiz'),
                'question' => __('Question', 'elearning-quiz'),
                'add_left_item' => __('Add Left Item', 'elearning-quiz'),
                'add_right_item' => __('Add Right Item', 'elearning-quiz'),
                'add_match' => __('Add Match', 'elearning-quiz'),
                'select_left' => __('Select left item', 'elearning-quiz'),
                'select_right' => __('Select right item', 'elearning-quiz'),
                'matches_with' => __('matches with', 'elearning-quiz'),
                'left_column' => __('Left Column', 'elearning-quiz'),
                'right_column' => __('Right Column', 'elearning-quiz'),
                'left_item' => __('Left item', 'elearning-quiz'),
                'right_item' => __('Right item', 'elearning-quiz'),
                'options' => __('Options', 'elearning-quiz'),
                'text_with_blanks' => __('Text with Blanks', 'elearning-quiz'),
                'blank_instruction' => __('Use {{blank}} to mark where blanks should appear.', 'elearning-quiz'),
                'word_bank' => __('Word Bank', 'elearning-quiz'),
                'correct_answer' => __('Correct Answer', 'elearning-quiz'),
                'true_option' => __('True', 'elearning-quiz'),
                'false_option' => __('False', 'elearning-quiz'),
                'correct_matches' => __('Correct Matches', 'elearning-quiz'),
            ]
        ]);
    }
    
    /**
     * Get file version for cache busting
     */
    private function getFileVersion(string $file_path): string {
        $full_path = ELEARNING_QUIZ_PLUGIN_DIR . $file_path;
        
        if (file_exists($full_path)) {
            return ELEARNING_QUIZ_VERSION . '-' . filemtime($full_path);
        }
        
        return ELEARNING_QUIZ_VERSION;
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    ELearningQuizSystem::getInstance();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    ELearningQuizSystem::getInstance()->activate();
});