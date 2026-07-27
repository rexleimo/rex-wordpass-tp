<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
if (have_posts()) : while (have_posts()) : the_post();
    [$article_content, $toc] = studio_portal_en_article_toc(apply_filters('the_content', get_the_content()));
    $categories = get_the_category();
    $category = $categories ? $categories[0]->name : 'Essay';
    ?>
    <article class="en-article">
        <header class="en-article-head en-container" data-hero-item>
            <div class="en-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">HOME</a> / <a href="<?php echo esc_url(home_url('/journal/')); ?>">JOURNAL</a> / ESSAY</div>
            <p class="en-kicker"><?php echo esc_html($category); ?> · <?php echo esc_html(studio_portal_en_post_date('M Y', get_the_ID())); ?></p>
            <h1><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?><p class="en-article-deck"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
            <div class="en-article-meta"><span>BY <?php echo esc_html(get_the_author() ?: 'STUDIO EDITORIAL'); ?></span><span><?php echo esc_html((string) studio_portal_en_reading_time($article_content)); ?> MIN READ</span><span>UPDATED <?php echo esc_html(studio_portal_en_post_date('M d, Y', get_the_ID(), true)); ?></span></div>
        </header>
        <?php if (has_post_thumbnail()) : ?><figure class="en-article-cover en-container" data-media><?php the_post_thumbnail('full', array('loading' => 'eager')); ?></figure><?php endif; ?>
        <div class="en-article-layout en-container">
            <?php if ($toc) : ?><aside class="en-article-toc"><span>ON THIS PAGE</span><nav><?php foreach ($toc as $index => $entry) : ?><a href="#<?php echo esc_attr($entry['id']); ?>"><b><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></b><?php echo esc_html($entry['title']); ?></a><?php endforeach; ?></nav></aside><?php endif; ?>
            <div class="en-article-content" data-article-content><?php echo $article_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
        </div>
    </article>
<?php endwhile; endif;
get_footer();
