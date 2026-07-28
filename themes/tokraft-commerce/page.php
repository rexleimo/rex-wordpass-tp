<?php
$tokraft_is_account_page = function_exists('is_account_page') && is_account_page();
get_header();
?>
<section class="page-hero"><div class="page-hero-inner"><div class="eyebrow">toKraft</div><h1><?php the_title(); ?></h1></div></section>
<section class="section<?php echo $tokraft_is_account_page ? ' account-page' : ''; ?>"><div class="section-inner"<?php echo $tokraft_is_account_page ? '' : ' style="max-width:880px"'; ?>><?php while (have_posts()) : the_post(); the_content(); endwhile; ?></div></section>
<?php get_footer(); ?>
