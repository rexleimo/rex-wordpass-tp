<?php
/**
 * Studio Portal theme bootstrap.
 *
 * This product intentionally has no WooCommerce or toKraft business-flow
 * dependency. All frontend functionality is owned by this theme.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/routes.php';
require_once get_template_directory() . '/inc/markdown.php';
require_once get_template_directory() . '/inc/article.php';

function studio_portal_setup(): void {
    load_theme_textdomain('studio-portal', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', array('comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));

    register_nav_menus(array(
        'primary' => __('Primary navigation', 'studio-portal'),
        'footer' => __('Footer navigation', 'studio-portal'),
    ));
}
add_action('after_setup_theme', 'studio_portal_setup');

function studio_portal_enqueue_assets(): void {
    $style_path = get_template_directory() . '/style.css';
    wp_enqueue_style(
        'studio-portal-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+SC:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );
    wp_enqueue_style(
        'studio-portal-style',
        get_stylesheet_uri(),
        array('studio-portal-fonts'),
        file_exists($style_path) ? (string) filemtime($style_path) : '0.1.0'
    );
    $script_path = get_template_directory() . '/assets/site.js';
    wp_enqueue_script(
        'studio-portal-site',
        get_template_directory_uri() . '/assets/site.js',
        array(),
        file_exists($script_path) ? (string) filemtime($script_path) : '0.1.0',
        true
    );
    if (is_front_page()) {
        $portal_path = get_template_directory() . '/assets/portal.css';
        wp_enqueue_style(
            'studio-portal-home',
            get_template_directory_uri() . '/assets/portal.css',
            array('studio-portal-style'),
            file_exists($portal_path) ? (string) filemtime($portal_path) : '0.1.0'
        );
    }
    if (is_single()) {
        $article_path = get_template_directory() . '/assets/article.css';
        wp_enqueue_style(
            'studio-portal-article',
            get_template_directory_uri() . '/assets/article.css',
            array('studio-portal-style'),
            file_exists($article_path) ? (string) filemtime($article_path) : '0.1.0'
        );
    }
    if (!is_front_page() && !is_single()) {
        $routes_path = get_template_directory() . '/assets/routes.css';
        wp_enqueue_style(
            'studio-portal-routes',
            get_template_directory_uri() . '/assets/routes.css',
            array('studio-portal-style'),
            file_exists($routes_path) ? (string) filemtime($routes_path) : '0.1.0'
        );
    }

    $motion_style_path = get_template_directory() . '/assets/motion.css';
    wp_enqueue_style(
        'studio-portal-motion',
        get_template_directory_uri() . '/assets/motion.css',
        array('studio-portal-style'),
        file_exists($motion_style_path) ? (string) filemtime($motion_style_path) : '0.1.0'
    );

    $gsap_path = get_template_directory() . '/assets/vendor/gsap/gsap.min.js';
    $scroll_trigger_path = get_template_directory() . '/assets/vendor/gsap/ScrollTrigger.min.js';
    $motion_script_path = get_template_directory() . '/assets/motion.js';
    wp_enqueue_script(
        'studio-portal-gsap',
        get_template_directory_uri() . '/assets/vendor/gsap/gsap.min.js',
        array(),
        file_exists($gsap_path) ? (string) filemtime($gsap_path) : '3.15.0',
        true
    );
    wp_enqueue_script(
        'studio-portal-scrolltrigger',
        get_template_directory_uri() . '/assets/vendor/gsap/ScrollTrigger.min.js',
        array('studio-portal-gsap'),
        file_exists($scroll_trigger_path) ? (string) filemtime($scroll_trigger_path) : '3.15.0',
        true
    );
    wp_enqueue_script(
        'studio-portal-motion',
        get_template_directory_uri() . '/assets/motion.js',
        array('studio-portal-gsap', 'studio-portal-scrolltrigger'),
        file_exists($motion_script_path) ? (string) filemtime($motion_script_path) : '0.1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'studio_portal_enqueue_assets');

function studio_portal_topics(): array {
    $post_ids = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_key' => '_studio_portal_demo_content',
        'meta_value' => '1',
    ));
    if (!$post_ids) {
        return array();
    }
    $topics = wp_get_object_terms($post_ids, 'category', array('orderby' => 'count', 'order' => 'DESC'));
    return is_wp_error($topics) ? array() : $topics;
}

function studio_portal_primary_menu_items(): array {
    return array(
        __('最新', 'studio-portal') => home_url('/journal/'),
        __('主题', 'studio-portal') => home_url('/services/'),
        __('指南', 'studio-portal') => home_url('/process/'),
        __('关于', 'studio-portal') => home_url('/about/'),
        __('联系', 'studio-portal') => home_url('/contact/'),
    );
}

function studio_portal_primary_menu_fallback(): void {
    foreach (studio_portal_primary_menu_items() as $label => $url) {
        printf('<a href="%s">%s</a>', esc_url($url), esc_html($label));
    }
}

/**
 * Create a safe, editable default menu on first activation. This prevents a
 * previous theme's commerce navigation leaking into the portal product.
 */
function studio_portal_register_default_menu(): void {
    $menu_name = 'Studio Portal Primary';
    $menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu($menu_name);

    if (is_wp_error($menu_id) || !$menu_id) {
        return;
    }

    $schema_version = (int) get_option('studio_portal_menu_schema', 0);
    if (!$menu || $schema_version < 4) {
        foreach ((array) wp_get_nav_menu_items($menu_id) as $item) {
            wp_delete_post((int) $item->ID, true);
        }
        foreach (studio_portal_primary_menu_items() as $label => $url) {
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title' => $label,
                'menu-item-url' => $url,
                'menu-item-status' => 'publish',
            ));
        }
        update_option('studio_portal_menu_schema', 4, false);
    }

    $locations = get_theme_mod('nav_menu_locations', array());
    $locations['primary'] = $menu_id;
    $locations['footer'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}
add_action('after_switch_theme', 'studio_portal_register_default_menu');
add_action('init', 'studio_portal_register_default_menu', 30);

function studio_portal_editorial_topics(): array {
    return array(
        'agent' => array(
            'label' => 'Agent 工程',
            'eyebrow' => 'AGENT SYSTEMS',
            'description' => '从上下文、记忆到工具调用，拆解 Agent 真正进入生产环境时的系统问题。',
            'keywords' => array('agent', 'context', 'rag', '检索', '记忆'),
        ),
        'models' => array(
            'label' => '模型与工具',
            'eyebrow' => 'MODELS & TOOLS',
            'description' => '不只看榜单，继续追问模型能力、工具边界与工程代价。',
            'keywords' => array('claude', 'llm', '模型', 'sota', 'code'),
        ),
        'open-source' => array(
            'label' => '开源与自托管',
            'eyebrow' => 'OPEN SOURCE',
            'description' => '围绕推理框架、本地部署、隐私与长期维护做可落地的技术选择。',
            'keywords' => array('llama', 'vllm', 'sglang', '开源', '部署', '推理'),
        ),
        'practice' => array(
            'label' => '工程实践',
            'eyebrow' => 'FIELD NOTES',
            'description' => '把抽象原则放回真实工作流，记录那些可以复用的约束与验证方法。',
            'keywords' => array('实践', '工作流', '接口', '约束', '验证', '工程'),
        ),
    );
}

function studio_portal_content_posts(int $limit = 24): array {
    $query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'ignore_sticky_posts' => true,
        'meta_key' => '_studio_portal_demo_content',
        'meta_value' => '1',
    ));

    if (!$query->posts) {
        $query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'ignore_sticky_posts' => true,
        ));
    }

    return $query->posts;
}

function studio_portal_filter_posts_by_topic(array $posts, string $topic): array {
    $topics = studio_portal_editorial_topics();
    if (!isset($topics[$topic])) {
        return $posts;
    }

    return array_values(array_filter($posts, static function ($post) use ($topics, $topic): bool {
        if (!$post instanceof WP_Post) {
            return false;
        }
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $haystack = mb_strtolower(get_the_title($post) . ' ' . get_the_excerpt($post) . ' ' . implode(' ', $categories));
        foreach ($topics[$topic]['keywords'] as $keyword) {
            if (false !== mb_strpos($haystack, mb_strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }));
}

function studio_portal_footer_menu_fallback(): void {
    studio_portal_primary_menu_fallback();
}

function studio_portal_contact_submission(): void {
    $redirect = wp_get_referer() ?: home_url('/contact/');
    $nonce = isset($_POST['studio_portal_contact_nonce']) ? sanitize_text_field(wp_unslash($_POST['studio_portal_contact_nonce'])) : '';

    if (!wp_verify_nonce($nonce, 'studio_portal_contact')) {
        wp_safe_redirect(add_query_arg('contact_error', 'security', $redirect));
        exit;
    }

    if (!empty($_POST['studio_portal_website'])) {
        wp_safe_redirect(add_query_arg('contact_error', 'spam', $redirect));
        exit;
    }

    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $company = isset($_POST['company']) ? sanitize_text_field(wp_unslash($_POST['company'])) : '';
    $budget = isset($_POST['budget']) ? sanitize_text_field(wp_unslash($_POST['budget'])) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';

    if ('' === $name || !is_email($email) || '' === $notes) {
        wp_safe_redirect(add_query_arg('contact_error', 'required', $redirect));
        exit;
    }

    $subject = sprintf('[%s] %s', wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), __('New project enquiry', 'studio-portal'));
    $message = implode("\n", array(
        'Name: ' . $name,
        'Email: ' . $email,
        'Company: ' . ($company ?: '—'),
        'Budget: ' . ($budget ?: '—'),
        '',
        'Project notes:',
        $notes,
    ));

    $sent = wp_mail(get_option('admin_email'), $subject, $message, array('Reply-To: ' . $email));
    wp_safe_redirect(add_query_arg($sent ? 'contact_sent' : 'contact_error', $sent ? '1' : 'delivery', $redirect));
    exit;
}
add_action('admin_post_studio_portal_contact', 'studio_portal_contact_submission');
add_action('admin_post_nopriv_studio_portal_contact', 'studio_portal_contact_submission');

function studio_portal_post_meta_line(int $post_id = 0): string {
    $post_id = $post_id ?: get_the_ID();
    $categories = get_the_category($post_id);
    $category = $categories ? $categories[0]->name : __('深度文章', 'studio-portal');
    return sprintf('%s · %s', get_the_date('Y.m.d', $post_id), $category);
}

function studio_portal_reading_time(int $post_id = 0): int {
    $post_id = $post_id ?: get_the_ID();
    $content = wp_strip_all_tags((string) get_post_field('post_content', $post_id));
    $latin_words = str_word_count($content);
    $cjk_characters = preg_match_all('/[\x{3400}-\x{9fff}]/u', $content, $matches);
    $reading_units = $latin_words + ((int) $cjk_characters / 2.2);
    return max(1, (int) ceil($reading_units / 220));
}
