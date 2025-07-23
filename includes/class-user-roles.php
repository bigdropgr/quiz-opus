<?php
/**
 * User Roles Class
 * 
 * Simplified user roles and capabilities for the e-learning system
 * Focus on essential roles: Administrator, Content Editor, and standard WordPress roles
 */

if (!defined('ABSPATH')) {
    exit;
}

class ELearning_User_Roles {
    
    public function __construct() {
        add_action('init', [$this, 'addCapabilitiesToExistingRoles']);
    }
    
    /**
     * Add custom user roles
     */
    public static function addRoles(): void {
        // Content Editor role - can create and edit lessons and quizzes
        add_role('elearning_content_editor', __('Content Editor', 'elearning-quiz'), [
            // Basic WordPress capabilities
            'read' => true,
            'upload_files' => true,
            'edit_files' => false,
            'unfiltered_html' => false,
            
            // Custom lesson capabilities
            'edit_elearning_lessons' => true,
            'edit_others_elearning_lessons' => false, // Can only edit own lessons
            'edit_published_elearning_lessons' => true,
            'edit_private_elearning_lessons' => true,
            'publish_elearning_lessons' => true,
            'read_elearning_lessons' => true,
            'read_private_elearning_lessons' => false,
            'delete_elearning_lessons' => true,
            'delete_others_elearning_lessons' => false,
            'delete_published_elearning_lessons' => true,
            'delete_private_elearning_lessons' => true,
            
            // Custom quiz capabilities
            'edit_elearning_quizzes' => true,
            'edit_others_elearning_quizzes' => false, // Can only edit own quizzes
            'edit_published_elearning_quizzes' => true,
            'edit_private_elearning_quizzes' => true,
            'publish_elearning_quizzes' => true,
            'read_elearning_quizzes' => true,
            'read_private_elearning_quizzes' => false,
            'delete_elearning_quizzes' => true,
            'delete_others_elearning_quizzes' => false,
            'delete_published_elearning_quizzes' => true,
            'delete_private_elearning_quizzes' => true,
            
            // Taxonomy capabilities
            'assign_quiz_categories' => true,
            'assign_lesson_categories' => true,
        ]);
    }
    
    /**
     * Remove custom user roles
     */
    public static function removeRoles(): void {
        remove_role('elearning_content_editor');
    }
    
    /**
     * Add capabilities to existing WordPress roles
     */
    public function addCapabilitiesToExistingRoles(): void {
        // Administrator gets all capabilities
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $this->addAllCapabilitiesToRole($admin_role);
        }
        
        // Editor gets content management capabilities
        $editor_role = get_role('editor');
        if ($editor_role) {
            $this->addEditorCapabilities($editor_role);
        }
        
        // Author gets limited capabilities for own content
        $author_role = get_role('author');
        if ($author_role) {
            $this->addAuthorCapabilities($author_role);
        }
    }
    
    /**
     * Add all e-learning capabilities to administrator role
     */
    private function addAllCapabilitiesToRole($role): void {
        $capabilities = [
            // Lesson capabilities
            'edit_elearning_lessons',
            'edit_others_elearning_lessons',
            'edit_published_elearning_lessons',
            'edit_private_elearning_lessons',
            'publish_elearning_lessons',
            'read_elearning_lessons',
            'read_private_elearning_lessons',
            'delete_elearning_lessons',
            'delete_others_elearning_lessons',
            'delete_published_elearning_lessons',
            'delete_private_elearning_lessons',
            
            // Quiz capabilities
            'edit_elearning_quizzes',
            'edit_others_elearning_quizzes',
            'edit_published_elearning_quizzes',
            'edit_private_elearning_quizzes',
            'publish_elearning_quizzes',
            'read_elearning_quizzes',
            'read_private_elearning_quizzes',
            'delete_elearning_quizzes',
            'delete_others_elearning_quizzes',
            'delete_published_elearning_quizzes',
            'delete_private_elearning_quizzes',
            
            // Analytics and management capabilities
            'view_elearning_analytics',
            'export_elearning_data',
            'manage_elearning_settings',
            
            // Taxonomy capabilities
            'manage_quiz_categories',
            'manage_lesson_categories',
            'edit_quiz_categories',
            'edit_lesson_categories',
            'delete_quiz_categories',
            'delete_lesson_categories',
            'assign_quiz_categories',
            'assign_lesson_categories',
        ];
        
        foreach ($capabilities as $capability) {
            $role->add_cap($capability);
        }
    }
    
    /**
     * Add editor-level capabilities (can manage all content but not settings)
     */
    private function addEditorCapabilities($role): void {
        $capabilities = [
            // Lesson capabilities (can edit others' content)
            'edit_elearning_lessons',
            'edit_others_elearning_lessons',
            'edit_published_elearning_lessons',
            'edit_private_elearning_lessons',
            'publish_elearning_lessons',
            'read_elearning_lessons',
            'read_private_elearning_lessons',
            'delete_elearning_lessons',
            'delete_published_elearning_lessons',
            'delete_private_elearning_lessons',
            // Note: No delete_others_elearning_lessons for safety
            
            // Quiz capabilities (can edit others' content)
            'edit_elearning_quizzes',
            'edit_others_elearning_quizzes',
            'edit_published_elearning_quizzes',
            'edit_private_elearning_quizzes',
            'publish_elearning_quizzes',
            'read_elearning_quizzes',
            'read_private_elearning_quizzes',
            'delete_elearning_quizzes',
            'delete_published_elearning_quizzes',
            'delete_private_elearning_quizzes',
            // Note: No delete_others_elearning_quizzes for safety
            
            // Limited analytics (view only)
            'view_elearning_analytics',
            'export_elearning_data',
            
            // Taxonomy capabilities
            'manage_quiz_categories',
            'manage_lesson_categories',
            'edit_quiz_categories',
            'edit_lesson_categories',
            'delete_quiz_categories',
            'delete_lesson_categories',
            'assign_quiz_categories',
            'assign_lesson_categories',
        ];
        
        foreach ($capabilities as $capability) {
            $role->add_cap($capability);
        }
    }
    
    /**
     * Add author-level capabilities (own content only)
     */
    private function addAuthorCapabilities($role): void {
        $capabilities = [
            // Lesson capabilities (own content only)
            'edit_elearning_lessons',
            'edit_published_elearning_lessons',
            'publish_elearning_lessons',
            'read_elearning_lessons',
            'delete_elearning_lessons',
            'delete_published_elearning_lessons',
            
            // Quiz capabilities (own content only)
            'edit_elearning_quizzes',
            'edit_published_elearning_quizzes',
            'publish_elearning_quizzes',
            'read_elearning_quizzes',
            'delete_elearning_quizzes',
            'delete_published_elearning_quizzes',
            
            // Taxonomy capabilities (assign only)
            'assign_quiz_categories',
            'assign_lesson_categories',
        ];
        
        foreach ($capabilities as $capability) {
            $role->add_cap($capability);
        }
    }
    
    /**
     * Check if current user can manage e-learning content
     */
    public static function canManageContent(): bool {
        return current_user_can('edit_elearning_lessons') || current_user_can('edit_elearning_quizzes');
    }
    
    /**
     * Check if current user can view analytics
     */
    public static function canViewAnalytics(): bool {
        return current_user_can('view_elearning_analytics');
    }
    
    /**
     * Check if current user can export data
     */
    public static function canExportData(): bool {
        return current_user_can('export_elearning_data');
    }
    
    /**
     * Check if current user can manage settings
     */
    public static function canManageSettings(): bool {
        return current_user_can('manage_elearning_settings');
    }
    
    /**
     * Get user's allowed post types
     */
    public static function getAllowedPostTypes(): array {
        $allowed = [];
        
        if (current_user_can('edit_elearning_lessons')) {
            $allowed[] = 'elearning_lesson';
        }
        
        if (current_user_can('edit_elearning_quizzes')) {
            $allowed[] = 'elearning_quiz';
        }
        
        return $allowed;
    }
    
    /**
     * Get role display name
     */
    public static function getRoleDisplayName($role_slug): string {
        $role_names = [
            'elearning_content_editor' => __('Content Editor', 'elearning-quiz'),
        ];
        
        return $role_names[$role_slug] ?? ucfirst(str_replace('_', ' ', $role_slug));
    }
    
    /**
     * Check if user has any e-learning role
     */
    public static function hasELearningRole($user_id = null): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $elearning_roles = ['elearning_content_editor'];
        $user_roles = $user->roles;
        
        return !empty(array_intersect($elearning_roles, $user_roles)) || 
               in_array('administrator', $user_roles) || 
               in_array('editor', $user_roles);
    }
    
    /**
     * Get capabilities for a specific content type
     */
    public static function getContentTypeCapabilities($content_type): array {
        $capabilities = [
            'elearning_lesson' => [
                'edit_posts' => 'edit_elearning_lessons',
                'edit_others_posts' => 'edit_others_elearning_lessons',
                'edit_published_posts' => 'edit_published_elearning_lessons',
                'edit_private_posts' => 'edit_private_elearning_lessons',
                'publish_posts' => 'publish_elearning_lessons',
                'read_private_posts' => 'read_private_elearning_lessons',
                'delete_posts' => 'delete_elearning_lessons',
                'delete_others_posts' => 'delete_others_elearning_lessons',
                'delete_published_posts' => 'delete_published_elearning_lessons',
                'delete_private_posts' => 'delete_private_elearning_lessons',
            ],
            'elearning_quiz' => [
                'edit_posts' => 'edit_elearning_quizzes',
                'edit_others_posts' => 'edit_others_elearning_quizzes',
                'edit_published_posts' => 'edit_published_elearning_quizzes',
                'edit_private_posts' => 'edit_private_elearning_quizzes',
                'publish_posts' => 'publish_elearning_quizzes',
                'read_private_posts' => 'read_private_elearning_quizzes',
                'delete_posts' => 'delete_elearning_quizzes',
                'delete_others_posts' => 'delete_others_elearning_quizzes',
                'delete_published_posts' => 'delete_published_elearning_quizzes',
                'delete_private_posts' => 'delete_private_elearning_quizzes',
            ]
        ];
        
        return $capabilities[$content_type] ?? [];
    }
    
    /**
     * Map meta capabilities for custom post types
     */
    public static function mapMetaCaps($caps, $cap, $user_id, $args): array {
        // Map custom post type capabilities
        if (in_array($cap, ['edit_post', 'delete_post', 'read_post'])) {
            $post = get_post($args[0]);
            
            if (!$post) {
                return $caps;
            }
            
            $post_type = $post->post_type;
            
            if (!in_array($post_type, ['elearning_lesson', 'elearning_quiz'])) {
                return $caps;
            }
            
            // Map to custom capabilities
            $custom_caps = self::getContentTypeCapabilities($post_type);
            
            if (isset($custom_caps[$cap])) {
                $caps = [$custom_caps[$cap]];
                
                // Check ownership for certain capabilities
                if (in_array($cap, ['edit_post', 'delete_post']) && $post->post_author != $user_id) {
                    $others_cap = str_replace('_posts', '_others_posts', $cap);
                    if (isset($custom_caps[$others_cap])) {
                        $caps[] = $custom_caps[$others_cap];
                    }
                }
                
                // Check post status
                if ($post->post_status === 'private' && isset($custom_caps['read_private_posts'])) {
                    $caps[] = $custom_caps['read_private_posts'];
                }
            }
        }
        
        return $caps;
    }
}

// Initialize capability mapping
add_filter('map_meta_cap', ['ELearning_User_Roles', 'mapMetaCaps'], 10, 4);