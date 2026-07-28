<?php get_header(); ?>
<section class="page-hero">
    <div class="page-hero-inner">
        <div class="eyebrow">toKraft journal</div>
        <h1><?php echo esc_html(get_the_title(get_option('page_for_posts')) ?: __('Blog', 'tokraft')); ?></h1>
        <p><?php esc_html_e('Practical notes on quoting, materials and production-ready printed parts.', 'tokraft'); ?></p>
    </div>
</section>
<section class="section blog-archive">
    <div class="section-inner blog-archive-grid">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class('blog-card'); ?>>
                    <a class="blog-card-media<?php echo has_post_thumbnail() ? ' has-image' : ''; ?>" href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('large', array('loading' => 'lazy')); ?>
                        <?php endif; ?>
                    </a>
                    <div class="blog-card-copy">
                        <div class="eyebrow"><?php echo esc_html(get_the_date()); ?></div>
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <p><?php echo esc_html(get_the_excerpt()); ?></p>
                        <a class="home-text-link" href="<?php the_permalink(); ?>"><?php esc_html_e('Read article', 'tokraft'); ?> <span aria-hidden="true">&rarr;</span></a>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <p class="empty-state"><?php esc_html_e('No articles have been published yet.', 'tokraft'); ?></p>
        <?php endif; ?>
    </div>
    <div class="section-inner" style="padding-top:10px;padding-bottom:70px"><?php the_posts_pagination(); ?></div>
</section>
<?php get_footer(); ?>
