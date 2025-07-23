<?php
/**
 * Custom Post Types Class - FIXED VERSION
 * 
 * Handles registration of custom post types and taxonomies
 * FIXES: Matching and Fill-in-the-blanks saving issues
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_Post_Types {
    
    public function __construct() {
        add_action('init', [$this, 'registerPostTypes']);
        add_action('init', [$this, 'registerTaxonomies']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMetaBoxes']);
        add_filter('manage_elearning_lesson_posts_columns', [$this, 'addLessonColumns']);
        add_filter('manage_elearning_quiz_posts_columns', [$this, 'addQuizColumns']);
        add_action('manage_elearning_lesson_posts_custom_column', [$this, 'populateLessonColumns'], 10, 2);
        add_action('manage_elearning_quiz_posts_custom_column', [$this, 'populateQuizColumns'], 10, 2);
    }
    
    /**
     * Register custom post types
     */
    public function registerPostTypes(): void {
        $this->registerLessonPostType();
        $this->registerQuizPostType();
    }
    
    /**
     * Register Lesson post type
     */
    private function registerLessonPostType(): void {
        $labels = [
            'name' => __('Lessons', 'elearning-quiz'),
            'singular_name' => __('Lesson', 'elearning-quiz'),
            'menu_name' => __('Lessons', 'elearning-quiz'),
            'add_new' => __('Add New', 'elearning-quiz'),
            'add_new_item' => __('Add New Lesson', 'elearning-quiz'),
            'edit_item' => __('Edit Lesson', 'elearning-quiz'),
            'new_item' => __('New Lesson', 'elearning-quiz'),
            'view_item' => __('View Lesson', 'elearning-quiz'),
            'view_items' => __('View Lessons', 'elearning-quiz'),
            'search_items' => __('Search Lessons', 'elearning-quiz'),
            'not_found' => __('No lessons found', 'elearning-quiz'),
            'not_found_in_trash' => __('No lessons found in trash', 'elearning-quiz'),
            'all_items' => __('All Lessons', 'elearning-quiz'),
            'archives' => __('Lesson Archives', 'elearning-quiz'),
            'attributes' => __('Lesson Attributes', 'elearning-quiz'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'lesson', 'with_front' => false],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-book-alt',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author'],
            'show_in_rest' => true,
            'rest_base' => 'lessons',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];
        
        register_post_type('elearning_lesson', $args);
    }
    
    /**
     * Register Quiz post type
     */
    private function registerQuizPostType(): void {
        $labels = [
            'name' => __('Quizzes', 'elearning-quiz'),
            'singular_name' => __('Quiz', 'elearning-quiz'),
            'menu_name' => __('Quizzes', 'elearning-quiz'),
            'add_new' => __('Add New', 'elearning-quiz'),
            'add_new_item' => __('Add New Quiz', 'elearning-quiz'),
            'edit_item' => __('Edit Quiz', 'elearning-quiz'),
            'new_item' => __('New Quiz', 'elearning-quiz'),
            'view_item' => __('View Quiz', 'elearning-quiz'),
            'view_items' => __('View Quizzes', 'elearning-quiz'),
            'search_items' => __('Search Quizzes', 'elearning-quiz'),
            'not_found' => __('No quizzes found', 'elearning-quiz'),
            'not_found_in_trash' => __('No quizzes found in trash', 'elearning-quiz'),
            'all_items' => __('All Quizzes', 'elearning-quiz'),
            'archives' => __('Quiz Archives', 'elearning-quiz'),
            'attributes' => __('Quiz Attributes', 'elearning-quiz'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'quiz', 'with_front' => false],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-forms',
            'supports' => ['title', 'excerpt', 'revisions', 'author'],
            'show_in_rest' => true,
            'rest_base' => 'quizzes',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];
        
        register_post_type('elearning_quiz', $args);
    }
    
    /**
     * Register taxonomies
     */
    public function registerTaxonomies(): void {
        // Quiz Categories
        $labels = [
            'name' => __('Quiz Categories', 'elearning-quiz'),
            'singular_name' => __('Quiz Category', 'elearning-quiz'),
            'menu_name' => __('Categories', 'elearning-quiz'),
            'all_items' => __('All Categories', 'elearning-quiz'),
            'edit_item' => __('Edit Category', 'elearning-quiz'),
            'view_item' => __('View Category', 'elearning-quiz'),
            'update_item' => __('Update Category', 'elearning-quiz'),
            'add_new_item' => __('Add New Category', 'elearning-quiz'),
            'new_item_name' => __('New Category Name', 'elearning-quiz'),
            'search_items' => __('Search Categories', 'elearning-quiz'),
        ];
        
        register_taxonomy('quiz_category', ['elearning_quiz'], [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'quiz-category'],
        ]);
        
        // Lesson Categories
        $lesson_labels = [
            'name' => __('Lesson Categories', 'elearning-quiz'),
            'singular_name' => __('Lesson Category', 'elearning-quiz'),
            'menu_name' => __('Categories', 'elearning-quiz'),
            'all_items' => __('All Categories', 'elearning-quiz'),
            'edit_item' => __('Edit Category', 'elearning-quiz'),
            'view_item' => __('View Category', 'elearning-quiz'),
            'update_item' => __('Update Category', 'elearning-quiz'),
            'add_new_item' => __('Add New Category', 'elearning-quiz'),
            'new_item_name' => __('New Category Name', 'elearning-quiz'),
            'search_items' => __('Search Categories', 'elearning-quiz'),
        ];
        
        register_taxonomy('lesson_category', ['elearning_lesson'], [
            'labels' => $lesson_labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'lesson-category'],
        ]);
    }
    
    /**
     * Add meta boxes
     */
    public function addMetaBoxes(): void {
        // Lesson meta boxes
        add_meta_box(
            'lesson_sections',
            __('Lesson Sections', 'elearning-quiz'),
            [$this, 'renderLessonSectionsMetaBox'],
            'elearning_lesson',
            'normal',
            'high'
        );
        
        add_meta_box(
            'lesson_quiz',
            __('Associated Quiz', 'elearning-quiz'),
            [$this, 'renderLessonQuizMetaBox'],
            'elearning_lesson',
            'side',
            'default'
        );
        
        // Quiz meta boxes
        add_meta_box(
            'quiz_settings',
            __('Quiz Settings', 'elearning-quiz'),
            [$this, 'renderQuizSettingsMetaBox'],
            'elearning_quiz',
            'side',
            'high'
        );
        
        add_meta_box(
            'quiz_lesson',
            __('Associated Lesson', 'elearning-quiz'),
            [$this, 'renderQuizLessonMetaBox'],
            'elearning_quiz',
            'side',
            'default'
        );
        
        add_meta_box(
            'quiz_questions',
            __('Quiz Questions', 'elearning-quiz'),
            [$this, 'renderQuizQuestionsMetaBox'],
            'elearning_quiz',
            'normal',
            'high'
        );
    }
    
    /**
     * Render lesson sections meta box
     */
    public function renderLessonSectionsMetaBox($post): void {
        wp_nonce_field('lesson_sections_nonce', 'lesson_sections_nonce');
        
        $sections = get_post_meta($post->ID, '_lesson_sections', true);
        if (!is_array($sections) || empty($sections)) {
            $sections = [['title' => '', 'content' => '']];
        }
        
        echo '<div id="lesson-sections-container">';
        
        foreach ($sections as $index => $section) {
            $this->renderSectionFields($index, $section);
        }
        
        echo '</div>';
        echo '<button type="button" id="add-section" class="button">' . __('Add Section', 'elearning-quiz') . '</button>';
        
        // JavaScript template for new sections
        echo '<script type="text/template" id="section-template">';
        $this->renderSectionFields('{{INDEX}}', ['title' => '', 'content' => '']);
        echo '</script>';
    }
    
    /**
     * Render section fields
     */
    private function renderSectionFields($index, $section): void {
        ?>
        <div class="lesson-section" data-index="<?php echo esc_attr($index); ?>">
            <div class="section-header">
                <h4><?php printf(__('Section %s', 'elearning-quiz'), is_numeric($index) ? $index + 1 : 1); ?></h4>
                <button type="button" class="remove-section button-link-delete"><?php _e('Remove', 'elearning-quiz'); ?></button>
            </div>
            <table class="form-table">
                <tr>
                    <th><label for="section_title_<?php echo esc_attr($index); ?>"><?php _e('Section Title', 'elearning-quiz'); ?></label></th>
                    <td>
                        <input type="text" 
                               id="section_title_<?php echo esc_attr($index); ?>" 
                               name="lesson_sections[<?php echo esc_attr($index); ?>][title]" 
                               value="<?php echo esc_attr($section['title'] ?? ''); ?>" 
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th><label for="section_content_<?php echo esc_attr($index); ?>"><?php _e('Section Content', 'elearning-quiz'); ?></label></th>
                    <td>
                        <?php
                        $editor_id = "section_content_" . $index;
                        $editor_name = "lesson_sections[" . $index . "][content]";
                        $content = $section['content'] ?? '';
                        
                        // Only render wp_editor for numeric indices (existing sections)
                        // Template sections will use textarea and be converted via JavaScript
                        if (is_numeric($index)) {
                            wp_editor($content, $editor_id, [
                                'textarea_name' => $editor_name,
                                'textarea_rows' => 8,
                                'media_buttons' => true,
                                'teeny' => false,
                                'dfw' => false,
                                'tinymce' => [
                                    'toolbar1' => 'bold,italic,underline,separator,alignleft,aligncenter,alignright,separator,link,unlink,undo,redo',
                                    'toolbar2' => 'formatselect,forecolor,separator,bullist,numlist,separator,outdent,indent,separator,image,code',
                                    'resize' => true,
                                    'wp_autoresize_on' => true,
                                ],
                                'quicktags' => [
                                    'buttons' => 'strong,em,ul,ol,li,link,close'
                                ]
                            ]);
                        } else {
                            // Template for new sections - will be converted to wp_editor via AJAX
                            ?>
                            <textarea name="<?php echo esc_attr($editor_name); ?>" 
                                      id="<?php echo esc_attr($editor_id); ?>" 
                                      rows="8" 
                                      class="large-text wp-editor-placeholder"><?php echo esc_textarea($content); ?></textarea>
                            <p class="description"><?php _e('Rich editor will be initialized when section is added.', 'elearning-quiz'); ?></p>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render lesson quiz meta box
     */
    public function renderLessonQuizMetaBox($post): void {
        wp_nonce_field('lesson_quiz_nonce', 'lesson_quiz_nonce');
        
        $associated_quiz = get_post_meta($post->ID, '_associated_quiz', true);
        
        $quizzes = get_posts([
            'post_type' => 'elearning_quiz',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft'],
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="associated_quiz">' . __('Select Quiz', 'elearning-quiz') . '</label></th>';
        echo '<td>';
        echo '<select name="associated_quiz" id="associated_quiz" class="widefat">';
        echo '<option value="">' . __('No Quiz', 'elearning-quiz') . '</option>';
        
        foreach ($quizzes as $quiz) {
            $selected = selected($associated_quiz, $quiz->ID, false);
            echo '<option value="' . esc_attr($quiz->ID) . '" ' . $selected . '>' . esc_html($quiz->post_title) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . __('Select a quiz to associate with this lesson. Students will take this quiz after completing the lesson.', 'elearning-quiz') . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }
    
    /**
     * Render quiz lesson meta box
     */
    public function renderQuizLessonMetaBox($post): void {
        wp_nonce_field('quiz_lesson_nonce', 'quiz_lesson_nonce');
        
        $associated_lesson = get_post_meta($post->ID, '_associated_lesson', true);
        
        $lessons = get_posts([
            'post_type' => 'elearning_lesson',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft'],
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="associated_lesson">' . __('Select Lesson', 'elearning-quiz') . '</label></th>';
        echo '<td>';
        echo '<select name="associated_lesson" id="associated_lesson" class="widefat">';
        echo '<option value="">' . __('No Lesson', 'elearning-quiz') . '</option>';
        
        foreach ($lessons as $lesson) {
            $selected = selected($associated_lesson, $lesson->ID, false);
            echo '<option value="' . esc_attr($lesson->ID) . '" ' . $selected . '>' . esc_html($lesson->post_title) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . __('Select a lesson this quiz belongs to. This creates a two-way association.', 'elearning-quiz') . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }
    
    /**
     * Render quiz settings meta box
     */
    public function renderQuizSettingsMetaBox($post): void {
        wp_nonce_field('quiz_settings_nonce', 'quiz_settings_nonce');
        
        $passing_score = get_post_meta($post->ID, '_passing_score', true) ?: 70;
        $min_questions = get_post_meta($post->ID, '_min_questions_to_show', true) ?: 5;
        $show_results = get_post_meta($post->ID, '_show_results_immediately', true) ?: 'yes';
        $time_limit = get_post_meta($post->ID, '_time_limit', true) ?: 0;
        $randomize_questions = get_post_meta($post->ID, '_randomize_questions', true) ?: 'no';
        $randomize_answers = get_post_meta($post->ID, '_randomize_answers', true) ?: 'no';
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="passing_score"><?php _e('Passing Score (%)', 'elearning-quiz'); ?></label></th>
                <td>
                    <input type="number" 
                           id="passing_score" 
                           name="passing_score" 
                           value="<?php echo esc_attr($passing_score); ?>" 
                           min="0" 
                           max="100" 
                           step="1" />
                    <p class="description"><?php _e('Minimum percentage required to pass the quiz.', 'elearning-quiz'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="min_questions_to_show"><?php _e('Questions to Show', 'elearning-quiz'); ?></label></th>
                <td>
                    <input type="number" 
                           id="min_questions_to_show" 
                           name="min_questions_to_show" 
                           value="<?php echo esc_attr($min_questions); ?>" 
                           min="1" 
                           step="1" />
                    <p class="description"><?php _e('Number of questions to show from the question bank. Leave empty to show all.', 'elearning-quiz'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="time_limit"><?php _e('Time Limit (minutes)', 'elearning-quiz'); ?></label></th>
                <td>
                    <input type="number" 
                           id="time_limit" 
                           name="time_limit" 
                           value="<?php echo esc_attr($time_limit); ?>" 
                           min="0" 
                           step="1" />
                    <p class="description"><?php _e('Time limit in minutes. Set to 0 for no time limit.', 'elearning-quiz'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="show_results_immediately"><?php _e('Show Results', 'elearning-quiz'); ?></label></th>
                <td>
                    <select name="show_results_immediately" id="show_results_immediately">
                        <option value="yes" <?php selected($show_results, 'yes'); ?>><?php _e('Yes - Show correct answers after submission', 'elearning-quiz'); ?></option>
                        <option value="no" <?php selected($show_results, 'no'); ?>><?php _e('No - Only show score', 'elearning-quiz'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="randomize_questions"><?php _e('Randomize Questions', 'elearning-quiz'); ?></label></th>
                <td>
                    <select name="randomize_questions" id="randomize_questions">
                        <option value="yes" <?php selected($randomize_questions, 'yes'); ?>><?php _e('Yes', 'elearning-quiz'); ?></option>
                        <option value="no" <?php selected($randomize_questions, 'no'); ?>><?php _e('No', 'elearning-quiz'); ?></option>
                    </select>
                    <p class="description"><?php _e('Show questions in random order for each attempt.', 'elearning-quiz'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="randomize_answers"><?php _e('Randomize Answers', 'elearning-quiz'); ?></label></th>
                <td>
                    <select name="randomize_answers" id="randomize_answers">
                        <option value="yes" <?php selected($randomize_answers, 'yes'); ?>><?php _e('Yes', 'elearning-quiz'); ?></option>
                        <option value="no" <?php selected($randomize_answers, 'no'); ?>><?php _e('No', 'elearning-quiz'); ?></option>
                    </select>
                    <p class="description"><?php _e('Show answer options in random order (for multiple choice questions).', 'elearning-quiz'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render quiz questions meta box
     */
    public function renderQuizQuestionsMetaBox($post): void {
        wp_nonce_field('quiz_questions_nonce', 'quiz_questions_nonce');
        
        $questions = get_post_meta($post->ID, '_quiz_questions', true);
        if (!is_array($questions) || empty($questions)) {
            $questions = [['type' => 'multiple_choice', 'question' => '', 'options' => [''], 'correct_answers' => []]];
        }
        
        // Add import/export buttons
        echo '<div class="quiz-questions-toolbar">';
        echo '<button type="button" class="button" onclick="openImportModal()">' . __('Import Questions', 'elearning-quiz') . '</button>';
        if (!empty($questions) && !empty($questions[0]['question'])) {
            echo ' <a href="' . admin_url('admin-ajax.php?action=elearning_export_questions&quiz_id=' . $post->ID . '&nonce=' . wp_create_nonce('elearning_export_nonce')) . '" class="button">' . __('Export Questions', 'elearning-quiz') . '</a>';
        }
        echo '</div>';
        
        echo '<div id="quiz-questions-container">';
        
        foreach ($questions as $index => $question) {
            $this->renderQuestionFields($index, $question);
        }
        
        echo '</div>';
        echo '<button type="button" id="add-question" class="button">' . __('Add Question', 'elearning-quiz') . '</button>';
        
        // JavaScript template for new questions
        echo '<script type="text/template" id="question-template">';
        $this->renderQuestionFields('{{INDEX}}', ['type' => 'multiple_choice', 'question' => '', 'options' => [''], 'correct_answers' => []]);
        echo '</script>';
        
        // Add some styling
        echo '<style>
        .quiz-questions-toolbar {
            margin-bottom: 20px;
            padding: 15px;
            background: #f0f0f1;
            border-radius: 4px;
        }
        
        .quiz-questions-toolbar .button {
            margin-right: 10px;
        }
        </style>';
    }
    
    /**
     * Render question fields
     */
    private function renderQuestionFields($index, $question): void {
        $question_types = [
            'multiple_choice' => __('Multiple Choice', 'elearning-quiz'),
            'fill_blanks' => __('Fill in the Blanks', 'elearning-quiz'),
            'true_false' => __('True/False', 'elearning-quiz'),
            'matching' => __('Matching', 'elearning-quiz'),
        ];
        
        // Ensure question array has default values
        $question = wp_parse_args($question, [
            'type' => 'multiple_choice',
            'question' => '',
            'options' => [''],
            'correct_answers' => []
        ]);
        
        ?>
        <div class="quiz-question" data-index="<?php echo esc_attr($index); ?>">
            <div class="question-header">
                <h4><?php printf(__('Question %s', 'elearning-quiz'), is_numeric($index) ? $index + 1 : 1); ?></h4>
                <button type="button" class="remove-question button-link-delete"><?php _e('Remove', 'elearning-quiz'); ?></button>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="question_type_<?php echo esc_attr($index); ?>"><?php _e('Question Type', 'elearning-quiz'); ?></label></th>
                    <td>
                        <select name="quiz_questions[<?php echo esc_attr($index); ?>][type]" id="question_type_<?php echo esc_attr($index); ?>" class="question-type-select">
                            <?php foreach ($question_types as $type => $label): ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($question['type'], $type); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="question_text_<?php echo esc_attr($index); ?>"><?php _e('Question', 'elearning-quiz'); ?></label></th>
                    <td>
                        <textarea name="quiz_questions[<?php echo esc_attr($index); ?>][question]" 
                                  id="question_text_<?php echo esc_attr($index); ?>" 
                                  rows="3" 
                                  class="large-text"><?php echo esc_textarea($question['question']); ?></textarea>
                    </td>
                </tr>
            </table>
            
            <div class="question-options" data-type="<?php echo esc_attr($question['type']); ?>">
                <?php $this->renderQuestionOptions($index, $question); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render question options based on type
     */
    private function renderQuestionOptions($index, $question): void {
        // Ensure we have proper default values
        $question = wp_parse_args($question, [
            'type' => 'multiple_choice',
            'options' => [''],
            'correct_answers' => [],
            'text_with_blanks' => '',
            'word_bank' => [''],
            'correct_answer' => 'true',
            'left_column' => [''],
            'right_column' => [''],
            'matches' => []
        ]);
        
        switch ($question['type']) {
            case 'multiple_choice':
                $this->renderMultipleChoiceOptions($index, $question);
                break;
            case 'fill_blanks':
                $this->renderFillBlanksOptions($index, $question);
                break;
            case 'true_false':
                $this->renderTrueFalseOptions($index, $question);
                break;
            case 'matching':
                $this->renderMatchingOptions($index, $question);
                break;
            default:
                echo '<p>' . __('Unknown question type', 'elearning-quiz') . '</p>';
                break;
        }
    }
    
    /**
     * Render multiple choice options
     */
    private function renderMultipleChoiceOptions($index, $question): void {
        $options = isset($question['options']) && is_array($question['options']) ? $question['options'] : [''];
        $correct_answers = isset($question['correct_answers']) && is_array($question['correct_answers']) ? $question['correct_answers'] : [];
        
        echo '<h5>' . __('Options', 'elearning-quiz') . '</h5>';
        echo '<div class="options-container">';
        
        foreach ($options as $opt_index => $option) {
            $is_correct = in_array($opt_index, $correct_answers);
            ?>
            <div class="option-row">
                <input type="text" 
                       name="quiz_questions[<?php echo esc_attr($index); ?>][options][<?php echo esc_attr($opt_index); ?>]" 
                       value="<?php echo esc_attr($option); ?>" 
                       placeholder="<?php _e('Option text', 'elearning-quiz'); ?>" 
                       class="regular-text" />
                <label>
                    <input type="checkbox" 
                           name="quiz_questions[<?php echo esc_attr($index); ?>][correct_answers][]" 
                           value="<?php echo esc_attr($opt_index); ?>" 
                           <?php checked($is_correct); ?> />
                    <?php _e('Correct', 'elearning-quiz'); ?>
                </label>
                <button type="button" class="remove-option button-link-delete"><?php _e('Remove', 'elearning-quiz'); ?></button>
            </div>
            <?php
        }
        
        echo '</div>';
        echo '<button type="button" class="add-option button">' . __('Add Option', 'elearning-quiz') . '</button>';
    }
    
    /**
     * Render fill in the blanks options
     */
    private function renderFillBlanksOptions($index, $question): void {
        $text_with_blanks = isset($question['text_with_blanks']) ? $question['text_with_blanks'] : '';
        $word_bank = isset($question['word_bank']) && is_array($question['word_bank']) ? $question['word_bank'] : [''];
        
        ?>
        <h5><?php _e('Text with Blanks', 'elearning-quiz'); ?></h5>
        <p class="description"><?php _e('Use {{blank}} to mark where blanks should appear.', 'elearning-quiz'); ?></p>
        <textarea name="quiz_questions[<?php echo esc_attr($index); ?>][text_with_blanks]" 
                  rows="4" 
                  class="large-text"><?php echo esc_textarea($text_with_blanks); ?></textarea>
        
        <h5><?php _e('Word Bank', 'elearning-quiz'); ?></h5>
        <div class="word-bank-container">
            <?php foreach ($word_bank as $word_index => $word): ?>
                <div class="word-row">
                    <input type="text" 
                           name="quiz_questions[<?php echo esc_attr($index); ?>][word_bank][<?php echo esc_attr($word_index); ?>]" 
                           value="<?php echo esc_attr($word); ?>" 
                           placeholder="<?php _e('Word', 'elearning-quiz'); ?>" 
                           class="regular-text" />
                    <button type="button" class="remove-word button-link-delete"><?php _e('Remove', 'elearning-quiz'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="add-word button"><?php _e('Add Word', 'elearning-quiz'); ?></button>
        <?php
    }
    
    /**
     * Render true/false options
     */
    private function renderTrueFalseOptions($index, $question): void {
        $correct_answer = isset($question['correct_answer']) ? $question['correct_answer'] : 'true';
        
        ?>
        <h5><?php _e('Correct Answer', 'elearning-quiz'); ?></h5>
        <label>
            <input type="radio" 
                   name="quiz_questions[<?php echo esc_attr($index); ?>][correct_answer]" 
                   value="true" 
                   <?php checked($correct_answer, 'true'); ?> />
            <?php _e('True', 'elearning-quiz'); ?>
        </label>
        <label>
            <input type="radio" 
                   name="quiz_questions[<?php echo esc_attr($index); ?>][correct_answer]" 
                   value="false" 
                   <?php checked($correct_answer, 'false'); ?> />
            <?php _e('False', 'elearning-quiz'); ?>
        </label>
        <?php
    }
    
    /**
     * Render matching options
     */
    private function renderMatchingOptions($index, $question): void {
        $left_column = isset($question['left_column']) && is_array($question['left_column']) ? $question['left_column'] : [''];
        $right_column = isset($question['right_column']) && is_array($question['right_column']) ? $question['right_column'] : [''];
        $matches = isset($question['matches']) && is_array($question['matches']) ? $question['matches'] : [];
        
        ?>
        <div class="matching-columns">
            <div class="left-column">
                <h5><?php _e('Left Column', 'elearning-quiz'); ?></h5>
                <div class="match-items-container">
                    <?php foreach ($left_column as $left_index => $left_item): ?>
                        <div class="match-item">
                            <input type="text" 
                                   name="quiz_questions[<?php echo esc_attr($index); ?>][left_column][<?php echo esc_attr($left_index); ?>]" 
                                   value="<?php echo esc_attr($left_item); ?>" 
                                   placeholder="<?php _e('Left item', 'elearning-quiz'); ?>" 
                                   class="regular-text" />
                            <button type="button" class="remove-left-item button-link-delete"><?php _e('Remove', 'elearning-quiz'); ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-left-item button"><?php _e('Add Left Item', 'elearning-quiz'); ?></button>
            </div>
            
            <div class="right-column">
                <h5><?php _e('Right Column', 'elearning-quiz'); ?></h5>
                <div class="match-items-container">
                    <?php foreach ($right_column as $right_index => $right_item): ?>
                        <div class="match-item">
                            <input type="text" 
                                   name="quiz_questions[<?php echo esc_attr($index); ?>][right_column][<?php echo esc_attr($right_index); ?>]" 
                                   value="<?php echo esc_attr($right_item); ?>" 
                                   placeholder="<?php _e('Right item', 'elearning-quiz'); ?>" 
                                   class="regular-text" />
                            <button type="button" class="remove-right-item button-link-delete"><?php _e('Remove', 'elearning-quiz'); ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-right-item button"><?php _e('Add Right Item', 'elearning-quiz'); ?></button>
            </div>
        </div>
        
        <h5><?php _e('Correct Matches', 'elearning-quiz'); ?></h5>
        <div class="matches-container">
            <?php foreach ($matches as $match_index => $match): ?>
                <div class="match-row">
                    <select name="quiz_questions[<?php echo esc_attr($index); ?>][matches][<?php echo esc_attr($match_index); ?>][left]" class="match-left-select">
                        <option value=""><?php _e('Select left item', 'elearning-quiz'); ?></option>
                        <?php foreach ($left_column as $left_index => $left_item): ?>
                            <option value="<?php echo esc_attr($left_index); ?>" <?php selected(isset($match['left']) ? $match['left'] : '', $left_index); ?>>
                                <?php echo esc_html($left_item); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <span><?php _e('matches with', 'elearning-quiz'); ?></span>
                    
                    <select name="quiz_questions[<?php echo esc_attr($index); ?>][matches][<?php echo esc_attr($match_index); ?>][right]" class="match-right-select">
                        <option value=""><?php _e('Select right item', 'elearning-quiz'); ?></option>
                        <?php foreach ($right_column as $right_index => $right_item): ?>
                            <option value="<?php echo esc_attr($right_index); ?>" <?php selected(isset($match['right']) ? $match['right'] : '', $right_index); ?>>
                                <?php echo esc_html($right_item); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="button" class="remove-match button-link-delete"><?php _e('Remove', 'elearning-quiz'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="add-match button"><?php _e('Add Match', 'elearning-quiz'); ?></button>
        <?php
    }
    
    /**
     * Save meta boxes data
     */
    public function saveMetaBoxes($post_id): void {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'elearning_lesson') {
            $this->saveLessonMeta($post_id);
        } elseif ($post_type === 'elearning_quiz') {
            $this->saveQuizMeta($post_id);
        }
    }
    
    /**
     * Save lesson meta data
     */
    private function saveLessonMeta($post_id): void {
        // Save lesson sections
        if (isset($_POST['lesson_sections_nonce']) && wp_verify_nonce($_POST['lesson_sections_nonce'], 'lesson_sections_nonce')) {
            if (isset($_POST['lesson_sections']) && is_array($_POST['lesson_sections'])) {
                $sections = array_map(function($section) {
                    return [
                        'title' => isset($section['title']) ? sanitize_text_field($section['title']) : '',
                        'content' => isset($section['content']) ? wp_kses_post($section['content']) : ''
                    ];
                }, $_POST['lesson_sections']);
                
                update_post_meta($post_id, '_lesson_sections', $sections);
            }
        }
        
        // Save associated quiz
        if (isset($_POST['lesson_quiz_nonce']) && wp_verify_nonce($_POST['lesson_quiz_nonce'], 'lesson_quiz_nonce')) {
            $old_quiz_id = get_post_meta($post_id, '_associated_quiz', true);
            $new_quiz_id = isset($_POST['associated_quiz']) ? intval($_POST['associated_quiz']) : '';
            
            // Update lesson → quiz association
            update_post_meta($post_id, '_associated_quiz', $new_quiz_id);
            
            // Remove old quiz → lesson association
            if ($old_quiz_id && $old_quiz_id != $new_quiz_id) {
                delete_post_meta($old_quiz_id, '_associated_lesson');
            }
            
            // Add new quiz → lesson association
            if ($new_quiz_id) {
                update_post_meta($new_quiz_id, '_associated_lesson', $post_id);
            }
        }
    }
    
    /**
     * Save quiz meta data - FIXED VERSION
     */
    private function saveQuizMeta($post_id): void {
        // Save quiz settings
        if (isset($_POST['quiz_settings_nonce']) && wp_verify_nonce($_POST['quiz_settings_nonce'], 'quiz_settings_nonce')) {
            $passing_score = isset($_POST['passing_score']) ? intval($_POST['passing_score']) : 70;
            $min_questions = isset($_POST['min_questions_to_show']) ? intval($_POST['min_questions_to_show']) : 5;
            $show_results = isset($_POST['show_results_immediately']) ? sanitize_text_field($_POST['show_results_immediately']) : 'yes';
            $time_limit = isset($_POST['time_limit']) ? intval($_POST['time_limit']) : 0;
            $randomize_questions = isset($_POST['randomize_questions']) ? sanitize_text_field($_POST['randomize_questions']) : 'no';
            $randomize_answers = isset($_POST['randomize_answers']) ? sanitize_text_field($_POST['randomize_answers']) : 'no';
            
            // Validate values
            $passing_score = max(0, min(100, $passing_score));
            $min_questions = max(1, $min_questions);
            $time_limit = max(0, $time_limit);
            
            update_post_meta($post_id, '_passing_score', $passing_score);
            update_post_meta($post_id, '_min_questions_to_show', $min_questions);
            update_post_meta($post_id, '_show_results_immediately', $show_results);
            update_post_meta($post_id, '_time_limit', $time_limit);
            update_post_meta($post_id, '_randomize_questions', $randomize_questions);
            update_post_meta($post_id, '_randomize_answers', $randomize_answers);
        }
        
        // Save associated lesson
        if (isset($_POST['quiz_lesson_nonce']) && wp_verify_nonce($_POST['quiz_lesson_nonce'], 'quiz_lesson_nonce')) {
            $old_lesson_id = get_post_meta($post_id, '_associated_lesson', true);
            $new_lesson_id = isset($_POST['associated_lesson']) ? intval($_POST['associated_lesson']) : '';
            
            // Update quiz → lesson association
            update_post_meta($post_id, '_associated_lesson', $new_lesson_id);
            
            // Remove old lesson → quiz association
            if ($old_lesson_id && $old_lesson_id != $new_lesson_id) {
                delete_post_meta($old_lesson_id, '_associated_quiz');
            }
            
            // Add new lesson → quiz association
            if ($new_lesson_id) {
                update_post_meta($new_lesson_id, '_associated_quiz', $post_id);
            }
        }
        
        // Save quiz questions with validation - FIXED VERSION
        if (isset($_POST['quiz_questions_nonce']) && wp_verify_nonce($_POST['quiz_questions_nonce'], 'quiz_questions_nonce')) {
            if (isset($_POST['quiz_questions']) && is_array($_POST['quiz_questions'])) {
                $questions = [];
                
                foreach ($_POST['quiz_questions'] as $question_data) {
                    // Skip empty questions
                    if (empty($question_data['question']) || empty($question_data['type'])) {
                        continue;
                    }
                    
                    // Validate question type
                    $allowed_types = ['multiple_choice', 'true_false', 'fill_blanks', 'matching'];
                    $question_type = sanitize_text_field($question_data['type']);
                    if (!in_array($question_type, $allowed_types)) {
                        $question_type = 'multiple_choice';
                    }
                    
                    $question = [
                        'type' => $question_type,
                        'question' => sanitize_textarea_field($question_data['question'])
                    ];
                    
                    // Sanitize question-specific data based on type
                    switch ($question['type']) {
                        case 'multiple_choice':
                            $options = [];
                            $correct_answers = [];
                            
                            if (isset($question_data['options']) && is_array($question_data['options'])) {
                                // Re-index the array to ensure sequential keys
                                $options_raw = array_values($question_data['options']);
                                foreach ($options_raw as $new_index => $option) {
                                    if (!empty(trim($option))) {
                                        $options[$new_index] = sanitize_text_field($option);
                                    }
                                }
                            }
                            
                            if (isset($question_data['correct_answers']) && is_array($question_data['correct_answers'])) {
                                foreach ($question_data['correct_answers'] as $answer) {
                                    // Check if this index exists in the re-indexed options
                                    $answer_int = intval($answer);
                                    // Find the new index for this answer
                                    if (isset($question_data['options'][$answer_int])) {
                                        $option_value = $question_data['options'][$answer_int];
                                        // Find this value in our re-indexed options
                                        $new_index = array_search(sanitize_text_field($option_value), $options);
                                        if ($new_index !== false) {
                                            $correct_answers[] = $new_index;
                                        }
                                    }
                                }
                            }
                            
                            // Validate we have at least 2 options and 1 correct answer
                            if (count($options) >= 2 && count($correct_answers) > 0) {
                                $question['options'] = $options;
                                $question['correct_answers'] = $correct_answers;
                            } else {
                                continue 2; // Skip invalid question
                            }
                            break;
                            
                        case 'fill_blanks':
                            $text_with_blanks = sanitize_textarea_field($question_data['text_with_blanks'] ?? '');
                            $word_bank = [];
                            
                            // Validate blanks exist
                            if (strpos($text_with_blanks, '{{blank}}') === false) {
                                continue 2; // Skip if no blanks
                            }
                            
                            if (isset($question_data['word_bank']) && is_array($question_data['word_bank'])) {
                                // Re-index the array to ensure sequential keys
                                $word_bank_raw = array_values($question_data['word_bank']);
                                foreach ($word_bank_raw as $word) {
                                    if (!empty(trim($word))) {
                                        $word_bank[] = sanitize_text_field($word);
                                    }
                                }
                            }
                            
                            // Validate word bank has enough words
                            $blank_count = substr_count($text_with_blanks, '{{blank}}');
                            if (count($word_bank) >= $blank_count) {
                                $question['text_with_blanks'] = $text_with_blanks;
                                $question['word_bank'] = $word_bank;
                            } else {
                                continue 2; // Skip invalid question
                            }
                            break;
                            
                        case 'true_false':
                            $question['correct_answer'] = sanitize_text_field($question_data['correct_answer'] ?? 'true');
                            if (!in_array($question['correct_answer'], ['true', 'false'])) {
                                $question['correct_answer'] = 'true';
                            }
                            break;
                            
                        case 'matching':
                            $left_column = [];
                            $right_column = [];
                            $matches = [];
                            
                            // Process left column - simpler approach
                            if (isset($question_data['left_column']) && is_array($question_data['left_column'])) {
                                foreach ($question_data['left_column'] as $item) {
                                    if (!empty(trim($item))) {
                                        $left_column[] = sanitize_text_field($item);
                                    }
                                }
                            }
                            
                            // Process right column - simpler approach
                            if (isset($question_data['right_column']) && is_array($question_data['right_column'])) {
                                foreach ($question_data['right_column'] as $item) {
                                    if (!empty(trim($item))) {
                                        $right_column[] = sanitize_text_field($item);
                                    }
                                }
                            }
                            
                            // Process matches - simpler approach
                            if (isset($question_data['matches']) && is_array($question_data['matches'])) {
                                foreach ($question_data['matches'] as $match) {
                                    if (isset($match['left']) && isset($match['right'])) {
                                        $left_idx = intval($match['left']);
                                        $right_idx = intval($match['right']);
                                        
                                        // Check if indices are valid
                                        if ($left_idx >= 0 && $left_idx < count($left_column) &&
                                            $right_idx >= 0 && $right_idx < count($right_column)) {
                                            $matches[] = [
                                                'left' => $left_idx,
                                                'right' => $right_idx
                                            ];
                                        }
                                    }
                                }
                            }
                            
                            // Validate we have items and matches
                            if (count($left_column) >= 2 && count($right_column) >= 2 && count($matches) > 0) {
                                $question['left_column'] = $left_column;
                                $question['right_column'] = $right_column;
                                $question['matches'] = $matches;
                            } else {
                                continue 2; // Skip invalid question
                            }
                            break;
                    }
                    
                    $questions[] = $question;
                }
                
                // Only save if we have valid questions
                if (!empty($questions)) {
                    update_post_meta($post_id, '_quiz_questions', $questions);
                }
            }
        }
    }
    
    /**
     * Add custom columns to lesson list
     */
    public function addLessonColumns($columns): array {
        $new_columns = [];
        
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            
            if ($key === 'title') {
                $new_columns['sections_count'] = __('Sections', 'elearning-quiz');
                $new_columns['associated_quiz'] = __('Quiz', 'elearning-quiz');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Add custom columns to quiz list
     */
    public function addQuizColumns($columns): array {
        $new_columns = [];
        
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            
            if ($key === 'title') {
                $new_columns['associated_lesson'] = __('Lesson', 'elearning-quiz');
                $new_columns['questions_count'] = __('Questions', 'elearning-quiz');
                $new_columns['passing_score'] = __('Passing Score', 'elearning-quiz');
                $new_columns['attempts'] = __('Attempts', 'elearning-quiz');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate lesson custom columns
     */
    public function populateLessonColumns($column, $post_id): void {
        switch ($column) {
            case 'sections_count':
                $sections = get_post_meta($post_id, '_lesson_sections', true) ?: [];
                echo count($sections);
                break;
                
            case 'associated_quiz':
                $quiz_id = get_post_meta($post_id, '_associated_quiz', true);
                if ($quiz_id) {
                    $quiz = get_post($quiz_id);
                    if ($quiz && $quiz->post_status !== 'trash') {
                        $status_text = $quiz->post_status === 'publish' ? '' : ' (' . ucfirst($quiz->post_status) . ')';
                        echo '<a href="' . get_edit_post_link($quiz_id) . '">' . esc_html($quiz->post_title) . $status_text . '</a>';
                    } else {
                        echo '<span style="color: #d63638;">' . __('Quiz not found', 'elearning-quiz') . '</span>';
                        // Clean up broken association
                        delete_post_meta($post_id, '_associated_quiz');
                    }
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Populate quiz custom columns
     */
    public function populateQuizColumns($column, $post_id): void {
        global $wpdb;
        
        switch ($column) {
            case 'associated_lesson':
                $lesson_id = get_post_meta($post_id, '_associated_lesson', true);
                if ($lesson_id) {
                    $lesson = get_post($lesson_id);
                    if ($lesson && $lesson->post_status !== 'trash') {
                        $status_text = $lesson->post_status === 'publish' ? '' : ' (' . ucfirst($lesson->post_status) . ')';
                        echo '<a href="' . get_edit_post_link($lesson_id) . '">' . esc_html($lesson->post_title) . $status_text . '</a>';
                    } else {
                        echo '<span style="color: #d63638;">' . __('Lesson not found', 'elearning-quiz') . '</span>';
                        // Clean up broken association
                        delete_post_meta($post_id, '_associated_lesson');
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'questions_count':
                $questions = get_post_meta($post_id, '_quiz_questions', true) ?: [];
                echo count($questions);
                break;
                
            case 'passing_score':
                $passing_score = get_post_meta($post_id, '_passing_score', true) ?: 70;
                echo $passing_score . '%';
                break;
                
            case 'attempts':
                $table_name = $wpdb->prefix . 'elearning_quiz_attempts';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE quiz_id = %d",
                    $post_id
                ));
                echo intval($count);
                break;
        }
    }
}