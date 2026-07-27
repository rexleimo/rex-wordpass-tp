<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

if (have_posts()) :
    while (have_posts()) :
        the_post();
        $categories = get_the_category();
        $category = $categories ? $categories[0]->name : __('深度文章', 'studio-portal');
        [$article_content, $toc] = studio_portal_article_content_with_toc(get_the_content());
        $reading_time = studio_portal_reading_time_for_content($article_content);
        ?>
        <article <?php post_class('sp-article'); ?>>
            <header class="sp-article-head">
                <div class="sp-article-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('首页', 'studio-portal'); ?></a><span>/</span><a href="<?php echo esc_url(home_url('/journal/')); ?>"><?php esc_html_e('全部文章', 'studio-portal'); ?></a><span>/</span><span><?php echo esc_html($category); ?></span></div>
                <p class="sp-kicker"><?php echo esc_html($category); ?> · <?php echo esc_html(get_the_date('Y.m.d')); ?></p>
                <h1><?php the_title(); ?></h1>
                <?php if (has_excerpt()) : ?><p class="sp-article-deck"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
                <div class="sp-article-meta"><span><?php echo esc_html(get_the_author()); ?></span><span><?php printf(esc_html__('%d 分钟阅读', 'studio-portal'), $reading_time); ?></span><a href="<?php echo esc_url(home_url('/journal/')); ?>">← <?php esc_html_e('返回全部文章', 'studio-portal'); ?></a></div>
            </header>
            <?php if (has_post_thumbnail()) : ?>
                <figure class="sp-article-cover"><?php the_post_thumbnail('full', array('loading' => 'eager')); ?></figure>
            <?php else : ?>
                <div class="sp-article-cover sp-article-cover--fallback" role="img" aria-label="<?php echo esc_attr(get_the_title()); ?>"><span><?php echo esc_html($category); ?></span><b><?php echo esc_html(get_the_date('Y')); ?></b></div>
            <?php endif; ?>
            <div class="sp-article-layout <?php echo $toc ? '' : 'has-no-toc'; ?>">
                <?php if ($toc) : ?>
                    <aside class="sp-article-toc" aria-label="<?php esc_attr_e('文章目录', 'studio-portal'); ?>"><span><?php esc_html_e('本文目录', 'studio-portal'); ?></span><nav><?php foreach ($toc as $entry) : ?><a class="level-<?php echo esc_attr((string) $entry['level']); ?>" href="#<?php echo esc_attr($entry['id']); ?>"><?php echo esc_html($entry['title']); ?></a><?php endforeach; ?></nav></aside>
                <?php endif; ?>
                <div class="sp-article-content"><?php echo $article_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            </div>
            <?php $tags = get_the_tags(); if ($tags) : ?><footer class="sp-article-tags"><?php foreach ($tags as $tag) : ?><a href="<?php echo esc_url(get_tag_link($tag)); ?>"><?php echo esc_html($tag->name); ?></a><?php endforeach; ?></footer><?php endif; ?>
        </article>
        <?php
    endwhile;
endif;

get_footer();
