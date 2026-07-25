<?php
/**
 * Upgrade blog posts to realistic long-form articles with images.
 * CLI: php /var/www/html/wp-content/themes/tokraft/tools/seed-blog-articles.php
 */
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require '/var/www/html/wp-load.php';

function tokraft_blog_img($attachment_id, $caption) {
    $url = wp_get_attachment_image_url($attachment_id, 'large');
    if (!$url) {
        return '';
    }
    $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: $caption;
    return '<figure><img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" loading="lazy"><figcaption>' . esc_html($caption) . '</figcaption></figure>';
}

function tokraft_blog_ensure_category($name, $slug) {
    $term = term_exists($slug, 'category');
    if (!$term) {
        $term = wp_insert_term($name, 'category', array('slug' => $slug));
    }
    if (is_wp_error($term)) {
        return 0;
    }
    return (int) (is_array($term) ? $term['term_id'] : $term);
}

$media = array();
global $wpdb;
$rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'");
foreach ((array) $rows as $row) {
    $media[basename((string) $row->meta_value)] = (int) $row->post_id;
}
$img = function ($name) use ($media) {
    return isset($media[$name]) ? (int) $media[$name] : 0;
};

$cat_guides = tokraft_blog_ensure_category('Guides', 'guides');
$cat_materials = tokraft_blog_ensure_category('Materials', 'materials');
$cat_process = tokraft_blog_ensure_category('Process', 'process');

$articles = array(
    'how-to-prepare-an-stl-for-quoting' => array(
        'title' => 'How to prepare an STL for quoting',
        'excerpt' => 'A clean quote starts with a clean file. Export watertight geometry, name critical dimensions, and tell us what the part has to do in the real world.',
        'thumb' => $img('service.jpg'),
        'date' => '2026-07-18 10:00:00',
        'cats' => array_filter(array($cat_guides, $cat_process)),
        'content' =>
            '<p>Most quote delays are not about price. They are about missing context: units, load path, fit, material assumptions, and whether the model is actually printable as-is.</p>'
            . '<p>If you upload a solid file and answer a few practical questions up front, the first reply can sound like a production conversation instead of a scavenger hunt.</p>'
            . tokraft_blog_img($img('service.jpg'), 'A production bay is only as fast as the information that arrives with the file.')
            . '<h2>What “ready to quote” actually means</h2>'
            . '<p>We do not need a perfect manufacturing drawing on day one. We do need enough information to answer four questions:</p>'
            . '<ul>'
            . '<li>Is the geometry closed and usable?</li>'
            . '<li>What does the part have to survive after it leaves the printer?</li>'
            . '<li>Which features are cosmetic, and which ones are critical?</li>'
            . '<li>Are there multiple parts that must assemble together?</li>'
            . '</ul>'
            . '<div class="blog-callout"><strong>Rule of thumb</strong><p>If a dimension would make the part fail when it is off by half a millimetre, write that dimension in the notes. Do not leave it buried in the CAD history.</p></div>'
            . '<h2>Before you export</h2>'
            . '<div class="blog-steps">'
            . '<div class="blog-step"><span>01</span><div><h3>Pick the right format</h3><p>STL and 3MF are fine for most FDM jobs. STEP is better when we may need to adjust wall thickness, fillets, or split the body for print orientation.</p></div></div>'
            . '<div class="blog-step"><span>02</span><div><h3>Check units once</h3><p>Confirm millimetres vs inches before export. A part that looks perfect at 1:1 scale is useless if it arrives 25.4× too large.</p></div></div>'
            . '<div class="blog-step"><span>03</span><div><h3>Make it watertight</h3><p>Repair non-manifold edges, flipped normals, and zero-thickness faces. Most slicer failures start here, not in material choice.</p></div></div>'
            . '<div class="blog-step"><span>04</span><div><h3>Name the critical features</h3><p>Holes that accept hardware, snap fits, mating faces, engraved labels, and cosmetic A-sides should be called out in plain language.</p></div></div>'
            . '</div>'
            . tokraft_blog_img($img('case-3.jpg'), 'Functional parts fail at the interfaces: holes, flats, clips, and load paths.')
            . '<h2>What to write in the quote form</h2>'
            . '<p>The toKraft quote page already captures material, colour, quantity, infill, walls, layer height, support, and brim/raft preferences. Use the notes field for the things the sliders cannot see:</p>'
            . '<ul>'
            . '<li>Expected load or force direction</li>'
            . '<li>Outdoor exposure, heat, oils, or chemicals</li>'
            . '<li>Whether the part must assemble with metal hardware or another printed body</li>'
            . '<li>Surface priority: strength first, cosmetics first, or balanced</li>'
            . '<li>Any hard deadline that affects process choice</li>'
            . '</ul>'
            . '<h2>A simple checklist you can reuse</h2>'
            . '<ol>'
            . '<li>Export a clean STL, 3MF, or STEP file</li>'
            . '<li>State the unit system</li>'
            . '<li>List critical dimensions and fits</li>'
            . '<li>Choose a material for the environment, not just the colour</li>'
            . '<li>Set print preferences as a starting point, not a final process card</li>'
            . '<li>Submit the quote and wait for file review before treating the estimate as final</li>'
            . '</ol>'
            . '<div class="blog-callout"><strong>Important</strong><p>The live price range on the quote page is an estimate. Final pricing and schedule are confirmed after the model is reviewed for orientation, support, volume, and manufacturability.</p></div>'
            . '<p>If you already have a file, start with the quote form and attach the notes that would save a back-and-forth email. That is usually the fastest path to a production-ready answer.</p>',
    ),
    'pla-vs-petg-vs-asa-for-outdoor-parts' => array(
        'title' => 'PLA vs PETG vs ASA for outdoor parts',
        'excerpt' => 'Indoor display models and sun-exposed fixtures should not share the same default material. Here is a practical way to choose.',
        'thumb' => $img('case-2.jpg'),
        'date' => '2026-07-20 11:30:00',
        'cats' => array_filter(array($cat_materials, $cat_guides)),
        'content' =>
            '<p>Material choice is where many first-time print requests go sideways. People pick PLA because it is familiar, then put the part on a deck railing, a garden enclosure, or a south-facing wall.</p>'
            . '<p>For outdoor work, the right question is not “what prints easily?” It is “what still works after sunlight, weather, and handling?”</p>'
            . tokraft_blog_img($img('hero.jpg'), 'Outdoor parts need UV resistance and a clear mounting story, not just a pretty render.')
            . '<h2>Quick comparison</h2>'
            . '<table class="blog-compare"><thead><tr><th>Material</th><th>Best for</th><th>Watch-outs</th></tr></thead><tbody>'
            . '<tr><td><strong>PLA</strong></td><td>Indoor display, concept models, low-heat cosmetic parts</td><td>Softens in heat; poor long-term outdoor durability</td></tr>'
            . '<tr><td><strong>PETG</strong></td><td>Brackets, jigs, housings, everyday functional parts</td><td>Better toughness than PLA, but still not the first pick for harsh UV exposure</td></tr>'
            . '<tr><td><strong>ASA</strong></td><td>Outdoor guides, fixtures, sun-facing parts</td><td>Often the practical outdoor default for FDM fixtures</td></tr>'
            . '</tbody></table>'
            . '<h2>When PLA is still the right answer</h2>'
            . '<p>PLA is excellent when the part lives indoors and the priority is clean detail. Think display models, form studies, presentation mockups, and low-load fixtures that never see a hot dashboard or afternoon sun.</p>'
            . '<p>If the brief is “make it look right on a desk,” PLA is often the economical choice. If the brief is “leave it outside for a season,” it usually is not.</p>'
            . tokraft_blog_img($img('case-2.jpg'), 'Display-oriented parts can prioritise detail. Load-bearing outdoor parts cannot.')
            . '<h2>Where PETG earns its keep</h2>'
            . '<p>PETG is the everyday functional plastic for many brackets and shop fixtures. It is tougher than PLA, handles handling better, and is a solid default when the part is used indoors or in mixed workshop conditions.</p>'
            . '<ul>'
            . '<li>Cable brackets under a desk</li>'
            . '<li>Assembly jigs that get picked up every day</li>'
            . '<li>Housings that need a little more impact resistance</li>'
            . '</ul>'
            . '<h2>Why ASA shows up in outdoor quotes</h2>'
            . '<p>ASA is commonly selected when UV and weather exposure matter. Cable guides, exterior clips, and fixtures that sit in sunlight usually belong here unless there is a specific reason to choose something else.</p>'
            . '<div class="blog-callout"><strong>Practical default</strong><p>If the part is outdoors and the geometry is a simple functional fixture, start the conversation with ASA. We can still change after file review if heat, chemicals, or flexibility point somewhere else.</p></div>'
            . '<h2>Do not forget geometry</h2>'
            . '<p>Material cannot rescue a weak design. Wall thickness, orientation, fastener choice, and water path still matter. An outdoor cable guide with thin walls and a poorly placed screw boss can fail in any plastic.</p>'
            . '<p>That is why the quote form asks for more than material: infill, walls, layer height, support, and free-form notes about the environment all feed the same review.</p>'
            . '<p>If you are unsure, pick the material that matches the environment and describe the use case. The file review is there to confirm or correct the starting point before production.</p>',
    ),
    'what-happens-after-you-submit-a-quote' => array(
        'title' => 'What happens after you submit a quote',
        'excerpt' => 'Upload is not approval. Every model is reviewed before price and schedule are confirmed. Here is the loop from request to production.',
        'thumb' => $img('shop.jpg'),
        'date' => '2026-07-22 09:15:00',
        'cats' => array_filter(array($cat_process, $cat_guides)),
        'content' =>
            '<p>Submitting a quote can feel like dropping a file into a black box. It is not. The public estimate on the page is only the starting range. The real work starts when the model is opened and checked against the way you want the part to be used.</p>'
            . tokraft_blog_img($img('shop.jpg'), 'A quote request becomes useful once geometry, material, and intent are reviewed together.')
            . '<h2>The review loop</h2>'
            . '<div class="blog-steps">'
            . '<div class="blog-step"><span>01</span><div><h3>Intake</h3><p>We collect the model, material, colour, quantity, print preferences, and any notes about tolerances, assemblies, or deadlines.</p></div></div>'
            . '<div class="blog-step"><span>02</span><div><h3>Geometry and process review</h3><p>Orientation, support needs, thin walls, holes, and material fit are checked against the intended use. This is where many estimates change.</p></div></div>'
            . '<div class="blog-step"><span>03</span><div><h3>Confirmed quote</h3><p>You receive a confirmed price and lead-time range based on the reviewed process, not only the initial slider estimate.</p></div></div>'
            . '<div class="blog-step"><span>04</span><div><h3>Production after approval</h3><p>Nothing moves into production until the quote is accepted. That keeps the commercial and technical decisions aligned.</p></div></div>'
            . '</div>'
            . '<h2>Why the website estimate is only a range</h2>'
            . '<p>Two models with the same bounding box can print very differently. Internal cavities, overhangs, required orientation, and post-processing all affect time and material use. The live estimate helps you compare options. It is not a binding commercial offer.</p>'
            . '<div class="blog-callout"><strong>What speeds things up</strong><p>Clean geometry, named critical fits, and a short note about environment or assembly. The less we have to guess, the faster the confirmed quote comes back.</p></div>'
            . tokraft_blog_img($img('equipment.jpg'), 'Process choices are confirmed against the actual machine profile and material behaviour.')
            . '<h2>What you should expect in the reply</h2>'
            . '<ul>'
            . '<li>Whether the file is printable as submitted</li>'
            . '<li>Material recommendation if the first choice is a poor fit</li>'
            . '<li>Any geometry risks that affect strength, cosmetics, or support marks</li>'
            . '<li>Confirmed pricing and schedule assumptions</li>'
            . '</ul>'
            . '<h2>If you need parts sooner</h2>'
            . '<p>For common fixtures that do not need a custom model, the shop is often faster. Ready-to-order products already have options, pricing, and checkout in place. Custom geometry still belongs on the quote path.</p>'
            . '<p>Use the quote form when the part is unique. Use the shop when the part is already solved. Both routes exist so you do not force every request through the same funnel.</p>',
    ),
);

foreach ($articles as $slug => $article) {
    $post = get_page_by_path($slug, OBJECT, 'post');
    if (!$post) {
        $post_id = wp_insert_post(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_title' => $article['title'],
            'post_name' => $slug,
            'post_content' => $article['content'],
            'post_excerpt' => $article['excerpt'],
            'post_date' => $article['date'],
            'post_date_gmt' => get_gmt_from_date($article['date']),
        ), true);
    } else {
        $post_id = wp_update_post(array(
            'ID' => $post->ID,
            'post_title' => $article['title'],
            'post_content' => $article['content'],
            'post_excerpt' => $article['excerpt'],
            'post_status' => 'publish',
            'post_date' => $article['date'],
            'post_date_gmt' => get_gmt_from_date($article['date']),
        ), true);
    }

    if (is_wp_error($post_id)) {
        echo 'ERROR ', $slug, ': ', $post_id->get_error_message(), "\n";
        continue;
    }

    if (!empty($article['thumb'])) {
        set_post_thumbnail($post_id, $article['thumb']);
    }
    if (!empty($article['cats'])) {
        wp_set_post_categories($post_id, $article['cats']);
    }

    echo 'OK ', $slug, ' #', $post_id, ' len=', strlen($article['content']), "\n";
}

// Soft-hide hello world if present.
$hello = get_page_by_path('hello-world', OBJECT, 'post');
if ($hello && 'publish' === $hello->post_status) {
    wp_update_post(array('ID' => $hello->ID, 'post_status' => 'draft'));
    echo "Drafted hello-world\n";
}

echo "Blog articles upgraded.\n";
