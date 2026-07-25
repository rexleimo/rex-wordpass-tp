<?php
/**
 * One-shot local bootstrap for toKraft P0+P1 demo:
 * - Home + Blog pages and reading settings
 * - Primary navigation
 * - Product categories
 * - Richer cases / materials / equipment / blog posts
 *
 * Usage (inside container):
 *   php /var/www/html/wp-content/themes/tokraft/tools/seed-demo-p0p1.php
 */

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require '/var/www/html/wp-load.php';

if (!function_exists('wp_insert_post')) {
    fwrite(STDERR, "WordPress failed to load\n");
    exit(1);
}

function tokraft_seed_log($message) {
    echo $message, "\n";
}

function tokraft_seed_ensure_page($title, $slug, $content = '') {
    $existing = get_page_by_path($slug);
    if ($existing) {
        return (int) $existing->ID;
    }

    $page_id = wp_insert_post(array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $content,
    ), true);

    if (is_wp_error($page_id)) {
        tokraft_seed_log('PAGE ERROR ' . $slug . ': ' . $page_id->get_error_message());
        return 0;
    }

    return (int) $page_id;
}

function tokraft_seed_upsert_post($post_type, $title, $slug, $content, $excerpt, $thumbnail_id = 0, $menu_order = 0) {
    $existing = get_page_by_path($slug, OBJECT, $post_type);
    $data = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'menu_order' => $menu_order,
    );

    if ($existing) {
        $data['ID'] = $existing->ID;
        $post_id = wp_update_post($data, true);
    } else {
        $post_id = wp_insert_post($data, true);
    }

    if (is_wp_error($post_id)) {
        tokraft_seed_log('POST ERROR ' . $slug . ': ' . $post_id->get_error_message());
        return 0;
    }

    if ($thumbnail_id) {
        set_post_thumbnail($post_id, $thumbnail_id);
    }

    return (int) $post_id;
}

function tokraft_seed_attachment_map() {
    global $wpdb;
    $map = array();
    $rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'");
    foreach ((array) $rows as $row) {
        $basename = basename((string) $row->meta_value);
        $map[$basename] = (int) $row->post_id;
    }
    return $map;
}

$media = tokraft_seed_attachment_map();
$img = function ($name) use ($media) {
    return isset($media[$name]) ? (int) $media[$name] : 0;
};

tokraft_seed_log('=== toKraft P0+P1 seed start ===');

// --- Pages + reading settings ---
$home_id = tokraft_seed_ensure_page('Home', 'home', 'Homepage managed by the toKraft front-page template.');
$blog_id = tokraft_seed_ensure_page('Blog', 'blog', 'Production notes and practical 3D printing guides.');

if ($home_id && $blog_id) {
    update_option('show_on_front', 'page');
    update_option('page_on_front', $home_id);
    update_option('page_for_posts', $blog_id);
    tokraft_seed_log("Reading settings: front={$home_id}, posts={$blog_id}");
}

// --- Primary menu ---
$menu_name = 'Primary';
$menu = wp_get_nav_menu_object($menu_name);
if (!$menu) {
    $menu_id = wp_create_nav_menu($menu_name);
} else {
    $menu_id = (int) $menu->term_id;
    $items = wp_get_nav_menu_items($menu_id);
    if ($items) {
        foreach ($items as $item) {
            wp_delete_post($item->ID, true);
        }
    }
}

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
$case_url = get_post_type_archive_link('tokraft_case_study') ?: home_url('/case-studies/');
$menu_links = array(
    array('Print Service', home_url('/quote/')),
    array('Shop', $shop_url),
    array('Materials', home_url('/materials/')),
    array('Case Studies', $case_url),
    array('Blog', get_permalink($blog_id)),
    array('Equipment', home_url('/#equipment')),
);

$position = 1;
foreach ($menu_links as $link) {
    wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title' => $link[0],
        'menu-item-url' => $link[1],
        'menu-item-status' => 'publish',
        'menu-item-type' => 'custom',
        'menu-item-position' => $position++,
    ));
}

$locations = get_theme_mod('nav_menu_locations');
if (!is_array($locations)) {
    $locations = array();
}
$locations['primary'] = $menu_id;
set_theme_mod('nav_menu_locations', $locations);
tokraft_seed_log("Primary menu assigned (#{$menu_id})");

// --- Product categories ---
$categories = array(
    'Desk Accessories' => 'desk-accessories',
    'Workshop' => 'workshop',
    'Outdoor' => 'outdoor',
);
$category_ids = array();
foreach ($categories as $name => $slug) {
    $term = term_exists($slug, 'product_cat');
    if (!$term) {
        $term = wp_insert_term($name, 'product_cat', array('slug' => $slug));
    }
    if (!is_wp_error($term)) {
        $category_ids[$slug] = (int) (is_array($term) ? $term['term_id'] : $term);
    }
}

$product_map = array(
    'cable-dock' => array('desk-accessories', 'A compact made-to-order desk cable dock with size, material and colour options.'),
    'under-desk-headphone-hook' => array('desk-accessories', 'Under-desk hook for headphones or light accessories, with PETG or ASA options.'),
    'workshop-tool-hanger' => array('workshop', 'Wall or pegboard hanger for lightweight workshop tools.'),
    'outdoor-cable-guide' => array('outdoor', 'Low-profile outdoor cable guide. ASA recommended for UV exposure.'),
);

foreach ($product_map as $slug => $meta) {
    $product = get_page_by_path($slug, OBJECT, 'product');
    if (!$product) {
        continue;
    }
    wp_set_object_terms($product->ID, array($meta[0]), 'product_cat', false);
    // Remove Uncategorized if present.
    wp_remove_object_terms($product->ID, 'uncategorized', 'product_cat');
    if (function_exists('wc_get_product')) {
        $wc_product = wc_get_product($product->ID);
        if ($wc_product) {
            $wc_product->set_short_description($meta[1]);
            $wc_product->save();
        }
    }
}
tokraft_seed_log('Product categories assigned');

// --- Materials ---
$material_data = array(
    'PLA' => array(
        'description' => 'Rigid, clean-detail material for concept models and everyday indoor parts. Avoid prolonged heat.',
        'color' => '#e7e3da',
        'rate' => 24,
        'image' => $img('hero.jpg'),
        'featured' => '1',
    ),
    'PETG' => array(
        'description' => 'Tougher than PLA with good moisture resistance for brackets, jigs and housings.',
        'color' => '#275d99',
        'rate' => 29,
        'image' => $img('case-3.jpg'),
        'featured' => '1',
    ),
    'ASA' => array(
        'description' => 'UV- and weather-resistant material for outdoor fixtures and sun-exposed parts.',
        'color' => '#d2a11f',
        'rate' => 32,
        'image' => $img('case-2.jpg'),
        'featured' => '1',
    ),
    'TPU' => array(
        'description' => 'Flexible, impact-absorbing material for feet, bumpers and protective covers.',
        'color' => '#29323a',
        'rate' => 37,
        'image' => $img('case-4.jpg'),
        'featured' => '1',
    ),
    'ABS' => array(
        'description' => 'Impact-resistant engineering plastic for enclosures and parts that need more heat tolerance than PLA.',
        'color' => '#8a8f98',
        'rate' => 34,
        'image' => $img('service.jpg'),
        'featured' => '1',
    ),
    'Nylon (PA)' => array(
        'description' => 'Strong, wear-resistant option for functional hinges, clips and mechanical interfaces.',
        'color' => '#4f5d73',
        'rate' => 42,
        'image' => $img('equipment.jpg'),
        'featured' => '1',
    ),
);

foreach ($material_data as $name => $data) {
    $term = term_exists($name, 'tokraft_material');
    if (!$term) {
        $term = wp_insert_term($name, 'tokraft_material');
    }
    if (is_wp_error($term)) {
        tokraft_seed_log('MATERIAL ERROR ' . $name . ': ' . $term->get_error_message());
        continue;
    }
    $term_id = (int) (is_array($term) ? $term['term_id'] : $term);
    update_term_meta($term_id, '_tokraft_material_short_description', $data['description']);
    update_term_meta($term_id, '_tokraft_material_color', $data['color']);
    update_term_meta($term_id, '_tokraft_material_quote_rate', $data['rate']);
    if ($data['image']) {
        update_term_meta($term_id, '_tokraft_material_image_id', $data['image']);
    }
    update_term_meta($term_id, '_tokraft_material_featured', $data['featured']);
}
tokraft_seed_log('Materials updated (6)');

// --- Equipment ---
$equipment = array(
    array(
        'Bambu Lab H2D',
        'bambu-lab-h2d',
        'Dual-nozzle production profile with a large build volume for multi-material prototypes and larger functional parts. Verify installed firmware, chamber settings and nozzle package before publishing production claims.',
        $img('hero.jpg'),
        1,
    ),
    array(
        'Bambu Lab X1 Carbon',
        'bambu-lab-x1-carbon',
        'Enclosed CoreXY profile suited to engineering materials and repeatable functional parts. Useful when dimensional consistency and protected material handling matter more than open-frame flexibility.',
        $img('shop.jpg'),
        2,
    ),
    array(
        'Bambu Lab P1S',
        'bambu-lab-p1s',
        'Enclosed workhorse for small-batch brackets, fixtures and everyday production parts. A practical default for PETG and ASA jobs that do not need the largest build volume.',
        $img('service.jpg'),
        3,
    ),
    array(
        'Bambu Lab A1 mini',
        'bambu-lab-a1-mini',
        'Compact profile for smaller samples, colour studies and quick iterations. Ideal when customers need a fast first look before committing to a larger production run.',
        $img('equipment.jpg'),
        4,
    ),
);

foreach ($equipment as $item) {
    $content = '<p>' . esc_html($item[2]) . '</p><ul><li>Profile used for customer-facing capability notes</li><li>Final process settings are confirmed during file review</li><li>Not every material is available on every machine</li></ul>';
    tokraft_seed_upsert_post('tokraft_equipment', $item[0], $item[1], $content, wp_trim_words($item[2], 22), $item[3], $item[4]);
}
tokraft_seed_log('Equipment updated');

// --- Case studies ---
$cases = array(
    array(
        'PETG Assembly Alignment Jig',
        'petg-assembly-alignment-jig',
        'Manufacturing / assembly',
        'PETG',
        $img('case-3.jpg'),
        'A tough PETG jig that keeps small assemblies aligned during fastening.',
        '<p>This application example shows how a print service request should describe fit, load and reuse. The part needs rigid locating features, reinforced mounting zones and enough toughness for repeated shop handling.</p><h2>What we specified</h2><ul><li>Material: PETG for toughness and practical chemical resistance</li><li>Critical fits called out in the notes, not only in the CAD file</li><li>Walls and orientation reviewed before quoting</li></ul><h2>Why it matters</h2><p>Alignment jigs fail when the quote form only captures “print this STL”. Capturing tolerance and reuse requirements up front reduces email back-and-forth.</p>',
        1,
    ),
    array(
        'Desk Cable Management Bracket',
        'desk-cable-management-bracket',
        'Workspace / cable management',
        'PETG',
        $img('case-1.jpg'),
        'A compact desk bracket designed around cable diameter, fastener choice and mounting surface.',
        '<p>Small brackets are a good demo of everyday functional printing: the geometry is simple, but the mounting surface, cable diameter and expected load still need to be explicit.</p><h2>What we specified</h2><ul><li>Cable diameter and clamp clearance</li><li>Screw or adhesive mounting preference</li><li>PETG for frequent handling</li></ul>',
        2,
    ),
    array(
        'Fine-Detail PLA Display Model',
        'fine-detail-pla-display-model',
        'Display model / product development',
        'PLA',
        $img('case-2.jpg'),
        'A PLA display part optimized for crisp edges and indoor presentation, not load-bearing use.',
        '<p>Display parts and structural parts should not be quoted the same way. This example keeps the focus on surface quality, layer height and presentation lighting rather than impact resistance.</p><h2>What we specified</h2><ul><li>PLA for clean detail</li><li>Finer layer height for curves</li><li>Indoor use only</li></ul>',
        3,
    ),
    array(
        'TPU Protective Equipment Foot',
        'tpu-protective-equipment-foot',
        'Protection / vibration control',
        'TPU',
        $img('case-4.jpg'),
        'A flexible foot that absorbs vibration and protects finished surfaces.',
        '<p>Flexible parts need a different review path from rigid brackets. Compression target, contact surface and hardness matter as much as the outer shape.</p><h2>What we specified</h2><ul><li>TPU for impact absorption</li><li>Notes about oils, heat and floor surface</li><li>Support strategy reviewed before production</li></ul>',
        4,
    ),
    array(
        'Outdoor ASA Cable Guide',
        'outdoor-asa-cable-guide',
        'Outdoor fixture / weather exposure',
        'ASA',
        $img('hero.jpg'),
        'A UV-ready outdoor guide for routing light cables along a wall or enclosure.',
        '<p>Outdoor parts fail for boring reasons: UV exposure, fastener choice and water path. This example forces those details into the quote notes before production.</p><h2>What we specified</h2><ul><li>ASA for sunlight and weather exposure</li><li>Fastener positions and wall type</li><li>Not an electrical fitting or strain relief for mains power</li></ul>',
        5,
    ),
);

// Soft-retire old generic demo titles if they still exist under old slugs.
foreach (get_posts(array(
    'post_type' => 'tokraft_case_study',
    'numberposts' => -1,
    'post_status' => 'any',
)) as $old_case) {
    if (0 === strpos($old_case->post_title, 'Application Example')) {
        wp_update_post(array(
            'ID' => $old_case->ID,
            'post_status' => 'draft',
        ));
    }
}

foreach ($cases as $case) {
    $case_id = tokraft_seed_upsert_post('tokraft_case_study', $case[0], $case[1], $case[6], $case[5], $case[4], $case[7]);
    if ($case_id) {
        wp_set_object_terms($case_id, array($case[3]), 'tokraft_material', false);
        update_post_meta($case_id, '_tokraft_case_industry', $case[2]);
        update_post_meta($case_id, '_tokraft_case_featured', '1');
    }
}
tokraft_seed_log('Case studies updated');

// --- Blog posts ---
// Draft the default Hello world noise.
$hello = get_page_by_path('hello-world', OBJECT, 'post');
if ($hello) {
    wp_update_post(array('ID' => $hello->ID, 'post_status' => 'draft'));
}

$posts = array(
    array(
        'How to prepare an STL for quoting',
        'how-to-prepare-an-stl-for-quoting',
        'A clean quote starts with a clean file. Export watertight geometry, name critical dimensions and say what the part must do.',
        '<p>Most quote delays are not about price. They are about missing context: units, load, fit, material assumptions and whether the model is manifold.</p><h2>Before you upload</h2><ul><li>Export STL, 3MF or STEP with a clear unit system</li><li>Call out critical tolerances in the notes</li><li>Tell us if multiple parts must assemble</li><li>Mention heat, outdoor exposure or chemical contact</li></ul><p>The toKraft quote form already captures infill, walls, layer height, support and adhesion preferences so the first review is closer to a production conversation.</p>',
        $img('service.jpg'),
    ),
    array(
        'PLA vs PETG vs ASA for outdoor parts',
        'pla-vs-petg-vs-asa-for-outdoor-parts',
        'Indoor display parts and sun-exposed fixtures should not share the same default material.',
        '<p>PLA is excellent for detail and quick indoor models. PETG is a better everyday functional plastic. ASA is the practical outdoor choice when UV and weather matter.</p><h2>Quick rule of thumb</h2><ul><li>PLA: indoor display, concept models, low heat</li><li>PETG: brackets, jigs, housings, frequent handling</li><li>ASA: outdoor guides, fixtures, sun-facing parts</li></ul><p>Final selection still depends on geometry, orientation and the file review. The public estimate is only a starting range.</p>',
        $img('case-2.jpg'),
    ),
    array(
        'What happens after you submit a quote',
        'what-happens-after-you-submit-a-quote',
        'Upload is not approval. Every model is reviewed before price and schedule are confirmed.',
        '<p>After you submit a quote request, the production team checks manufacturability, support needs, material fit and any notes about tolerances or assemblies.</p><h2>The review loop</h2><ol><li>File and parameter intake</li><li>Geometry and process review</li><li>Confirmed quote and lead time</li><li>Production only after approval</li></ol><p>That is why the live estimate on the quote page is labelled as an estimate. It helps you compare options, but it is not the final commercial offer.</p>',
        $img('shop.jpg'),
    ),
);

foreach ($posts as $index => $post) {
    $post_id = tokraft_seed_upsert_post('post', $post[0], $post[1], $post[3], $post[2], $post[4], $index + 1);
    if ($post_id) {
        wp_set_post_categories($post_id, array());
    }
}
tokraft_seed_log('Blog posts published (3)');

// --- Homepage settings touch-up ---
$settings = function_exists('tokraft_home_settings') ? tokraft_home_settings() : (array) get_option('tokraft_home_settings', array());
$settings = array_merge($settings, array(
    'materials_count' => 6,
    'cases_count' => 5,
    'equipment_count' => 4,
    'materials_button_url' => '/materials/',
    'cases_button_label' => 'View Case Studies',
    'materials_button_label' => 'View Material Library',
));
if ($img('hero.jpg')) {
    $settings['hero_image'] = $img('hero.jpg');
}
if ($img('service.jpg')) {
    $settings['service_image'] = $img('service.jpg');
}
if ($img('shop.jpg')) {
    $settings['shop_image'] = $img('shop.jpg');
}
update_option('tokraft_home_settings', $settings);

flush_rewrite_rules(false);
update_option('tokraft_demo_seed_version', 'p0p1-1.0.0');

tokraft_seed_log('=== seed complete ===');
tokraft_seed_log('Home: ' . home_url('/'));
tokraft_seed_log('Blog: ' . get_permalink($blog_id));
tokraft_seed_log('Quote: ' . home_url('/quote/'));
tokraft_seed_log('Shop: ' . $shop_url);
tokraft_seed_log('Cases: ' . $case_url);
