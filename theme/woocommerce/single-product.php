<?php
defined('ABSPATH') || exit;
get_header('shop');
?>
<section class="shop-page-hero"><div class="shop-page-inner"><div class="eyebrow">Ready-to-order parts</div><p class="shop-breadcrumb"><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Shop</a> / <?php the_title(); ?></p></div></section>
<section class="tokraft-shop-shell">
<?php while (have_posts()) : the_post(); wc_get_template_part('content', 'single-product'); endwhile; ?>
</section>
<?php get_footer('shop');
