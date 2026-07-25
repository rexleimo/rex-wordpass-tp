<?php
/**
 * Convert PLA Basic to variable product with Color / Type / Size (custom attributes).
 * php /var/www/html/wp-content/themes/tokraft/tools/seed-variable-pla-basic.php
 */
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require '/var/www/html/wp-load.php';
if (!class_exists('WooCommerce')) {
    exit("WooCommerce required\n");
}

function tokraft_pla_basic_media($source_path, $filename, $title, $alt) {
    global $wpdb;

    $attachment_id = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
            '%' . $wpdb->esc_like('/' . $filename)
        )
    );
    if ($attachment_id) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
        return $attachment_id;
    }

    if (!is_readable($source_path)) {
        echo "Image not readable: {$source_path}\n";
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $upload = wp_upload_bits($filename, null, file_get_contents($source_path));
    if (!empty($upload['error'])) {
        echo "Image upload failed: {$upload['error']}\n";
        return 0;
    }

    $filetype = wp_check_filetype($upload['file']);
    $attachment_id = wp_insert_attachment(array(
        'post_mime_type' => $filetype['type'],
        'post_title' => $title,
        'post_content' => '',
        'post_status' => 'inherit',
    ), $upload['file']);
    if (is_wp_error($attachment_id)) {
        echo "Attachment failed: {$attachment_id->get_error_message()}\n";
        return 0;
    }

    wp_update_attachment_metadata(
        $attachment_id,
        wp_generate_attachment_metadata($attachment_id, $upload['file'])
    );
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
    return (int) $attachment_id;
}

$post = get_page_by_path('pla-basic', OBJECT, 'product');
if (!$post) {
    exit("PLA Basic not found\n");
}

$product_id = (int) $post->ID;
$old = wc_get_product($product_id);
$base_price = 24.99;
$image_id = $old ? (int) $old->get_image_id() : 0;
$gallery_image_ids = $old ? $old->get_gallery_image_ids() : array();
$asset_dir = get_template_directory() . '/assets/images/products';
$asset_uri = get_template_directory_uri() . '/assets/images/products';
$seed_image_id = tokraft_pla_basic_media(
    $asset_dir . '/pla-basic-spool.png',
    'pla-basic-spool.png',
    'PLA Basic filament spool',
    'Dark green PLA Basic filament spool'
);
$seed_gallery_id = tokraft_pla_basic_media(
    $asset_dir . '/pla-basic-print.png',
    'pla-basic-print.png',
    'PLA Basic printed model',
    'Dark green sculptural model printed with PLA Basic'
);
if ($seed_image_id) {
    $image_id = $seed_image_id;
}
if ($seed_gallery_id) {
    $gallery_image_ids = array($seed_gallery_id);
}

$colors = array(
    'Black' => '#1C1C1C',
    'White' => '#F5F5F5',
    'Gray' => '#9E9E9E',
    'Red' => '#C62828',
    'Orange' => '#EF6C00',
    'Yellow' => '#F9A825',
    'Green' => '#2E7D32',
    'Blue' => '#1565C0',
    'Mistletoe Green' => '#2F5D50',
);
$types = array(
    'Filament with spool' => 0.0,
    'Refill' => -3.0,
);
$sizes = array('1 kg');

// Remove previous children.
if ($old) {
    foreach ($old->get_children() as $child_id) {
        wp_delete_post($child_id, true);
    }
}

wp_set_object_terms($product_id, 'variable', 'product_type');
$product = new WC_Product_Variable($product_id);
$product->set_name('PLA Basic');
$product->set_status('publish');
$product->set_catalog_visibility('visible');
$product->set_description(
    '<div class="tk-detail-lead"><p>Reliable everyday PLA for clean prototypes, display models, organizers and workshop aids. It is intended for low-load indoor parts where printability and surface finish matter more than heat resistance.</p></div>'
    . '<div class="tk-detail-facts">'
    . '<div><span>Material</span><strong>PLA</strong></div>'
    . '<div><span>Diameter</span><strong>1.75 +/- 0.03 mm</strong></div>'
    . '<div><span>Net weight</span><strong>1 kg</strong></div>'
    . '<div><span>Format</span><strong>Spool or refill</strong></div>'
    . '</div>'
    . '<article class="tk-product-story" aria-labelledby="tk-story-title">'
    . '<div class="tk-story-copy"><p class="tk-detail-kicker">Application note 01</p><h3 id="tk-story-title">A sculptural form study in Mistletoe Green</h3>'
    . '<p>This example shows how PLA Basic can be used when the goal is a clean visual prototype with readable curves and a consistent surface. It is an application reference, not a guaranteed printer profile.</p>'
    . '<dl class="tk-story-data">'
    . '<div><dt>Build intent</dt><dd>Visual prototype</dd></div>'
    . '<div><dt>Example layer</dt><dd>0.20 mm</dd></div>'
    . '<div><dt>Starting nozzle</dt><dd>205 C</dd></div>'
    . '<div><dt>Starting plate</dt><dd>50 C</dd></div>'
    . '</dl></div>'
    . '<figure class="tk-story-visual"><img src="' . esc_url($asset_uri . '/pla-basic-print.png') . '" alt="Sculptural sample model in dark green PLA" loading="eager"><figcaption>Example application / sculptural display model. Tune the profile for the machine, plate and room conditions.</figcaption></figure>'
    . '</article>'
    . '<div class="tk-detail-grid">'
    . '<section class="tk-detail-card"><p class="tk-detail-kicker">Best for</p><h3>Everyday print work</h3><ul>'
    . '<li>Visual prototypes and presentation models</li>'
    . '<li>Organizers, fixtures and low-load indoor parts</li>'
    . '<li>Multi-colour work with a compatible spool system</li>'
    . '</ul></section>'
    . '<section class="tk-detail-card"><p class="tk-detail-kicker">Starting profile</p><h3>Machine-dependent settings</h3><ul>'
    . '<li>Nozzle temperature: 190-220 C</li>'
    . '<li>Build plate temperature: 35-60 C</li>'
    . '<li>Typical layer height: 0.12-0.28 mm with a 0.4 mm nozzle</li>'
    . '</ul></section>'
    . '<section class="tk-detail-card"><p class="tk-detail-kicker">Storage</p><h3>Keep the filament dry</h3><ul>'
    . '<li>Reseal after use and store with desiccant</li>'
    . '<li>If stringing or brittleness appears, dry at 45-50 C for 6-8 hours</li>'
    . '<li>Refill format requires a compatible reusable spool</li>'
    . '</ul></section>'
    . '</div>'
    . '<section class="tk-product-workflow">'
    . '<figure><img src="' . esc_url($asset_uri . '/pla-basic-spool.png') . '" alt="Dark green PLA Basic filament on a reusable spool" loading="eager"><figcaption>Filament with spool shown. Refill format requires a compatible reusable spool.</figcaption></figure>'
    . '<div class="tk-workflow-copy"><p class="tk-detail-kicker">Workshop workflow</p><h3>From sealed pack to first-layer check</h3><ol>'
    . '<li><span>01</span><div><strong>Confirm the format</strong><p>Use the supplied spool, or install the refill on a compatible reusable spool before loading.</p></div></li>'
    . '<li><span>02</span><div><strong>Start with a known PLA profile</strong><p>Use 190-220 C at the nozzle and 35-60 C at the build plate as the tuning range.</p></div></li>'
    . '<li><span>03</span><div><strong>Review the first layers</strong><p>Check adhesion, extrusion consistency and filament routing before committing to a long print.</p></div></li>'
    . '<li><span>04</span><div><strong>Reseal after use</strong><p>Return unused filament to a dry bag with desiccant to reduce moisture-related stringing and brittleness.</p></div></li>'
    . '</ol></div>'
    . '</section>'
);
$product->set_short_description('General-purpose 1.75 mm PLA for prototypes, visual models, organizers and low-load indoor parts. Choose a 1 kg spool or refill.');
$product->set_sku('TK-PLA-BASIC');
if ($image_id) {
    $product->set_image_id($image_id);
}
$product->set_gallery_image_ids($gallery_image_ids);

$attributes = array();
$defs = array(
    array('name' => 'Color', 'options' => array_keys($colors)),
    array('name' => 'Type', 'options' => array_keys($types)),
    array('name' => 'Size', 'options' => $sizes),
);
$pos = 0;
foreach ($defs as $def) {
    $attribute = new WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name($def['name']);
    $attribute->set_options($def['options']);
    $attribute->set_position($pos++);
    $attribute->set_visible(true);
    $attribute->set_variation(true);
    $attributes[] = $attribute;
}
$product->set_attributes($attributes);
$product->set_default_attributes(array(
    'color' => 'Mistletoe Green',
    'type' => 'Filament with spool',
    'size' => '1 kg',
));
$product->save();

$created = 0;
foreach ($colors as $color_name => $hex) {
    foreach ($types as $type_name => $delta) {
        foreach ($sizes as $size_name) {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_status('publish');
            $variation->set_regular_price((string) max(1, round($base_price + $delta, 2)));
            $variation->set_manage_stock(false);
            $variation->set_stock_status('instock');
            // Custom attribute keys are sanitized attribute names.
            $variation->set_attributes(array(
                'color' => $color_name,
                'type' => $type_name,
                'size' => $size_name,
            ));
            if ($image_id) {
                $variation->set_image_id($image_id);
            }
            $variation->save();
            $created++;
        }
    }
}

WC_Product_Variable::sync($product_id);
wc_delete_product_transients($product_id);
update_post_meta($product_id, '_tokraft_color_map', $colors);
// Keep the PDP recommendations tied to published catalogue products and notes.
$discover_product_slugs = array(
    'reusable-spool' => 'For PLA Basic refill format; no filament is included.',
    'pla-basic-starter-classic-pack' => 'Starter multipack of PLA Basic colours for first projects and AMS setup.',
    'pla-cmyk-lithophane-bundle' => 'A coordinated PLA set for lithophane and multi-colour experiments.',
);
$discover_product_ids = array();
$discover_product_notes = array();
foreach ($discover_product_slugs as $slug => $note) {
    $discover_post = get_page_by_path($slug, OBJECT, 'product');
    $discover_candidate = $discover_post ? wc_get_product($discover_post->ID) : false;
    if (!$discover_candidate || !$discover_candidate->is_purchasable() || !$discover_candidate->is_in_stock()) {
        continue;
    }
    $discover_product_ids[] = $discover_candidate->get_id();
    $discover_product_notes[$discover_candidate->get_id()] = $note;
}
update_post_meta($product_id, '_tokraft_discover_product_ids', $discover_product_ids);
update_post_meta($product_id, '_tokraft_discover_product_notes', $discover_product_notes);
update_post_meta($product_id, '_tokraft_features', array(
    'General-purpose PLA for low-load indoor parts',
    '1.75 mm diameter with +/- 0.03 mm tolerance',
    'Available on a 1 kg spool or as a refill',
    'Starting profile: 190-220 C nozzle, 35-60 C build plate',
));
update_post_meta($product_id, '_tokraft_cautions', array(
    'Refill format requires a compatible reusable spool',
    'Not recommended for sustained heat or long-term outdoor UV exposure',
));
update_post_meta($product_id, '_tokraft_specifications', array(
    'Material' => 'Polylactic acid (PLA)',
    'Filament diameter' => '1.75 mm +/- 0.03 mm',
    'Net filament weight' => '1 kg',
    'Available format' => 'Filament with spool or refill',
    'Recommended nozzle temperature' => '190-220 C',
    'Recommended build plate temperature' => '35-60 C',
    'Typical nozzle size' => '0.4 mm or larger',
    'Material density' => 'Approx. 1.24 g/cm3',
    'Colour' => 'Selected at checkout',
    'Storage' => 'Cool, dry and resealed after use',
));
update_post_meta($product_id, '_tokraft_shipping_returns', 'In-stock filament is normally packed within 1-2 business days. Available carriers, transit estimates and shipping charges are shown at checkout. Keep the product sealed until you confirm the colour and format. If an item arrives damaged or incorrect, contact us with the order number and photos before use.');
update_post_meta($product_id, '_tokraft_disclaimer', 'Published temperatures are starting points and must be tuned for the printer, build surface and environment. PLA is not recommended for sustained heat, long-term outdoor UV exposure, food-contact, medical or safety-critical applications unless the finished part has been independently assessed for that use. Colour and packaging may vary slightly by batch.');

$check = wc_get_product($product_id);
echo "type=", $check->get_type(), " variations=", $created, " available=", count($check->get_available_variations()), "\n";
echo "sample=", json_encode($check->get_available_variations()[0]['attributes'] ?? array()), "\n";
echo "url=", get_permalink($product_id), "\n";
