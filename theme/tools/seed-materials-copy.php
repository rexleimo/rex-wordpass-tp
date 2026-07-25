<?php
/**
 * Seed client-ready material library copy.
 * php /var/www/html/wp-content/themes/tokraft/tools/seed-materials-copy.php
 */
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require '/var/www/html/wp-load.php';

$materials = array(
    'PLA' => array(
        'summary' => 'Clean-detail indoor plastic for models, fixtures and low-heat everyday parts.',
        'best_for' => 'Concept models, display parts, desk fixtures, and indoor prototypes where appearance and cost matter more than heat or outdoor durability.',
        'avoid' => 'Hot environments, long-term outdoor use, sun-facing fixtures, and parts that must absorb impact or flex repeatedly.',
        'notes' => 'Confirm whether the part is only for indoor display or will see load. PLA is usually the economical starting point when cosmetics come first.',
        'color' => '#e7e3da',
        'rate' => 24,
        'featured' => '1',
    ),
    'PETG' => array(
        'summary' => 'Everyday functional plastic for brackets, jigs and housings that need more toughness than PLA.',
        'best_for' => 'Workshop brackets, cable management, assembly jigs, protective housings, and frequently handled indoor parts.',
        'avoid' => 'Parts that need rubber-like flex, or fixtures that sit in strong UV and weather for long periods.',
        'notes' => 'Confirm mounting load, fastener type and whether the part is handled every day. PETG is a practical default when toughness matters more than fine cosmetics.',
        'color' => '#275d99',
        'rate' => 29,
        'featured' => '1',
    ),
    'ASA' => array(
        'summary' => 'Outdoor-ready material for sun-exposed fixtures and weather-facing parts.',
        'best_for' => 'Outdoor cable guides, exterior clips, garden or site fixtures, and other parts that stay in sunlight or weather.',
        'avoid' => 'High-flex seals, soft protective feet, or purely indoor display models where PLA is enough.',
        'notes' => 'Confirm outdoor exposure, fastener positions and whether the part is structural or only routing/holding. ASA is the usual outdoor starting point.',
        'color' => '#d2a11f',
        'rate' => 32,
        'featured' => '1',
    ),
    'TPU' => array(
        'summary' => 'Flexible material for feet, bumpers, gaskets and impact-absorbing covers.',
        'best_for' => 'Protective feet, vibration pads, soft covers, bumper features and compliant interfaces.',
        'avoid' => 'Rigid brackets, precision alignment jigs, or thin structural shells that must stay stiff.',
        'notes' => 'Confirm how soft the part should feel, what it contacts, and whether oils, heat or compression cycles are expected. Flexible prints need a different review than rigid parts.',
        'color' => '#29323a',
        'rate' => 37,
        'featured' => '1',
    ),
    'ABS' => array(
        'summary' => 'Impact-resistant engineering plastic for enclosures and warmer indoor environments.',
        'best_for' => 'Enclosures, covers, and functional housings that need more heat tolerance and toughness than PLA.',
        'avoid' => 'Long outdoor UV exposure without protection, soft flexible parts, and fine display models where PLA is cleaner and cheaper.',
        'notes' => 'Confirm heat exposure, impact risk and surface expectations. ABS can warp more easily, so geometry and orientation are reviewed carefully before production.',
        'color' => '#8a8f98',
        'rate' => 34,
        'featured' => '1',
    ),
    'Nylon (PA)' => array(
        'summary' => 'Strong, wear-resistant option for mechanical interfaces, clips and moving contact points.',
        'best_for' => 'Hinges, clips, latches, wear interfaces and mechanical parts that need strength plus some resilience.',
        'avoid' => 'Simple indoor display models, soft gaskets, or jobs where moisture-sensitive process control is not practical.',
        'notes' => 'Confirm load path, wear surface and environment. Nylon is selected when mechanical duty is higher than a standard PLA/PETG fixture.',
        'color' => '#4f5d73',
        'rate' => 42,
        'featured' => '1',
    ),
);

// Keep existing images if present.
foreach ($materials as $name => $data) {
    $term = term_exists($name, 'tokraft_material');
    if (!$term) {
        $term = wp_insert_term($name, 'tokraft_material');
    }
    if (is_wp_error($term)) {
        echo 'ERROR ', $name, ': ', $term->get_error_message(), "\n";
        continue;
    }
    $term_id = (int) (is_array($term) ? $term['term_id'] : $term);
    update_term_meta($term_id, '_tokraft_material_short_description', $data['summary']);
    update_term_meta($term_id, '_tokraft_material_best_for', $data['best_for']);
    update_term_meta($term_id, '_tokraft_material_avoid', $data['avoid']);
    update_term_meta($term_id, '_tokraft_material_notes', $data['notes']);
    update_term_meta($term_id, '_tokraft_material_color', $data['color']);
    update_term_meta($term_id, '_tokraft_material_quote_rate', $data['rate']);
    update_term_meta($term_id, '_tokraft_material_featured', $data['featured']);
    echo 'OK ', $name, ' #', $term_id, "\n";
}

// Homepage claims should match real inventory (6 core materials, not "20+").
$settings = function_exists('tokraft_home_settings') ? tokraft_home_settings() : (array) get_option('tokraft_home_settings', array());
$settings['materials_eyebrow'] = 'MATERIALS THAT WORK';
$settings['materials_title'] = 'Core print materials, clearly explained.';
$settings['materials_text'] = 'Six practical materials for quoting: what each one is good at, when to avoid it, and what we confirm before production.';
$settings['materials_count'] = 6;
$settings['materials_button_label'] = 'View material library';
$settings['materials_button_url'] = '/materials/';
// Also soften over-claiming proof points if still present.
if (!empty($settings['hero_proof']) && false !== strpos($settings['hero_proof'], '20+')) {
    $settings['hero_proof'] = "Accepted files | STL, 3MF, STEP, STP & OBJ\nPrint controls | Material, infill, walls & layer height\nQuote workflow | File review before the final quote\nOrder options | Custom prints or ready-to-order parts";
}
if (!empty($settings['metric_two_value']) && false !== strpos((string) $settings['metric_two_value'], '20+')) {
    $settings['metric_two_value'] = 'PLA / PETG / ASA / TPU';
    $settings['metric_two_label'] = 'Core materials available for quote';
}
update_option('tokraft_home_settings', $settings);

echo "Material copy upgraded.\n";
