<?php
/**
 * Lesson Feed Widget
 * 
 * Widget to display lesson feed in sidebars
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_Lesson_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'elearning_lesson_feed',
            __('E-Learning Lesson Feed', 'elearning-quiz'),
            [
                'description' => __('Display a feed of lessons', 'elearning-quiz'),
                'customize_selective_refresh' => true,
            ]
        );
    }
    
    /**
     * Widget output
     */
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Latest Lessons', 'elearning-quiz');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_progress = !empty($instance['show_progress']);
        $show_thumbnail = !empty($instance['show_thumbnail']);
        $category = !empty($instance['category']) ? $instance['category'] : '';
        
        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }
        
        // Query lessons
        $query_args = [
            'post_type' => 'elearning_lesson',
            'posts_per_page' => $number,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        if ($category) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'lesson_category',
                'field' => 'term_id',
                'terms' => $category
            ]];
        }
        
        $lessons = new WP_Query($query_args);
        
        if ($lessons->have_posts()): ?>
            <ul class="elearning-widget-lessons">
                <?php while ($lessons->have_posts()): $lessons->the_post(); ?>
                    <?php
                    $lesson_id = get_the_ID();
                    $user_session = ELearning_Database::getOrCreateUserSession();
                    $progress = $this->getLessonProgress($lesson_id, $user_session);
                    ?>
                    <li class="widget-lesson-item">
                        <?php if ($show_thumbnail && has_post_thumbnail()): ?>
                            <div class="widget-lesson-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('thumbnail'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="widget-lesson-content">
                            <h4 class="widget-lesson-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            
                            <?php if ($show_progress): ?>
                                <div class="widget-lesson-progress">
                                    <div class="progress-bar mini">
                                        <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p><?php _e('No lessons found.', 'elearning-quiz'); ?></p>
        <?php endif;
        
        wp_reset_postdata();
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget settings form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Latest Lessons', 'elearning-quiz');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_progress = !empty($instance['show_progress']);
        $show_thumbnail = !empty($instance['show_thumbnail']);
        $category = !empty($instance['category']) ? $instance['category'] : '';
        
        // Get lesson categories
        $categories = get_terms([
            'taxonomy' => 'lesson_category',
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
            <label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of lessons to show:', 'elearning-quiz'); ?></label>
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
            <input class="checkbox" type="checkbox" <?php checked($show_progress); ?> 
                   id="<?php echo $this->get_field_id('show_progress'); ?>" 
                   name="<?php echo $this->get_field_name('show_progress'); ?>">
            <label for="<?php echo $this->get_field_id('show_progress'); ?>">
                <?php _e('Show progress bar', 'elearning-quiz'); ?>
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
        $instance['show_progress'] = !empty($new_instance['show_progress']);
        $instance['show_thumbnail'] = !empty($new_instance['show_thumbnail']);
        $instance['category'] = (!empty($new_instance['category'])) ? intval($new_instance['category']) : '';
        
        return $instance;
    }
    
    /**
     * Get lesson progress
     */
    private function getLessonProgress($lesson_id, $user_session) {
        $sections = get_post_meta($lesson_id, '_lesson_sections', true) ?: [];
        if (empty($sections)) {
            return 0;
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
}

// Register widget
add_action('widgets_init', function() {
    register_widget('ELearning_Lesson_Widget');
});