<?php
/**
 * Seed shop products from Bambu Lab filament collection first page data.
 * Images sideloaded from public CDN URLs captured from the live collection page.
 *
 * php /var/www/html/wp-content/themes/tokraft/tools/seed-filament-products.php
 */
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require '/var/www/html/wp-load.php';

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce required\n");
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function tokraft_log($msg) {
    echo $msg, "\n";
}

function tokraft_ensure_term($name, $taxonomy, $parent = 0, $slug = '') {
    $existing = term_exists($name, $taxonomy);
    if ($existing) {
        return (int) (is_array($existing) ? $existing['term_id'] : $existing);
    }
    $args = array();
    if ($slug) {
        $args['slug'] = $slug;
    }
    if ($parent) {
        $args['parent'] = $parent;
    }
    $term = wp_insert_term($name, $taxonomy, $args);
    if (is_wp_error($term)) {
        tokraft_log('TERM ERR ' . $name . ': ' . $term->get_error_message());
        return 0;
    }
    return (int) $term['term_id'];
}

function tokraft_usd_to_cad($usd) {
    return (string) round(((float) $usd) * 1.35, 2);
}

function tokraft_sideload_image($url, $title) {
    static $cache = array();
    if (isset($cache[$url])) {
        return $cache[$url];
    }

    // Prefer cleaner source URL without heavy transform if possible.
    $clean = preg_replace('/__op__.*$/', '', $url);
    $try = array($url);
    if ($clean && $clean !== $url) {
        $try[] = $clean;
    }

    $attachment_id = 0;
    foreach ($try as $candidate) {
        $id = media_sideload_image($candidate, 0, $title, 'id');
        if (!is_wp_error($id) && $id) {
            $attachment_id = (int) $id;
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $title);
            break;
        }
        tokraft_log('IMG fail ' . $title . ' :: ' . (is_wp_error($id) ? $id->get_error_message() : 'unknown') . ' url=' . $candidate);
    }

    $cache[$url] = $attachment_id;
    return $attachment_id;
}

function tokraft_guess_material_family($title) {
    $t = strtoupper($title);
    if (false !== strpos($t, 'TPU')) {
        return 'TPU';
    }
    if (false !== strpos($t, 'ASA')) {
        return 'ASA';
    }
    if (false !== strpos($t, 'ABS')) {
        return 'ABS';
    }
    if (false !== strpos($t, 'PETG') || 0 === strpos($t, 'PET-')) {
        return 'PETG';
    }
    if (false !== strpos($t, 'PLA')) {
        return 'PLA';
    }
    if (preg_match('/\bPA\d|\bPAHT|\bPPA|\bPPS|\bPC\b/', $t)) {
        return 'Nylon (PA)';
    }
    return '';
}

function tokraft_guess_category_slug($title) {
    $t = strtoupper($title);
    if (false !== strpos($t, 'SPOOL') && false === strpos($t, 'PLA') && false === strpos($t, 'PETG') && false === strpos($t, 'TPU')) {
        return 'accessories';
    }
    if (false !== strpos($t, 'BUNDLE') || false !== strpos($t, 'PACK') || false !== strpos($t, 'STARTER')) {
        return 'bundles';
    }
    if (false !== strpos($t, '-CF') || false !== strpos($t, '-GF') || false !== strpos($t, 'FIBER')) {
        return 'fiber-reinforced';
    }
    if (false !== strpos($t, 'TPU')) {
        return 'tpu';
    }
    if (false !== strpos($t, 'ASA') || false !== strpos($t, 'ABS') || false !== strpos($t, 'PC') || preg_match('/\bPA\d|\bPAHT|\bPPA|\bPPS|\bPET-CF/', $t)) {
        return 'engineering';
    }
    if (false !== strpos($t, 'PETG')) {
        return 'petg';
    }
    if (false !== strpos($t, 'PLA')) {
        return 'pla';
    }
    return 'filament';
}

function tokraft_product_blurb($title, $family) {
    $base = array(
        'PLA' => 'Easy-print PLA filament for models, fixtures and everyday indoor parts. Clean detail and reliable bed adhesion.',
        'PETG' => 'Tough PETG filament for brackets, jigs and functional housings that need more durability than PLA.',
        'ASA' => 'Weather-ready ASA filament for outdoor fixtures and UV-exposed parts.',
        'ABS' => 'Impact-resistant ABS filament for enclosures and warmer indoor use cases.',
        'TPU' => 'Flexible TPU filament for feet, bumpers, gaskets and impact-absorbing parts.',
        'Nylon (PA)' => 'Engineering filament for higher-duty mechanical parts. Confirm dryer and process settings before production.',
    );
    $summary = isset($base[$family]) ? $base[$family] : 'Production filament for FDM printing. Confirm material fit for your part environment before ordering.';
    return array(
        'short' => $title . ' — ' . $summary,
        'long' => '<p>' . esc_html($summary) . '</p><p>Sold as a ready-to-order spool for local printing and shop projects. Colour and exact variant availability can vary by batch; final selection is confirmed at checkout or during fulfilment.</p><ul><li>Use case guidance available in the material library</li><li>Custom printed parts still go through the quote workflow</li><li>Made for practical production, not catalogue-only demos</li></ul>',
    );
}

// Draft old demo printed-parts products so the shop focuses on filament inventory.
foreach (array('cable-dock', 'under-desk-headphone-hook', 'workshop-tool-hanger', 'outdoor-cable-guide') as $old_slug) {
    $old = get_page_by_path($old_slug, OBJECT, 'product');
    if ($old) {
        wp_update_post(array('ID' => $old->ID, 'post_status' => 'draft'));
        tokraft_log('Drafted old product #' . $old->ID . ' ' . $old_slug);
    }
}

// Categories
$cat_filament = tokraft_ensure_term('Filament', 'product_cat', 0, 'filament');
$subcats = array(
    'pla' => 'PLA',
    'petg' => 'PETG',
    'engineering' => 'Engineering',
    'tpu' => 'TPU',
    'fiber-reinforced' => 'Fiber Reinforced',
    'bundles' => 'Bundles',
    'accessories' => 'Accessories',
);
$cat_map = array('filament' => $cat_filament);
foreach ($subcats as $slug => $label) {
    $cat_map[$slug] = tokraft_ensure_term($label, 'product_cat', $cat_filament, $slug);
}

// First-page collection data (title, USD price, image URL).
$products = array(
    array('PLA Basic', '12.99', 'https://store.bblcdn.com/s7/default/8b4f6ec791ab4cf3815cb2ddd3473d43/PLABasic.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Matte', '12.99', 'https://store.bblcdn.com/s7/default/dcaa2b99b00b433ca562f8dffb48a6ef/PLAMatte.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Pure', '14.29', 'https://store.bblcdn.com/s7/default/fa13a6fee5b74643a435ea5ae3aaa3b1/20260616-095409.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PETG Basic', '11.69', 'https://store.bblcdn.com/s7/default/6de667e142ec4c21bd9419a3d08feab5/1.png__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Silk+', '12.99', 'https://store.bblcdn.com/s7/default/533b82498e69479fb9b3f3358884a3c4/PLA_Silk_model_2.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Translucent', '14.94', 'https://store.bblcdn.com/s7/default/1ca67c64e814468d9b7c820388e8a20b/PLA_Translucent_interaction.png__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PETG Translucent', '12.99', 'https://store.bblcdn.com/s7/default/62fc8fa4a41848ab96e2d38a69f154a8/1a0131d637ff5b88f0146384e77220c1.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('ABS', '12.99', 'https://store.bblcdn.com/s7/default/f62181138052404ab9ae9a54df9bbbfd/BambuABSfilament_1.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PETG HF', '12.99', 'https://store.bblcdn.com/s7/default/74641acf3ac041bb8a93319a534f2096/PETG_HF_pic.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Tough+', '20.99', 'https://store.bblcdn.com/s7/default/bc5d618a73414598b79794fcce9e9ffd/pla_tough.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Basic Starter Classic Pack', '65.99', 'https://store.bblcdn.com/s7/default/eba4fb3d06fd416f99d5f78293ea5b2d/20250811-115747.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA CMYK Lithophane Bundle', '65.99', 'https://store.bblcdn.com/s7/default/0e739e8a26b94e74bc7e2af2d3315d62/CMYKLithophanebundle.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Silk Multi-Color', '24.99', 'https://store.bblcdn.com/s7/default/c3171a1c3d1b457cb3e0540e4862c57f/pla_silk_gradient_model_1.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA-CF', '31.99', 'https://store.bblcdn.com/s7/default/8cd6f82911534710a91dbfba51ce3a47/PLA-CF.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('Reusable Spool', '11.99', 'https://store.bblcdn.com/s7/default/32a1cc8cc47d4f26b4bb75deeef96dfb/a-zA-Z0-9.2.png__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Basic Gradient', '24.99', 'https://store.bblcdn.com/s7/default/a9f9fad49ee54898ae1e2319435f4fda/2_4be2ce8d-cb75-4dcb-975c-419922b52689.png__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Sparkle', '24.99', 'https://store.bblcdn.com/s7/default/f85fca41c8db45b493d1019db7604c25/PLASparkle.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Metal', '24.99', 'https://store.bblcdn.com/s7/default/aea990af675d44bfb990db4987854f6e/Metalmodel1.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Marble', '24.99', 'https://store.bblcdn.com/s7/default/d435b5df8f1e4232a1a32dc4d230b51d/marblecookie.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Galaxy', '24.99', 'https://store.bblcdn.com/s7/default/7750c77e5de74c3480a4230e59a22ce0/PLAGalaxy.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Wood', '24.99', 'https://store.bblcdn.com/s7/default/1cf3a503ebc94093b3060d55a60d0470/PLA_Wood_2.png__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PLA Glow', '24.99', 'https://store.bblcdn.com/s7/default/d9a59b6344414b648da6c7e2fbf72d7c/PLAGlow.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('TPU for AMS', '34.99', 'https://store.bblcdn.com/s7/default/cf84186e3a9e462f8f1cfc1b30916814/1a0131d637ff5b88f0146384e77220c1_e4ca1891-4984-4ffc-901b-b7c417bc75bf.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('TPU 95A HF', '41.99', 'https://store.bblcdn.com/s7/default/58ed2cb8089840748db3c493eec3e777/TPU_95A_HF.jpg__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('TPU 85A / TPU 90A', '41.99', 'https://store.bblcdn.com/s7/default/def1db0ade91478499be90557e898dd5/silver2.png__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PETG-CF', '31.99', 'https://store.bblcdn.com/s7/default/7e58422cceeb42cb8e8ac26c20d1805b/Frame_1123861736.png__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PAHT-CF', '49.99', 'https://store.bblcdn.com/s7/default/fe28e2fca678410ca6d1ced6be5721f1/PAHT-CF_6.png__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('ASA', '29.99', 'https://store.bblcdn.com/s7/default/7fda50731fb64a90b802d818292b17ec/ASAModel_1.png__op__resize,m_lfit,w_640__op__format,f_auto__op__quality,q_80'),
    array('PA6-CF', '42.99', 'https://store.bblcdn.com/s7/default/9232afdd9bd24638baa1f2411daa32e4/Rectangle1307.png__op__resize,m_lfit,w_1080__op__format,f_auto__op__quality,q_80'),
    array('PC', '39.99', 'https://store.bblcdn.com/s7/default/b381757c23d546719ee9baa56e6ff005/PC_d749e9d8-30a1-440a-9594-6fda08ac3172.jpg__op__resize,m_lfit,w_1080__op__format,f_auto__op__quality,q_80'),
    array('PET-CF', '44.99', 'https://store.bblcdn.com/s7/default/ae74c2401dfa467da10ee4acc2f33485/PETG-CF-model.png__op__resize,m_lfit,w_1080__op__format,f_auto__op__quality,q_80'),
    array('PA6-GF', '59.99', 'https://store.bblcdn.com/s7/default/1ec2ef9eb31044069bed35fbf5b233ce/14200d9cb8db37c75d38d9e2f4ba99c0.jpg__op__resize,m_lfit,w_1080__op__format,f_auto__op__quality,q_80'),
    array('ABS-GF', '29.99', 'https://store.bblcdn.com/s7/default/32b4c0fe8a02470597b2c048759153da/ABSGFMODEL2.jpg__op__resize,m_lfit,w_1080__op__format,f_auto__op__quality,q_80'),
    array('ASA-CF', '36.99', 'https://store.bblcdn.com/s7/default/0ac9a02e83c5422a82b879090ef4cf4c/2_ac9211d6-f9a4-459a-9179-a199cbd58721.png__op__resize,m_lfit,w_1080__op__format,f_auto__op__quality,q_80'),
    array('PPA-CF', '149.99', 'https://store.bblcdn.com/s7/default/666ccbae5c0b4de9b1956840d2fc8f9f/1a0131d637ff5b88f0146384e77220c1_0c1e2389-1634-4564-9a03-a35558cbdf44.jpg__op__resize,m_lfit,w_1080__op__format,f_auto__op__quality,q_80'),
    array('PPS-CF', '129.99', 'https://store.bblcdn.com/s7/default/998ca546f9224ba0a2b98a41d84f7594/Mainimage2.png__op__resize,m_lfit,w_1080__op__format,f_auto__op__quality,q_80'),
);

$created = 0;
$menu_order = 1;
foreach ($products as $row) {
    list($title, $usd, $img_url) = $row;
    $slug = sanitize_title($title);
    $family = tokraft_guess_material_family($title);
    $cat_slug = tokraft_guess_category_slug($title);
    $price = tokraft_usd_to_cad($usd);
    $blurb = tokraft_product_blurb($title, $family);

    $existing = get_page_by_path($slug, OBJECT, 'product');
    if ($existing) {
        $product = new WC_Product_Simple($existing->ID);
    } else {
        $product = new WC_Product_Simple();
    }

    $product->set_name($title);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_description($blurb['long']);
    $product->set_short_description($blurb['short']);
    $product->set_regular_price($price);
    $product->set_price($price);
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_sold_individually(false);
    $product->set_menu_order($menu_order++);
    $product->set_sku('TK-FIL-' . strtoupper(substr(md5($slug), 0, 8)));

    // Basic attributes for shop filters / clarity.
    $attributes = array();
    $weight_attr = new WC_Product_Attribute();
    $weight_attr->set_id(0);
    $weight_attr->set_name('Spool size');
    $weight_attr->set_options(array('1 kg'));
    $weight_attr->set_visible(true);
    $weight_attr->set_variation(false);
    $attributes[] = $weight_attr;

    if ($family) {
        $mat_attr = new WC_Product_Attribute();
        $mat_attr->set_id(0);
        $mat_attr->set_name('Material family');
        $mat_attr->set_options(array($family));
        $mat_attr->set_visible(true);
        $mat_attr->set_variation(false);
        $attributes[] = $mat_attr;
    }
    $product->set_attributes($attributes);

    $product_id = $product->save();
    if (!$product_id) {
        tokraft_log('FAIL save ' . $title);
        continue;
    }

    // Categories
    $term_ids = array_filter(array(
        $cat_filament,
        isset($cat_map[$cat_slug]) ? $cat_map[$cat_slug] : 0,
    ));
    wp_set_object_terms($product_id, array_map('intval', $term_ids), 'product_cat', false);
    wp_remove_object_terms($product_id, 'uncategorized', 'product_cat');

    // Material taxonomy
    if ($family && taxonomy_exists('tokraft_material')) {
        wp_set_object_terms($product_id, array($family), 'tokraft_material', false);
    }

    // Image
    $image_id = tokraft_sideload_image($img_url, $title . ' filament');
    if ($image_id) {
        $product = wc_get_product($product_id);
        $product->set_image_id($image_id);
        $product->save();
    }

    update_post_meta($product_id, '_tokraft_source', 'bambu-filament-first-page');
    update_post_meta($product_id, '_tokraft_source_usd', $usd);
    update_post_meta($product_id, '_tokraft_disclaimer', 'Filament is sold ready-to-order. Colour and packaging can vary slightly by batch. Custom geometry still requires a print quote.');

    $created++;
    tokraft_log('OK #' . $product_id . ' ' . $title . ' CAD ' . $price . ($image_id ? ' img=' . $image_id : ' NOIMG'));
}

// Point homepage shop copy at filament inventory.
$settings = function_exists('tokraft_home_settings') ? tokraft_home_settings() : (array) get_option('tokraft_home_settings', array());
$settings['shop_title'] = 'Shop Filament & Print Supplies';
$settings['shop_text'] = "Production filaments mapped from a real first-page assortment.\nChoose a spool, add to cart, or start a custom part quote.";
$settings['shop_points'] = "PLA, PETG, ASA, ABS, TPU & engineering options\nFiber-reinforced CF/GF grades\nStarter packs and specialty finishes\nCheckout through WooCommerce";
$settings['shop_button_label'] = 'Browse filament shop';
$settings['hero_shop_label'] = 'Browse Filament';
update_option('tokraft_home_settings', $settings);

// Flush product transients
if (function_exists('wc_delete_product_transients')) {
    wc_delete_product_transients();
}

tokraft_log('Created/updated filament products: ' . $created);
tokraft_log('Shop: ' . (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')));
