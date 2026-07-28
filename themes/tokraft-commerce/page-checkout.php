<?php
/**
 * Checkout keeps WooCommerce Blocks intact while giving the purchase flow
 * the same full-width commerce shell as the cart.
 */
defined('ABSPATH') || exit;

get_header('shop');
?>
<section class="tk-checkout-page">
    <div class="tk-checkout-hero">
        <p class="tk-shop-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('HOME', 'tokraft'); ?></a>
            <span>/</span>
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('CART', 'tokraft'); ?></a>
            <span>/</span>
            <span><?php esc_html_e('CHECKOUT', 'tokraft'); ?></span>
        </p>
        <div class="tk-checkout-hero__content">
            <div>
                <h1><?php esc_html_e('Checkout', 'tokraft'); ?></h1>
                <p><?php esc_html_e('Add your details, review your order, and place it when everything looks right.', 'tokraft'); ?></p>
            </div>
            <ol class="tk-checkout-steps" aria-label="<?php esc_attr_e('Checkout progress', 'tokraft'); ?>">
                <li><a href="<?php echo esc_url(wc_get_cart_url()); ?>"><span>01</span><?php esc_html_e('Cart', 'tokraft'); ?></a></li>
                <li aria-current="step"><span>02</span><?php esc_html_e('Details', 'tokraft'); ?></li>
                <li><span>03</span><?php esc_html_e('Review', 'tokraft'); ?></li>
            </ol>
        </div>
    </div>
    <div class="tk-checkout-shell">
        <?php while (have_posts()) : the_post(); ?>
            <?php the_content(); ?>
        <?php endwhile; ?>
    </div>
</section>
<?php
get_footer('shop');
