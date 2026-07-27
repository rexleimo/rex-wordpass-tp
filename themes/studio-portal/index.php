<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="sp-inner-hero">
    <div class="sp-inner-container sp-inner-hero-grid">
        <div>
            <p class="sp-inner-eyebrow"><span></span><?php esc_html_e('EDITORIAL INDEX', 'studio-portal'); ?></p>
            <h1><?php esc_html_e('持续更新的技术观察与工程判断。', 'studio-portal'); ?></h1>
        </div>
        <div class="sp-inner-hero-note"><span>01</span><p><?php esc_html_e('这里收录所有公开内容。你可以顺着时间浏览，也可以从主题和指南进入更清晰的阅读路径。', 'studio-portal'); ?></p></div>
    </div>
</section>

<section class="sp-inner-section sp-inner-container">
    <header class="sp-inner-section-head">
        <div><p><?php esc_html_e('ALL STORIES', 'studio-portal'); ?></p><h2><?php esc_html_e('全部文章', 'studio-portal'); ?></h2></div>
        <p><?php esc_html_e('中文负责清晰表达，英文只保留在必要的专业语境与栏目眉题中。', 'studio-portal'); ?></p>
    </header>
    <?php if (have_posts()) : ?>
        <div class="sp-inner-card-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php $categories = get_the_category(); $category = $categories ? $categories[0]->name : __('深度文章', 'studio-portal'); ?>
                <article <?php post_class('sp-inner-card'); ?>>
                    <a class="sp-inner-card-link" href="<?php the_permalink(); ?>">
                        <span class="sp-inner-card-media">
                            <?php if (has_post_thumbnail()) : the_post_thumbnail('medium_large', array('loading' => 'lazy', 'decoding' => 'async', 'alt' => get_the_title())); else : ?>
                                <span class="sp-inner-card-fallback" role="img" aria-label="<?php echo esc_attr(get_the_title()); ?>"><small><?php echo esc_html($category); ?></small><b><?php echo esc_html(get_the_date('d')); ?></b></span>
                            <?php endif; ?>
                        </span>
                        <span class="sp-inner-card-meta"><b><?php echo esc_html($category); ?></b><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y.m.d')); ?></time></span>
                        <strong><?php the_title(); ?></strong>
                        <span class="sp-inner-card-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 30)); ?></span>
                        <span class="sp-inner-card-foot"><small><?php echo esc_html(studio_portal_reading_time(get_the_ID())); ?> 分钟阅读</small><b><?php esc_html_e('阅读文章 →', 'studio-portal'); ?></b></span>
                    </a>
                </article>
            <?php endwhile; ?>
        </div>
        <nav class="sp-inner-pagination" aria-label="<?php esc_attr_e('文章分页', 'studio-portal'); ?>"><?php the_posts_pagination(array('mid_size' => 1, 'prev_text' => '← 上一页', 'next_text' => '下一页 →')); ?></nav>
    <?php else : ?>
        <div class="sp-inner-empty"><span><?php esc_html_e('NO STORIES YET', 'studio-portal'); ?></span><h2><?php esc_html_e('第一篇内容正在整理中。', 'studio-portal'); ?></h2><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('返回首页 →', 'studio-portal'); ?></a></div>
    <?php endif; ?>
</section>
<?php get_footer(); ?>
