<?php
if (!defined('ABSPATH')) {
    exit;
}

function studio_portal_en_article_toc(string $content): array {
    $toc = array();
    $count = 0;
    $content = preg_replace_callback('/<h([23])(?:[^>]*)>(.*?)<\/h\1>/', static function (array $match) use (&$toc, &$count): string {
        $title = wp_strip_all_tags($match[2]);
        $id = 'section-' . (++$count) . '-' . sanitize_title($title);
        $toc[] = array('id' => $id, 'level' => (int) $match[1], 'title' => $title);
        return '<h' . $match[1] . ' id="' . esc_attr($id) . '">' . $match[2] . '</h' . $match[1] . '>';
    }, $content) ?? $content;
    return array($content, $toc);
}
