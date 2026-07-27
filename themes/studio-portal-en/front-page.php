<?php
if (!defined('ABSPATH')) {
    exit;
}

$graphic_base = get_template_directory_uri() . '/assets/graphics/';
$artworks = array(
    $graphic_base . 'hyperlane-console.svg',
    $graphic_base . 'zora-orbit.svg',
    $graphic_base . 'safe-grid.svg',
    $graphic_base . 'stark-signal.svg',
    $graphic_base . 'studio-wall.svg',
);

$story_category = static function ($post): string {
    if (!$post) {
        return 'EDITORIAL';
    }
    $categories = get_the_category($post->ID);
    return $categories ? strtoupper(wp_specialchars_decode($categories[0]->name, ENT_QUOTES)) : 'EDITORIAL';
};
$story_column = static function ($post): string {
    if (!$post) {
        return 'STUDIO DESK';
    }
    $column_terms = wp_get_post_terms($post->ID, 'studio_column');
    if (!is_wp_error($column_terms) && $column_terms) {
        return strtoupper(wp_specialchars_decode($column_terms[0]->name, ENT_QUOTES));
    }
    $tags = get_the_tags($post->ID);
    return $tags ? strtoupper(wp_specialchars_decode($tags[0]->name, ENT_QUOTES)) : 'STUDIO DESK';
};
$story_excerpt = static function ($post, int $words = 24): string {
    if (!$post) {
        return 'Independent reporting and useful ideas for people building the next version of everyday life.';
    }
    $excerpt = get_the_excerpt($post);
    return wp_trim_words($excerpt ?: wp_strip_all_tags($post->post_content), $words);
};
$story_url = static function ($post): string {
    return $post ? get_permalink($post) : home_url('/journal/');
};
$story_title = static function ($post): string {
    return $post ? get_the_title($post) : 'The next issue is being edited now';
};
$story_art = static function (int $index) use ($artworks): string {
    return $artworks[$index % count($artworks)];
};
$story_image = static function ($post, int $index) use ($story_art): string {
    if ($post && has_post_thumbnail($post)) {
        $thumbnail = get_the_post_thumbnail_url($post, 'large');
        if ($thumbnail) {
            return $thumbnail;
        }
    }
    return $story_art($index);
};

$lead_posts = studio_portal_en_home_posts('lead', 1);
$lead = $lead_posts[0] ?? null;
$lead_id = $lead ? (int) $lead->ID : 0;
$side_stories = studio_portal_en_home_posts('latest', 2, array($lead_id));
$latest_exclude = array_merge(array($lead_id), array_map(static fn(WP_Post $post): int => (int) $post->ID, $side_stories));
$latest_stories = studio_portal_en_home_posts('latest', 5, $latest_exclude);
$trending_stories = studio_portal_en_home_posts('trending', 5);
$briefing_stories = studio_portal_en_home_posts('briefing', 3);
$pick_stories = studio_portal_en_home_posts('editors_pick', 3);
$home_columns = studio_portal_en_home_columns(4);
$home_topics = studio_portal_en_home_topics(6);
$briefing_signals = array_values(array_filter(array_map('trim', preg_split('/\R/', studio_portal_en_newsroom_option('briefing_signals')) ?: array())));
$newsletter_email = studio_portal_en_newsroom_option('newsletter_email');

get_header();
?>
<div class="en-news-utility en-container" data-hero-item>
    <p><?php echo esc_html(studio_portal_en_newsroom_option('issue_label')); ?></p>
    <nav aria-label="Topic shortcuts">
        <?php foreach (array_slice($home_topics, 0, 4) as $topic) : ?>
            <a href="#topic-<?php echo esc_attr($topic['term']->slug); ?>"><?php echo esc_html(strtoupper(wp_specialchars_decode($topic['term']->name, ENT_QUOTES))); ?></a>
        <?php endforeach; ?>
    </nav>
    <p><?php echo esc_html(strtoupper((new DateTimeImmutable('now', wp_timezone()))->format('D d M'))); ?> / <?php echo esc_html(strtoupper(studio_portal_en_newsroom_option('edition_label'))); ?></p>
</div>

<section class="en-news-lead en-container" aria-labelledby="lead-story-title">
    <article class="en-lead-story" data-hero-item>
        <div class="en-news-label"><span>THE BIG READ</span><span>12 MIN</span></div>
        <h1 id="lead-story-title"><a href="<?php echo esc_url($story_url($lead)); ?>"><?php echo esc_html($story_title($lead)); ?></a></h1>
        <p class="en-lead-deck"><?php echo esc_html($story_excerpt($lead, 34)); ?></p>
        <p class="en-news-byline">BY <?php echo esc_html($lead ? (get_the_author_meta('display_name', (int) $lead->post_author) ?: 'STUDIO EDITORIAL') : 'STUDIO EDITORIAL'); ?> / <?php echo esc_html($lead ? studio_portal_en_post_date('M d, Y', $lead) : 'JUL 27, 2026'); ?></p>
        <a class="en-news-read" href="<?php echo esc_url($story_url($lead)); ?>">READ THE STORY <span aria-hidden="true">&rarr;</span></a>
    </article>
    <figure class="en-lead-art" data-hero-item data-media>
        <a href="<?php echo esc_url($story_url($lead)); ?>"><img src="<?php echo esc_url($story_image($lead, 0)); ?>" alt="<?php echo esc_attr($story_title($lead)); ?>" width="1600" height="900"></a>
        <figcaption><span>STUDIO VISUAL DESK / FIG.01</span><span><?php echo esc_html($story_category($lead)); ?></span></figcaption>
    </figure>
    <div class="en-lead-side" data-stagger>
        <?php foreach ($side_stories as $index => $story) : ?>
            <article>
                <a class="en-side-art" href="<?php echo esc_url($story_url($story)); ?>"><img src="<?php echo esc_url($story_image($story, $index + 1)); ?>" alt="" width="800" height="500"></a>
                <p class="en-kicker"><?php echo esc_html($story_column($story)); ?> / 0<?php echo esc_html((string) ($index + 1)); ?></p>
                <h2><a href="<?php echo esc_url($story_url($story)); ?>"><?php echo esc_html($story_title($story)); ?></a></h2>
                <p><?php echo esc_html($story_excerpt($story, 18)); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="en-ticker" aria-label="Newsroom update">
    <div>
        <?php for ($ticker_copy = 0; $ticker_copy < 2; $ticker_copy++) : ?>
            <span<?php echo $ticker_copy ? ' aria-hidden="true"' : ''; ?>>NOW READING</span><i<?php echo $ticker_copy ? ' aria-hidden="true"' : ''; ?>>+</i>
            <?php foreach ($home_topics as $topic) : ?>
                <span<?php echo $ticker_copy ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html(strtoupper(wp_specialchars_decode($topic['term']->name, ENT_QUOTES))); ?></span><i<?php echo $ticker_copy ? ' aria-hidden="true"' : ''; ?>>+</i>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>

<section class="en-news-section en-container" id="latest">
    <header class="en-news-section-head" data-reveal>
        <p class="en-kicker">[01] LATEST</p>
        <h2>THE NEWSROOM<br>RIGHT NOW.</h2>
        <a href="<?php echo esc_url(home_url('/journal/')); ?>">VIEW ALL STORIES &rarr;</a>
    </header>
    <div class="en-latest-layout">
        <div class="en-latest-feed" data-stagger>
            <?php foreach ($latest_stories as $index => $story) : ?>
                <article>
                    <a class="en-latest-thumb" href="<?php echo esc_url($story_url($story)); ?>"><img src="<?php echo esc_url($story_image($story, $index + 3)); ?>" alt="" width="500" height="320"></a>
                    <div>
                        <p class="en-kicker"><?php echo esc_html($story_category($story)); ?> / <?php echo esc_html($story ? studio_portal_en_post_date('H:i', $story) : '09:30'); ?></p>
                        <h3><a href="<?php echo esc_url($story_url($story)); ?>"><?php echo esc_html($story_title($story)); ?></a></h3>
                        <p><?php echo esc_html($story_excerpt($story, 22)); ?></p>
                    </div>
                    <a class="en-row-arrow" href="<?php echo esc_url($story_url($story)); ?>" aria-label="Read <?php echo esc_attr($story_title($story)); ?>">&rarr;</a>
                </article>
            <?php endforeach; ?>
        </div>
        <aside class="en-most-read" data-reveal>
            <div class="en-most-read-head"><span>MOST READ</span><span>24H</span></div>
            <ol>
                <?php foreach ($trending_stories as $index => $story) : ?>
                    <li><span>0<?php echo esc_html((string) ($index + 1)); ?></span><div><p><?php echo esc_html($story_category($story)); ?></p><a href="<?php echo esc_url($story_url($story)); ?>"><?php echo esc_html($story_title($story)); ?></a></div></li>
                <?php endforeach; ?>
            </ol>
        </aside>
    </div>
</section>

<section class="en-news-section en-columns-section" id="columns">
    <div class="en-container">
        <header class="en-news-section-head" data-reveal>
            <p class="en-kicker">[02] COLUMNS</p>
            <h2>VOICES WITH<br>A POINT OF VIEW.</h2>
            <p>Recurring arguments, field reports and reviews from writers who stay with a subject.</p>
        </header>
        <div class="en-columns-grid" data-stagger>
            <?php foreach ($home_columns as $index => $column) :
                $story = $column['post'];
                $author = get_the_author_meta('display_name', (int) $story->post_author) ?: 'STUDIO EDITORIAL';
                $column_name = wp_specialchars_decode($column['term']->name, ENT_QUOTES);
                $description = trim(wp_strip_all_tags($column['term']->description)) ?: sprintf('The latest reporting and analysis from %s.', $column_name);
                ?>
                <article>
                    <div class="en-column-number">C<?php echo esc_html((string) ($index + 1)); ?></div>
                    <p>RECURRING COLUMN</p>
                    <h3><a href="<?php echo esc_url($column['url']); ?>"><?php echo esc_html(strtoupper($column_name)); ?></a></h3>
                    <p><?php echo esc_html($description); ?></p>
                    <div class="en-column-author"><span><?php echo esc_html(strtoupper(substr($author, 0, 1))); ?></span><p>BY <strong><?php echo esc_html(strtoupper($author)); ?></strong><br><?php echo esc_html(strtoupper(studio_portal_en_post_date('M d, Y', $story))); ?></p></div>
                    <a href="<?php echo esc_url($story_url($story)); ?>">LATEST: <?php echo esc_html($story_title($story)); ?> &rarr;</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="en-news-section en-container" id="topics">
    <header class="en-news-section-head" data-reveal>
        <p class="en-kicker">[03] TOPIC DESKS</p>
        <h2>FOLLOW THE<br>WHOLE STORY.</h2>
        <p>Six desks connecting breaking news to the systems, people and consequences behind it.</p>
    </header>
    <div class="en-topic-grid" data-stagger>
        <?php foreach ($home_topics as $index => $topic) :
            $story = $topic['post'];
            $topic_name = wp_specialchars_decode($topic['term']->name, ENT_QUOTES);
            $description = trim(wp_strip_all_tags($topic['term']->description)) ?: sprintf('Latest reporting from the %s desk.', $topic_name);
            ?>
            <article id="topic-<?php echo esc_attr($topic['term']->slug); ?>">
                <div><span>T<?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span><a href="<?php echo esc_url($topic['url']); ?>">OPEN DESK &rarr;</a></div>
                <h3><a href="<?php echo esc_url($topic['url']); ?>"><?php echo esc_html(strtoupper($topic_name)); ?></a></h3>
                <p><?php echo esc_html($description); ?></p>
                <a class="en-topic-story" href="<?php echo esc_url($story_url($story)); ?>"><span>LATEST</span><strong><?php echo esc_html($story_title($story)); ?></strong></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="en-news-section en-briefing" id="briefing">
    <div class="en-container">
        <header class="en-news-section-head" data-reveal>
            <p class="en-kicker">[04] THE BRIEFING</p>
            <h2>WHAT MATTERS<br>BEFORE NOON.</h2>
            <p>A compact global edit: three signals, one argument and the context to use them.</p>
        </header>
        <div class="en-briefing-grid" data-stagger>
            <article class="en-briefing-lead">
                <p>MONDAY / 07:00 GMT</p>
                <h3><?php echo esc_html(studio_portal_en_newsroom_option('briefing_title')); ?></h3>
                <ol><?php foreach ($briefing_signals as $signal) : ?><li><?php echo esc_html($signal); ?></li><?php endforeach; ?></ol>
                <a href="<?php echo esc_url($story_url($briefing_stories[0] ?? $lead)); ?>">OPEN TODAY'S BRIEFING &rarr;</a>
            </article>
            <?php foreach ($briefing_stories as $index => $story) : ?>
                <article class="en-briefing-card">
                    <span>0<?php echo esc_html((string) ($index + 1)); ?></span>
                    <p><?php echo esc_html($story_category($story)); ?></p>
                    <h3><a href="<?php echo esc_url($story_url($story)); ?>"><?php echo esc_html($story_title($story)); ?></a></h3>
                    <a href="<?php echo esc_url($story_url($story)); ?>">READ IN <?php echo esc_html((string) studio_portal_en_reading_time($story ? $story->post_content : '')); ?> MIN &rarr;</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="en-news-section en-container">
    <header class="en-news-section-head" data-reveal>
        <p class="en-kicker">[05] EDITOR'S PICKS</p>
        <h2>KEEP THESE<br>OPEN.</h2>
        <p>Long reads and practical references selected by the editorial desk.</p>
    </header>
    <div class="en-picks-grid" data-stagger>
        <?php foreach ($pick_stories as $pick_index => $story) : ?>
            <article>
                <a href="<?php echo esc_url($story_url($story)); ?>"><img src="<?php echo esc_url($story_image($story, $pick_index + 2)); ?>" alt="" width="900" height="600"></a>
                <p class="en-kicker"><?php echo esc_html($story_column($story)); ?> / PICK 0<?php echo esc_html((string) ($pick_index + 1)); ?></p>
                <h3><a href="<?php echo esc_url($story_url($story)); ?>"><?php echo esc_html($story_title($story)); ?></a></h3>
                <p><?php echo esc_html($story_excerpt($story, 22)); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="en-newsletter" id="newsletter">
    <div class="en-container" data-reveal>
        <p class="en-kicker">THE WEEKLY EDIT / NO NOISE</p>
        <h2>ONE EMAIL.<br>THE WHOLE PICTURE.</h2>
        <p>Our editors connect the week's most useful ideas across technology, culture, systems and design. Every Thursday. Free.</p>
        <a class="en-button en-button--flame" href="mailto:<?php echo esc_attr($newsletter_email); ?>?subject=Subscribe%20to%20The%20Weekly%20Edit">SUBSCRIBE BY EMAIL <span>&rarr;</span></a>
        <small><?php echo esc_html(strtoupper(studio_portal_en_newsroom_option('subscriber_label'))); ?> / UNSUBSCRIBE ANY TIME</small>
    </div>
</section>
<?php get_footer(); ?>
