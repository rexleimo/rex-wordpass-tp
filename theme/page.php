<?php get_header(); ?>
<section class="page-hero"><div class="page-hero-inner"><div class="eyebrow">toKraft</div><h1><?php the_title(); ?></h1></div></section>
<section class="section"><div class="section-inner" style="max-width:880px"><?php while (have_posts()) : the_post(); the_content(); endwhile; ?></div></section>
<?php get_footer(); ?>
