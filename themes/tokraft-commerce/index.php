<?php get_header(); ?>
<section class="page-hero">
    <div class="page-hero-inner">
        <div class="eyebrow">toKraft journal</div>
        <h1><?php echo esc_html(get_the_title(get_option('page_for_posts')) ?: __('Blog', 'tokraft')); ?></h1>
        <p><?php esc_html_e('Practical notes on quoting, materials and production-ready printed parts.', 'tokraft'); ?></p>
    </div>
</section>
<section class="section">
    <div class="section-inner">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article class="blog-list-item" style="padding:28px 0;border-bottom:1px solid var(--line)">
                    <div class="eyebrow" style="margin-bottom:10px"><?php echo esc_html(get_the_date()); ?></div>
                    <h2 style="font-size:34px;line-height:1.15;margin:0 0 10px"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <p style="max-width:70ch;margin:0"><?php echo esc_html(get_the_excerpt()); ?></p>
                    <p style="margin:14px 0 0"><a class="btn btn-ghost btn-small" href="<?php the_permalink(); ?>"><?php esc_html_e('Read article', 'tokraft'); ?> <span aria-hidden="true">&rarr;</span></a></p>
                </article>
            <?php endwhile; ?>
            <div style="padding:28px 0"><?php the_posts_pagination(); ?></div>
        <?php else : ?>
            <p class="empty-state"><?php esc_html_e('No articles have been published yet.', 'tokraft'); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php get_footer(); ?>
