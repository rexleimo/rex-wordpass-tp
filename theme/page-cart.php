<?php
/**
 * Classic cart page shell — full-width SHENS layout (not page.php 880px clamp).
 */
defined('ABSPATH') || exit;
get_header('shop');
?>
<section class="tk-cart-page">
    <div class="tk-cart-hero">
        <p class="tk-shop-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">HOME</a>
            <span>/</span>
            <span>CART</span>
        </p>
        <h1><?php esc_html_e('Your cart', 'tokraft'); ?></h1>
        <p><?php esc_html_e('Review items, update quantities, then continue to checkout.', 'tokraft'); ?></p>
    </div>
    <div class="tk-cart-shell">
        <?php echo do_shortcode('[woocommerce_cart]'); ?>
    </div>
</section>
<?php
get_footer('shop');
