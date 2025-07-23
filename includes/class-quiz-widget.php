<?php
/**
 * Quiz Feed Widget
 * 
 * Widget to display quiz feed in sidebars
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_Quiz_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'elearning_quiz_feed',
            __('E-Learning Quiz Feed', 'elearning-quiz'),
            [
                'description' => __('Display a feed of quizzes', 'elearning-quiz'),
                'customize_selective_refresh' => true,
            ]
        );
    }
    
    /**
     * Widget output
     */
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Latest Quizzes', 'elearning-quiz');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_stats = !empty($instance['show_stats']);
        $show_thumbnail = !empty($instance['show_thumbnail']);
        $category = !empty($instance['category']) ? $instance['category'] : '';
        
        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }
        
        // Query quizzes
        $query_args = [
            'post_type' => 'elearning_quiz',
            'posts_per_page' => $number,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        if ($category) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'quiz_category',
                'field' => 'term_id',
                'terms' => $category
            ]];
        }
        
        $quizzes = new WP_Query($query_args);
        
        if ($quizzes->have_posts()): ?>
            <ul class="elearning-widget-quizzes">
                <?php while ($quizzes->have_posts()): $quizzes->the_post(); ?>
                    <?php
                    $quiz_id = get_the_ID();
                    $questions = get_post_meta($quiz_id, '_quiz_questions', true) ?: [];
                    $passing_score = get_post_meta($quiz_id, '_passing_score', true) ?: 70;
                    
                    // Get user attempts
                    $user_session = ELearning_Database::getOrCreateUserSession();
                    $attempts = ELearning_Database::getUserQuizAttempts($user_session, $quiz_id);
                    $has_passed = false;
                    
                    foreach ($attempts as $attempt) {
                        if ($attempt['passed'] == 1) {
                            $has_passed = true;
                            break;
                        }
                    }
                    ?>
                    <li class="widget-quiz-item <?php echo $has_passed ? 'passed' : ''; ?>">
                        <?php if ($show_thumbnail && has_post_thumbnail()): ?>
                            <div class="widget-quiz-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('thumbnail'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="widget-quiz-content">
                            <h4 class="widget-quiz-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            
                            <?php if ($show_stats): ?>
                                <div class="widget-quiz-meta">
                                    <span class="questions"><?php echo count($questions); ?> <?php _e('questions', 'elearning-quiz'); ?></span>
                                    <?php if ($has_passed): ?>
                                        <span class="status passed"><?php _e('Passed', 'elearning-quiz'); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p><?php _e('No quizzes found.', 'elearning-quiz'); ?></p>
        <?php endif;
        
        wp_reset_postdata();
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget settings form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Latest Quizzes', 'elearning-quiz');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_stats = !empty($instance['show_stats']);
        $show_thumbnail = !empty($instance['show_thumbnail']);
        $category = !empty($instance['category']) ? $instance['category'] : '';
        
        // Get quiz categories
        $categories = get_terms([
            'taxonomy' => 'quiz_category',
            'hide_empty' => false
        ]);
        ?>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'elearning-quiz'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of quizzes to show:', 'elearning-quiz'); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('number'); ?>" 
                   name="<?php echo $this->get_field_name('number'); ?>" 
                   type="number" step="1" min="1" value="<?php echo $number; ?>" size="3">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Category:', 'elearning-quiz'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('category'); ?>" 
                    name="<?php echo $this->get_field_name('category'); ?>">
                <option value=""><?php _e('All Categories', 'elearning-quiz'); ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat->term_id; ?>" <?php selected($category, $cat->term_id); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_stats); ?> 
                   id="<?php echo $this->get_field_id('show_stats'); ?>" 
                   name="<?php echo $this->get_field_name('show_stats'); ?>">
            <label for="<?php echo $this->get_field_id('show_stats'); ?>">
                <?php _e('Show quiz statistics', 'elearning-quiz'); ?>
            </label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_thumbnail); ?> 
                   id="<?php echo $this->get_field_id('show_thumbnail'); ?>" 
                   name="<?php echo $this->get_field_name('show_thumbnail'); ?>">
            <label for="<?php echo $this->get_field_id('show_thumbnail'); ?>">
                <?php _e('Show thumbnail', 'elearning-quiz'); ?>
            </label>
        </p>
        <?php
    }
    
    /**
     * Save widget settings
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['number'] = (!empty($new_instance['number'])) ? absint($new_instance['number']) : 5;
        $instance['show_stats'] = !empty($new_instance['show_stats']);
        $instance['show_thumbnail'] = !empty($new_instance['show_thumbnail']);
        $instance['category'] = (!empty($new_instance['category'])) ? intval($new_instance['category']) : '';
        
        return $instance;
    }
}

// Register widget
add_action('widgets_init', function() {
    register_widget('ELearning_Quiz_Widget');
});