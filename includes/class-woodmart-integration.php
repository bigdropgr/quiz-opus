<?php
/**
 * WoodMart Theme Integration Class
 * 
 * Dynamically generates CSS based on WoodMart theme settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_WoodMart_Integration {
    
    private static $instance = null;
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into WordPress to inject dynamic CSS
        add_action('wp_head', [$this, 'injectDynamicCSS'], 100);
        add_action('wp_enqueue_scripts', [$this, 'enqueueIntegrationStyles'], 20);
        
        // Hook into WoodMart's dynamic CSS generation if available
        add_filter('woodmart_dynamic_css', [$this, 'addELearningCSS']);
        
        // Support for WoodMart's color scheme changes
        add_action('customize_save_after', [$this, 'clearCSSCache']);
    }
    
    /**
     * Check if WoodMart theme is active
     */
    public function isWoodMartActive(): bool {
        $theme = wp_get_theme();
        return $theme->get('Name') === 'WoodMart' || $theme->get('Template') === 'woodmart';
    }
    
    /**
     * Get WoodMart theme settings
     */
    private function getWoodMartSettings(): array {
        $settings = [];
        
        // Get WoodMart options (these are the actual option names used by WoodMart)
        $settings['primary_color'] = get_theme_mod('primary-color', '#83b735');
        $settings['secondary_color'] = get_theme_mod('secondary-color', '#fbbc34');
        $settings['link_color'] = get_theme_mod('link-color', '#333333');
        $settings['link_color_hover'] = get_theme_mod('link-color-hover', '#777777');
        
        // Typography settings
        $settings['text_font_family'] = get_theme_mod('text-font', 'Lato');
        $settings['text_font_size'] = get_theme_mod('text-font-size', '14');
        $settings['text_font_weight'] = get_theme_mod('text-font-weight', '400');
        $settings['text_color'] = get_theme_mod('text-color', '#777777');
        
        // Title typography
        $settings['title_font_family'] = get_theme_mod('title-font', 'Poppins');
        $settings['title_font_weight'] = get_theme_mod('title-font-weight', '600');
        $settings['title_color'] = get_theme_mod('title-color', '#2d2a2a');
        
        // Button settings
        $settings['btn_color'] = get_theme_mod('btns-color', '#FFFFFF');
        $settings['btn_color_hover'] = get_theme_mod('btns-color-hover', '#FFFFFF');
        $settings['btn_bg'] = get_theme_mod('btns-bg', '#83b735');
        $settings['btn_bg_hover'] = get_theme_mod('btns-bg-hover', '#6e9a2c');
        $settings['btn_border_radius'] = get_theme_mod('btns-border-radius', '0');
        $settings['btn_padding_v'] = get_theme_mod('btns-padding-vertical', '12');
        $settings['btn_padding_h'] = get_theme_mod('btns-padding-horizontal', '25');
        
        // Form settings
        $settings['form_border_color'] = get_theme_mod('form-border-color', '#E8E8E8');
        $settings['form_bg'] = get_theme_mod('form-bg', '#FFFFFF');
        $settings['form_color'] = get_theme_mod('form-color', '#777777');
        
        // Border radius
        $settings['border_radius'] = get_theme_mod('border-radius', '9');
        
        // Colors for states
        $settings['success_color'] = get_theme_mod('success-color', '#459647');
        $settings['warning_color'] = get_theme_mod('warning-color', '#E0AC1B');
        $settings['error_color'] = get_theme_mod('error-color', '#E24B4B');
        
        // Dark mode colors (if enabled)
        $settings['dark_bg'] = get_theme_mod('dark-main-bgcolor', '#1e1e1e');
        $settings['dark_text_color'] = get_theme_mod('dark-text-color', '#CCCCCC');
        
        return $settings;
    }
    
    /**
     * Generate dynamic CSS based on WoodMart settings
     */
    public function generateDynamicCSS(): string {
        if (!$this->isWoodMartActive()) {
            return '';
        }
        
        $settings = $this->getWoodMartSettings();
        
        ob_start();
        ?>
        <style id="elearning-woodmart-dynamic-css">
        /* E-Learning Dynamic CSS - WoodMart Integration */
        :root {
            /* Map WoodMart settings to CSS variables */
            --el-primary-color: <?php echo esc_attr($settings['primary_color']); ?>;
            --el-secondary-color: <?php echo esc_attr($settings['secondary_color']); ?>;
            --el-text-color: <?php echo esc_attr($settings['text_color']); ?>;
            --el-title-color: <?php echo esc_attr($settings['title_color']); ?>;
            --el-link-color: <?php echo esc_attr($settings['link_color']); ?>;
            --el-link-hover-color: <?php echo esc_attr($settings['link_color_hover']); ?>;
            --el-success-color: <?php echo esc_attr($settings['success_color']); ?>;
            --el-warning-color: <?php echo esc_attr($settings['warning_color']); ?>;
            --el-error-color: <?php echo esc_attr($settings['error_color']); ?>;
            --el-border-radius: <?php echo esc_attr($settings['border_radius']); ?>px;
            --el-btn-bg: <?php echo esc_attr($settings['btn_bg']); ?>;
            --el-btn-bg-hover: <?php echo esc_attr($settings['btn_bg_hover']); ?>;
            --el-btn-color: <?php echo esc_attr($settings['btn_color']); ?>;
            --el-btn-color-hover: <?php echo esc_attr($settings['btn_color_hover']); ?>;
            --el-form-border-color: <?php echo esc_attr($settings['form_border_color']); ?>;
            --el-form-bg: <?php echo esc_attr($settings['form_bg']); ?>;
        }
        
        /* Override plugin styles with WoodMart theme settings */
        .elearning-quiz-container,
        .elearning-lesson-container {
            font-family: <?php echo esc_attr($settings['text_font_family']); ?>, -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: <?php echo esc_attr($settings['text_font_size']); ?>px;
            font-weight: <?php echo esc_attr($settings['text_font_weight']); ?>;
            color: <?php echo esc_attr($settings['text_color']); ?>;
        }
        
        /* Titles */
        .elearning-quiz-container h1,
        .elearning-quiz-container h2,
        .elearning-quiz-container h3,
        .elearning-quiz-container h4,
        .elearning-lesson-container h1,
        .elearning-lesson-container h2,
        .elearning-lesson-container h3,
        .elearning-lesson-container h4,
        .question-title,
        .section-title {
            font-family: <?php echo esc_attr($settings['title_font_family']); ?>, -apple-system, BlinkMacSystemFont, sans-serif;
            font-weight: <?php echo esc_attr($settings['title_font_weight']); ?>;
            color: <?php echo esc_attr($settings['title_color']); ?>;
        }
        
        /* Buttons */
        .elearning-quiz-container button,
        .elearning-quiz-container .button,
        .elearning-lesson-container button,
        .elearning-lesson-container .button {
            background-color: <?php echo esc_attr($settings['btn_bg']); ?>;
            color: <?php echo esc_attr($settings['btn_color']); ?>;
            border-radius: <?php echo esc_attr($settings['btn_border_radius']); ?>px;
            padding: <?php echo esc_attr($settings['btn_padding_v']); ?>px <?php echo esc_attr($settings['btn_padding_h']); ?>px;
            font-family: <?php echo esc_attr($settings['text_font_family']); ?>, -apple-system, BlinkMacSystemFont, sans-serif;
            transition: all .25s ease;
        }
        
        .elearning-quiz-container button:hover,
        .elearning-quiz-container .button:hover,
        .elearning-lesson-container button:hover,
        .elearning-lesson-container .button:hover {
            background-color: <?php echo esc_attr($settings['btn_bg_hover']); ?>;
            color: <?php echo esc_attr($settings['btn_color_hover']); ?>;
        }
        
        /* Form elements */
        .elearning-quiz-container input[type="text"],
        .elearning-quiz-container input[type="number"],
        .elearning-quiz-container select,
        .elearning-quiz-container textarea {
            border-color: <?php echo esc_attr($settings['form_border_color']); ?>;
            background-color: <?php echo esc_attr($settings['form_bg']); ?>;
            color: <?php echo esc_attr($settings['form_color']); ?>;
            border-radius: <?php echo esc_attr($settings['border_radius']); ?>px;
        }
        
        /* Cards and containers */
        .quiz-question,
        .lesson-section,
        .quiz-results,
        .elearning-quiz-intro {
            border-radius: <?php echo esc_attr($settings['border_radius']); ?>px;
        }
        
        /* Option labels */
        .option-label {
            border-radius: <?php echo esc_attr($settings['border_radius']); ?>px;
        }
        
        /* Links */
        .elearning-quiz-container a,
        .elearning-lesson-container a {
            color: <?php echo esc_attr($settings['link_color']); ?>;
        }
        
        .elearning-quiz-container a:hover,
        .elearning-lesson-container a:hover {
            color: <?php echo esc_attr($settings['link_color_hover']); ?>;
        }
        
        /* Success states */
        .quiz-results.passed,
        .lesson-section.completed,
        .elearning-quiz-passed {
            border-color: <?php echo esc_attr($settings['success_color']); ?>;
        }
        
        .quiz-success-icon,
        .fa-check-circle {
            color: <?php echo esc_attr($settings['success_color']); ?>;
        }
        
        /* Error states */
        .quiz-results.failed {
            border-color: <?php echo esc_attr($settings['error_color']); ?>;
        }
        
        .quiz-submit-btn {
            background-color: <?php echo esc_attr($settings['error_color']); ?>;
        }
        
        /* Warning states */
        .quiz-timer.warning {
            border-color: <?php echo esc_attr($settings['warning_color']); ?>;
            color: <?php echo esc_attr($settings['warning_color']); ?>;
        }
        
        /* Progress bars */
        .progress-fill {
            background-color: <?php echo esc_attr($settings['primary_color']); ?>;
        }
        
        /* Focus states */
        .elearning-quiz-container input:focus,
        .elearning-quiz-container select:focus,
        .elearning-quiz-container textarea:focus,
        .elearning-quiz-container button:focus {
            outline-color: <?php echo esc_attr($settings['primary_color']); ?>;
            border-color: <?php echo esc_attr($settings['primary_color']); ?>;
        }
        
        /* Dark mode support */
        body.wd-dark .elearning-quiz-container,
        body.wd-dark .elearning-lesson-container {
            background-color: <?php echo esc_attr($settings['dark_bg']); ?>;
            color: <?php echo esc_attr($settings['dark_text_color']); ?>;
        }
        
        /* WoodMart specific classes compatibility */
        .elearning-quiz-container .wd-btn,
        .elearning-lesson-container .wd-btn {
            /* Inherit WoodMart button styles */
        }
        
        /* Responsive adjustments based on WoodMart breakpoints */
        @media (max-width: 1024px) {
            .elearning-quiz-container,
            .elearning-lesson-container {
                padding: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .elearning-quiz-container,
            .elearning-lesson-container {
                padding: 10px;
            }
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Inject dynamic CSS into head
     */
    public function injectDynamicCSS(): void {
        echo $this->generateDynamicCSS();
    }
    
    /**
     * Enqueue integration styles
     */
    public function enqueueIntegrationStyles(): void {
        if (!$this->isWoodMartActive()) {
            return;
        }
        
        // Only load on relevant pages
        if (!is_singular(['elearning_lesson', 'elearning_quiz']) && !$this->hasELearningShortcode()) {
            return;
        }
        
        // Dequeue the original frontend CSS
        wp_dequeue_style('elearning-quiz-frontend');
        
        // Enqueue our integrated version
        wp_enqueue_style(
            'elearning-woodmart-integrated',
            ELEARNING_QUIZ_PLUGIN_URL . 'assets/css/woodmart-integrated-frontend.css',
            ['woodmart-style'], // Make it dependent on WoodMart's main style
            ELEARNING_QUIZ_VERSION
        );
    }
    
    /**
     * Add CSS to WoodMart's dynamic CSS system
     */
    public function addELearningCSS($css): string {
        if (!is_singular(['elearning_lesson', 'elearning_quiz']) && !$this->hasELearningShortcode()) {
            return $css;
        }
        
        // Add our dynamic CSS to WoodMart's system
        $css .= $this->generateDynamicCSS();
        
        return $css;
    }
    
    /**
     * Clear CSS cache when customizer saves
     */
    public function clearCSSCache(): void {
        // Clear any cached CSS if you implement caching
        delete_transient('elearning_woodmart_css_cache');
    }
    
    /**
     * Check if current page has e-learning shortcode
     */
    private function hasELearningShortcode(): bool {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        $shortcodes = ['loan_calculator', 'display_lesson', 'display_quiz', 'quiz_stats', 'user_progress'];
        
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get custom CSS for specific WoodMart presets
     */
    public function getPresetCSS($preset_name): string {
        $preset_css = '';
        
        switch ($preset_name) {
            case 'furniture':
                $preset_css = '
                    .elearning-quiz-container button {
                        text-transform: uppercase;
                        letter-spacing: 1px;
                    }
                ';
                break;
                
            case 'electronics':
                $preset_css = '
                    .quiz-question,
                    .lesson-section {
                        border: 1px solid #e0e0e0;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    }
                ';
                break;
                
            case 'fashion':
                $preset_css = '
                    .elearning-quiz-container,
                    .elearning-lesson-container {
                        font-weight: 300;
                    }
                    .question-title,
                    .section-title {
                        font-weight: 400;
                        letter-spacing: 0.5px;
                    }
                ';
                break;
        }
        
        return $preset_css;
    }
}

// Initialize the integration
add_action('init', function() {
    ELearning_WoodMart_Integration::getInstance();
});