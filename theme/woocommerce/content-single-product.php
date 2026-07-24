<?php
defined('ABSPATH') || exit;
global $product;
do_action('woocommerce_before_single_product');
if (post_password_required()) { echo get_the_password_form(); return; }
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('tokraft-product-detail', $product); ?>>
    <div class="tokraft-product-main">
        <div class="tokraft-product-gallery"><?php do_action('woocommerce_before_single_product_summary'); ?></div>
        <div class="summary entry-summary tokraft-product-summary"><?php do_action('woocommerce_single_product_summary'); ?></div>
    </div>
    <div class="tokraft-product-information">
        <section><span class="route-kicker">Specifications</span><h2>Made for real use.</h2><div class="product-attributes"><?php wc_display_product_attributes($product); ?></div></section>
        <section><span class="route-kicker">Material & use</span><h2>Choose what fits the job.</h2><?php
            $materials = get_the_terms(get_the_ID(), 'tokraft_material');
            if (!is_wp_error($materials) && $materials) : ?>
                <ul class="product-material-list"><?php foreach ($materials as $material) :
                    $description = get_term_meta($material->term_id, '_tokraft_material_short_description', true); ?>
                    <li><strong><?php echo esc_html($material->name); ?></strong><?php if ($description) : ?><span><?php echo esc_html($description); ?></span><?php endif; ?><a href="<?php echo esc_url(add_query_arg('material', $material->slug, home_url('/quote/'))); ?>">Request this material</a></li>
                <?php endforeach; ?></ul>
            <?php else : ?>
                <p><?php echo wp_kses_post(get_post_meta(get_the_ID(), '_tokraft_material_notes', true) ?: 'Select material and color options that match the intended application. Each product is produced with practical durability and everyday use in mind.'); ?></p>
            <?php endif; ?>
        </section>
        <section><span class="route-kicker">Installation</span><h2>Designed to be straightforward.</h2><p><?php echo wp_kses_post(get_post_meta(get_the_ID(), '_tokraft_installation_notes', true) ?: 'Follow the included fitting instructions. Verify dimensions and the intended mounting surface before installation.'); ?></p></section>
        <section class="product-disclaimer"><span class="route-kicker">Disclaimer</span><p><?php echo wp_kses_post(get_post_meta(get_the_ID(), '_tokraft_disclaimer', true) ?: '3D printed products have layer lines and material-specific characteristics. Confirm compatibility with your use case before ordering.'); ?></p></section>
    </div>
    <div class="tokraft-product-related"><?php do_action('woocommerce_after_single_product_summary'); ?></div>
</div>
<?php do_action('woocommerce_after_single_product'); ?>
