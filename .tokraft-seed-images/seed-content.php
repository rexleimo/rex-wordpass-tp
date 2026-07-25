<?php
/**
 * Local-only content seed for the toKraft development site.
 * It imports already-uploaded media into usable site records without touching
 * theme source, design files, or Docker configuration.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

global $wpdb;

function tokraft_seed_attachment_id($filename) {
    global $wpdb;
    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
            '%' . $wpdb->esc_like('/' . $filename)
        )
    );
}

function tokraft_seed_media($filename, $title, $alt, $source_id) {
    $attachment_id = tokraft_seed_attachment_id($filename);
    if (!$attachment_id) {
        WP_CLI::warning("Could not find imported media: {$filename}");
        return 0;
    }

    $license = 'Source: Unsplash (Unsplash License: https://unsplash.com/license). Original image: https://images.unsplash.com/' . $source_id;
    wp_update_post(array(
        'ID' => $attachment_id,
        'post_title' => $title,
        'post_excerpt' => $license,
        'post_content' => $license,
    ));
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
    return $attachment_id;
}

function tokraft_seed_post($post_type, $title, $content, $excerpt, $thumbnail_id, $menu_order = 0) {
    $existing = get_page_by_path(sanitize_title($title), OBJECT, $post_type);
    $post_data = array(
        'post_type' => $post_type,
        'post_title' => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_status' => 'publish',
        'menu_order' => $menu_order,
    );
    if ($existing) {
        $post_data['ID'] = $existing->ID;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }
    if (is_wp_error($post_id)) {
        WP_CLI::warning($post_id->get_error_message());
        return 0;
    }
    if ($thumbnail_id) {
        set_post_thumbnail($post_id, $thumbnail_id);
    }
    return (int) $post_id;
}

function tokraft_seed_product_attributes($values) {
    $attributes = array();
    foreach ($values as $name => $options) {
        $attribute = new WC_Product_Attribute();
        $attribute->set_id(0);
        $attribute->set_name($name);
        $attribute->set_options($options);
        $attribute->set_position(count($attributes));
        $attribute->set_visible(true);
        $attribute->set_variation(true);
        $attributes[sanitize_title($name)] = $attribute;
    }
    return $attributes;
}

function tokraft_seed_variable_product($title, $description, $short_description, $thumbnail_id, $gallery_ids, $materials, $attributes, $variations, $dimensions, $notes) {
    $existing = get_page_by_path(sanitize_title($title), OBJECT, 'product');
    if ($existing) {
        wp_set_object_terms($existing->ID, 'variable', 'product_type');
        $product = new WC_Product_Variable($existing->ID);
    } else {
        $product = new WC_Product_Variable();
    }

    $product->set_name($title);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_description($description);
    $product->set_short_description($short_description);
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_image_id($thumbnail_id);
    $product->set_gallery_image_ids(array_values(array_filter($gallery_ids)));
    $product->set_length($dimensions[0]);
    $product->set_width($dimensions[1]);
    $product->set_height($dimensions[2]);
    $product->set_weight($dimensions[3]);
    $product->set_attributes(tokraft_seed_product_attributes($attributes));
    $product_id = $product->save();

    wp_set_object_terms($product_id, $materials, 'tokraft_material', false);
    update_post_meta($product_id, '_tokraft_installation_notes', $notes['installation']);
    update_post_meta($product_id, '_tokraft_disclaimer', $notes['disclaimer']);

    $existing_children = $product->get_children();
    foreach ($existing_children as $child_id) {
        if ('1' === get_post_meta($child_id, '_tokraft_seed_content', true)) {
            wp_delete_post($child_id, true);
        }
    }

    foreach ($variations as $variation_data) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($product_id);
        $variation->set_attributes($variation_data['attributes']);
        $variation->set_regular_price((string) $variation_data['price']);
        $variation->set_price((string) $variation_data['price']);
        $variation->set_stock_status('instock');
        $variation->set_manage_stock(false);
        $variation->set_image_id($thumbnail_id);
        $variation_id = $variation->save();
        update_post_meta($variation_id, '_tokraft_seed_content', '1');
    }
    WC_Product_Variable::sync($product_id);
    wc_delete_product_transients($product_id);
    return $product_id;
}

$media = array(
    'hero' => tokraft_seed_media('hero.jpg', '3D printer producing a yellow part', '3D printer nozzle producing a yellow 3D printed part', 'photo-1642969164999-979483e21601'),
    'service' => tokraft_seed_media('service.jpg', '3D printer production bay', '3D printer production bay with illuminated work area', 'photo-1705475025559-ad8efdedc74f'),
    'shop' => tokraft_seed_media('shop.jpg', '3D printers in a workshop', 'Several 3D printers in a clean workshop', 'photo-1611117775350-ac3950990985'),
    'case_1' => tokraft_seed_media('case-1.jpg', '3D print workshop', '3D print workshop with desktop fabrication equipment', 'photo-1702390600380-5dc2bb300025'),
    'case_2' => tokraft_seed_media('case-2.jpg', '3D printed display pieces', '3D printer with finished printed display pieces', 'photo-1702863361902-93c51bfbd923'),
    'case_3' => tokraft_seed_media('case-3.jpg', 'Functional part being printed', '3D printer producing a functional patterned part', 'photo-1611505908502-5b67e53e3a76'),
    'case_4' => tokraft_seed_media('case-4.jpg', 'Open-frame 3D printer', 'Open-frame 3D printer producing a part', 'photo-1603974739154-7b055aeec101'),
    'equipment' => tokraft_seed_media('equipment.jpg', '3D printer hot end close-up', 'Close-up of a 3D printer hot end in operation', 'photo-1611505982706-9ebc79e5d3f1'),
);

$material_data = array(
    'PLA' => array(
        'description' => 'Rigid, clean-detail material for concept models and everyday indoor parts. Avoid prolonged high heat.',
        'color' => '#e7e3da', 'rate' => 24, 'image' => $media['hero'],
    ),
    'PETG' => array(
        'description' => 'Tougher than PLA with good moisture and chemical resistance for functional brackets and housings.',
        'color' => '#275d99', 'rate' => 29, 'image' => $media['case_3'],
    ),
    'TPU' => array(
        'description' => 'Flexible, impact-absorbing material for feet, protective covers and compliant parts.',
        'color' => '#29323a', 'rate' => 37, 'image' => $media['case_4'],
    ),
    'ASA' => array(
        'description' => 'UV- and weather-resistant material for outdoor fixtures and parts exposed to sunlight.',
        'color' => '#d2a11f', 'rate' => 32, 'image' => $media['case_2'],
    ),
);

foreach ($material_data as $name => $data) {
    $term = term_exists($name, 'tokraft_material');
    if (!$term) {
        $term = wp_insert_term($name, 'tokraft_material');
    }
    if (is_wp_error($term)) {
        WP_CLI::warning($term->get_error_message());
        continue;
    }
    $term_id = is_array($term) ? $term['term_id'] : $term;
    update_term_meta($term_id, '_tokraft_material_short_description', $data['description']);
    update_term_meta($term_id, '_tokraft_material_color', $data['color']);
    update_term_meta($term_id, '_tokraft_material_quote_rate', $data['rate']);
    update_term_meta($term_id, '_tokraft_material_image_id', $data['image']);
    update_term_meta($term_id, '_tokraft_material_featured', '1');
}

$equipment = array(
    array('Bambu Lab H2D', 'Dual-nozzle FDM platform with a 350 x 320 x 325 mm build volume, a 350 C hotend and actively heated chamber capability. Manufacturer specifications should be verified against the installed configuration before launch.', $media['hero']),
    array('Bambu Lab X1 Carbon', 'Enclosed CoreXY system with a 256 x 256 x 256 mm build volume and up to 300 C nozzle temperature. A practical profile for multi-material prototypes and functional parts.', $media['shop']),
    array('Bambu Lab P1S', 'Enclosed CoreXY system with a 256 x 256 x 256 mm build volume. Useful for repeatable small-batch parts and protected material handling.', $media['service']),
    array('Bambu Lab A1 mini', 'Compact 180 x 180 x 180 mm printer profile for smaller parts, rapid samples and colour studies.', $media['equipment']),
);

foreach ($equipment as $index => $item) {
    tokraft_seed_post('tokraft_equipment', $item[0], '<p>' . esc_html($item[1]) . '</p>', wp_trim_words($item[1], 18), $item[2], $index + 1);
}

$case_studies = array(
    array('Application Example - Assembly Alignment Jig', 'Manufacturing / assembly', 'PETG', $media['case_3'], '<p><strong>Demonstration application:</strong> This page shows the information a production-ready alignment jig should include. Replace it with a completed toKraft project and approved client photography before using it as a customer reference.</p><h2>Part requirements</h2><p>Repeatable alignment, reinforced mounting zones and clear fit notes are more useful than a highly polished rendering. PETG is often chosen when toughness and practical chemical resistance matter.</p>'),
    array('Application Example - Cable Management Bracket', 'Workspace / cable management', 'PETG', $media['case_1'], '<p><strong>Demonstration application:</strong> A small bracket is a useful way to compare material choices, mounting direction and wall thickness. The public copy should be replaced with your own verified project details before launch.</p><h2>Part requirements</h2><p>Confirm cable diameter, fastener type, mounting surface and expected load before quoting. A file review can identify overhangs or features that need support.</p>'),
    array('Application Example - Fine Detail Display Part', 'Display model / product development', 'PLA', $media['case_2'], '<p><strong>Demonstration application:</strong> This example explains how display-oriented parts differ from load-bearing components. It is not presented as a completed customer order.</p><h2>Part requirements</h2><p>PLA is often suitable when visual detail, crisp edges and standard indoor conditions matter more than heat or impact resistance.</p>'),
    array('Application Example - Flexible Protective Foot', 'Protection / vibration control', 'TPU', $media['case_4'], '<p><strong>Demonstration application:</strong> Flexible parts need a different design review from rigid brackets. This is a sample content record, ready to be replaced by an approved project.</p><h2>Part requirements</h2><p>For TPU, specify the compression target, contact surface, material hardness and whether the part will be exposed to oils, heat or weather.</p>'),
    array('Application Example - Outdoor Cable Guide', 'Outdoor fixture / weather exposure', 'ASA', $media['hero'], '<p><strong>Demonstration application:</strong> An outdoor guide illustrates the need to specify UV exposure, fastener positions and the expected environment. This entry is not a client project claim.</p><h2>Part requirements</h2><p>ASA is commonly selected when outdoor weathering and UV resistance are important. Final wall thickness and orientation require a review of the supplied model.</p>'),
);

foreach ($case_studies as $index => $case) {
    $case_id = tokraft_seed_post('tokraft_case_study', $case[0], $case[4], 'Demonstration application for a functional 3D printed part.', $case[3], $index + 1);
    if ($case_id) {
        wp_set_object_terms($case_id, array($case[2]), 'tokraft_material', false);
        update_post_meta($case_id, '_tokraft_case_industry', $case[1]);
        update_post_meta($case_id, '_tokraft_case_featured', '1');
    }
}

$common_disclaimer = 'This is a made-to-order 3D printed item. Surface texture, layer lines and colour can vary slightly. Confirm dimensions, fit and intended use before ordering.';

tokraft_seed_variable_product(
    'Cable Dock',
    '<p>A compact, made-to-order cable dock for keeping charging leads and small cables from slipping behind a desk.</p><p>Select the size, material and colour that fits the location. PETG is the tougher option for frequently handled desk setups.</p>',
    'A made-to-order desk cable dock with size, material and colour choices.',
    $media['case_3'], array($media['hero'], $media['shop']), array('PLA', 'PETG'),
    array('Size' => array('Single', 'Double'), 'Material' => array('PLA', 'PETG'), 'Colour' => array('Charcoal', 'Sand')),
    array(
        array('attributes' => array('size' => 'Single', 'material' => 'PLA', 'colour' => 'Charcoal'), 'price' => 18),
        array('attributes' => array('size' => 'Single', 'material' => 'PLA', 'colour' => 'Sand'), 'price' => 18),
        array('attributes' => array('size' => 'Single', 'material' => 'PETG', 'colour' => 'Charcoal'), 'price' => 21),
        array('attributes' => array('size' => 'Single', 'material' => 'PETG', 'colour' => 'Sand'), 'price' => 21),
        array('attributes' => array('size' => 'Double', 'material' => 'PLA', 'colour' => 'Charcoal'), 'price' => 25),
        array('attributes' => array('size' => 'Double', 'material' => 'PLA', 'colour' => 'Sand'), 'price' => 25),
        array('attributes' => array('size' => 'Double', 'material' => 'PETG', 'colour' => 'Charcoal'), 'price' => 29),
        array('attributes' => array('size' => 'Double', 'material' => 'PETG', 'colour' => 'Sand'), 'price' => 29),
    ),
    array('58', '34', '22', '0.04'),
    array('installation' => 'Clean the mounting surface before using adhesive. If using screws, pre-check the surface and keep the fasteners clear of hidden cables or services.', 'disclaimer' => $common_disclaimer)
);

tokraft_seed_variable_product(
    'Under-Desk Headphone Hook',
    '<p>A compact hook for hanging headphones or a light accessory below a desk edge. Designed for a clean installation with either an adhesive pad or a suitable mechanical fixing.</p>',
    'Made-to-order under-desk hook with material and colour options.',
    $media['case_2'], array($media['case_1'], $media['equipment']), array('PETG', 'ASA'),
    array('Material' => array('PETG', 'ASA'), 'Colour' => array('Black', 'Gold')),
    array(
        array('attributes' => array('material' => 'PETG', 'colour' => 'Black'), 'price' => 24),
        array('attributes' => array('material' => 'PETG', 'colour' => 'Gold'), 'price' => 24),
        array('attributes' => array('material' => 'ASA', 'colour' => 'Black'), 'price' => 28),
        array('attributes' => array('material' => 'ASA', 'colour' => 'Gold'), 'price' => 28),
    ),
    array('42', '35', '72', '0.06'),
    array('installation' => 'Choose a flat, clean mounting area. For a screwed installation, verify the desk material and fastener length before drilling.', 'disclaimer' => $common_disclaimer . ' Do not use for safety-critical loads or unstable mounting surfaces.')
);

tokraft_seed_variable_product(
    'Workshop Tool Hanger',
    '<p>A simple wall or pegboard-compatible hanger for organizing lightweight workshop tools. Choose the material based on whether the item will be used indoors or outdoors.</p>',
    'Made-to-order workshop organizer for lightweight tools.',
    $media['shop'], array($media['case_1'], $media['case_4']), array('PETG', 'ASA'),
    array('Material' => array('PETG', 'ASA'), 'Colour' => array('Black', 'Orange')),
    array(
        array('attributes' => array('material' => 'PETG', 'colour' => 'Black'), 'price' => 16),
        array('attributes' => array('material' => 'PETG', 'colour' => 'Orange'), 'price' => 16),
        array('attributes' => array('material' => 'ASA', 'colour' => 'Black'), 'price' => 19),
        array('attributes' => array('material' => 'ASA', 'colour' => 'Orange'), 'price' => 19),
    ),
    array('32', '28', '80', '0.05'),
    array('installation' => 'Fix the hanger to a suitable wall, panel or pegboard using hardware that matches the mounting surface. Test with a light load first.', 'disclaimer' => $common_disclaimer . ' Load capacity depends on print material, orientation, mounting hardware and the supporting surface.')
);

tokraft_seed_variable_product(
    'Outdoor Cable Guide',
    '<p>A low-profile guide for routing one or two light cables along a wall or enclosure. ASA is the recommended option when the guide will be exposed to sunlight and outdoor weather.</p>',
    'Weather-ready cable guide with size, material and colour choices.',
    $media['hero'], array($media['equipment'], $media['case_4']), array('ASA', 'PETG'),
    array('Size' => array('Small', 'Large'), 'Material' => array('PETG', 'ASA'), 'Colour' => array('Black', 'White')),
    array(
        array('attributes' => array('size' => 'Small', 'material' => 'PETG', 'colour' => 'Black'), 'price' => 12),
        array('attributes' => array('size' => 'Small', 'material' => 'PETG', 'colour' => 'White'), 'price' => 12),
        array('attributes' => array('size' => 'Small', 'material' => 'ASA', 'colour' => 'Black'), 'price' => 15),
        array('attributes' => array('size' => 'Small', 'material' => 'ASA', 'colour' => 'White'), 'price' => 15),
        array('attributes' => array('size' => 'Large', 'material' => 'PETG', 'colour' => 'Black'), 'price' => 15),
        array('attributes' => array('size' => 'Large', 'material' => 'PETG', 'colour' => 'White'), 'price' => 15),
        array('attributes' => array('size' => 'Large', 'material' => 'ASA', 'colour' => 'Black'), 'price' => 18),
        array('attributes' => array('size' => 'Large', 'material' => 'ASA', 'colour' => 'White'), 'price' => 18),
    ),
    array('36', '18', '24', '0.02'),
    array('installation' => 'Route the cable without pinching it. Use hardware appropriate for the mounting surface and keep clear of electrical wiring and moving parts.', 'disclaimer' => $common_disclaimer . ' This product is not an electrical fitting and does not provide strain relief for mains-power wiring.')
);

$settings = function_exists('tokraft_home_settings') ? tokraft_home_settings() : (array) get_option('tokraft_home_settings', array());
$settings = array_merge($settings, array(
    'hero_eyebrow' => 'CUSTOM 3D PRINTING & READY-TO-ORDER PARTS',
    'hero_title' => 'Functional parts made for',
    'hero_accent' => 'real-world use.',
    'hero_description' => "Upload your model for a reviewed production quote, or order useful printed parts from the shop.\nClear options. Practical materials. File review before final pricing.",
    'hero_quote_label' => 'Get a Print Quote',
    'hero_quote_url' => '/quote/',
    'hero_shop_label' => 'Browse Shop',
    'hero_shop_url' => '/shop/',
    'hero_image' => $media['hero'],
    'hero_proof' => "Accepted files | STL, 3MF, STEP, STP & OBJ\nPrint controls | Material, infill, walls & layer height\nQuote workflow | File review before the final quote\nOrder options | Custom prints or ready-to-order parts",
    'service_title' => '3D Printing Service',
    'service_text' => "Upload a model and specify the details that affect fit, strength and finish.\nYour file is reviewed before a production quote is confirmed.",
    'service_points' => "STL, 3MF, STEP, STP & OBJ uploads\nMaterial, colour and quantity selection\nInfill, walls, layers & support preferences\nTolerance, assembly and special-requirement notes",
    'service_button_label' => 'Start a Quote',
    'service_image' => $media['service'],
    'shop_title' => 'Shop Ready-to-Order Parts',
    'shop_text' => "Useful desk, cable and workshop parts with product options.\nChoose variants, add to cart and complete checkout through WooCommerce.",
    'shop_points' => "Size, material & colour variants\nMade-to-order product information\nCart and checkout workflow\nProduct specifications & installation notes",
    'shop_button_label' => 'Browse the Shop',
    'shop_image' => $media['shop'],
    'equipment_eyebrow' => 'PRODUCTION CAPABILITY',
    'equipment_title' => 'Equipment Profiles',
    'equipment_text' => 'The printer records below give customers a clear view of print formats and capability. Confirm the installed fleet and settings before publishing a production site.',
    'equipment_count' => 4,
    'materials_eyebrow' => 'MATERIALS THAT WORK',
    'materials_title' => 'Core Print Materials',
    'materials_text' => 'Compare four practical materials by their performance and intended use, then select one directly in the quote form.',
    'materials_count' => 4,
    'materials_button_label' => 'View Material Library',
    'materials_button_url' => '/materials/',
    'cases_eyebrow' => 'APPLICATION EXAMPLES',
    'cases_title' => 'Functional parts, clearly specified.',
    'cases_count' => 5,
    'cases_button_label' => 'View Applications',
    'metrics_eyebrow' => 'CLEAR FROM REQUEST TO REVIEW',
    'metrics_title' => 'A quote workflow built around the part.',
    'metrics_text' => 'Customers provide the production details up front, and the team validates the file before confirming the price and schedule.',
    'metric_one_value' => 'STL / 3MF / STEP',
    'metric_one_label' => 'Accepted model formats',
    'metric_two_value' => 'PLA / PETG / TPU / ASA',
    'metric_two_label' => 'Core materials available',
    'metric_three_value' => 'File review required',
    'metric_three_label' => 'Final quote confirmed after inspection',
));
update_option('tokraft_home_settings', $settings);

WP_CLI::success('toKraft local content seed complete.');
