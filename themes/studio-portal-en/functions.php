<?php
/**
 * Studio Portal International bootstrap.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/routes.php';
require_once get_template_directory() . '/inc/article.php';
require_once get_template_directory() . '/inc/editorial.php';

function studio_portal_en_setup(): void {
    load_theme_textdomain('studio-portal-en', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', array('comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));

    register_nav_menus(array(
        'primary' => __('Primary navigation', 'studio-portal-en'),
        'footer' => __('Footer navigation', 'studio-portal-en'),
    ));
}
add_action('after_setup_theme', 'studio_portal_en_setup');

function studio_portal_en_asset_version(string $relative): string {
    $path = get_template_directory() . '/' . ltrim($relative, '/');
    return file_exists($path) ? (string) filemtime($path) : '0.3.0';
}

function studio_portal_en_enqueue_assets(): void {
    wp_enqueue_style(
        'studio-portal-en-fonts',
        'https://fonts.googleapis.com/css2?family=Archivo+Black&family=JetBrains+Mono:wght@400;500;600;700&display=swap',
        array(),
        null
    );
    wp_enqueue_style('studio-portal-en-style', get_stylesheet_uri(), array('studio-portal-en-fonts'), studio_portal_en_asset_version('style.css'));
    wp_enqueue_style('studio-portal-en-pages', get_template_directory_uri() . '/assets/pages.css', array('studio-portal-en-style'), studio_portal_en_asset_version('assets/pages.css'));
    wp_enqueue_style('studio-portal-en-motion', get_template_directory_uri() . '/assets/motion.css', array('studio-portal-en-style'), studio_portal_en_asset_version('assets/motion.css'));

    wp_enqueue_script('studio-portal-en-site', get_template_directory_uri() . '/assets/site.js', array(), studio_portal_en_asset_version('assets/site.js'), true);
    wp_enqueue_script('studio-portal-en-gsap', get_template_directory_uri() . '/assets/vendor/gsap/gsap.min.js', array(), studio_portal_en_asset_version('assets/vendor/gsap/gsap.min.js'), true);
    wp_enqueue_script('studio-portal-en-scrolltrigger', get_template_directory_uri() . '/assets/vendor/gsap/ScrollTrigger.min.js', array('studio-portal-en-gsap'), studio_portal_en_asset_version('assets/vendor/gsap/ScrollTrigger.min.js'), true);
    wp_enqueue_script('studio-portal-en-motion', get_template_directory_uri() . '/assets/motion.js', array('studio-portal-en-gsap', 'studio-portal-en-scrolltrigger'), studio_portal_en_asset_version('assets/motion.js'), true);
}
add_action('wp_enqueue_scripts', 'studio_portal_en_enqueue_assets');

function studio_portal_en_language_attributes(string $attributes): string {
    if (preg_match('/\blang=("|\')[^"\']+\1/', $attributes)) {
        return (string) preg_replace('/\blang=("|\')[^"\']+\1/', 'lang="en-US"', $attributes, 1);
    }
    return trim($attributes . ' lang="en-US"');
}
add_filter('language_attributes', 'studio_portal_en_language_attributes');

function studio_portal_en_document_title(array $parts): array {
    $route_titles = array(
        'work' => 'Selected Work',
        'case' => 'Case Study',
        'services' => 'Services',
        'studio' => 'Studio',
        'process' => 'Process',
        'journal' => 'Journal',
        'contact' => 'Start a Project',
    );
    $route = (string) get_query_var('studio_portal_en_route');
    if (isset($parts['site'])) {
        $parts['site'] = 'STUDIO INTERNATIONAL';
    }
    if (is_front_page()) {
        $parts['title'] = 'STUDIO INTERNATIONAL - Ideas, Technology and Culture';
    } elseif (isset($route_titles[$route])) {
        $parts['title'] = 'case' === $route
            ? (studio_portal_en_projects()[sanitize_key((string) get_query_var('studio_portal_en_case'))]['title'] ?? $route_titles[$route])
            : $route_titles[$route];
    }
    return $parts;
}
add_filter('document_title_parts', 'studio_portal_en_document_title');

function studio_portal_en_meta_description(): void {
    if (!is_front_page() && !get_query_var('studio_portal_en_route')) {
        return;
    }
    $description = is_front_page()
        ? 'An independent international publication covering technology, design, digital culture and the systems shaping modern life.'
        : 'Read STUDIO INTERNATIONAL reporting, columns, reviews and field notes on technology, design and digital culture.';
    printf("\n<meta name=\"description\" content=\"%s\">\n", esc_attr($description));
}
add_action('wp_head', 'studio_portal_en_meta_description', 2);

function studio_portal_en_post_date(string $format, $post = null, bool $modified = false): string {
    $timestamp = $modified
        ? get_post_modified_time('U', true, $post)
        : get_post_time('U', true, $post);
    if (!$timestamp) {
        return '';
    }
    return (new DateTimeImmutable('@' . $timestamp))->setTimezone(wp_timezone())->format($format);
}

function studio_portal_en_primary_menu_items(): array {
    return array(
        'Latest' => home_url('/#latest'),
        'Columns' => home_url('/#columns'),
        'Topics' => home_url('/#topics'),
        'Briefing' => home_url('/#briefing'),
        'Journal' => home_url('/journal/'),
    );
}

function studio_portal_en_menu_fallback(): void {
    echo '<ul class="en-nav-list">';
    foreach (studio_portal_en_primary_menu_items() as $label => $url) {
        printf('<li><a href="%s">%s</a></li>', esc_url($url), esc_html($label));
    }
    echo '</ul>';
}

function studio_portal_en_register_default_menu(): void {
    $menu_name = 'Studio Portal International';
    $menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu($menu_name);
    if (is_wp_error($menu_id) || !$menu_id) {
        return;
    }

    if (!$menu || (int) get_option('studio_portal_en_menu_schema', 0) < 2) {
        foreach ((array) wp_get_nav_menu_items($menu_id) as $item) {
            wp_delete_post((int) $item->ID, true);
        }
        foreach (studio_portal_en_primary_menu_items() as $label => $url) {
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title' => $label,
                'menu-item-url' => $url,
                'menu-item-status' => 'publish',
            ));
        }
        update_option('studio_portal_en_menu_schema', 2, false);
    }

    $locations = get_theme_mod('nav_menu_locations', array());
    $locations['primary'] = $menu_id;
    $locations['footer'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}
add_action('after_switch_theme', 'studio_portal_en_register_default_menu');
add_action('init', static function (): void {
    if ((int) get_option('studio_portal_en_menu_schema', 0) < 2) {
        studio_portal_en_register_default_menu();
    }
}, 20);

function studio_portal_en_projects(): array {
    $base = get_template_directory_uri() . '/assets/graphics/';
    return array(
        'hyperlane' => array('title' => 'Hyperlane', 'discipline' => 'Brand · Protocol UX', 'year' => '2026', 'image' => $base . 'hyperlane-console.svg', 'summary' => 'A cross-chain protocol rebuilt as one legible brand and product system.', 'metric' => '+312% qualified leads'),
        'zora-marketplace' => array('title' => 'Zora Marketplace', 'discipline' => 'Product UI · 3D', 'year' => '2026', 'image' => $base . 'zora-orbit.svg', 'summary' => 'A collecting surface where market data and culture share the same rhythm.', 'metric' => '2.4x collection depth'),
        'safe-wallet' => array('title' => 'Safe{Wallet}', 'discipline' => 'Design System', 'year' => '2025', 'image' => $base . 'safe-grid.svg', 'summary' => 'A token-led interface system used across wallet, governance and docs.', 'metric' => '46 shared components'),
        'stark-bridge' => array('title' => 'Stark Bridge', 'discipline' => 'Motion · Web', 'year' => '2025', 'image' => $base . 'stark-signal.svg', 'summary' => 'A fast, state-aware bridge flow designed around transaction confidence.', 'metric' => '34% fewer exits'),
    );
}

function studio_portal_en_journal_posts(int $limit = 8): array {
    $query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'ignore_sticky_posts' => true,
        'meta_key' => '_studio_portal_en_content',
        'meta_value' => '1',
    ));
    if (!$query->posts) {
        $query = new WP_Query(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $limit, 'ignore_sticky_posts' => true));
    }
    return $query->posts;
}

function studio_portal_en_contact_submission(): void {
    $redirect = wp_get_referer() ?: home_url('/contact/');
    $nonce = isset($_POST['studio_portal_en_contact_nonce']) ? sanitize_text_field(wp_unslash($_POST['studio_portal_en_contact_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'studio_portal_en_contact') || !empty($_POST['studio_portal_en_website'])) {
        wp_safe_redirect(add_query_arg('contact_error', 'security', $redirect));
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

    $subject = sprintf('[%s] New project enquiry from %s', wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $name);
    $message = "Name: {$name}\nEmail: {$email}\nCompany: {$company}\nBudget: {$budget}\n\nProject notes:\n{$notes}";
    $sent = wp_mail(get_option('admin_email'), $subject, $message, array('Reply-To: ' . $email));
    wp_safe_redirect(add_query_arg($sent ? 'contact_sent' : 'contact_error', $sent ? '1' : 'delivery', $redirect));
    exit;
}
add_action('admin_post_studio_portal_en_contact', 'studio_portal_en_contact_submission');
add_action('admin_post_nopriv_studio_portal_en_contact', 'studio_portal_en_contact_submission');

function studio_portal_en_reading_time(string $content): int {
    return max(1, (int) ceil(str_word_count(wp_strip_all_tags($content)) / 220));
}

/**
 * Add a small editable English journal on first activation. Content remains
 * standard WordPress posts and can be replaced without touching the theme.
 */
function studio_portal_en_seed_journal(): void {
    $content_schema = 3;
    if ((int) get_option('studio_portal_en_content_schema', 0) >= $content_schema) {
        return;
    }

    foreach (studio_portal_en_default_columns() as $column_name => $column_description) {
        $column_term = term_exists($column_name, 'studio_column');
        if (!$column_term) {
            wp_insert_term($column_name, 'studio_column', array('description' => $column_description));
        } elseif (is_array($column_term)) {
            $term = get_term((int) $column_term['term_id'], 'studio_column');
            if ($term instanceof WP_Term && '' === trim($term->description)) {
                wp_update_term((int) $term->term_id, 'studio_column', array('description' => $column_description));
            }
        }
    }

    $entries = array(
        array(
            'title' => 'Stop Designing for the Empty Wallet',
            'slug' => 'stop-designing-for-the-empty-wallet',
            'category' => 'Protocol UX',
            'column' => 'The Interface',
            'excerpt' => 'Why onboarding that assumes a funded wallet fails most users, and how state-aware design shortens the first successful session.',
            'content' => '<h2>The empty wallet myth</h2><p>Most product onboarding assumes the user arrives ready: funded, connected and confident about the next irreversible action. In the sessions we observed, that was the exception rather than the rule.</p><p>We rebuilt the opening surface around four states: no wallet, wrong network, empty balance and ready. Each state received a clear next action, visible progress and an exit path.</p><blockquote>If the first screen cannot name the user\'s current state, the product is asking for trust it has not earned.</blockquote><h2>The nine-minute tunnel</h2><p>The goal was not to remove every step. It was to remove ambiguity between steps. Median time to the first successful message dropped from twenty-seven minutes to nine.</p><h2>What shipped</h2><p>A shared state map, interface components, risk language and implementation notes now connect the marketing site, app shell and support documentation.</p>',
        ),
        array(
            'title' => 'The Handoff Is the Design',
            'slug' => 'the-handoff-is-the-design',
            'category' => 'Design Systems',
            'column' => 'The Operator',
            'excerpt' => 'If engineering cannot reproduce the intent from the system, the design work is not finished.',
            'content' => '<h2>Files are not outcomes</h2><p>A polished file can still leave engineers guessing about state, hierarchy and responsive behavior. Handoff starts when the system is being shaped, not when the screens are approved.</p><h2>Build the shared grammar</h2><p>Tokens, component boundaries and content rules turn visual intent into repeatable decisions. We pair designers and engineers before the first library is considered stable.</p><h2>Measure adoption</h2><p>The useful metric is not component count. It is how often product teams can ship a coherent new surface without returning to the original design team.</p>',
        ),
        array(
            'title' => 'Type That Survives Product Velocity',
            'slug' => 'type-that-survives-product-velocity',
            'category' => 'Brand Systems',
            'column' => 'Signal Path',
            'excerpt' => 'A display system can be expressive and still survive dense dashboards, translations and weekly releases.',
            'content' => '<h2>Start with the hardest surface</h2><p>We test type inside data tables, transaction states and narrow mobile controls before approving the hero. If the voice only works at ninety-six pixels, it is not a product system.</p><h2>Separate voice from utility</h2><p>One display face carries the point of view. A disciplined mono or sans family handles instructions, values and changing content.</p><h2>Document the tension</h2><p>The system should explain where typography is allowed to become loud and where clarity wins without debate.</p>',
        ),
        array(
            'title' => 'Motion Budgets for Real Products',
            'slug' => 'motion-budgets-for-real-products',
            'category' => 'Motion',
            'column' => 'Tested',
            'excerpt' => 'A practical framework for deciding which movement deserves runtime cost and user attention.',
            'content' => '<h2>Motion needs a job</h2><p>Every transition should explain state, preserve context or establish hierarchy. Movement without one of those jobs becomes interface noise.</p><h2>Budget by device</h2><p>Desktop can support richer spatial transitions. Mobile favors short state changes and no scroll-linked parallax. Reduced-motion preferences bypass both.</p><h2>Test the middle</h2><p>We tune for a mid-range device on a normal connection. A sixty-frame prototype on a studio workstation is not evidence of production performance.</p>',
        ),
        array(
            'title' => 'Why Every AI Agent Needs a Kill Switch',
            'slug' => 'why-every-ai-agent-needs-a-kill-switch',
            'category' => 'AI & Work',
            'column' => 'The Operator',
            'excerpt' => 'Autonomy without a visible stop condition is not intelligence. It is an operations problem waiting to happen.',
            'content' => '<h2>The stop condition is a product feature</h2><p>Useful agents need boundaries that are legible to the people accountable for their work. A kill switch is not an admission of failure; it is part of the operating model.</p><h2>Design for interruption</h2><p>Every long-running task should expose its current action, the next irreversible step and a safe way to pause.</p><h2>Trust is observable</h2><p>Teams adopt automation faster when they can see, question and stop it without losing the work already completed.</p>',
        ),
        array(
            'title' => 'The Return of the Personal Website',
            'slug' => 'the-return-of-the-personal-website',
            'category' => 'Digital Culture',
            'column' => 'Network State',
            'excerpt' => 'A generation raised inside feeds is rediscovering the pleasure of owning a small, strange corner of the web.',
            'content' => '<h2>Outside the feed</h2><p>The personal site is returning because it asks for decisions that platforms remove: what belongs together, what deserves permanence and what should remain difficult to find.</p><h2>Small tools, strong identity</h2><p>Static generators, independent publishing tools and cheap hosting have made ownership practical again.</p><h2>The web gets weird again</h2><p>The result is not nostalgia. It is a new layer of culture built by people who want context without an algorithmic middleman.</p>',
        ),
        array(
            'title' => 'Five Tools That Made Us Delete Five More',
            'slug' => 'five-tools-that-made-us-delete-five-more',
            'category' => 'Reviews',
            'column' => 'Tested',
            'excerpt' => 'Our field test for software that earns a permanent place in a small international newsroom.',
            'content' => '<h2>Utility over novelty</h2><p>We tested each tool inside a live publishing week and measured whether it removed a handoff, shortened a decision or preserved context.</p><h2>The deletion test</h2><p>A new tool only stayed when it allowed at least one older subscription to disappear.</p><h2>What survived</h2><p>The winners were quiet, interoperable and easy to leave. The most impressive demos rarely made the final list.</p>',
        ),
        array(
            'title' => 'Inside Seoul\'s Midnight Hardware Clubs',
            'slug' => 'inside-seouls-midnight-hardware-clubs',
            'category' => 'Field Notes',
            'column' => 'Dispatches',
            'excerpt' => 'After the offices close, a loose network of engineers turns basements and cafes into public laboratories.',
            'content' => '<h2>The second shift</h2><p>By midnight the tables are covered with boards, sensors and half-finished enclosures. Nobody is waiting for a formal program.</p><h2>Knowledge moves sideways</h2><p>Designers teach soldering, firmware engineers critique enclosures and students arrive with problems that become group projects.</p><h2>A city as laboratory</h2><p>The clubs matter because they make experimentation social, local and cheap enough to repeat.</p>',
        ),
        array(
            'title' => 'The Quiet Infrastructure of Instant Payments',
            'slug' => 'the-quiet-infrastructure-of-instant-payments',
            'category' => 'Systems',
            'column' => 'The Ledger',
            'excerpt' => 'The most consequential financial interface of the decade may be the one users barely notice.',
            'content' => '<h2>Speed changes expectation</h2><p>Once settlement becomes immediate, every delay around it starts to feel like a product defect.</p><h2>Invisible systems still need explanation</h2><p>Receipts, reversals and risk states must make the underlying rules visible at exactly the right moment.</p><h2>The next competitive layer</h2><p>Reliability will become assumed. The products that win will make instant money movement understandable across borders and contexts.</p>',
        ),
        array(
            'title' => 'A City Designed for the Heat',
            'slug' => 'a-city-designed-for-the-heat',
            'category' => 'Climate',
            'column' => 'Field Manual',
            'excerpt' => 'From shaded transit to night-time logistics, cities are rebuilding daily life around a hotter baseline.',
            'content' => '<h2>Heat is an interface</h2><p>Temperature shapes when people move, where they wait and which services remain accessible.</p><h2>Design the ordinary systems</h2><p>Shade, water, surface materials and opening hours often matter more than a singular landmark project.</p><h2>Adaptation is cultural</h2><p>The most resilient responses combine infrastructure with habits that communities can maintain themselves.</p>',
        ),
        array(
            'title' => 'Who Owns the Default Interface?',
            'slug' => 'who-owns-the-default-interface',
            'category' => 'Opinion',
            'column' => 'The Interface',
            'excerpt' => 'Defaults are policy wearing the clothes of convenience, and their authors deserve more scrutiny.',
            'content' => '<h2>The quiet decision</h2><p>Most users never change a default. That makes the initial state one of the most powerful editorial choices in any product.</p><h2>Convenience for whom</h2><p>A useful review asks who benefits, who pays and how difficult the choice is to reverse.</p><h2>Make authorship visible</h2><p>Products should explain consequential defaults in plain language and preserve a credible path to another choice.</p>',
        ),
        array(
            'title' => 'The Browser Is Becoming an Operating System Again',
            'slug' => 'the-browser-is-becoming-an-operating-system-again',
            'category' => 'Technology',
            'column' => 'Signal Path',
            'excerpt' => 'Agents, local models and richer permissions are turning the browser into the center of work once more.',
            'content' => '<h2>The application layer moves upward</h2><p>More work now begins with a tab that can understand other tabs, local files and the user\'s current task.</p><h2>Permission becomes the product</h2><p>The winning browser will not simply do more. It will make scope, memory and authority understandable.</p><h2>A familiar contest returns</h2><p>The browser is again where standards, distribution and personal computing meet.</p>',
        ),
    );

    $placements = array(
        'the-quiet-infrastructure-of-instant-payments' => array('_studio_portal_en_home_lead', '_studio_portal_en_briefing'),
        'why-every-ai-agent-needs-a-kill-switch' => array('_studio_portal_en_trending'),
        'the-return-of-the-personal-website' => array('_studio_portal_en_trending', '_studio_portal_en_briefing'),
        'inside-seouls-midnight-hardware-clubs' => array('_studio_portal_en_trending', '_studio_portal_en_editors_pick'),
        'a-city-designed-for-the-heat' => array('_studio_portal_en_trending', '_studio_portal_en_editors_pick'),
        'the-browser-is-becoming-an-operating-system-again' => array('_studio_portal_en_trending', '_studio_portal_en_briefing'),
        'the-handoff-is-the-design' => array('_studio_portal_en_editors_pick'),
    );
    $apply_editorial_data = static function (int $post_id, array $entry) use ($placements): void {
        update_post_meta($post_id, '_studio_portal_en_content', '1');
        wp_set_post_terms($post_id, array($entry['column']), 'post_tag', false);
        wp_set_post_terms($post_id, array($entry['column']), 'studio_column', false);
        foreach ($placements[$entry['slug']] ?? array() as $meta_key) {
            update_post_meta($post_id, $meta_key, '1');
        }
    };

    foreach ($entries as $entry) {
        $existing_post = get_page_by_path($entry['slug'], OBJECT, 'post');
        if ($existing_post instanceof WP_Post) {
            $apply_editorial_data((int) $existing_post->ID, $entry);
            continue;
        }
        $term = term_exists($entry['category'], 'category');
        if (!$term) {
            $term = wp_insert_term($entry['category'], 'category');
        }
        $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
        $post_id = wp_insert_post(array(
            'post_title' => $entry['title'],
            'post_name' => $entry['slug'],
            'post_excerpt' => $entry['excerpt'],
            'post_content' => $entry['content'],
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_category' => $term_id ? array($term_id) : array(),
            'tags_input' => array($entry['column']),
        ));
        if (!is_wp_error($post_id) && $post_id) {
            $apply_editorial_data((int) $post_id, $entry);
        }
    }
    update_option('studio_portal_en_content_schema', $content_schema, false);
}
add_action('after_switch_theme', 'studio_portal_en_seed_journal');
add_action('init', 'studio_portal_en_seed_journal', 30);
