<?php
/**
 * Translations Class
 * 
 * Centralizes all translatable strings to avoid duplicates
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_Translations {
    
    private static $strings = null;
    
    /**
     * Get all translatable strings
     */
    public static function getStrings() {
        if (self::$strings === null) {
            self::$strings = [
                // Common Actions
                'start_quiz' => __('Start Quiz', 'elearning-quiz'),
                'retake_quiz' => __('Retake Quiz', 'elearning-quiz'),
                'retry_quiz' => __('Retry Quiz', 'elearning-quiz'),
                'submit_quiz' => __('Submit Quiz', 'elearning-quiz'),
                'take_quiz' => __('Take Quiz', 'elearning-quiz'),
                'view_quiz' => __('View Quiz', 'elearning-quiz'),
                'preview_quiz' => __('Preview Quiz', 'elearning-quiz'),
                
                // Lesson Actions
                'start_lesson' => __('Start Lesson', 'elearning-quiz'),
                'review_lesson' => __('Review Lesson', 'elearning-quiz'),
                'view_lesson' => __('View Lesson', 'elearning-quiz'),
                'go_to_lesson' => __('Go to Lesson', 'elearning-quiz'),
                'preview_lesson' => __('Preview Lesson', 'elearning-quiz'),
                
                // Common UI Elements
                'loading' => __('Loading...', 'elearning-quiz'),
                'error' => __('An error occurred. Please try again.', 'elearning-quiz'),
                'save' => __('Save', 'elearning-quiz'),
                'cancel' => __('Cancel', 'elearning-quiz'),
                'close' => __('Close', 'elearning-quiz'),
                'next' => __('Next', 'elearning-quiz'),
                'previous' => __('Previous', 'elearning-quiz'),
                'submit' => __('Submit', 'elearning-quiz'),
                'confirm' => __('Confirm', 'elearning-quiz'),
                
                // Quiz Specific
                'questions' => __('Questions', 'elearning-quiz'),
                'question' => __('Question', 'elearning-quiz'),
                'passing_score' => __('Passing Score', 'elearning-quiz'),
                'time_limit' => __('Time Limit', 'elearning-quiz'),
                'none' => __('None', 'elearning-quiz'),
                'congratulations' => __('Congratulations!', 'elearning-quiz'),
                'quiz_passed' => __('You have successfully passed this quiz.', 'elearning-quiz'),
                'quiz_failed' => __('You did not pass this quiz. Please review the material and try again.', 'elearning-quiz'),
                'try_again' => __('Try Again', 'elearning-quiz'),
                'correct_answers' => __('Correct Answers', 'elearning-quiz'),
                'your_answer' => __('Your Answer', 'elearning-quiz'),
                'correct_answer' => __('Correct Answer', 'elearning-quiz'),
                'score' => __('Score', 'elearning-quiz'),
                'attempts' => __('Attempts', 'elearning-quiz'),
                'best_score' => __('Best Score', 'elearning-quiz'),
                
                // Lesson Specific
                'sections' => __('Sections', 'elearning-quiz'),
                'section' => __('Section', 'elearning-quiz'),
                'mark_complete' => __('Mark as Complete', 'elearning-quiz'),
                'completed' => __('Completed', 'elearning-quiz'),
                'in_progress' => __('In Progress', 'elearning-quiz'),
                'locked' => __('Locked', 'elearning-quiz'),
                'unlock_message' => __('Complete the previous section to unlock this content.', 'elearning-quiz'),
                
                // Status Messages
                'no_questions' => __('This quiz has no questions yet.', 'elearning-quiz'),
                'no_sections' => __('This lesson has no sections yet.', 'elearning-quiz'),
                'no_lessons_found' => __('No lessons found.', 'elearning-quiz'),
                'no_quizzes_found' => __('No quizzes found.', 'elearning-quiz'),
                'quiz_locked' => __('Quiz Locked', 'elearning-quiz'),
                'lesson_required' => __('You must finish %s in order to take this quiz.', 'elearning-quiz'),
                
                // Progress
                'your_progress' => __('Your Progress', 'elearning-quiz'),
                'progress_complete' => __('%d%% complete', 'elearning-quiz'),
                'sections_completed' => __('%d of %d sections completed', 'elearning-quiz'),
                
                // Question Types
                'multiple_choice' => __('Multiple Choice', 'elearning-quiz'),
                'true_false' => __('True/False', 'elearning-quiz'),
                'fill_blanks' => __('Fill in the Blanks', 'elearning-quiz'),
                'matching' => __('Matching', 'elearning-quiz'),
                
                // Question Instructions
                'select_answer' => __('Select the correct answer:', 'elearning-quiz'),
                'select_all_correct' => __('Select all correct answers:', 'elearning-quiz'),
                'select_true_false' => __('Select True or False:', 'elearning-quiz'),
                'drag_words' => __('Drag words to fill the blanks:', 'elearning-quiz'),
                'drag_match' => __('Drag items from the right column to match with items in the left column:', 'elearning-quiz'),
                
                // Admin
                'add' => __('Add', 'elearning-quiz'),
                'add_new' => __('Add New', 'elearning-quiz'),
                'edit' => __('Edit', 'elearning-quiz'),
                'delete' => __('Delete', 'elearning-quiz'),
                'remove' => __('Remove', 'elearning-quiz'),
                'settings' => __('Settings', 'elearning-quiz'),
                'analytics' => __('Analytics', 'elearning-quiz'),
                'dashboard' => __('Dashboard', 'elearning-quiz'),
                'import' => __('Import', 'elearning-quiz'),
                'export' => __('Export', 'elearning-quiz'),
                
                // Time
                'minutes' => __('minutes', 'elearning-quiz'),
                'seconds' => __('seconds', 'elearning-quiz'),
                'time_remaining' => __('Time Remaining', 'elearning-quiz'),
                'time_up' => __('Time\'s Up!', 'elearning-quiz'),
                
                // Loan Calculator
                'loan_calculator' => __('Loan Calculator', 'elearning-quiz'),
                'loan_amount' => __('Loan Amount', 'elearning-quiz'),
                'interest_rate' => __('Annual Interest Rate', 'elearning-quiz'),
                'loan_term' => __('Loan Term', 'elearning-quiz'),
                'years' => __('years', 'elearning-quiz'),
                'calculate' => __('Calculate', 'elearning-quiz'),
                'monthly_payment' => __('Monthly Payment', 'elearning-quiz'),
                'total_payment' => __('Total Payment', 'elearning-quiz'),
                'total_interest' => __('Total Interest', 'elearning-quiz'),
                
                // Validation Messages
                'required_field' => __('This field is required', 'elearning-quiz'),
                'invalid_input' => __('Please enter a valid value', 'elearning-quiz'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'elearning-quiz'),
                
                // WPML Specific
                'language' => __('Language', 'elearning-quiz'),
                'select_language' => __('Select Language', 'elearning-quiz'),
            ];
        }
        
        return self::$strings;
    }
    
    /**
     * Get a specific string
     */
    public static function get($key) {
        $strings = self::getStrings();
        return isset($strings[$key]) ? $strings[$key] : $key;
    }
    
    /**
     * Echo a specific string
     */
    public static function e($key) {
        echo self::get($key);
    }
    
    /**
     * Get formatted string with placeholders
     */
    public static function getFormatted($key, ...$args) {
        $string = self::get($key);
        return sprintf($string, ...$args);
    }
    
    /**
     * Register strings with WPML
     */
    public static function registerWithWPML() {
        if (!function_exists('icl_register_string')) {
            return;
        }
        
        $strings = self::getStrings();
        
        foreach ($strings as $key => $value) {
            icl_register_string('elearning-quiz', $key, $value);
        }
    }
}

// Register strings with WPML on init
add_action('init', ['ELearning_Translations', 'registerWithWPML']);