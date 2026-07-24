<?php get_header(); ?>
<?php while (have_posts()) : the_post();
    $materials = get_the_terms(get_the_ID(), 'tokraft_material');
    $industry = get_post_meta(get_the_ID(), '_tokraft_case_industry', true); ?>
    <article class="case-single">
        <div class="case-single-copy"><span class="route-kicker">Application example<?php echo $industry ? ' / ' . esc_html($industry) : ''; ?></span><h1><?php the_title(); ?></h1><?php if (!is_wp_error($materials) && $materials) : ?><p class="case-materials">Material: <?php echo esc_html(implode(', ', wp_list_pluck($materials, 'name'))); ?></p><?php endif; ?><div class="case-single-content"><?php the_content(); ?></div><a class="btn btn-primary" href="<?php echo esc_url(home_url('/quote/')); ?>">Get a print quote <span aria-hidden="true">&rarr;</span></a></div>
        <?php if (has_post_thumbnail()) : ?><div class="case-single-image"><?php the_post_thumbnail('large', array('loading' => 'eager')); ?></div><?php endif; ?>
    </article>
<?php endwhile; ?>
<?php get_footer(); ?>
