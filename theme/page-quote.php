<?php
/* Template Name: 3D Printing Quote */
get_header();
$sent = isset($_GET['quote_sent']);
$error = isset($_GET['quote_error']);
$quote_settings = tokraft_quote_settings();
$quote_materials = get_terms(array('taxonomy' => 'tokraft_material', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC'));
if (is_wp_error($quote_materials)) {
    $quote_materials = array();
}
$requested_material = isset($_GET['material']) ? sanitize_title(wp_unslash($_GET['material'])) : '';

// Normalise terms (or a seeded fallback list) into one shape the markup below can loop over.
$material_choices = array();
foreach ($quote_materials as $material) {
    $material_choices[] = array(
        'name' => $material->name,
        'slug' => $material->slug,
        'estimate' => tokraft_material_quote_rate($material),
        'colors' => tokraft_material_colors($material),
    );
}
if (!$material_choices) {
    foreach (array('PLA' => 24, 'PETG' => 29, 'ASA' => 32, 'TPU' => 37) as $fallback_name => $fallback_rate) {
        $material_choices[] = array(
            'name' => $fallback_name,
            'slug' => sanitize_title($fallback_name),
            'estimate' => $fallback_rate,
            'colors' => tokraft_material_default_colors(),
        );
    }
}

$selected_slug = $material_choices[0]['slug'];
foreach ($material_choices as $choice) {
    if ($requested_material && $requested_material === $choice['slug']) {
        $selected_slug = $choice['slug'];
        break;
    }
}

$sliders = array(
    'infill' => array('id' => 'infill', 'name' => 'infill', 'suffix' => '%', 'decimals' => 0),
    'walls' => array('id' => 'walls', 'name' => 'walls', 'suffix' => ' walls', 'decimals' => 0),
    'layer' => array('id' => 'layer-height', 'name' => 'layer_height', 'suffix' => ' mm', 'decimals' => 2),
);
?>
<section class="page-hero"><div class="page-hero-inner"><div class="eyebrow">Print service / Request a quote</div><h1>Tell us what your part needs.</h1><p>Share your file and production preferences in one place. We will review manufacturability and confirm the final price and schedule before anything is made.</p></div></section>
<div class="quote-layout">
    <form class="quote-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="tokraft_quote">
        <?php wp_nonce_field('tokraft_quote', 'tokraft_quote_nonce'); ?>
        <?php if ($sent) : ?><div class="notice">Thanks - your request is with our production team. We will review the file and reply with a confirmed quote.</div><?php endif; ?>
        <?php if ($error) : ?><div class="notice" style="border-color:#a93b31;background:#fff1f0;color:#7d271f;">Please complete the required details, pick at least one colour, and upload a supported model file if you have one.</div><?php endif; ?>
        <section class="form-step"><div class="step-heading"><span class="step-number">1</span><div><h2>Upload your model</h2><p>Our team checks every file before production. Add any supported model you already have.</p></div></div><label class="upload-zone"><input type="file" id="model-file" name="model_file" accept=".stl,.3mf,.step,.stp,.obj"><span><span class="upload-icon">⇧</span><strong>Drop your model here or choose a file</strong><span>STL, 3MF, STEP, STP or OBJ · up to your server upload limit</span></span></label><p id="file-status" class="file-status" aria-live="polite"></p></section>
        <section class="form-step">
            <div class="step-heading"><span class="step-number">2</span><div><h2>Choose print details</h2><p>Set your preferences; a question mark explains what each setting affects.</p></div></div>
            <div class="form-grid">
                <div class="field">
                    <label for="material">Material</label>
                    <select id="material" name="material">
                        <?php foreach ($material_choices as $choice) : ?>
                            <option value="<?php echo esc_attr($choice['name']); ?>" data-estimate="<?php echo esc_attr($choice['estimate']); ?>" data-material-slug="<?php echo esc_attr($choice['slug']); ?>" <?php selected($selected_slug, $choice['slug']); ?>><?php echo esc_html($choice['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="1" value="1" required>
                </div>
                <div class="field field-colors">
                    <span class="field-label">Colour <small>(choose one or more)</small></span>
                    <?php foreach ($material_choices as $choice) : ?>
                        <div class="color-choice" data-color-group="<?php echo esc_attr($choice['slug']); ?>" aria-label="Colours available in <?php echo esc_attr($choice['name']); ?>" <?php echo $selected_slug === $choice['slug'] ? '' : 'hidden'; ?>>
                            <?php foreach ($choice['colors'] as $color) : ?>
                                <label class="choice">
                                    <input type="checkbox" name="color[]" value="<?php echo esc_attr($color['label']); ?>">
                                    <span style="background:<?php echo esc_attr($color['hex']); ?>" title="<?php echo esc_attr($color['label']); ?>"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $has_slider = false;
            foreach ($sliders as $group => $slider) {
                if (tokraft_quote_enabled($group)) {
                    $has_slider = true;
                    break;
                }
            }
            ?>
            <?php if ($has_slider) : ?>
                <div style="margin-top:31px">
                    <?php foreach ($sliders as $group => $slider) :
                        if (!tokraft_quote_enabled($group)) {
                            continue;
                        }
                        $default = (float) $quote_settings[$group . '_default'];
                        $display = number_format($default, $slider['decimals']) . $slider['suffix'];
                        ?>
                        <div class="range-field">
                            <div class="range-top">
                                <label class="field-label" for="<?php echo esc_attr($slider['id']); ?>"><?php echo esc_html($quote_settings[$group . '_label']); ?> <button class="help" type="button" data-help="<?php echo esc_attr($quote_settings[$group . '_help']); ?>">?</button></label>
                                <output class="range-value" for="<?php echo esc_attr($slider['id']); ?>" id="<?php echo esc_attr($slider['id']); ?>-value"><?php echo esc_html($display); ?></output>
                            </div>
                            <input type="range" id="<?php echo esc_attr($slider['id']); ?>" name="<?php echo esc_attr($slider['name']); ?>"
                                min="<?php echo esc_attr($quote_settings[$group . '_min']); ?>"
                                max="<?php echo esc_attr($quote_settings[$group . '_max']); ?>"
                                step="<?php echo esc_attr($quote_settings[$group . '_step']); ?>"
                                value="<?php echo esc_attr($quote_settings[$group . '_default']); ?>"
                                data-output="<?php echo esc_attr($slider['id']); ?>-value"
                                data-suffix="<?php echo esc_attr($slider['suffix']); ?>"
                                data-impact="<?php echo esc_attr('layer' === $group ? 'layer-impact' : $slider['id'] . '-impact'); ?>"
                                data-decimals="<?php echo esc_attr($slider['decimals']); ?>">
                            <div class="range-impact" id="<?php echo esc_attr('layer' === $group ? 'layer-impact' : $slider['id'] . '-impact'); ?>"><?php echo esc_html($quote_settings[$group . '_impact_mid']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (tokraft_quote_enabled('support') || tokraft_quote_enabled('adhesion')) : ?>
                <div class="form-grid">
                    <?php foreach (array('support', 'adhesion') as $group) :
                        if (!tokraft_quote_enabled($group)) {
                            continue;
                        }
                        $choices = tokraft_quote_choice_options($group . '_options');
                        if (!$choices) {
                            continue;
                        }
                        $group_default = $quote_settings[$group . '_default'];
                        ?>
                        <div class="field">
                            <span class="field-label"><?php echo esc_html($quote_settings[$group . '_label']); ?> <button class="help" type="button" data-help="<?php echo esc_attr($quote_settings[$group . '_help']); ?>">?</button></span>
                            <div class="toggle-row">
                                <?php foreach ($choices as $index => $choice) : ?>
                                    <label class="choice">
                                        <input type="radio" name="<?php echo esc_attr($group); ?>" value="<?php echo esc_attr($choice['value']); ?>" <?php checked($group_default === $choice['value'] || (0 === $index && !in_array($group_default, wp_list_pluck($choices, 'value'), true))); ?>>
                                        <span><?php echo esc_html($choice['label']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section class="form-step">
            <div class="step-heading"><span class="step-number">3</span><div><h2>Project &amp; contact</h2><p>Tell us anything that affects how the part must fit, work or look.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="contact-first-name">First name</label><input id="contact-first-name" name="contact_first_name" autocomplete="given-name" required></div>
                <div class="field"><label for="contact-last-name">Last name</label><input id="contact-last-name" name="contact_last_name" autocomplete="family-name" required></div>
                <div class="field"><label for="contact-email">Email</label><input id="contact-email" name="contact_email" type="email" autocomplete="email" required></div>
                <div class="field"><label for="company">Company <small>(optional)</small></label><input id="company" name="company" autocomplete="organization"></div>
                <div class="field"><label for="project-type">Project type</label><select id="project-type" name="project_type"><option>Prototype</option><option>Replacement part</option><option>Small production run</option><option>Product development</option></select></div>
            </div>
            <div class="field" style="margin-top:22px"><label for="notes">Tolerances, assemblies or special requirements</label><textarea id="notes" name="notes" placeholder="For example: critical fit dimensions, threaded inserts, multiple parts that must assemble, finish expectations or deadline."></textarea></div>
            <label class="terms"><input type="checkbox" required><span>I understand this is a request for quotation. The shown estimate and production time are indicative only; a final quote requires a file review.</span></label>
            <button class="btn btn-primary" type="submit">Submit quote request <span aria-hidden="true">→</span></button>
        </section>
    </form>
    <aside class="quote-summary" aria-live="polite">
        <div class="eyebrow" style="color:var(--gold-light)">Live estimate</div>
        <h2>Your print summary</h2>
        <ul class="summary-list">
            <li><span>Material</span><b id="summary-material"><?php echo esc_html($material_choices[0]['name']); ?></b></li>
            <li><span>Colour</span><b id="summary-color">Select a colour</b></li>
            <li><span>Quantity</span><b id="summary-quantity">1 part</b></li>
            <?php if (tokraft_quote_enabled('infill')) : ?><li><span>Infill</span><b id="summary-infill"><?php echo esc_html($quote_settings['infill_default']); ?>%</b></li><?php endif; ?>
            <?php if (tokraft_quote_enabled('walls')) : ?><li><span>Walls</span><b id="summary-walls"><?php echo esc_html($quote_settings['walls_default']); ?></b></li><?php endif; ?>
            <?php if (tokraft_quote_enabled('layer')) : ?><li><span>Layer height</span><b id="summary-layer"><?php echo esc_html(number_format((float) $quote_settings['layer_default'], 2)); ?> mm</b></li><?php endif; ?>
        </ul>
        <div class="estimate"><span>Estimated range</span><strong id="estimate-price">$24–$34</strong></div>
        <p class="summary-disclaimer">This is an initial estimate, not a final offer. Model volume, orientation, geometry and post-processing are confirmed by our team after file review.</p>
    </aside>
</div>
<dialog class="modal" id="help-modal"><button class="modal-close" type="button" aria-label="Close">×</button><h3 id="help-title">Print setting</h3><p id="help-copy"></p><button class="btn btn-primary btn-small" type="button">Got it</button></dialog>
<?php get_footer(); ?>
