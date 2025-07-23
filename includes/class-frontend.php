<?php
/**
 * Frontend Class - VERSION 3.0 WITH QUIZ ACCESS CONTROL
 * 
 * Fixed the True/False HTML structure to prevent grid layout issues
 * Fixed the quiz prompt display after marking sections complete
 * Added quiz access control based on lesson completion
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_Frontend {
    
    public function __construct() {
        add_filter('the_content', [$this, 'addQuizContent']);
        add_filter('the_content', [$this, 'addLessonContent']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueQuizAssets']);
        add_action('wp_footer', [$this, 'addQuizModalStructure']);
    }
    
    /**
     * Add quiz content to single quiz pages
     */
    public function addQuizContent($content) {
        if (!is_singular('elearning_quiz') || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        global $post;
        
        // Get associated lesson(s)
        $associated_lesson = get_post_meta($post->ID, '_associated_lesson', true);
        
        // Check if user has completed the required lesson(s)
        if ($associated_lesson) {
            $user_session = ELearning_Database::getOrCreateUserSession();
            $lesson_completed = $this->isLessonCompleted($associated_lesson, $user_session);
            
            if (!$lesson_completed) {
                // User hasn't completed the required lesson
                $lesson = get_post($associated_lesson);
                if ($lesson && $lesson->post_status === 'publish') {
                    $content .= '<div class="elearning-quiz-container" data-quiz-id="' . esc_attr($post->ID) . '">';
                    $content .= '<div class="quiz-access-denied">';
                    $content .= '<div class="access-denied-icon"><i class="fas fa-lock"></i></div>';
                    $content .= '<h2>' . __('Quiz Locked', 'elearning-quiz') . '</h2>';
                    $content .= '<p>' . sprintf(
                        __('You must finish %s in order to take this quiz.', 'elearning-quiz'),
                        '<strong>' . esc_html($lesson->post_title) . '</strong>'
                    ) . '</p>';
                    
                    // Show lesson progress
                    $progress = $this->getLessonProgressPercentage($associated_lesson, $user_session);
                    if ($progress > 0) {
                        $content .= '<div class="lesson-progress-preview">';
                        $content .= '<p>' . __('Your progress:', 'elearning-quiz') . '</p>';
                        $content .= '<div class="progress-bar">';
                        $content .= '<div class="progress-fill" style="width: ' . $progress . '%;"></div>';
                        $content .= '</div>';
                        $content .= '<p class="progress-text">' . sprintf(__('%d%% completed', 'elearning-quiz'), $progress) . '</p>';
                        $content .= '</div>';
                    }
                    
                    $content .= '<a href="' . get_permalink($associated_lesson) . '" class="button go-to-lesson-btn">';
                    $content .= __('Go to Lesson', 'elearning-quiz');
                    $content .= '</a>';
                    $content .= '</div>';
                    $content .= '</div>';
                    
                    // Add CSS for the access denied screen
                    $content .= $this->getAccessDeniedStyles();
                    
                    return $content;
                }
            }
        }
        
        // Get quiz data
        $questions = get_post_meta($post->ID, '_quiz_questions', true) ?: [];
        $passing_score = get_post_meta($post->ID, '_passing_score', true) ?: 70;
        $min_questions = get_post_meta($post->ID, '_min_questions_to_show', true) ?: count($questions);
        $show_results = get_post_meta($post->ID, '_show_results_immediately', true) ?: 'yes';
        
        if (empty($questions)) {
            $content .= '<div class="elearning-quiz-notice">';
            $content .= '<p>' . __('This quiz has no questions yet.', 'elearning-quiz') . '</p>';
            $content .= '</div>';
            return $content;
        }
        
        // FORCE FRESH START - Clear any existing session data
        $this->clearQuizSession($post->ID);
        
        // Quiz interface - ALWAYS show intro, never cached results
        $content .= '<div class="elearning-quiz-container" data-quiz-id="' . esc_attr($post->ID) . '">';
        $content .= $this->renderQuizInterface($post->ID, $questions, $passing_score, $min_questions, $show_results);
        $content .= '</div>';
        
        return $content;
    }
    
    /**
     * Check if lesson is completed
     */
    private function isLessonCompleted($lesson_id, $user_session) {
        $sections = get_post_meta($lesson_id, '_lesson_sections', true) ?: [];
        if (empty($sections)) {
            return true; // No sections means lesson is accessible
        }
        
        $progress = ELearning_Database::getLessonProgress($lesson_id, $user_session);
        
        // Check if all sections are completed
        $total_sections = count($sections);
        $completed_sections = 0;
        
        foreach ($progress as $section_progress) {
            if (!empty($section_progress['completed'])) {
                $completed_sections++;
            }
        }
        
        return $completed_sections >= $total_sections;
    }
    
    /**
     * Get lesson progress percentage
     */
    private function getLessonProgressPercentage($lesson_id, $user_session) {
        $sections = get_post_meta($lesson_id, '_lesson_sections', true) ?: [];
        if (empty($sections)) {
            return 100;
        }
        
        $progress = ELearning_Database::getLessonProgress($lesson_id, $user_session);
        
        $total_sections = count($sections);
        $completed_sections = 0;
        
        foreach ($progress as $section_progress) {
            if (!empty($section_progress['completed'])) {
                $completed_sections++;
            }
        }
        
        return $total_sections > 0 ? round(($completed_sections / $total_sections) * 100) : 0;
    }
    
    /**
     * Get access denied styles
     */
    private function getAccessDeniedStyles() {
        return '<style>
        .quiz-access-denied {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .access-denied-icon {
            font-size: 64px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .quiz-access-denied h2 {
            color: #343a40;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .quiz-access-denied p {
            color: #6c757d;
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .lesson-progress-preview {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .lesson-progress-preview p {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .lesson-progress-preview .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .lesson-progress-preview .progress-fill {
            height: 100%;
            background: #28a745;
            transition: width 0.3s ease;
        }
        
        .lesson-progress-preview .progress-text {
            font-size: 14px;
            color: #6c757d;
            margin: 0;
        }
        
        .go-to-lesson-btn {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .go-to-lesson-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        </style>';
    }
    
    /**
     * Clear quiz session data to force fresh start
     */
    private function clearQuizSession($quiz_id) {
        // Clear quiz-specific session data if session exists
        if (isset($_SESSION)) {
            unset($_SESSION['quiz_' . $quiz_id]);
            unset($_SESSION['quiz_attempt_' . $quiz_id]);
        }
        
        // Clear any transients
        delete_transient('quiz_result_' . $quiz_id);
        delete_transient('quiz_state_' . $quiz_id);
    }
    
    /**
     * Add lesson content to single lesson pages - FIXED VERSION 2.0
     */
    public function addLessonContent($content) {
        if (!is_singular('elearning_lesson') || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        global $post;
        
        // Get lesson data
        $sections = get_post_meta($post->ID, '_lesson_sections', true) ?: [];
        $associated_quiz = get_post_meta($post->ID, '_associated_quiz', true);
        
        if (empty($sections)) {
            $content .= '<div class="elearning-lesson-notice">';
            $content .= '<p>' . __('This lesson has no sections yet.', 'elearning-quiz') . '</p>';
            $content .= '</div>';
            return $content;
        }
        
        // Get user progress
        $user_session = ELearning_Database::getOrCreateUserSession();
        $progress = ELearning_Database::getLessonProgress($post->ID, $user_session);
        
        // Build progress array
        $progress_by_section = [];
        foreach ($progress as $p) {
            $progress_by_section[$p['section_index']] = $p;
        }
        
        // Start lesson container
        $content .= '<div class="elearning-lesson-container" data-lesson-id="' . esc_attr($post->ID) . '">';
        
        // Progress indicator
        $completed_sections = 0;
        foreach ($progress as $p) {
            if (!empty($p['completed'])) {
                $completed_sections++;
            }
        }
        $progress_percentage = count($sections) > 0 ? ($completed_sections / count($sections)) * 100 : 0;
        
        $content .= '<div class="lesson-progress-overview">';
        $content .= '<h3>' . __('Your Progress', 'elearning-quiz') . '</h3>';
        $content .= '<div class="progress-bar">';
        $content .= '<div class="progress-fill" style="width: ' . $progress_percentage . '%;"></div>';
        $content .= '</div>';
        $content .= '<p class="progress-text">' . sprintf(__('%d of %d sections completed', 'elearning-quiz'), $completed_sections, count($sections)) . '</p>';
        $content .= '</div>';
        
        // Render sections
        $content .= '<div class="lesson-sections">';
        foreach ($sections as $index => $section) {
            $section_progress = isset($progress_by_section[$index]) ? $progress_by_section[$index] : null;
            $is_completed = $section_progress && !empty($section_progress['completed']);
            $is_accessible = $index === 0 || (isset($progress_by_section[$index - 1]) && !empty($progress_by_section[$index - 1]['completed']));
            
            $section_class = 'lesson-section';
            if ($is_completed) {
                $section_class .= ' completed';
            }
            if (!$is_accessible) {
                $section_class .= ' locked';
            }
            
            $content .= '<div class="' . $section_class . '" data-section-index="' . esc_attr($index) . '">';
            
            // Section header
            $content .= '<div class="section-header">';
            $content .= '<h3 class="section-title">';
            if ($is_completed) {
                $content .= '<i class="fas fa-check-circle"></i> ';
            } elseif (!$is_accessible) {
                $content .= '<i class="fas fa-lock"></i> ';
            }
            $content .= esc_html($section['title']);
            $content .= '</h3>';
            $content .= '</div>';
            
            // Section content
            if ($is_accessible) {
                $content .= '<div class="section-content" data-section-index="' . esc_attr($index) . '">';
                $content .= wp_kses_post($section['content']);
                
                if (!$is_completed) {
                    $content .= '<div class="section-actions">';
                    $content .= '<button type="button" class="mark-complete-btn" data-section-index="' . esc_attr($index) . '">';
                    $content .= __('Mark as Complete', 'elearning-quiz');
                    $content .= '</button>';
                    $content .= '</div>';
                }
                
                $content .= '</div>';
            } else {
                $content .= '<div class="section-locked-message">';
                $content .= '<p>' . __('Complete the previous section to unlock this content.', 'elearning-quiz') . '</p>';
                $content .= '</div>';
            }
            
            $content .= '</div>';
        }
        $content .= '</div>';
        
        // Quiz link - FIXED: Show initially if all sections completed, otherwise hide
        $quiz_display_style = $completed_sections >= count($sections) ? 'block' : 'none';
        if ($associated_quiz) {
            $quiz = get_post($associated_quiz);
            if ($quiz && $quiz->post_status === 'publish') {
                $content .= '<div class="lesson-quiz-prompt" style="display: ' . $quiz_display_style . ';" data-quiz-id="' . esc_attr($associated_quiz) . '">';
                $content .= '<h3>' . __('Ready for the Quiz?', 'elearning-quiz') . '</h3>';
                $content .= '<p>' . __('You have completed all sections. Test your knowledge with the quiz!', 'elearning-quiz') . '</p>';
                $content .= '<a href="' . get_permalink($associated_quiz) . '" class="button quiz-button">' . __('Take Quiz', 'elearning-quiz') . '</a>';
                $content .= '</div>';
            }
        }
        
        $content .= '</div>';
        
        // Add JavaScript for section tracking - FIXED VERSION
        $content .= $this->getLessonTrackingScript();
        
        return $content;
    }
    
    /**
     * Render quiz interface - ALWAYS starts fresh
     */
    private function renderQuizInterface($quiz_id, $questions, $passing_score, $min_questions, $show_results) {
        // Check if user has already passed this quiz
        $user_session = ELearning_Database::getOrCreateUserSession();
        $previous_attempts = ELearning_Database::getUserQuizAttempts($user_session, $quiz_id);
        $has_passed = false;
        
        foreach ($previous_attempts as $attempt) {
            if ($attempt['passed'] == 1) {
                $has_passed = true;
                break;
            }
        }
        
        // INITIALIZE $html HERE - This is the fix
        $html = '';
        
        // Show passed state if user has already passed
        if ($has_passed) {
            $html = '<div class="elearning-quiz-passed">';
            $html .= '<i class="fas fa-check-circle"></i>';
            $html .= '<h3>' . __('Congratulations!', 'elearning-quiz') . '</h3>';
            $html .= '<p>' . __('You have already passed this quiz.', 'elearning-quiz') . '</p>';
            $html .= '<div class="quiz-stats">';
            $html .= '<p>' . sprintf(__('Your best score: %.1f%%', 'elearning-quiz'), $this->getBestScore($previous_attempts)) . '</p>';
            $html .= '<p>' . sprintf(__('Total attempts: %d', 'elearning-quiz'), count($previous_attempts)) . '</p>';
            $html .= '</div>';
            $html .= '<button type="button" class="retake-quiz-btn" data-quiz-id="' . esc_attr($quiz_id) . '">' . __('Retake Quiz', 'elearning-quiz') . '</button>';
            $html .= '</div>';
        }
        // Select questions for this attempt
        $selected_questions = $this->selectQuizQuestions($quiz_id, $questions, $min_questions);
        
        $html .= '<div class="elearning-quiz-intro" ' . ($has_passed ? 'style="display:none;"' : '') . '>';
        $html .= '<div class="quiz-info">';
        $html .= '<div class="quiz-stat"><span class="label">' . __('Questions:', 'elearning-quiz') . '</span> <span class="value">' . count($selected_questions) . '</span></div>';
        $html .= '<div class="quiz-stat"><span class="label">' . __('Passing Score:', 'elearning-quiz') . '</span> <span class="value">' . $passing_score . '%</span></div>';
        $html .= '<div class="quiz-stat"><span class="label">' . __('Time Limit:', 'elearning-quiz') . '</span> <span class="value">' . __('None', 'elearning-quiz') . '</span></div>';
        $html .= '</div>';
        $html .= '<button type="button" class="start-quiz-btn" data-quiz-id="' . esc_attr($quiz_id) . '">' . __('Start Quiz', 'elearning-quiz') . '</button>';
        $html .= '</div>';
        
        // Quiz form (hidden initially)
        $html .= '<form class="elearning-quiz-form" style="display: none;" data-passing-score="' . esc_attr($passing_score) . '" data-show-results="' . esc_attr($show_results) . '">';
        $html .= wp_nonce_field('elearning_quiz_submit', 'quiz_nonce', true, false);
        $html .= '<input type="hidden" name="quiz_id" value="' . esc_attr($quiz_id) . '" />';
        $html .= '<input type="hidden" name="attempt_id" value="" />';
        
        // Progress indicator
        $html .= '<div class="quiz-progress">';
        $html .= '<div class="progress-bar"><div class="progress-fill" style="width: 0%"></div></div>';
        $html .= '<div class="progress-text"><span class="current">1</span> / <span class="total">' . count($selected_questions) . '</span></div>';
        $html .= '</div>';
        
        // Questions container
        $html .= '<div class="quiz-questions-container">';
        
        foreach ($selected_questions as $index => $question) {
            $html .= $this->renderQuestion($index, $question);
        }
        
        $html .= '</div>';
        
        // Navigation buttons
        $html .= '<div class="quiz-navigation">';
        $html .= '<button type="button" class="quiz-nav-btn prev-btn" disabled>' . __('Previous', 'elearning-quiz') . '</button>';
        $html .= '<button type="button" class="quiz-nav-btn next-btn">' . __('Next', 'elearning-quiz') . '</button>';
        $html .= '<button type="button" class="quiz-submit-btn" style="display: none;">' . __('Submit Quiz', 'elearning-quiz') . '</button>';
        $html .= '</div>';
        
        $html .= '</form>';
        
        // Results container (hidden initially)
        $html .= '<div class="quiz-results" style="display: none;"></div>';
        
        return $html;
    }
    
    /**
     * Select questions for quiz attempt
     */
    private function selectQuizQuestions($quiz_id, $all_questions, $min_questions) {
        if (count($all_questions) <= $min_questions) {
            return $all_questions;
        }
        
        // For now, just return first N questions to avoid session issues
        return array_slice($all_questions, 0, $min_questions);
    }
    
    /**
     * Render individual question
     */
    private function renderQuestion($index, $question) {
        $question_class = $index === 0 ? 'quiz-question active' : 'quiz-question';
        
        $html = '<div class="' . $question_class . '" data-question-index="' . esc_attr($index) . '" data-question-type="' . esc_attr($question['type']) . '">';
        $html .= '<div class="question-header">';
        $html .= '<h3 class="question-title">' . sprintf(__('Question %d', 'elearning-quiz'), $index + 1) . '</h3>';
        $html .= '</div>';
        
        $html .= '<div class="question-content">';
        $html .= '<div class="question-text">' . wp_kses_post($question['question']) . '</div>';
        
        switch ($question['type']) {
            case 'multiple_choice':
                $html .= $this->renderMultipleChoice($index, $question);
                break;
            case 'fill_blanks':
                $html .= $this->renderFillBlanks($index, $question);
                break;
            case 'true_false':
                $html .= $this->renderTrueFalse($index, $question);
                break;
            case 'matching':
                $html .= $this->renderMatching($index, $question);
                break;
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render multiple choice question
     */
    private function renderMultipleChoice($index, $question) {
        $options = $question['options'] ?? [];
        $correct_answers = $question['correct_answers'] ?? [];
        $is_multi_select = count($correct_answers) > 1;
        
        $html = '<p class="instruction">';
        if ($is_multi_select) {
            $html .= __('Select all correct answers:', 'elearning-quiz');
        } else {
            $html .= __('Select the correct answer:', 'elearning-quiz');
        }
        $html .= '</p>';
        
        $html .= '<div class="multiple-choice-options">';
        
        foreach ($options as $opt_index => $option) {
            $input_type = $is_multi_select ? 'checkbox' : 'radio';
            $input_name = $is_multi_select ? "questions[{$index}][answers][]" : "questions[{$index}][answer]";
            
            $html .= '<label class="option-label">';
            $html .= '<input type="' . $input_type . '" name="' . $input_name . '" value="' . esc_attr($opt_index) . '" />';
            $html .= '<span class="option-text">' . esc_html($option) . '</span>';
            $html .= '</label>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render fill in the blanks question
     */
    private function renderFillBlanks($index, $question) {
        $text_with_blanks = $question['text_with_blanks'] ?? '';
        $word_bank = $question['word_bank'] ?? [];
        
        // Process text to create blanks
        $blank_count = 0;
        $processed_text = preg_replace_callback('/\{\{blank\}\}/', function($matches) use (&$blank_count) {
            return '<span class="blank-space" data-blank-index="' . $blank_count++ . '"></span>';
        }, $text_with_blanks);
        
        $html = '<p class="instruction">' . __('Drag words to fill the blanks:', 'elearning-quiz') . '</p>';
        
        $html .= '<div class="fill-blanks-container">';
        
        $html .= '<div class="text-with-blanks">' . wp_kses_post($processed_text) . '</div>';
        
        if (!empty($word_bank)) {
            $html .= '<div class="word-bank">';
            $html .= '<h4>' . __('Word Bank:', 'elearning-quiz') . '</h4>';
            $html .= '<div class="word-bank-items">';
            
            // Shuffle word bank
            $shuffled_words = $word_bank;
            shuffle($shuffled_words);
            
            foreach ($shuffled_words as $word_index => $word) {
                $html .= '<span class="word-item" draggable="true" data-word="' . esc_attr($word) . '">' . esc_html($word) . '</span>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            
            // Hidden inputs for answers
            for ($i = 0; $i < $blank_count; $i++) {
                $html .= '<input type="hidden" name="questions[' . $index . '][answers][' . $i . ']" value="" class="blank-answer" data-blank-index="' . $i . '" />';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render true/false question - FIXED STRUCTURE
     */
    private function renderTrueFalse($index, $question) {
        // FIXED: Move instruction outside the grid container
        $html = '<p class="instruction">' . __('Select True or False:', 'elearning-quiz') . '</p>';
        
        // FIXED: Only the two label elements should be in the grid
        $html .= '<div class="true-false-options">';
        
        $html .= '<label class="option-label neutral-option">';
        $html .= '<input type="radio" name="questions[' . $index . '][answer]" value="true" />';
        $html .= '<span class="option-text">' . __('True', 'elearning-quiz') . '</span>';
        $html .= '</label>';
        
        $html .= '<label class="option-label neutral-option">';
        $html .= '<input type="radio" name="questions[' . $index . '][answer]" value="false" />';
        $html .= '<span class="option-text">' . __('False', 'elearning-quiz') . '</span>';
        $html .= '</label>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render matching question - Drag & Drop Implementation
     */
    private function renderMatching($index, $question) {
        $left_column = $question['left_column'] ?? [];
        $right_column = $question['right_column'] ?? [];
        
        // Shuffle right column for display
        $shuffled_right = $right_column;
        shuffle($shuffled_right);
        
        $html = '<p class="instruction">' . __('Drag items from the right column to match with items in the left column:', 'elearning-quiz') . '</p>';
        
        $html .= '<div class="matching-container">';
        
        $html .= '<div class="matching-columns">';
        
        // Left column - Drop zones
        $html .= '<div class="left-column">';
        $html .= '<h4>' . __('Match These:', 'elearning-quiz') . '</h4>';
        foreach ($left_column as $left_index => $left_item) {
            $html .= '<div class="match-item left-item" data-left-index="' . esc_attr($left_index) . '">';
            $html .= '<div class="item-text">' . esc_html($left_item) . '</div>';
            $html .= '<div class="drop-zone" data-left-index="' . esc_attr($left_index) . '">';
            $html .= '<span class="drop-placeholder">' . __('Drop here', 'elearning-quiz') . '</span>';
            $html .= '</div>';
            $html .= '<input type="hidden" name="questions[' . $index . '][answers][' . $left_index . ']" value="" class="match-answer" />';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Right column - Draggable items
        $html .= '<div class="right-column">';
        $html .= '<h4>' . __('Available Options:', 'elearning-quiz') . '</h4>';
        $html .= '<div class="draggable-items">';
        foreach ($shuffled_right as $right_index => $right_item) {
            // Find original index
            $original_index = array_search($right_item, $right_column);
            $html .= '<div class="match-item right-item draggable-item" draggable="true" data-right-index="' . esc_attr($original_index) . '" data-item-text="' . esc_attr($right_item) . '">';
            $html .= '<span class="item-text">' . esc_html($right_item) . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Enqueue quiz-specific assets
     */
    public function enqueueQuizAssets() {
        if (is_singular(['elearning_quiz', 'elearning_lesson'])) {
            // jQuery UI for drag and drop
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script('jquery-ui-droppable');
            wp_enqueue_script('jquery-ui-sortable');
        }
    }
    
    /**
     * Add quiz modal structure to footer
     */
    public function addQuizModalStructure() {
        if (!is_singular('elearning_quiz')) {
            return;
        }
        ?>
        <div id="quiz-loading-modal" class="quiz-modal" style="display: none;">
            <div class="modal-content">
                <div class="loading-spinner"></div>
                <p><?php _e('Processing your answers...', 'elearning-quiz'); ?></p>
            </div>
        </div>
        
        <div id="quiz-confirmation-modal" class="quiz-modal" style="display: none;">
            <div class="modal-content">
                <h3><?php _e('Submit Quiz?', 'elearning-quiz'); ?></h3>
                <p><?php _e('Are you sure you want to submit your answers? You cannot change them after submission.', 'elearning-quiz'); ?></p>
                <div class="modal-buttons">
                    <button type="button" class="button secondary" id="cancel-submit"><?php _e('Cancel', 'elearning-quiz'); ?></button>
                    <button type="button" class="button primary" id="confirm-submit"><?php _e('Submit Quiz', 'elearning-quiz'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get best score from attempts
     */
    private function getBestScore($attempts) {
        $best_score = 0;
        foreach ($attempts as $attempt) {
            if ($attempt['score'] > $best_score) {
                $best_score = $attempt['score'];
            }
        }
                
        return $best_score;
    }
    
    /**
     * Get lesson tracking script - FIXED VERSION 4.0
     */
    private function getLessonTrackingScript() {
        ob_start();
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Track section scroll completion
            let sectionTimers = {};
            let sectionScrollTracking = {};
            
            // Initialize tracking for existing sections
            function initializeSectionTracking() {
                $('.section-content').each(function() {
                    const $section = $(this);
                    const sectionIndex = $section.data('section-index');
                    
                    if (!sectionTimers.hasOwnProperty(sectionIndex)) {
                        sectionTimers[sectionIndex] = Date.now();
                        sectionScrollTracking[sectionIndex] = false;
                        
                        // Track scroll completion
                        $section.off('scroll').on('scroll', function() {
                            const scrollPercentage = ($section.scrollTop() + $section.height()) / $section[0].scrollHeight * 100;
                            
                            if (scrollPercentage >= 90 && !sectionScrollTracking[sectionIndex]) {
                                sectionScrollTracking[sectionIndex] = true;
                                console.log('Section ' + sectionIndex + ' scroll completed');
                            }
                        });
                    }
                });
            }
            
            // Initialize on page load
            initializeSectionTracking();
            
            // FIXED: Use event delegation for dynamically added buttons
            $(document).on('click', '.mark-complete-btn', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const sectionIndex = $button.data('section-index');
                const lessonId = $('.elearning-lesson-container').data('lesson-id');
                
                // Prevent double-clicking
                if ($button.prop('disabled')) {
                    return;
                }
                
                $button.prop('disabled', true).text('<?php echo esc_js(__('Marking Complete...', 'elearning-quiz')); ?>');
                
                // Calculate time spent
                const timeSpent = Math.round((Date.now() - (sectionTimers[sectionIndex] || Date.now())) / 1000);
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'elearning_update_lesson_progress',
                        lesson_id: lessonId,
                        section_index: sectionIndex,
                        completed: true,
                        time_spent: timeSpent,
                        scroll_percentage: sectionScrollTracking[sectionIndex] ? 100 : 0,
                        nonce: '<?php echo wp_create_nonce('elearning_quiz_nonce'); ?>'
                    },
                    // ENCODING FIX: Ensure proper UTF-8 handling
                    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                    success: function(response) {
                        if (response.success) {
                            // Update UI
                            const $section = $button.closest('.lesson-section');
                            $section.addClass('completed');
                            
                            // Add completion icon if not already present
                            if (!$section.find('.section-title .completion-icon').length) {
                                $section.find('.section-title').prepend('<i class="fas fa-check-circle"></i> ');
                            }
                            
                            // Remove the button's container
                            $button.parent('.section-actions').remove();
                            
                            // Update progress bar
                            updateProgressBar();
                            
                            // Check if all sections are now completed
                            const totalSections = $('.lesson-section').length;
                            const completedSections = $('.lesson-section.completed').length;
                            
                            // Show quiz prompt immediately if all sections completed
                            if (completedSections === totalSections) {
                                $('.lesson-quiz-prompt').slideDown();
                            }
                            
                            // Unlock next section
                            const $nextSection = $section.next('.lesson-section');
                            if ($nextSection.length && $nextSection.hasClass('locked')) {
                                $nextSection.removeClass('locked');
                                $nextSection.find('.fa-lock').remove();
                                
                                // Replace locked message with actual content
                                const nextSectionIndex = parseInt($nextSection.data('section-index'));
                                
                                // AJAX call to get section content
                                $.ajax({
                                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                    type: 'POST',
                                    data: {
                                        action: 'elearning_get_section_content',
                                        lesson_id: lessonId,
                                        section_index: nextSectionIndex,
                                        nonce: '<?php echo wp_create_nonce('elearning_quiz_nonce'); ?>'
                                    },
                                    // ENCODING FIX: Ensure proper UTF-8 handling
                                    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                                    dataType: 'json',
                                    success: function(contentResponse) {
                                        if (contentResponse.success) {
                                            $nextSection.find('.section-locked-message').remove();
                                            
                                            const sectionContent = '<div class="section-content" data-section-index="' + nextSectionIndex + '">' +
                                                contentResponse.data.content +
                                                '<div class="section-actions">' +
                                                '<button type="button" class="mark-complete-btn" data-section-index="' + nextSectionIndex + '">' +
                                                '<?php echo esc_js(__('Mark as Complete', 'elearning-quiz')); ?>' +
                                                '</button>' +
                                                '</div>' +
                                                '</div>';
                                            
                                            $nextSection.append(sectionContent);
                                            
                                            // Initialize tracking for the new section
                                            sectionTimers[nextSectionIndex] = Date.now();
                                            sectionScrollTracking[nextSectionIndex] = false;
                                            
                                            // Re-initialize section tracking for new content
                                            initializeSectionTracking();
                                        }
                                    },
                                    error: function() {
                                        // Fallback: reload page if AJAX fails
                                        location.reload();
                                    }
                                });
                            }
                        } else {
                            alert(response.data || '<?php echo esc_js(__('Error updating progress', 'elearning-quiz')); ?>');
                            $button.prop('disabled', false).text('<?php echo esc_js(__('Mark as Complete', 'elearning-quiz')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Error updating progress', 'elearning-quiz')); ?>');
                        $button.prop('disabled', false).text('<?php echo esc_js(__('Mark as Complete', 'elearning-quiz')); ?>');
                    }
                });
            });
            
            function updateProgressBar() {
                const totalSections = $('.lesson-section').length;
                const completedSections = $('.lesson-section.completed').length;
                const percentage = (completedSections / totalSections) * 100;
                
                $('.lesson-progress-overview .progress-fill').css('width', percentage + '%');
                $('.lesson-progress-overview .progress-text').text(
                    '<?php echo esc_js(__('%d of %d sections completed', 'elearning-quiz')); ?>'
                        .replace('%d', completedSections)
                        .replace('%d', totalSections)
                );
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}