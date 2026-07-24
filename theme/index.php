<?php get_header(); ?>
<section class="page-hero"><div class="page-hero-inner"><div class="eyebrow">toKraft journal</div><h1><?php bloginfo('name'); ?></h1></div></section>
<section class="section"><div class="section-inner"><?php if (have_posts()) : while (have_posts()) : the_post(); ?><article style="padding:25px 0;border-bottom:1px solid var(--line)"><h2 style="font-size:38px"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p><?php the_excerpt(); ?></p></article><?php endwhile; else : ?><p>No content has been published yet.</p><?php endif; ?></div></section>
<?php get_footer(); ?>
