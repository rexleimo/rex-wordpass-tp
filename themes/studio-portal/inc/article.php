<?php
if (!defined('ABSPATH')) {
    exit;
}

function studio_portal_article_content_with_toc(string $content): array {
    $toc = array();
    $index = 0;
    $content = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/', static function (array $match) use (&$toc, &$index): string {
        $title = wp_strip_all_tags($match[2]);
        $id = 'section-' . (++$index) . '-' . sanitize_title($title);
        $toc[] = array('id' => $id, 'level' => (int) $match[1], 'title' => $title);
        return '<h' . $match[1] . ' id="' . esc_attr($id) . '">' . $match[2] . '</h' . $match[1] . '>';
    }, $content) ?? $content;
    return array($content, $toc);
}

function studio_portal_reading_time_for_content(string $content): int {
    $plain = wp_strip_all_tags($content);
    $words = max(1, str_word_count($plain) + mb_strlen(preg_replace('/\s/u', '', $plain) ?? ''));
    return max(1, (int) ceil($words / 550));
}
