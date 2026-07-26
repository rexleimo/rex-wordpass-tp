<?php
/**
 * Converge the shop catalogue to Bambu Lab H2D / H2S printers.
 *
 * Everything else is moved to draft (reversible). H2D/H2S are created or
 * updated by title so the script is idempotent.
 *
 * docker compose exec -T -w /var/www/html wordpress php \
 *   wp-content/themes/tokraft/tools/reset-catalog-h2.php
 */
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require '/var/www/html/wp-load.php';

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce required\n");
    exit(1);
}

function tokraft_h2_log($message) {
    echo $message, "\n";
}

function tokraft_h2_ensure_term($name, $taxonomy, $slug = '') {
    $existing = term_exists($name, $taxonomy);
    if ($existing) {
        return (int) (is_array($existing) ? $existing['term_id'] : $existing);
    }
    $args = array();
    if ($slug) {
        $args['slug'] = $slug;
    }
    $term = wp_insert_term($name, $taxonomy, $args);
    if (is_wp_error($term)) {
        tokraft_h2_log('TERM ERR ' . $name . ': ' . $term->get_error_message());
        return 0;
    }
    return (int) $term['term_id'];
}

function tokraft_h2_find_product_by_title($title) {
    $posts = get_posts(array(
        'post_type' => 'product',
        'post_status' => array('publish', 'draft', 'private', 'pending'),
        'title' => $title,
        'numberposts' => 1,
    ));
    if ($posts) {
        return (int) $posts[0]->ID;
    }

    // Fallback for older WP title matching quirks.
    $query = new WP_Query(array(
        'post_type' => 'product',
        'post_status' => array('publish', 'draft', 'private', 'pending'),
        'posts_per_page' => 50,
        's' => $title,
        'fields' => 'ids',
    ));
    foreach ($query->posts as $product_id) {
        if (get_the_title($product_id) === $title) {
            return (int) $product_id;
        }
    }
    return 0;
}

function tokraft_h2_upsert_product($config, $category_id) {
    $product_id = tokraft_h2_find_product_by_title($config['title']);
    $product = $product_id ? wc_get_product($product_id) : null;
    if (!$product || !$product->is_type('simple')) {
        $product = new WC_Product_Simple();
        if ($product_id) {
            $product->set_id($product_id);
        }
    }

    $product->set_name($config['title']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_sku($config['sku']);
    $product->set_regular_price($config['price']);
    $product->set_short_description($config['short']);
    $product->set_description($config['content']);
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_sold_individually(false);
    $product->set_menu_order($config['menu_order']);
    $product->set_category_ids(array_filter(array($category_id)));
    $saved_id = $product->save();

    update_post_meta($saved_id, '_tokraft_specifications', $config['specs']);
    update_post_meta($saved_id, '_tokraft_shipping_returns', $config['shipping']);
    update_post_meta($saved_id, '_tokraft_disclaimer', $config['disclaimer']);
    update_post_meta($saved_id, '_tokraft_features', $config['features']);
    update_post_meta($saved_id, '_tokraft_cautions', $config['cautions']);
    // Leave cover empty so ops can upload their own authorised photos.
    if (!get_post_meta($saved_id, '_tokraft_showcase_cover_id', true)) {
        update_post_meta($saved_id, '_tokraft_showcase_cover_id', 0);
    }

    tokraft_h2_log(($product_id ? 'UPDATED' : 'CREATED') . ' #' . $saved_id . ' ' . $config['title'] . ' ' . get_permalink($saved_id));
    return (int) $saved_id;
}

$keep_titles = array(
    'Bambu Lab H2D',
    'Bambu Lab H2S',
);

// Draft everything that is not one of the two printers. Variations ride along
// with their parents when the parent is drafted; we still draft orphan variations.
$all = get_posts(array(
    'post_type' => array('product', 'product_variation'),
    'post_status' => array('publish', 'private', 'pending'),
    'numberposts' => -1,
    'fields' => 'ids',
));
$drafted = 0;
foreach ($all as $product_id) {
    $title = get_the_title($product_id);
    if (in_array($title, $keep_titles, true)) {
        continue;
    }
    $result = wp_update_post(array(
        'ID' => $product_id,
        'post_status' => 'draft',
    ), true);
    if (is_wp_error($result)) {
        tokraft_h2_log('DRAFT ERR #' . $product_id . ' ' . $result->get_error_message());
        continue;
    }
    $drafted++;
}
tokraft_h2_log('Drafted ' . $drafted . ' product/variation posts');

$printers_cat = tokraft_h2_ensure_term('Printers', 'product_cat', 'printers');

$catalog = array(
    array(
        'title' => 'Bambu Lab H2D',
        'sku' => 'TK-H2D',
        'price' => '3299.00',
        'menu_order' => 1,
        'short' => 'Dual-nozzle production printer for shops that need multi-material throughput without babysitting every plate.',
        'content' => <<<HTML
<!-- wp:paragraph -->
<p>H2D is the dual-nozzle workhorse we run for mixed-material and multi-colour jobs. One machine can keep a support filament loaded while the other nozzle prints the part, so support removal and colour changes stop being a full plate restart.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Where it fits</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li>Short-run production with support material still loaded</li><li>Engineering polymers that need an actively heated chamber</li><li>Teams that want closed-loop sensors without a full industrial cell</li></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Specs below are the publicly documented machine envelope. Final print settings are tuned in-house for each material and geometry.</p>
<!-- /wp:paragraph -->
HTML,
        'specs' => array(
            'Build volume' => '325 x 320 x 325 mm',
            'Peak toolhead speed' => '1000 mm/s',
            'Max acceleration' => '20000 mm/s²',
            'Nozzle temperature' => 'Up to 350 °C',
            'Heated bed' => 'Up to 120 °C',
            'Active chamber' => 'Up to 65 °C',
            'Nozzles' => 'Dual independent nozzles',
            'Sensors / cameras' => 'Multi-sensor closed loop + multi-camera monitoring',
            'Connectivity' => 'Wi-Fi / LAN / USB',
            'Material range' => 'PLA, PETG, TPU, ABS, ASA, PC, PA, PET, PPS and fibre-filled grades',
        ),
        'features' => array(
            'Dual-nozzle workflow for multi-material or multi-colour plates',
            'Actively heated chamber for engineering polymers',
            'Closed-loop sensing intended for unattended production runs',
        ),
        'cautions' => array(
            'Final material profiles are tuned per job; published temperatures are starting points only',
            'Fibre-filled filaments require hardened nozzles and slower feed rates',
        ),
        'shipping' => 'Printers ship as complete units with a packing checklist. Transit times and carriers are confirmed at checkout or in your quote. Local Alberta pickup can be arranged after the unit has been inspected.',
        'disclaimer' => 'Machine specifications are taken from publicly documented manufacturer data and may change with firmware or hardware revisions. toKraft rewrites application guidance for our production workflow; confirm critical dimensions, materials and certifications with our team before placing a production order.',
    ),
    array(
        'title' => 'Bambu Lab H2S',
        'sku' => 'TK-H2S',
        'price' => '2499.00',
        'menu_order' => 2,
        'short' => 'Single-nozzle H2-series printer with a large chamber, high-flow tooling and the sensor stack we use for everyday production.',
        'content' => <<<HTML
<!-- wp:paragraph -->
<p>H2S is the single-nozzle sibling in the H2 family: same production-oriented chassis, actively heated chamber and high-flow tooling, without the dual-nozzle complexity when a job only needs one material path.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Where it fits</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li>Large single-material parts that still need chamber heat</li><li>Higher-flow nozzles for thicker walls and faster solid fills</li><li>Shops standardising on one production platform with optional laser modules</li></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>We keep H2S on the floor for jobs that do not need dual nozzles but still need the H2 motion system, sensors and chamber control.</p>
<!-- /wp:paragraph -->
HTML,
        'specs' => array(
            'Build volume' => '340 x 320 x 340 mm',
            'Peak toolhead speed' => '1000 mm/s',
            'Max acceleration' => '20000 mm/s²',
            'Nozzle temperature' => 'Up to 350 °C',
            'Heated bed' => 'Up to 120 °C',
            'Active chamber' => 'Up to 65 °C',
            'High-flow nozzle' => '40 mm³/s standard, optional 65 mm³/s',
            'Motion accuracy' => '< 50 μm',
            'Sensors / cameras' => '23 sensors + 3 cameras',
            'Noise' => '< 50 dB in quiet modes',
            'Material range' => 'PLA, PETG, TPU, PVA, BVOH, ABS, ASA, PC, PA, PET, PPS and CF/GF grades',
            'Optional modules' => '10W laser cutting module; AMS 2 Pro / AMS HT compatible',
        ),
        'features' => array(
            'Larger single-nozzle build volume than H2D',
            'High-flow tooling for thicker walls and faster solid regions',
            'Optional laser and AMS modules for expanded shop workflows',
        ),
        'cautions' => array(
            'Optional laser modules require the matching safety enclosure and local compliance checks',
            'Published speeds assume calibrated belts, nozzles and filament drying',
        ),
        'shipping' => 'Printers ship as complete units with a packing checklist. Transit times and carriers are confirmed at checkout or in your quote. Local Alberta pickup can be arranged after the unit has been inspected.',
        'disclaimer' => 'Machine specifications are taken from publicly documented manufacturer data and may change with firmware or hardware revisions. toKraft rewrites application guidance for our production workflow; confirm critical dimensions, materials and certifications with our team before placing a production order.',
    ),
);

$ids = array();
foreach ($catalog as $config) {
    $ids[] = tokraft_h2_upsert_product($config, $printers_cat);
}

// Final publish check.
$published = get_posts(array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'numberposts' => -1,
    'fields' => 'ids',
));
tokraft_h2_log('Published products now: ' . count($published));
foreach ($published as $product_id) {
    tokraft_h2_log('  #' . $product_id . ' ' . get_the_title($product_id) . ' ' . get_permalink($product_id));
}

tokraft_h2_log('Done. Kept/created: ' . implode(', ', $ids));
