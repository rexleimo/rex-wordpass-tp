<?php
if (!defined('ABSPATH')) {
    exit;
}

/** Convert the bundled Hugo Markdown subset into semantic, safe post HTML. */
function studio_portal_markdown_inline(string $text): string {
    $text = esc_html(trim($text));
    $text = preg_replace_callback('/`([^`]+)`/', static fn(array $m): string => '<code>' . esc_html($m[1]) . '</code>', $text) ?? $text;
    $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" rel="noopener noreferrer">$1</a>', $text) ?? $text;
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
    $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text) ?? $text;
    return $text;
}

function studio_portal_markdown_to_html(string $markdown): string {
    $lines = preg_split('/\R/', str_replace("\r\n", "\n", $markdown)) ?: array();
    $html = array();
    $paragraph = array();
    $flush_paragraph = static function () use (&$html, &$paragraph): void {
        if ($paragraph) {
            $html[] = '<p>' . studio_portal_markdown_inline(implode(' ', $paragraph)) . '</p>';
            $paragraph = array();
        }
    };

    for ($i = 0, $count = count($lines); $i < $count; $i++) {
        $line = rtrim($lines[$i]);
        if ('' === trim($line)) {
            $flush_paragraph();
            continue;
        }
        if (preg_match('/^```([^\s]*)/', $line, $match)) {
            $flush_paragraph();
            $language = sanitize_html_class($match[1]);
            $code = array();
            while (++$i < $count && !str_starts_with($lines[$i], '```')) {
                $code[] = $lines[$i];
            }
            $html[] = '<pre><code class="language-' . esc_attr($language) . '">' . esc_html(implode("\n", $code)) . '</code></pre>';
            continue;
        }
        if (preg_match('/^!\[([^\]]*)\]\((\/images\/[^)]+)\)/', trim($line), $match)) {
            $flush_paragraph();
            $html[] = '<figure class="sp-inline-figure"><img src="https://rexai.top' . esc_url($match[2]) . '" alt="' . esc_attr($match[1]) . '" loading="lazy"></figure>';
            continue;
        }
        if (preg_match('/^(#{1,3})\s+(.+)$/', $line, $match)) {
            $flush_paragraph();
            $level = strlen($match[1]);
            $html[] = '<h' . $level . '>' . studio_portal_markdown_inline($match[2]) . '</h' . $level . '>';
            continue;
        }
        if (preg_match('/^>\s?(.*)$/', $line, $match)) {
            $flush_paragraph();
            $quote = array($match[1]);
            while ($i + 1 < $count && preg_match('/^>\s?(.*)$/', $lines[$i + 1], $next)) {
                $quote[] = $next[1];
                $i++;
            }
            $html[] = '<blockquote><p>' . studio_portal_markdown_inline(implode(' ', $quote)) . '</p></blockquote>';
            continue;
        }
        if (preg_match('/^[-*+]\s+(.+)$/', $line, $match) || preg_match('/^\d+\.\s+(.+)$/', $line, $match)) {
            $flush_paragraph();
            $ordered = (bool) preg_match('/^\d+\./', $line);
            $items = array($match[1]);
            while ($i + 1 < $count && ($ordered ? preg_match('/^\d+\.\s+(.+)$/', $lines[$i + 1], $next) : preg_match('/^[-*+]\s+(.+)$/', $lines[$i + 1], $next))) {
                $items[] = $next[1];
                $i++;
            }
            $tag = $ordered ? 'ol' : 'ul';
            $html[] = '<' . $tag . '><li>' . implode('</li><li>', array_map('studio_portal_markdown_inline', $items)) . '</li></' . $tag . '>';
            continue;
        }
        if (preg_match('/^\|(.+)\|$/', $line) && $i + 1 < $count && preg_match('/^\|?\s*:?-{3,}/', $lines[$i + 1])) {
            $flush_paragraph();
            $headers = array_map('trim', explode('|', trim($line, '|')));
            $i += 2;
            $rows = array();
            while ($i < $count && preg_match('/^\|(.+)\|$/', $lines[$i], $row)) {
                $rows[] = array_map('trim', explode('|', trim($row[1], '|')));
                $i++;
            }
            $i--;
            $thead = '<thead><tr>' . implode('', array_map(static fn(string $cell): string => '<th>' . studio_portal_markdown_inline($cell) . '</th>', $headers)) . '</tr></thead>';
            $tbody = '<tbody>';
            foreach ($rows as $row) {
                $tbody .= '<tr>' . implode('', array_map(static fn(string $cell): string => '<td>' . studio_portal_markdown_inline($cell) . '</td>', $row)) . '</tr>';
            }
            $html[] = '<table>' . $thead . $tbody . '</tbody></table>';
            continue;
        }
        if (preg_match('/^-{3,}$/', trim($line))) {
            $flush_paragraph();
            $html[] = '<hr>';
            continue;
        }
        $paragraph[] = trim($line);
    }
    $flush_paragraph();
    return wp_kses_post(implode("\n", $html));
}
