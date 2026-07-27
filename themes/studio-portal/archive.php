<?php
if (!defined('ABSPATH')) {
    exit;
}

$archive_title = (is_category() || is_tag() || is_tax())
    ? single_term_title('', false)
    : wp_strip_all_tags(get_the_archive_title());
$archive_description = wp_strip_all_tags(get_the_archive_description());
$archive_count = (int) $GLOBALS['wp_query']->found_posts;

get_header();
?>
<section class="sp-inner-hero sp-archive-hero">
    <div class="sp-inner-container sp-inner-hero-grid">
        <div>
            <p class="sp-inner-eyebrow"><span></span><?php esc_html_e('TOPIC ARCHIVE', 'studio-portal'); ?></p>
            <h1><?php echo esc_html($archive_title); ?></h1>
        </div>
        <div class="sp-inner-hero-note">
            <span><?php echo esc_html(str_pad((string) $archive_count, 2, '0', STR_PAD_LEFT)); ?></span>
            <p><?php echo $archive_description ? esc_html($archive_description) : esc_html__('围绕同一个问题持续整理，把零散信息变成可以反复查阅的判断与方法。', 'studio-portal'); ?></p>
        </div>
    </div>
</section>

<section class="sp-inner-section sp-inner-container">
    <header class="sp-inner-section-head">
        <div><p><?php esc_html_e('ALL STORIES', 'studio-portal'); ?></p><h2><?php esc_html_e('这个主题下的全部文章', 'studio-portal'); ?></h2></div>
        <p><?php printf(esc_html__('%d 篇内容，按发布时间由近到远排列。', 'studio-portal'), $archive_count); ?></p>
    </header>

    <?php if (have_posts()) : ?>
        <div class="sp-inner-card-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php $categories = get_the_category(); $category = $categories ? $categories[0]->name : __('深度文章', 'studio-portal'); ?>
                <article <?php post_class('sp-inner-card'); ?>>
                    <a class="sp-inner-card-link" href="<?php the_permalink(); ?>">
                        <span class="sp-inner-card-media">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large', array('loading' => 'lazy', 'decoding' => 'async', 'alt' => get_the_title())); ?>
                            <?php else : ?>
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
        <div class="sp-inner-empty"><span><?php esc_html_e('NO STORIES YET', 'studio-portal'); ?></span><h2><?php esc_html_e('这个主题的内容正在整理中。', 'studio-portal'); ?></h2><a href="<?php echo esc_url(home_url('/journal/')); ?>"><?php esc_html_e('返回全部文章 →', 'studio-portal'); ?></a></div>
    <?php endif; ?>
</section>

<section class="sp-inner-cta">
    <div class="sp-inner-container"><p><?php esc_html_e('KEEP EXPLORING', 'studio-portal'); ?></p><h2><?php esc_html_e('从主题出发，找到下一条阅读路径。', 'studio-portal'); ?></h2><a href="<?php echo esc_url(home_url('/services/')); ?>"><?php esc_html_e('浏览全部主题', 'studio-portal'); ?> <span>→</span></a></div>
</section>
<?php get_footer(); ?>
