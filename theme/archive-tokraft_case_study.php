<?php get_header(); ?>
<section class="catalog-hero">
    <div><span class="route-kicker">Application examples</span><h1>Functional parts, carefully specified.</h1><p>Explore material and application examples for prototypes, workshop fixtures and production-ready components. Replace these demonstration entries with approved toKraft projects before presenting them as customer work.</p></div>
</section>
<main class="case-archive">
    <?php if (have_posts()) : ?><div class="case-archive-grid"><?php while (have_posts()) : the_post();
        $materials = get_the_terms(get_the_ID(), 'tokraft_material');
        $material_name = !is_wp_error($materials) && $materials ? $materials[0]->name : '';
        $industry = get_post_meta(get_the_ID(), '_tokraft_case_industry', true); ?>
        <article class="case-archive-card"><a class="case-archive-image" href="<?php the_permalink(); ?>"><?php if (has_post_thumbnail()) { the_post_thumbnail('large', array('loading' => 'lazy')); } ?></a><div><span class="route-kicker"><?php echo esc_html($material_name); ?></span><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php if ($industry) : ?><p><?php echo esc_html($industry); ?></p><?php endif; ?></div></article>
    <?php endwhile; ?></div><?php the_posts_pagination(); ?><?php else : ?><p class="empty-state">Application examples will appear here as they are published.</p><?php endif; ?>
</main>
<?php get_footer(); ?>
