<?php
get_header();

$tokraft_search_query = get_search_query();
$tokraft_result_count = isset($wp_query) ? (int) $wp_query->found_posts : 0;
?>
<section class="page-hero tokraft-search-hero">
    <div class="page-hero-inner">
        <div class="eyebrow"><?php esc_html_e('Search toKraft', 'tokraft'); ?></div>
        <h1>
            <?php
            printf(
                esc_html__('Results for "%s"', 'tokraft'),
                esc_html($tokraft_search_query)
            );
            ?>
        </h1>
        <?php get_search_form(); ?>
    </div>
</section>

<section class="section tokraft-search-results">
    <div class="section-inner">
        <?php if (have_posts()) : ?>
            <div class="tokraft-search-summary">
                <strong>
                    <?php
                    printf(
                        esc_html(_n('%s result', '%s results', $tokraft_result_count, 'tokraft')),
                        esc_html(number_format_i18n($tokraft_result_count))
                    );
                    ?>
                </strong>
                <span><?php esc_html_e('Products, services and editorial resources are searched together.', 'tokraft'); ?></span>
            </div>
            <div class="tokraft-search-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php
                    $tokraft_post_type = get_post_type();
                    $tokraft_type_object = get_post_type_object($tokraft_post_type);
                    $tokraft_type_label = $tokraft_type_object ? $tokraft_type_object->labels->singular_name : __('Resource', 'tokraft');
                    $tokraft_product = 'product' === $tokraft_post_type && function_exists('wc_get_product') ? wc_get_product(get_the_ID()) : null;
                    ?>
                    <article <?php post_class('tokraft-search-card'); ?>>
                        <a class="tokraft-search-card-media<?php echo has_post_thumbnail() ? ' has-image' : ''; ?>" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large', array('loading' => 'lazy')); ?>
                            <?php else : ?>
                                <span><?php echo esc_html(tokraft_uppercase($tokraft_type_label)); ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="tokraft-search-card-copy">
                            <div class="eyebrow"><?php echo esc_html($tokraft_type_label); ?></div>
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <?php if ($tokraft_product) : ?>
                                <div class="tokraft-search-price"><?php echo wp_kses_post($tokraft_product->get_price_html()); ?></div>
                            <?php else : ?>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
                            <?php endif; ?>
                            <a class="home-text-link" href="<?php the_permalink(); ?>">
                                <?php echo esc_html($tokraft_product ? __('View product', 'tokraft') : __('Read more', 'tokraft')); ?>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <div class="tokraft-search-pagination"><?php the_posts_pagination(); ?></div>
        <?php else : ?>
            <div class="tokraft-search-empty">
                <div class="eyebrow"><?php esc_html_e('No matches', 'tokraft'); ?></div>
                <h2><?php esc_html_e('Try a material, product or process name.', 'tokraft'); ?></h2>
                <p><?php esc_html_e('Check the spelling or browse one of the destinations below.', 'tokraft'); ?></p>
                <div class="tokraft-search-empty-actions">
                    <a class="btn btn-primary" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php esc_html_e('Browse shop', 'tokraft'); ?></a>
                    <a class="btn btn-ghost" href="<?php echo esc_url(home_url('/materials/')); ?>"><?php esc_html_e('Explore materials', 'tokraft'); ?></a>
                    <button class="btn btn-ghost" type="button" data-search-open aria-controls="tokraft-search-overlay" aria-expanded="false"><?php esc_html_e('Search again', 'tokraft'); ?></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php get_footer(); ?>
