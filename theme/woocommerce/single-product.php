<?php
/**
 * Single product page shell.
 */
defined('ABSPATH') || exit;
get_header('shop');
?>
<section class="tk-product-page">
    <?php while (have_posts()) : the_post(); ?>
        <?php wc_get_template_part('content', 'single-product'); ?>
    <?php endwhile; ?>
</section>
<?php
get_footer('shop');
