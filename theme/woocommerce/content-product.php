<?php
/**
 * Bambu-like filament product card.
 */
defined('ABSPATH') || exit;
global $product;
if (empty($product) || !$product->is_visible()) {
    return;
}

$cats = get_the_terms($product->get_id(), 'product_cat');
$cat_label = '';
if (!is_wp_error($cats) && $cats) {
    foreach ($cats as $cat) {
        if ((int) $cat->parent > 0) {
            $cat_label = $cat->name;
            break;
        }
    }
    if (!$cat_label) {
        $cat_label = $cats[0]->name;
    }
}

$swatches = tokraft_product_color_swatches($product);
$gallery_ids = $product->get_gallery_image_ids();
$hover_src = '';
if (!empty($gallery_ids[0])) {
    $hover_src = wp_get_attachment_image_url($gallery_ids[0], 'large');
}
$primary_src = wp_get_attachment_image_url($product->get_image_id(), 'large');
$can_quick_add = $product->is_purchasable() && $product->is_in_stock() && $product->is_type('simple');
?>
<li <?php wc_product_class('tk-fcard', $product); ?> data-product-id="<?php echo esc_attr($product->get_id()); ?>">
    <div class="tk-fcard-media">
        <a class="tk-fcard-link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>">
            <?php if ($primary_src) : ?>
                <img class="tk-fcard-img is-primary" src="<?php echo esc_url($primary_src); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" loading="lazy" width="600" height="600">
            <?php else : ?>
                <?php echo $product->get_image('large'); ?>
            <?php endif; ?>
            <?php if ($hover_src) : ?>
                <img class="tk-fcard-img is-hover" src="<?php echo esc_url($hover_src); ?>" alt="" loading="lazy" width="600" height="600" aria-hidden="true">
            <?php endif; ?>
        </a>
        <?php if ($product->is_on_sale()) : ?>
            <span class="tk-fcard-badge sale"><?php esc_html_e('Sale', 'tokraft'); ?></span>
        <?php elseif ($product->is_in_stock()) : ?>
            <span class="tk-fcard-badge">IN / STOCK</span>
        <?php endif; ?>

        <?php if ($can_quick_add) : ?>
            <form class="tk-fcard-quick-add cart" method="post" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>">
                <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>">
                <button type="submit" class="tk-fcard-atc button"><?php esc_html_e('Add to Cart', 'tokraft'); ?></button>
            </form>
        <?php else : ?>
            <a class="tk-fcard-atc" href="<?php the_permalink(); ?>"><?php esc_html_e('View product', 'tokraft'); ?></a>
        <?php endif; ?>
    </div>

    <div class="tk-fcard-body">
        <?php if ($cat_label) : ?>
            <div class="tk-fcard-cat"><?php echo esc_html($cat_label); ?></div>
        <?php endif; ?>
        <h2 class="woocommerce-loop-product__title">
            <a href="<?php the_permalink(); ?>">
                <?php
                $name = $product->get_name();
                // Bambu-style: first word on one line, rest on the next, e.g. "PLA<br>Basic".
                if (strpos($name, ' / ') !== false) {
                    echo esc_html(str_replace(' / ', ' /', $name));
                } elseif (strpos($name, ' ') !== false) {
                    // insert <br> after first space, escape rest
                    $parts = explode(' ', $name, 2);
                    echo esc_html($parts[0]) . '<br>' . esc_html($parts[1]);
                } else {
                    echo esc_html($name);
                }
                ?>
            </a>
        </h2>
        <div class="tk-fcard-price">
            <?php echo wp_kses_post($product->get_price_html()); ?>
            <span class="tk-fcard-currency">CAD</span>
        </div>
        <?php if ($swatches) : ?>
            <div class="tk-fcard-swatches" role="radiogroup" aria-label="<?php esc_attr_e('Available colours', 'tokraft'); ?>">
                <?php foreach ($swatches as $i => $swatch) : ?>
                    <button
                        type="button"
                        class="tk-swatch<?php echo $i === 0 ? ' is-active' : ''; ?>"
                        role="radio"
                        aria-checked="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                        aria-label="<?php echo esc_attr($swatch['label']); ?>"
                        data-color="<?php echo esc_attr($swatch['hex']); ?>"
                        data-image="<?php echo esc_attr($swatch['image'] ?? ''); ?>"
                        style="--swatch: <?php echo esc_attr($swatch['hex']); ?>"
                    ></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</li>
