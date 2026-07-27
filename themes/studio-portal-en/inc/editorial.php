<?php
/**
 * Native WordPress editorial controls for the international newsroom.
 */

if (!defined('ABSPATH')) {
    exit;
}

function studio_portal_en_default_columns(): array {
    return array(
        'The Interface' => 'Power, defaults and the politics hidden inside everyday screens.',
        'The Operator' => 'How teams, agents and tools behave when the demo is over.',
        'Dispatches' => 'Reports from the rooms where new culture is being assembled.',
        'Tested' => 'Technology reviews with a permanent deletion policy.',
        'Network State' => 'Identity, media and public life after the feed.',
        'The Ledger' => 'Money, governance and the infrastructure beneath daily life.',
        'Field Manual' => 'Practical responses to climate, cities and changing environments.',
        'Signal Path' => 'Tools, standards and the next computing layer.',
    );
}

function studio_portal_en_register_editorial_taxonomy(): void {
    register_taxonomy('studio_column', array('post'), array(
        'labels' => array(
            'name' => __('Columns', 'studio-portal-en'),
            'singular_name' => __('Column', 'studio-portal-en'),
            'search_items' => __('Search columns', 'studio-portal-en'),
            'all_items' => __('All columns', 'studio-portal-en'),
            'edit_item' => __('Edit column', 'studio-portal-en'),
            'update_item' => __('Update column', 'studio-portal-en'),
            'add_new_item' => __('Add new column', 'studio-portal-en'),
            'new_item_name' => __('New column name', 'studio-portal-en'),
            'menu_name' => __('Columns', 'studio-portal-en'),
        ),
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'column', 'with_front' => false),
    ));
}
add_action('init', 'studio_portal_en_register_editorial_taxonomy', 5);

function studio_portal_en_editorial_placement_fields(): array {
    return array(
        '_studio_portal_en_home_lead' => array('Lead story', 'Use this as the main home-page headline. Selecting a new lead replaces the previous one.'),
        '_studio_portal_en_trending' => array('Trending / Most Read', 'Include this article in the manually curated Most Read list.'),
        '_studio_portal_en_briefing' => array('Daily Briefing', 'Include this article in the Briefing module.'),
        '_studio_portal_en_editors_pick' => array("Editor's Pick", 'Include this article in the Editor\'s Picks module.'),
    );
}

function studio_portal_en_add_editorial_meta_box(): void {
    add_meta_box(
        'studio-portal-en-placement',
        __('Newsroom placement', 'studio-portal-en'),
        'studio_portal_en_render_editorial_meta_box',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes_post', 'studio_portal_en_add_editorial_meta_box');

function studio_portal_en_render_editorial_meta_box(WP_Post $post): void {
    wp_nonce_field('studio_portal_en_save_editorial', 'studio_portal_en_editorial_nonce');
    echo '<p>' . esc_html__('Latest stories appear automatically. Use these controls only for curated positions.', 'studio-portal-en') . '</p>';
    foreach (studio_portal_en_editorial_placement_fields() as $key => $field) {
        printf(
            '<p><label><input type="checkbox" name="studio_portal_en_placements[]" value="%1$s" %2$s> <strong>%3$s</strong></label><br><small>%4$s</small></p>',
            esc_attr($key),
            checked('1', get_post_meta($post->ID, $key, true), false),
            esc_html($field[0]),
            esc_html($field[1])
        );
    }
}

function studio_portal_en_save_editorial_meta(int $post_id): void {
    $nonce = isset($_POST['studio_portal_en_editorial_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['studio_portal_en_editorial_nonce']))
        : '';
    if (!wp_verify_nonce($nonce, 'studio_portal_en_save_editorial') ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        wp_is_post_revision($post_id) ||
        !current_user_can('edit_post', $post_id)) {
        return;
    }

    $selected = isset($_POST['studio_portal_en_placements'])
        ? array_map('sanitize_key', (array) wp_unslash($_POST['studio_portal_en_placements']))
        : array();
    $allowed = studio_portal_en_editorial_placement_fields();

    foreach (array_keys($allowed) as $key) {
        if (in_array($key, $selected, true)) {
            if ('_studio_portal_en_home_lead' === $key) {
                delete_post_meta_by_key($key);
            }
            update_post_meta($post_id, $key, '1');
        } else {
            delete_post_meta($post_id, $key);
        }
    }
}
add_action('save_post_post', 'studio_portal_en_save_editorial_meta');

function studio_portal_en_track_editorial_post(int $post_id): void {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    update_post_meta($post_id, '_studio_portal_en_content', '1');
}
add_action('save_post_post', 'studio_portal_en_track_editorial_post', 5);

function studio_portal_en_content_meta_query(): array {
    return array(
        array(
            'key' => '_studio_portal_en_content',
            'value' => '1',
        ),
    );
}

function studio_portal_en_home_posts(string $placement, int $limit, array $exclude = array()): array {
    $limit = max(1, $limit);
    $exclude = array_values(array_unique(array_map('intval', $exclude)));
    $meta_keys = array(
        'lead' => '_studio_portal_en_home_lead',
        'trending' => '_studio_portal_en_trending',
        'briefing' => '_studio_portal_en_briefing',
        'editors_pick' => '_studio_portal_en_editors_pick',
    );
    $posts = array();

    if (isset($meta_keys[$placement])) {
        $query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => $exclude,
            'ignore_sticky_posts' => true,
            'meta_query' => array(
                'relation' => 'AND',
                studio_portal_en_content_meta_query()[0],
                array(
                    'key' => $meta_keys[$placement],
                    'value' => '1',
                ),
            ),
        ));
        $posts = $query->posts;
    }

    if ('lead' === $placement && !$posts) {
        $sticky_ids = array_values(array_diff(array_map('intval', get_option('sticky_posts', array())), $exclude));
        if ($sticky_ids) {
            $query = new WP_Query(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'post__in' => $sticky_ids,
                'orderby' => 'post__in',
                'ignore_sticky_posts' => true,
                'meta_query' => studio_portal_en_content_meta_query(),
            ));
            $posts = $query->posts;
        }
    }

    $found_ids = array_map(static fn(WP_Post $post): int => (int) $post->ID, $posts);
    if (count($posts) < $limit) {
        $fallback = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit - count($posts),
            'post__not_in' => array_values(array_unique(array_merge($exclude, $found_ids))),
            'ignore_sticky_posts' => true,
            'meta_query' => studio_portal_en_content_meta_query(),
        ));
        $posts = array_merge($posts, $fallback->posts);
    }

    return array_slice($posts, 0, $limit);
}

function studio_portal_en_home_columns(int $limit = 4): array {
    $terms = get_terms(array(
        'taxonomy' => 'studio_column',
        'hide_empty' => true,
        'number' => max(1, $limit),
        'orderby' => 'count',
        'order' => 'DESC',
    ));
    if (is_wp_error($terms)) {
        return array();
    }

    $columns = array();
    foreach ($terms as $term) {
        $query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'ignore_sticky_posts' => true,
            'meta_query' => studio_portal_en_content_meta_query(),
            'tax_query' => array(array(
                'taxonomy' => 'studio_column',
                'field' => 'term_id',
                'terms' => (int) $term->term_id,
            )),
        ));
        if (!$query->posts) {
            continue;
        }
        $url = get_term_link($term);
        $columns[] = array(
            'term' => $term,
            'post' => $query->posts[0],
            'url' => is_wp_error($url) ? home_url('/journal/') : $url,
        );
    }
    return $columns;
}

function studio_portal_en_home_topics(int $limit = 6): array {
    $eligible_posts = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'ignore_sticky_posts' => true,
        'meta_query' => studio_portal_en_content_meta_query(),
    ));
    if (!$eligible_posts) {
        return array();
    }
    $terms = get_categories(array(
        'hide_empty' => true,
        'number' => max(1, $limit),
        'orderby' => 'count',
        'order' => 'DESC',
        'object_ids' => array_map('intval', $eligible_posts),
    ));
    $topics = array();
    foreach ($terms as $term) {
        $query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'ignore_sticky_posts' => true,
            'cat' => (int) $term->term_id,
            'meta_query' => studio_portal_en_content_meta_query(),
        ));
        if (!$query->posts) {
            continue;
        }
        $url = get_category_link((int) $term->term_id);
        $topics[] = array('term' => $term, 'post' => $query->posts[0], 'url' => $url);
    }
    return $topics;
}

function studio_portal_en_scope_editorial_archives(WP_Query $query): void {
    if (is_admin() || !$query->is_main_query() || (!$query->is_category() && !$query->is_tax('studio_column'))) {
        return;
    }
    $query->set('meta_query', studio_portal_en_content_meta_query());
}
add_action('pre_get_posts', 'studio_portal_en_scope_editorial_archives');

function studio_portal_en_newsroom_defaults(): array {
    return array(
        'issue_label' => 'VOL.06 / ISSUE 30',
        'edition_label' => 'GLOBAL EDITION',
        'newsletter_email' => 'briefing@studio.news',
        'subscriber_label' => 'JOIN 18,240 READERS',
        'briefing_title' => 'THE 90-SECOND EDIT',
        'briefing_signals' => "Browsers are adding agent permissions faster than shared standards can describe them.\nInstant payment networks are moving competition from speed to trust.\nIndependent websites are becoming social objects again.",
    );
}

function studio_portal_en_newsroom_options(): array {
    return wp_parse_args((array) get_option('studio_portal_en_newsroom_options', array()), studio_portal_en_newsroom_defaults());
}

function studio_portal_en_newsroom_option(string $key): string {
    $options = studio_portal_en_newsroom_options();
    return isset($options[$key]) ? (string) $options[$key] : '';
}

function studio_portal_en_sanitize_newsroom_options(array $input): array {
    $defaults = studio_portal_en_newsroom_defaults();
    return array(
        'issue_label' => sanitize_text_field($input['issue_label'] ?? $defaults['issue_label']),
        'edition_label' => sanitize_text_field($input['edition_label'] ?? $defaults['edition_label']),
        'newsletter_email' => sanitize_email($input['newsletter_email'] ?? $defaults['newsletter_email']),
        'subscriber_label' => sanitize_text_field($input['subscriber_label'] ?? $defaults['subscriber_label']),
        'briefing_title' => sanitize_text_field($input['briefing_title'] ?? $defaults['briefing_title']),
        'briefing_signals' => sanitize_textarea_field($input['briefing_signals'] ?? $defaults['briefing_signals']),
    );
}

function studio_portal_en_register_newsroom_settings(): void {
    register_setting('studio_portal_en_newsroom', 'studio_portal_en_newsroom_options', array(
        'type' => 'array',
        'sanitize_callback' => 'studio_portal_en_sanitize_newsroom_options',
        'default' => studio_portal_en_newsroom_defaults(),
    ));
}
add_action('admin_init', 'studio_portal_en_register_newsroom_settings');

function studio_portal_en_register_newsroom_page(): void {
    add_theme_page(
        __('Newsroom setup', 'studio-portal-en'),
        __('Newsroom setup', 'studio-portal-en'),
        'edit_theme_options',
        'studio-portal-en-newsroom',
        'studio_portal_en_render_newsroom_page'
    );
}
add_action('admin_menu', 'studio_portal_en_register_newsroom_page');

function studio_portal_en_render_newsroom_page(): void {
    if (!current_user_can('edit_theme_options')) {
        return;
    }
    $options = studio_portal_en_newsroom_options();
    $post_count = wp_count_posts('post');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Studio International Newsroom', 'studio-portal-en'); ?></h1>
        <p><?php esc_html_e('The home page is assembled from normal WordPress content. Publish posts for Latest, use Categories for Topics, assign Columns, and curate special positions from the Newsroom placement box in each article.', 'studio-portal-en'); ?></p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;max-width:1000px;margin:24px 0;">
            <a class="button button-hero" href="<?php echo esc_url(admin_url('post-new.php')); ?>"><?php esc_html_e('Write a story', 'studio-portal-en'); ?></a>
            <a class="button button-hero" href="<?php echo esc_url(admin_url('edit.php')); ?>"><?php echo esc_html(sprintf(__('Manage %d stories', 'studio-portal-en'), (int) $post_count->publish)); ?></a>
            <a class="button button-hero" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=category')); ?>"><?php esc_html_e('Manage topic desks', 'studio-portal-en'); ?></a>
            <a class="button button-hero" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=studio_column&post_type=post')); ?>"><?php esc_html_e('Manage columns', 'studio-portal-en'); ?></a>
        </div>
        <form method="post" action="options.php" style="max-width:820px;">
            <?php settings_fields('studio_portal_en_newsroom'); ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row"><label for="studio-issue-label"><?php esc_html_e('Issue label', 'studio-portal-en'); ?></label></th><td><input class="regular-text" id="studio-issue-label" name="studio_portal_en_newsroom_options[issue_label]" value="<?php echo esc_attr($options['issue_label']); ?>"></td></tr>
                <tr><th scope="row"><label for="studio-edition-label"><?php esc_html_e('Edition label', 'studio-portal-en'); ?></label></th><td><input class="regular-text" id="studio-edition-label" name="studio_portal_en_newsroom_options[edition_label]" value="<?php echo esc_attr($options['edition_label']); ?>"></td></tr>
                <tr><th scope="row"><label for="studio-newsletter-email"><?php esc_html_e('Newsletter email', 'studio-portal-en'); ?></label></th><td><input class="regular-text" type="email" id="studio-newsletter-email" name="studio_portal_en_newsroom_options[newsletter_email]" value="<?php echo esc_attr($options['newsletter_email']); ?>"></td></tr>
                <tr><th scope="row"><label for="studio-subscriber-label"><?php esc_html_e('Subscriber label', 'studio-portal-en'); ?></label></th><td><input class="regular-text" id="studio-subscriber-label" name="studio_portal_en_newsroom_options[subscriber_label]" value="<?php echo esc_attr($options['subscriber_label']); ?>"></td></tr>
                <tr><th scope="row"><label for="studio-briefing-title"><?php esc_html_e('Briefing title', 'studio-portal-en'); ?></label></th><td><input class="regular-text" id="studio-briefing-title" name="studio_portal_en_newsroom_options[briefing_title]" value="<?php echo esc_attr($options['briefing_title']); ?>"></td></tr>
                <tr><th scope="row"><label for="studio-briefing-signals"><?php esc_html_e('Briefing signals', 'studio-portal-en'); ?></label></th><td><textarea class="large-text" rows="6" id="studio-briefing-signals" name="studio_portal_en_newsroom_options[briefing_signals]"><?php echo esc_textarea($options['briefing_signals']); ?></textarea><p class="description"><?php esc_html_e('Use one signal per line.', 'studio-portal-en'); ?></p></td></tr>
            </table>
            <?php submit_button(__('Save newsroom settings', 'studio-portal-en')); ?>
        </form>
    </div>
    <?php
}

function studio_portal_en_flush_editorial_rewrites(): void {
    if ((int) get_option('studio_portal_en_editorial_rewrite_schema', 0) >= 1) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('studio_portal_en_editorial_rewrite_schema', 1, false);
}
add_action('init', 'studio_portal_en_flush_editorial_rewrites', 99);
