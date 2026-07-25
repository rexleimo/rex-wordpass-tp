<?php
/**
 * Technical editorial product detail.
 */
defined('ABSPATH') || exit;
global $product;
do_action('woocommerce_before_single_product');
if (post_password_required()) {
    echo get_the_password_form();
    return;
}

$cats = get_the_terms(get_the_ID(), 'product_cat');
$cat_slugs = array();
$cat_trail = array();
if (!is_wp_error($cats) && $cats) {
    foreach ($cats as $cat) {
        $cat_slugs[] = $cat->slug;
        $cat_trail[] = $cat->name;
    }
}

$kind = 'filament';
if (in_array('accessories', $cat_slugs, true)) {
    $kind = 'accessory';
} elseif (in_array('bundles', $cat_slugs, true)) {
    $kind = 'bundle';
}

$is_variable = $product->is_type('variable');
$color_map = get_post_meta(get_the_ID(), '_tokraft_color_map', true);
if (!is_array($color_map)) {
    $color_map = array();
}
$shop_label = array(
    'filament' => __('Filaments', 'tokraft'),
    'accessory' => __('Accessories', 'tokraft'),
    'bundle' => __('Bundles', 'tokraft'),
)[$kind];
$category_label = $cat_trail ? $cat_trail[0] : '';
if ($category_label && strcasecmp($category_label, $shop_label) === 0) {
    $category_label = '';
}

$material_label = wp_strip_all_tags($product->get_attribute('material'));
if (!$material_label && preg_match('/\b(PLA|PETG|TPU|ABS|ASA|PA|PC)\b/i', $product->get_name(), $material_match)) {
    $material_label = strtoupper($material_match[1]);
}
if (!$material_label) {
    $material_label = $kind === 'filament' ? __('Filament', 'tokraft') : $shop_label;
}

$format_label = wp_strip_all_tags($product->get_attribute('size'));
if (!$format_label) {
    $format_label = $is_variable ? __('Configurable', 'tokraft') : __('Ready to fit', 'tokraft');
}
$fulfilment_label = $product->is_in_stock() ? __('Ready to ship', 'tokraft') : __('Made to order', 'tokraft');
$initial_price_html = $product->get_price_html();
if ($is_variable && $product->get_default_attributes()) {
    $data_store = WC_Data_Store::load('product');
    $default_variation_id = $data_store->find_matching_product_variation($product, $product->get_default_attributes());
    $default_variation = $default_variation_id ? wc_get_product($default_variation_id) : false;
    if ($default_variation) {
        $initial_price_html = $default_variation->get_price_html();
    }
}
$specifications = get_post_meta(get_the_ID(), '_tokraft_specifications', true);
$shipping_returns = get_post_meta(get_the_ID(), '_tokraft_shipping_returns', true);
$discover_product_ids = array_values(array_filter(array_map('absint', (array) get_post_meta(get_the_ID(), '_tokraft_discover_product_ids', true))));
$discover_product_notes = get_post_meta(get_the_ID(), '_tokraft_discover_product_notes', true);
if (!is_array($discover_product_notes)) {
    $discover_product_notes = array();
}
$discover_products = array();
foreach ($discover_product_ids as $discover_product_id) {
    if ($discover_product_id === $product->get_id()) {
        continue;
    }

    $discover_product = wc_get_product($discover_product_id);
    // Keep the purchase area honest: only show products that can be added here.
    if (!$discover_product || !$discover_product->is_type('simple') || !$discover_product->is_purchasable() || !$discover_product->is_in_stock()) {
        continue;
    }

    $discover_products[] = $discover_product;
    if (count($discover_products) === 3) {
        break;
    }
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('tk-product-detail bambu-like kind-' . esc_attr($kind), $product); ?>
    data-color-map="<?php echo esc_attr(wp_json_encode($color_map)); ?>">
    <div class="tk-product-top">
        <nav class="tk-product-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'tokraft'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
            <span>/</span>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php echo esc_html($shop_label); ?></a>
            <?php if ($category_label) : ?>
                <span>/</span>
                <span><?php echo esc_html($category_label); ?></span>
            <?php endif; ?>
            <span>/</span>
            <span><?php the_title(); ?></span>
        </nav>

        <div class="tk-product-main">
            <div class="tk-product-gallery">
                <div class="tk-product-gallery-frame">
                    <span class="tk-product-gallery-label">PRODUCT PHOTO / <?php echo esc_html(strtoupper($material_label)); ?></span>
                    <?php do_action('woocommerce_before_single_product_summary'); ?>
                    <div class="tk-product-tech-rail" aria-label="<?php esc_attr_e('Product overview', 'tokraft'); ?>">
                        <div><span><?php esc_html_e('Material', 'tokraft'); ?></span><strong><?php echo esc_html($material_label); ?></strong></div>
                        <div><span><?php esc_html_e('Format', 'tokraft'); ?></span><strong><?php echo esc_html($format_label); ?></strong></div>
                        <div><span><?php esc_html_e('Fulfilment', 'tokraft'); ?></span><strong><?php echo esc_html($fulfilment_label); ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="summary entry-summary tk-product-summary">
                <p class="tk-product-badge"><?php echo esc_html(strtoupper($fulfilment_label . ' / ' . $shop_label)); ?></p>

                <h1 class="product_title entry-title"><?php the_title(); ?></h1>

                <div class="tk-product-price-row">
                    <p class="<?php echo esc_attr(apply_filters('woocommerce_product_price_class', 'price')); ?>">
                        <span class="tk-product-current-price"><?php echo wp_kses_post($initial_price_html); ?></span>
                        <span class="tk-fcard-currency">CAD</span>
                    </p>
                    <?php if ($is_variable) : ?>
                        <small class="tk-price-note" aria-live="polite">1 kg / selected configuration / in stock</small>
                    <?php endif; ?>
                </div>

                <div class="woocommerce-product-details__short-description">
                    <?php echo wp_kses_post(wpautop($product->get_short_description())); ?>
                </div>

                <div class="tk-product-buy tk-pdp-options">
                    <?php woocommerce_template_single_add_to_cart(); ?>
                </div>

                <?php if ($discover_products) : ?>
                    <section class="tk-discover-more" aria-labelledby="tk-discover-more-heading" data-tk-discover-more>
                        <header class="tk-discover-more-header">
                            <p><?php esc_html_e('Optional companions', 'tokraft'); ?></p>
                            <h2 id="tk-discover-more-heading"><?php esc_html_e('Discover More Here!', 'tokraft'); ?></h2>
                            <span><?php esc_html_e('Useful additions selected from our current material catalogue.', 'tokraft'); ?></span>
                        </header>
                        <div class="tk-discover-more-list">
                            <?php foreach ($discover_products as $discover_product) : ?>
                                <?php
                                $discover_id = $discover_product->get_id();
                                $discover_name = $discover_product->get_name();
                                $discover_note = isset($discover_product_notes[$discover_id]) ? $discover_product_notes[$discover_id] : $discover_product->get_short_description();
                                $discover_input_id = 'tk-discover-quantity-' . $discover_id;
                                ?>
                                <article class="tk-discover-item" data-tk-discover-item>
                                    <a class="tk-discover-item-image" href="<?php echo esc_url($discover_product->get_permalink()); ?>" tabindex="-1" aria-hidden="true">
                                        <?php echo $discover_product->get_image('woocommerce_thumbnail', array('loading' => 'lazy', 'decoding' => 'async', 'alt' => $discover_name)); ?>
                                    </a>
                                    <div class="tk-discover-item-copy">
                                        <h3><a href="<?php echo esc_url($discover_product->get_permalink()); ?>"><?php echo esc_html($discover_name); ?></a></h3>
                                        <div class="tk-discover-item-price"><?php echo wp_kses_post($discover_product->get_price_html()); ?></div>
                                        <?php if ($discover_note) : ?>
                                            <p><?php echo esc_html(wp_strip_all_tags($discover_note)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <form class="tk-discover-item-form cart" method="post" action="<?php echo esc_url($discover_product->add_to_cart_url()); ?>" data-tk-discover-form data-product-id="<?php echo esc_attr($discover_id); ?>">
                                        <div class="tk-discover-quantity" role="group" aria-label="<?php echo esc_attr(sprintf(__('Quantity for %s', 'tokraft'), $discover_name)); ?>">
                                            <button type="button" data-tk-discover-quantity="decrease" aria-controls="<?php echo esc_attr($discover_input_id); ?>" aria-label="<?php echo esc_attr(sprintf(__('Decrease quantity of %s', 'tokraft'), $discover_name)); ?>">-</button>
                                            <input id="<?php echo esc_attr($discover_input_id); ?>" class="tk-discover-quantity-input" type="number" name="quantity" value="1" min="1" step="1" inputmode="numeric" aria-label="<?php echo esc_attr(sprintf(__('Quantity for %s', 'tokraft'), $discover_name)); ?>">
                                            <button type="button" data-tk-discover-quantity="increase" aria-controls="<?php echo esc_attr($discover_input_id); ?>" aria-label="<?php echo esc_attr(sprintf(__('Increase quantity of %s', 'tokraft'), $discover_name)); ?>">+</button>
                                        </div>
                                        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($discover_id); ?>">
                                        <button type="submit" class="tk-discover-add-button button"><?php esc_html_e('Add', 'tokraft'); ?></button>
                                    </form>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <p class="tk-product-stock-note">
                    <?php echo $product->is_in_stock() ? esc_html__('In stock · Shipping calculated at checkout', 'tokraft') : esc_html__('Out of stock', 'tokraft'); ?>
                </p>
                <div class="tk-product-custom-callout">
                    <div>
                        <strong><?php esc_html_e('Need a custom setup?', 'tokraft'); ?></strong>
                        <span><?php esc_html_e('Share dimensions, a drawing or a photo for a human-reviewed print quote.', 'tokraft'); ?></span>
                    </div>
                    <a href="<?php echo esc_url(home_url('/quote/')); ?>"><?php esc_html_e('Request a quote', 'tokraft'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <section class="tk-product-spec-section" aria-labelledby="tk-product-details-heading">
        <header class="tk-product-detail-intro">
            <p>Product information</p>
            <h2 id="tk-product-details-heading">Product details</h2>
        </header>
        <div class="tk-product-tabs" role="tablist" aria-label="Product information sections">
            <button type="button" id="tk-product-tab-details" class="is-active" role="tab" aria-selected="true" aria-controls="tk-product-panel-details" tabindex="0" data-tk-tab="details">Details</button>
            <button type="button" id="tk-product-tab-specs" role="tab" aria-selected="false" aria-controls="tk-product-panel-specs" tabindex="-1" data-tk-tab="specs">Specifications</button>
            <button type="button" id="tk-product-tab-ship" role="tab" aria-selected="false" aria-controls="tk-product-panel-ship" tabindex="-1" data-tk-tab="ship">Shipping & returns</button>
            <button type="button" id="tk-product-tab-disclaimer" role="tab" aria-selected="false" aria-controls="tk-product-panel-disclaimer" tabindex="-1" data-tk-tab="disclaimer">Disclaimer</button>
        </div>
        <div class="tk-product-tab-panels">
            <div id="tk-product-panel-details" class="tk-product-tab-panel is-active" role="tabpanel" aria-labelledby="tk-product-tab-details" tabindex="0" data-tk-panel="details">
                <div class="tk-product-longform"><?php the_content(); ?></div>
            </div>
            <div id="tk-product-panel-specs" class="tk-product-tab-panel" role="tabpanel" aria-labelledby="tk-product-tab-specs" tabindex="0" data-tk-panel="specs" hidden>
                <div class="tk-product-spec-table">
                    <h2>Product specifications</h2>
                    <div class="tk-spec-row"><span>Product</span><strong><?php echo esc_html($product->get_name()); ?></strong></div>
                    <div class="tk-spec-row"><span>Type</span><strong><?php echo esc_html(ucfirst($kind)); ?></strong></div>
                    <div class="tk-spec-row"><span>SKU</span><strong><?php echo esc_html($product->get_sku() ?: '—'); ?></strong></div>
                    <div class="tk-spec-row"><span>Stock</span><strong><?php echo esc_html($product->is_in_stock() ? 'In stock' : 'Out of stock'); ?></strong></div>
                    <?php if (is_array($specifications)) : ?>
                        <?php foreach ($specifications as $label => $value) : ?>
                            <div class="tk-spec-row"><span><?php echo esc_html($label); ?></span><strong><?php echo esc_html($value); ?></strong></div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="product-attributes"><?php wc_display_product_attributes($product); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div id="tk-product-panel-ship" class="tk-product-tab-panel" role="tabpanel" aria-labelledby="tk-product-tab-ship" tabindex="0" data-tk-panel="ship" hidden>
                <h2>Shipping & returns</h2>
                <p><?php echo esc_html($shipping_returns ?: 'Shipping charges and delivery estimates are shown at checkout. Contact us before using an item that arrives damaged or incorrect.'); ?></p>
            </div>
            <div id="tk-product-panel-disclaimer" class="tk-product-tab-panel" role="tabpanel" aria-labelledby="tk-product-tab-disclaimer" tabindex="0" data-tk-panel="disclaimer" hidden>
                <h2>Disclaimer</h2>
                <p><?php echo wp_kses_post(get_post_meta(get_the_ID(), '_tokraft_disclaimer', true) ?: 'Confirm suitability for your application before production use. Colour and packaging can vary slightly by batch.'); ?></p>
            </div>
        </div>
    </section>

    <div class="tk-product-related">
        <?php
        remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
        do_action('woocommerce_after_single_product_summary');
        ?>
    </div>
</div>
<?php do_action('woocommerce_after_single_product'); ?>
