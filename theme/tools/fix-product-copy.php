<?php
/**
 * Fix product copy by type: accessories / bundles / filament families.
 * php /var/www/html/wp-content/themes/tokraft/tools/fix-product-copy.php
 */
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require '/var/www/html/wp-load.php';
if (!class_exists('WooCommerce')) {
    exit("WooCommerce required\n");
}

function tokraft_set_product_copy(WC_Product $product, $short, $long, $disclaimer, $attrs = array()) {
    $product->set_short_description($short);
    $product->set_description($long);
    $product->save();
    update_post_meta($product->get_id(), '_tokraft_disclaimer', $disclaimer);

    if ($attrs) {
        $wc_attrs = array();
        foreach ($attrs as $name => $value) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_name($name);
            $attribute->set_options(array($value));
            $attribute->set_visible(true);
            $attribute->set_variation(false);
            $wc_attrs[] = $attribute;
        }
        $product->set_attributes($wc_attrs);
        $product->save();
    }

    echo 'updated ', $product->get_name(), "\n";
}

$products = wc_get_products(array('limit' => 100, 'status' => array('publish', 'draft')));

foreach ($products as $product) {
    $name = $product->get_name();
    $slugs = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'slugs'));
    if (is_wp_error($slugs)) {
        $slugs = array();
    }

    // Accessories
    if (in_array('accessories', $slugs, true) || stripos($name, 'Reusable Spool') !== false) {
        $short = 'Reusable empty spool for filament refills. Pair with refill-format filament — not a filament material itself.';
        $long = '<p>A reusable spool core for refill filament workflows. Use it when you buy filament refills and need a rigid spool body for storage, AMS paths, or open-frame printers.</p>'
            . '<ul>'
            . '<li>Accessory only — no filament included</li>'
            . '<li>Designed for refill-style consumables</li>'
            . '<li>Check diameter / width fit for your printer or AMS before ordering</li>'
            . '</ul>'
            . '<p>If you need printed custom parts, use the quote flow instead of this accessory listing.</p>';
        $disclaimer = 'This is a reusable spool accessory, not filament. Confirm mechanical fit with your refill system before purchase.';
        tokraft_set_product_copy($product, $short, $long, $disclaimer, array(
            'Product type' => 'Reusable spool accessory',
            'Includes filament' => 'No',
            'Format' => 'Empty spool / refill carrier',
        ));
        continue;
    }

    // Bundles
    if (in_array('bundles', $slugs, true)) {
        if (stripos($name, 'CMYK') !== false || stripos($name, 'Lithophane') !== false) {
            $short = 'Multi-colour PLA pack for lithophane and multi-material colour experiments.';
            $long = '<p>A colour multipack built around lithophane and multi-colour PLA work. Useful when you want a coordinated set instead of buying each colour separately.</p>'
                . '<ul><li>Multiple PLA colours in one pack</li><li>Good for AMS colour trials</li><li>Exact colour mix can vary by batch</li></ul>';
        } else {
            $short = 'Starter multipack of PLA Basic colours for first projects and AMS setup.';
            $long = '<p>A practical starter bundle of PLA Basic colours so you can begin printing without buying spools one-by-one.</p>'
                . '<ul><li>Multiple PLA Basic spools / colours</li><li>Everyday models, fixtures and prototypes</li><li>Colour selection can vary by batch</li></ul>';
        }
        $disclaimer = 'Bundle contents and colour mix can vary by batch. Open multipacks have limited returns for moisture-control reasons.';
        tokraft_set_product_copy($product, $short, $long, $disclaimer, array(
            'Product type' => 'Filament bundle',
            'Material family' => 'PLA',
            'Format' => 'Multi-spool pack',
        ));
        continue;
    }

    // Filament families — improve generic lines
    $family = '';
    $upper = strtoupper($name);
    if (strpos($upper, 'TPU') !== false) {
        $family = 'TPU';
        $short = $name . ' — Flexible filament for feet, bumpers, gaskets and impact-absorbing parts.';
        $long = '<p>Flexible TPU for parts that need bend, grip or impact absorption. Dry thoroughly and print with slower speeds than rigid materials.</p>'
            . '<ul><li>Soft-touch and flexible geometries</li><li>Good for seals, bumpers and protective interfaces</li><li>Not a substitute for certified elastomers in safety systems</li></ul>';
        $attrs = array('Material family' => 'TPU', 'Format' => '1 kg spool', 'Process' => 'FDM / FFF');
    } elseif (strpos($upper, 'PETG') !== false || strpos($upper, 'PET-CF') !== false) {
        $family = 'PETG';
        $short = $name . ' — Tough PETG for brackets, jigs, housings and everyday functional parts.';
        $long = '<p>PETG balances toughness and printability for functional parts that need more durability than basic PLA.</p>'
            . '<ul><li>Brackets, jigs and protective housings</li><li>Better toughness than standard PLA for many shop jobs</li><li>Confirm chemical and temperature limits for your environment</li></ul>';
        $attrs = array('Material family' => (strpos($upper, 'CF') !== false ? 'PETG-CF / filled' : 'PETG'), 'Format' => '1 kg spool', 'Process' => 'FDM / FFF');
    } elseif (strpos($upper, 'ASA') !== false) {
        $family = 'ASA';
        $short = $name . ' — Weather-ready ASA for outdoor fixtures and UV-exposed parts.';
        $long = '<p>ASA is chosen for outdoor and UV-exposed parts where PLA is a poor long-term fit.</p>'
            . '<ul><li>Outdoor fixtures and covers</li><li>Better UV/weather behaviour than PLA</li><li>Print in a controlled enclosure when possible</li></ul>';
        $attrs = array('Material family' => 'ASA', 'Format' => '1 kg spool', 'Process' => 'FDM / FFF');
    } elseif (strpos($upper, 'ABS') !== false) {
        $family = 'ABS';
        $short = $name . ' — Impact-resistant ABS for enclosures and warmer indoor use cases.';
        $long = '<p>ABS for enclosures and parts that need more heat resistance than PLA in indoor service.</p>'
            . '<ul><li>Enclosures and structural prototypes</li><li>Prefer enclosed printers for dimensional stability</li><li>Not for high-pressure or chemical service without review</li></ul>';
        $attrs = array('Material family' => (strpos($upper, 'GF') !== false ? 'ABS-GF' : 'ABS'), 'Format' => '1 kg spool', 'Process' => 'FDM / FFF');
    } elseif (preg_match('/\b(PC|PA6|PAHT|PPA|PPS)\b/', $upper)) {
        $family = 'Engineering';
        $short = $name . ' — Engineering filament for higher-duty mechanical parts. Confirm dryer and process settings before production.';
        $long = '<p>Engineering-grade filament for higher mechanical or thermal demand. These materials usually need dryer control and tuned profiles.</p>'
            . '<ul><li>Higher-duty mechanical prototypes</li><li>Dry before printing</li><li>Validate fit and load path on real parts before volume runs</li></ul>';
        $attrs = array('Material family' => 'Engineering', 'Format' => '1 kg spool', 'Process' => 'FDM / FFF');
    } elseif (strpos($upper, 'PLA') !== false) {
        $family = 'PLA';
        $short = $name . ' — Easy-print PLA for models, fixtures and everyday indoor parts.';
        $long = '<p>PLA remains the practical default for models, jigs and indoor parts that do not need outdoor UV performance or high heat resistance.</p>'
            . '<ul><li>Models, fixtures and indoor functional parts</li><li>Fast setup and predictable print behaviour</li><li>Not ideal for long outdoor UV exposure or hot environments</li></ul>';
        $attrs = array('Material family' => (strpos($upper, 'CF') !== false ? 'PLA-CF' : 'PLA'), 'Format' => '1 kg spool', 'Process' => 'FDM / FFF');
    } else {
        continue;
    }

    $disclaimer = 'Filament is sold ready-to-order. Colour and packaging can vary slightly by batch. Confirm suitability for your application before production use.';
    tokraft_set_product_copy($product, $short, $long, $disclaimer, $attrs);

    // Attach material taxonomy when possible
    $map = array(
        'PLA' => 'pla',
        'PETG' => 'petg',
        'ASA' => 'asa',
        'ABS' => 'abs',
        'TPU' => 'tpu',
        'Engineering' => 'nylon-pa',
    );
    if (isset($map[$family])) {
        $term = get_term_by('slug', $map[$family], 'tokraft_material');
        if ($term && !is_wp_error($term)) {
            wp_set_object_terms($product->get_id(), array((int) $term->term_id), 'tokraft_material', false);
        }
    }
}

echo "done\n";
