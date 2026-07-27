<?php
/**
 * Import the bundled Hugo-derived portal demo posts.
 *
 * Run from the active WordPress container:
 * php /var/www/html/wp-content/themes/studio-portal/tools/import-hugo-demo-content.php
 */

if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

require_once dirname(__DIR__) . '/inc/markdown.php';

$source = dirname(__DIR__) . '/demo/hugo-posts.json';
if (!is_readable($source)) {
    fwrite(STDERR, "Missing demo source: {$source}\n");
    exit(1);
}

$records = json_decode((string) file_get_contents($source), true);
if (!is_array($records)) {
    fwrite(STDERR, "Invalid demo JSON\n");
    exit(1);
}

$imported = 0;
$updated = 0;
foreach ($records as $record) {
    $slug = sanitize_title((string) ($record['slug'] ?? ''));
    if ('' === $slug || empty($record['title'])) {
        continue;
    }

    $existing = get_page_by_path($slug, OBJECT, 'post');
    $post_data = array(
        'ID' => $existing ? (int) $existing->ID : 0,
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_name' => $slug,
        'post_title' => wp_strip_all_tags((string) $record['title']),
        'post_excerpt' => sanitize_textarea_field((string) ($record['description'] ?? '')),
        'post_content' => studio_portal_markdown_to_html((string) ($record['body_markdown'] ?? '')),
        'post_date' => gmdate('Y-m-d H:i:s', strtotime((string) ($record['date'] ?? 'now'))),
        'post_date_gmt' => gmdate('Y-m-d H:i:s', strtotime((string) ($record['date'] ?? 'now'))),
    );

    $post_id = wp_insert_post(wp_slash($post_data), true);
    if (is_wp_error($post_id)) {
        fwrite(STDERR, $post_id->get_error_message() . PHP_EOL);
        exit(1);
    }

    $category_ids = array();
    $topic_names = array_unique(array_merge((array) ($record['categories'] ?? array()), (array) ($record['portal_topics'] ?? array())));
    foreach ($topic_names as $category_name) {
        $category_name = sanitize_text_field((string) $category_name);
        if ('' === $category_name) {
            continue;
        }
        $category = get_category_by_slug(sanitize_title($category_name));
        $category_ids[] = $category ? (int) $category->term_id : (int) wp_create_category($category_name);
    }
    wp_set_post_categories($post_id, array_filter($category_ids));
    wp_set_post_tags($post_id, array_map('sanitize_text_field', (array) ($record['tags'] ?? array())));
    update_post_meta($post_id, '_studio_portal_hugo_source', esc_url_raw((string) ($record['source'] ?? '')));
    update_post_meta($post_id, '_studio_portal_demo_content', '1');

    $cover_url = esc_url_raw((string) ($record['cover_url'] ?? ''));
    if ($cover_url && !has_post_thumbnail($post_id)) {
        $temporary_file = download_url($cover_url, 30);
        if (!is_wp_error($temporary_file)) {
            $file = array(
                'name' => sanitize_file_name($slug . '-' . basename((string) parse_url($cover_url, PHP_URL_PATH))),
                'tmp_name' => $temporary_file,
            );
            $attachment_id = media_handle_sideload($file, $post_id, wp_strip_all_tags((string) $record['title']));
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
    }

    $existing ? $updated++ : $imported++;
}

printf("imported=%d updated=%d total=%d\n", $imported, $updated, $imported + $updated);
