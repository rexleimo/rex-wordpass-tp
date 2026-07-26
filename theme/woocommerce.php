<?php
/**
 * Filament shop archive — Bambu-like layout + SHENS tokens.
 */
defined('ABSPATH') || exit;

if (!(function_exists('is_shop') && (is_shop() || is_product_taxonomy()))) {
    get_header('shop');
    echo '<section class="tokraft-shop-shell" style="padding:40px 32px 80px">';
    woocommerce_content();
    echo '</section>';
    get_footer('shop');
    return;
}

// Showcase mode is the cover-led layout for a short line-up. Category archives
// always keep the catalogue form so filtering still works once the range grows.
if (is_shop() && function_exists('tokraft_shop_layout') && 'showcase' === tokraft_shop_layout()) {
    require get_theme_file_path('woocommerce-showcase.php');
    return;
}

global $wp_query;
$count = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
$current_slug = '';
if (is_product_category()) {
    $term = get_queried_object();
    $current_slug = ($term && !is_wp_error($term)) ? $term->slug : '';
}

$material_filters = array(
    array('slug' => '', 'label' => 'All'),
    array('slug' => 'pla', 'label' => 'PLA'),
    array('slug' => 'petg', 'label' => 'PETG'),
    array('slug' => 'engineering', 'label' => 'ABS/ASA'),
    array('slug' => 'tpu', 'label' => 'TPU'),
    array('slug' => 'fiber-reinforced', 'label' => 'Fiber Reinforced'),
    array('slug' => 'bundles', 'label' => 'Bundles'),
    array('slug' => 'accessories', 'label' => 'Accessories'),
);

$orderby = isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : 'menu_order';
$search = isset($_GET['s']) ? wc_clean(wp_unslash($_GET['s'])) : '';
$min_price = isset($_GET['min_price']) ? wc_clean(wp_unslash($_GET['min_price'])) : '';
$max_price = isset($_GET['max_price']) ? wc_clean(wp_unslash($_GET['max_price'])) : '';
$stock = isset($_GET['stock_status']) ? wc_clean(wp_unslash($_GET['stock_status'])) : '';

get_header('shop');
?>
<section class="tk-filaments">
    <header class="tk-filaments-hero">
        <div class="tk-filaments-hero-copy">
            <p class="tk-shop-breadcrumb">
                <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                <span>/</span>
                <span>Filaments</span>
                <?php if ($current_slug) : ?>
                    <span>/</span>
                    <span><?php echo esc_html(ucwords(str_replace('-', ' ', $current_slug))); ?></span>
                <?php endif; ?>
            </p>
            <h1><?php echo esc_html($current_slug ? single_term_title('', false) : __('Filaments', 'tokraft')); ?></h1>
            <p><?php esc_html_e('Production filament stock for everyday prints and engineering jobs. Filter by material, price and availability — CAD prices shown before cart.', 'tokraft'); ?></p>
        </div>
        <a class="btn btn-primary" href="<?php echo esc_url(home_url('/quote/')); ?>"><?php esc_html_e('Custom print quote', 'tokraft'); ?></a>
    </header>

    <div class="tk-filaments-layout">
        <aside class="tk-filaments-filters" aria-label="<?php esc_attr_e('Product filters', 'tokraft'); ?>">
            <div class="tk-filter-head">
                <strong><?php esc_html_e('Filter', 'tokraft'); ?></strong>
                <a class="tk-filter-clear" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Clear', 'tokraft'); ?></a>
            </div>

            <details class="tk-filter-group" open>
                <summary><?php esc_html_e('By Material', 'tokraft'); ?></summary>
                <ul class="tk-filter-list">
                    <?php foreach ($material_filters as $item) :
                        $active = ($item['slug'] === $current_slug) || ($item['slug'] === '' && $current_slug === '' && is_shop());
                        if ($item['slug'] === '') {
                            $url = wc_get_page_permalink('shop');
                        } else {
                            $term = get_term_by('slug', $item['slug'], 'product_cat');
                            $url = ($term && !is_wp_error($term)) ? get_term_link($term) : wc_get_page_permalink('shop');
                        }
                        if (!is_wp_error($url) && $orderby && $orderby !== 'menu_order') {
                            $url = add_query_arg('orderby', $orderby, $url);
                        }
                        ?>
                        <li>
                            <a class="<?php echo $active ? 'is-active' : ''; ?>" href="<?php echo esc_url($url); ?>">
                                <span class="tk-check" aria-hidden="true"></span>
                                <?php echo esc_html($item['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </details>

            <details class="tk-filter-group" open>
                <summary><?php esc_html_e('Availability', 'tokraft'); ?></summary>
                <ul class="tk-filter-list">
                    <?php
                    $base = is_product_category() ? get_term_link(get_queried_object()) : wc_get_page_permalink('shop');
                    if (is_wp_error($base)) {
                        $base = wc_get_page_permalink('shop');
                    }
                    $in_url = remove_query_arg('stock_status', $base);
                    $out_url = add_query_arg('stock_status', 'outofstock', $base);
                    $in_url = add_query_arg('stock_status', 'instock', $base);
                    ?>
                    <li><a class="<?php echo $stock === 'instock' ? 'is-active' : ''; ?>" href="<?php echo esc_url($in_url); ?>"><span class="tk-check"></span><?php esc_html_e('In Stock', 'tokraft'); ?></a></li>
                    <li><a class="<?php echo $stock === 'outofstock' ? 'is-active' : ''; ?>" href="<?php echo esc_url($out_url); ?>"><span class="tk-check"></span><?php esc_html_e('Out of Stock', 'tokraft'); ?></a></li>
                </ul>
            </details>

            <details class="tk-filter-group" open>
                <summary><?php esc_html_e('Price (CAD)', 'tokraft'); ?></summary>
                <form class="tk-price-filter" method="get" action="">
                    <?php if ($current_slug) : ?>
                        <!-- category archive keeps path -->
                    <?php endif; ?>
                    <?php if ($orderby) : ?><input type="hidden" name="orderby" value="<?php echo esc_attr($orderby); ?>"><?php endif; ?>
                    <?php if ($stock) : ?><input type="hidden" name="stock_status" value="<?php echo esc_attr($stock); ?>"><?php endif; ?>
                    <?php if ($search) : ?><input type="hidden" name="s" value="<?php echo esc_attr($search); ?>"><input type="hidden" name="post_type" value="product"><?php endif; ?>
                    <div class="tk-price-inputs">
                        <label><span>$</span><input type="number" name="min_price" min="0" step="1" placeholder="Min" value="<?php echo esc_attr($min_price); ?>"></label>
                        <span>To</span>
                        <label><span>$</span><input type="number" name="max_price" min="0" step="1" placeholder="Max" value="<?php echo esc_attr($max_price); ?>"></label>
                    </div>
                    <button type="submit" class="btn btn-ghost btn-small"><?php esc_html_e('Apply', 'tokraft'); ?></button>
                </form>
            </details>

            <details class="tk-filter-group">
                <summary><?php esc_html_e('Spool Type', 'tokraft'); ?></summary>
                <ul class="tk-filter-list">
                    <li><span class="tk-check"></span><?php esc_html_e('Filament with spool', 'tokraft'); ?></li>
                    <li><span class="tk-check"></span><?php esc_html_e('Bundle / multi-spool', 'tokraft'); ?></li>
                </ul>
            </details>
        </aside>

        <div class="tk-filaments-main">
            <div class="tk-filaments-toolbar">
                <form class="tk-filaments-search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <input type="hidden" name="post_type" value="product">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search for products in this collection', 'tokraft'); ?>">
                    <button type="submit" aria-label="<?php esc_attr_e('Search', 'tokraft'); ?>">⌕</button>
                </form>
                <div class="tk-filaments-meta">
                    <span class="tk-filaments-count"><strong><?php echo (int) $count; ?></strong> <?php echo esc_html(_n('item', 'items', $count, 'tokraft')); ?></span>
                    <form class="tk-filaments-sort" method="get" action="">
                        <?php if ($stock) : ?><input type="hidden" name="stock_status" value="<?php echo esc_attr($stock); ?>"><?php endif; ?>
                        <?php if ($min_price !== '') : ?><input type="hidden" name="min_price" value="<?php echo esc_attr($min_price); ?>"><?php endif; ?>
                        <?php if ($max_price !== '') : ?><input type="hidden" name="max_price" value="<?php echo esc_attr($max_price); ?>"><?php endif; ?>
                        <label class="screen-reader-text" for="tk-orderby"><?php esc_html_e('Sort', 'tokraft'); ?></label>
                        <select name="orderby" id="tk-orderby" onchange="this.form.submit()">
                            <option value="menu_order" <?php selected($orderby, 'menu_order'); ?>><?php esc_html_e('Sort: Featured', 'tokraft'); ?></option>
                            <option value="popularity" <?php selected($orderby, 'popularity'); ?>><?php esc_html_e('Sort: Popularity', 'tokraft'); ?></option>
                            <option value="date" <?php selected($orderby, 'date'); ?>><?php esc_html_e('Sort: Newest', 'tokraft'); ?></option>
                            <option value="price" <?php selected($orderby, 'price'); ?>><?php esc_html_e('Sort: Price low–high', 'tokraft'); ?></option>
                            <option value="price-desc" <?php selected($orderby, 'price-desc'); ?>><?php esc_html_e('Sort: Price high–low', 'tokraft'); ?></option>
                        </select>
                    </form>
                    <div class="tk-grid-toggle" role="group" aria-label="<?php esc_attr_e('Grid density', 'tokraft'); ?>">
                        <button type="button" data-tk-grid="2" aria-label="<?php esc_attr_e('Two per row', 'tokraft'); ?>">▥</button>
                        <button type="button" data-tk-grid="4" class="is-active" aria-label="<?php esc_attr_e('Four per row', 'tokraft'); ?>">▦</button>
                    </div>
                </div>
            </div>

            <div class="tk-filaments-grid-shell" data-tk-grid-cols="4">
                <?php woocommerce_content(); ?>
            </div>
        </div>
    </div>
</section>
<?php
get_footer('shop');
