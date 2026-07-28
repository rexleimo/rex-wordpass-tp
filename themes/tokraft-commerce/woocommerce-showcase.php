<?php
/**
 * Shop archive — showcase layout.
 *
 * Cover-image-led alternative to the filtered catalogue in woocommerce.php.
 * Built for a short line-up: every product gets a full-width cover card instead
 * of a grid cell. Switch back via toKraft → 首页内容区块 → 商城页面布局.
 */
defined('ABSPATH') || exit;

$showcase_products = wc_get_products(array(
    'status' => 'publish',
    'limit' => 12,
    'orderby' => 'menu_order',
    'order' => 'ASC',
    'visibility' => 'catalog',
));

/**
 * Cover priority: explicit showcase cover, then the featured image, then the
 * first gallery image. Returns an attachment ID or 0.
 */
$showcase_cover_id = static function ($product) {
    $cover_id = (int) get_post_meta($product->get_id(), '_tokraft_showcase_cover_id', true);
    if ($cover_id) {
        return $cover_id;
    }
    if ($product->get_image_id()) {
        return (int) $product->get_image_id();
    }
    $gallery = $product->get_gallery_image_ids();
    return $gallery ? (int) $gallery[0] : 0;
};

/** First N rows of the product spec table, as label/value pairs. */
$showcase_specs = static function ($product, $limit = 3) {
    $specs = get_post_meta($product->get_id(), '_tokraft_specifications', true);
    if (!is_array($specs) || !$specs) {
        return array();
    }
    $rows = array();
    foreach ($specs as $label => $value) {
        if ('' === trim((string) $value)) {
            continue;
        }
        $rows[] = array('label' => (string) $label, 'value' => (string) $value);
        if (count($rows) >= $limit) {
            break;
        }
    }
    return $rows;
};

$button_url = tokraft_home_value('shop_showcase_button_url');
if (!$button_url) {
    $button_url = home_url('/quote/');
}

get_header('shop');
?>
<section class="tk-showcase">
    <header class="tk-showcase-hero">
        <p class="tk-shop-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'tokraft'); ?></a>
            <span>/</span>
            <span><?php esc_html_e('Shop', 'tokraft'); ?></span>
        </p>
        <p class="tk-showcase-eyebrow"><?php echo esc_html(tokraft_home_value('shop_showcase_eyebrow')); ?></p>
        <h1><?php echo esc_html(tokraft_home_value('shop_showcase_title')); ?></h1>
        <p class="tk-showcase-lede"><?php echo esc_html(tokraft_home_value('shop_showcase_text')); ?></p>
        <a class="btn btn-primary" href="<?php echo esc_url($button_url); ?>">
            <?php echo esc_html(tokraft_home_value('shop_showcase_button_label')); ?>
            <span aria-hidden="true">→</span>
        </a>
    </header>

    <?php if (!$showcase_products) : ?>
        <div class="tk-showcase-empty">
            <h2><?php esc_html_e('No products published yet.', 'tokraft'); ?></h2>
            <p><?php esc_html_e('Publish a product in the backend and it appears here automatically. Custom parts can still be quoted in the meantime.', 'tokraft'); ?></p>
            <a class="btn btn-ghost" href="<?php echo esc_url(home_url('/quote/')); ?>"><?php esc_html_e('Request a custom print', 'tokraft'); ?></a>
        </div>
    <?php else : ?>
        <div class="tk-showcase-list">
            <?php foreach ($showcase_products as $index => $product) :
                $cover_id = $showcase_cover_id($product);
                $specs = $showcase_specs($product);
                $permalink = $product->get_permalink();
                $short = wp_strip_all_tags($product->get_short_description());
                if (!$short) {
                    $short = wp_trim_words(wp_strip_all_tags($product->get_description()), 32);
                }
                $card_id = 'tk-showcase-title-' . $product->get_id();
                ?>
                <article class="tk-showcase-card<?php echo ($index % 2) ? ' is-flipped' : ''; ?>" aria-labelledby="<?php echo esc_attr($card_id); ?>">
                    <div class="tk-showcase-cover">
                        <?php if ($cover_id) : ?>
                            <?php
                            echo wp_get_attachment_image($cover_id, 'full', false, array(
                                'loading' => $index ? 'lazy' : 'eager',
                                'decoding' => 'async',
                                'alt' => $product->get_name(),
                            ));
                            ?>
                        <?php else : ?>
                            <span class="tk-showcase-cover-fallback"><?php echo esc_html(tokraft_uppercase($product->get_name())); ?></span>
                        <?php endif; ?>
                        <span class="tk-showcase-index"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                    </div>
                    <div class="tk-showcase-copy">
                        <p class="tk-showcase-kicker">
                            <?php echo esc_html($product->is_in_stock() ? __('In stock', 'tokraft') : __('Made to order', 'tokraft')); ?>
                        </p>
                        <h2 id="<?php echo esc_attr($card_id); ?>">
                            <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($product->get_name()); ?></a>
                        </h2>
                        <?php if ($short) : ?>
                            <p class="tk-showcase-text"><?php echo esc_html($short); ?></p>
                        <?php endif; ?>
                        <?php if ($specs) : ?>
                            <dl class="tk-showcase-specs">
                                <?php foreach ($specs as $spec) : ?>
                                    <div>
                                        <dt><?php echo esc_html($spec['label']); ?></dt>
                                        <dd><?php echo esc_html($spec['value']); ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                        <?php endif; ?>
                        <div class="tk-showcase-foot">
                            <span class="tk-showcase-price"><?php echo wp_kses_post($product->get_price_html()); ?></span>
                            <span class="tk-showcase-link" aria-hidden="true"><?php esc_html_e('Explore', 'tokraft'); ?> →</span>
                        </div>
                    </div>
                    <?php // Covers the whole card so the click target matches what the design implies. ?>
                    <a class="tk-showcase-overlay" href="<?php echo esc_url($permalink); ?>" tabindex="-1" aria-hidden="true"></a>
                </article>
            <?php endforeach; ?>
        </div>

        <?php
        // Comparison bar only reads when the machines share spec labels.
        $compare_products = array_slice($showcase_products, 0, 2);
        $compare_rows = array();
        if (count($compare_products) === 2) {
            $left = get_post_meta($compare_products[0]->get_id(), '_tokraft_specifications', true);
            $right = get_post_meta($compare_products[1]->get_id(), '_tokraft_specifications', true);
            if (is_array($left) && is_array($right)) {
                foreach ($left as $label => $value) {
                    if (!isset($right[$label]) || '' === trim((string) $value) || '' === trim((string) $right[$label])) {
                        continue;
                    }
                    $compare_rows[] = array('label' => (string) $label, 'left' => (string) $value, 'right' => (string) $right[$label]);
                    if (count($compare_rows) >= 6) {
                        break;
                    }
                }
            }
        }
        ?>
        <?php if ($compare_rows) : ?>
            <section class="tk-showcase-compare" aria-labelledby="tk-showcase-compare-heading">
                <h2 id="tk-showcase-compare-heading"><?php esc_html_e('Side by side', 'tokraft'); ?></h2>
                <div class="tk-showcase-compare-table">
                    <div class="tk-showcase-compare-head">
                        <span></span>
                        <span><?php echo esc_html($compare_products[0]->get_name()); ?></span>
                        <span><?php echo esc_html($compare_products[1]->get_name()); ?></span>
                    </div>
                    <?php foreach ($compare_rows as $row) : ?>
                        <div class="tk-showcase-compare-row">
                            <span><?php echo esc_html($row['label']); ?></span>
                            <span><?php echo esc_html($row['left']); ?></span>
                            <span><?php echo esc_html($row['right']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php
get_footer('shop');
