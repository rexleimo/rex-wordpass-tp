<?php get_header(); ?>
<?php while (have_posts()) : the_post(); ?>
<article class="blog-single">
    <header class="blog-single-hero<?php echo has_post_thumbnail() ? ' has-cover' : ' no-cover'; ?>">
        <?php if (has_post_thumbnail()) : ?>
            <figure class="blog-single-cover">
                <?php the_post_thumbnail('full', array('loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1440px) 1344px, calc(100vw - 36px)')); ?>
            </figure>
        <?php endif; ?>
        <div class="blog-single-hero-copy">
            <div class="eyebrow"><?php esc_html_e('toKraft journal', 'tokraft'); ?></div>
            <h1><?php the_title(); ?></h1>
            <p class="blog-single-meta">
                <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date()); ?></time>
                <span aria-hidden="true">·</span>
                <span><?php echo esc_html(sprintf(__('%d min read', 'tokraft'), max(3, (int) ceil(str_word_count(wp_strip_all_tags(get_post_field('post_content', get_the_ID()))) / 180)))); ?></span>
            </p>
            <?php if (has_excerpt()) : ?>
                <p class="blog-single-dek"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>
        </div>
    </header>

    <div class="blog-single-body">
        <div class="blog-single-content entry-content">
            <?php the_content(); ?>
        </div>
        <aside class="blog-single-aside">
            <div class="blog-single-card">
                <span class="eyebrow"><?php esc_html_e('Next step', 'tokraft'); ?></span>
                <h2><?php esc_html_e('Ready to print a part?', 'tokraft'); ?></h2>
                <p><?php esc_html_e('Upload your model, set materials and print preferences, and we will review the file before confirming price and schedule.', 'tokraft'); ?></p>
                <a class="btn btn-primary btn-small" href="<?php echo esc_url(home_url('/quote/')); ?>"><?php esc_html_e('Start a quote', 'tokraft'); ?> <span aria-hidden="true">&rarr;</span></a>
            </div>
            <div class="blog-single-card is-muted">
                <span class="eyebrow"><?php esc_html_e('Also useful', 'tokraft'); ?></span>
                <a href="<?php echo esc_url(home_url('/materials/')); ?>"><?php esc_html_e('Material library', 'tokraft'); ?></a>
                <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php esc_html_e('Ready-to-order shop', 'tokraft'); ?></a>
                <a href="<?php echo esc_url(get_post_type_archive_link('tokraft_case_study') ?: home_url('/case-studies/')); ?>"><?php esc_html_e('Application examples', 'tokraft'); ?></a>
            </div>
        </aside>
    </div>
</article>
<?php endwhile; ?>
<?php get_footer(); ?>
