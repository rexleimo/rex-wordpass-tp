<?php
get_header();
$materials = get_terms(array(
    'taxonomy' => 'tokraft_material',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
));
$has_materials = !is_wp_error($materials) && !empty($materials);
$hero_material = $has_materials ? $materials[0] : null;
$hero_image_id = $hero_material ? absint(get_term_meta($hero_material->term_id, '_tokraft_material_image_id', true)) : 0;
$type_labels = tokraft_material_type_choices();
$available_types = array();
if ($has_materials) {
    foreach ($materials as $material) {
        $available_types[tokraft_material_type($material)] = true;
    }
}
?>
<section class="materials-hero">
    <div class="materials-hero-inner">
        <div class="materials-hero-copy">
            <span class="materials-kicker">Material library / 01</span>
            <h1>Material choices, made tangible.</h1>
            <p>Explore production-ready polymers through their real-world strengths, trade-offs and quoting considerations. Every choice is reviewed against the geometry you need to make.</p>
            <div class="materials-hero-notes" aria-label="Material library highlights">
                <span><b><?php echo esc_html(str_pad((string) count($materials), 2, '0', STR_PAD_LEFT)); ?></b> curated grades</span>
                <span><b>1:1</b> file review before production</span>
            </div>
        </div>
        <div class="materials-hero-art" aria-hidden="true">
            <span class="materials-orbit materials-orbit-one"></span>
            <span class="materials-orbit materials-orbit-two"></span>
            <span class="materials-spool"></span>
            <figure class="materials-specimen<?php echo $hero_image_id ? ' has-image' : ''; ?>">
                <?php if ($hero_image_id) {
                    echo wp_get_attachment_image($hero_image_id, 'medium_large', false, array('loading' => 'eager'));
                } ?>
                <figcaption><?php echo $hero_material ? esc_html($hero_material->name) : esc_html__('Material sample', 'tokraft'); ?></figcaption>
            </figure>
        </div>
    </div>
</section>
<main class="material-library">
    <?php if ($has_materials) : ?>
        <header class="material-library-intro">
            <div>
                <span class="materials-kicker">The material index</span>
                <h2>Choose a type. Draw a card.</h2>
            </div>
            <p>Every card gives you the complete production conversation upfront: use case, trade-offs and what we check before quoting.</p>
        </header>

        <div class="material-library-filter" role="tablist" aria-label="Filter materials by type">
            <button class="is-active" type="button" role="tab" aria-selected="true" data-material-filter="all">All types</button>
            <?php foreach ($type_labels as $type => $label) :
                if (empty($available_types[$type])) {
                    continue;
                }
                ?>
                <button type="button" role="tab" aria-selected="false" data-material-filter="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></button>
            <?php endforeach; ?>
        </div>

        <div class="material-library-stage">
            <div class="material-library-swiper swiper" data-material-library-swiper aria-label="Material cards">
                <div class="swiper-wrapper">
                    <?php foreach ($materials as $index => $material) :
                        $image_id = absint(get_term_meta($material->term_id, '_tokraft_material_image_id', true));
                        $color = sanitize_hex_color(get_term_meta($material->term_id, '_tokraft_material_color', true)) ?: '#d9d9d9';
                        $summary = get_term_meta($material->term_id, '_tokraft_material_short_description', true);
                        $best_for = get_term_meta($material->term_id, '_tokraft_material_best_for', true);
                        $avoid = get_term_meta($material->term_id, '_tokraft_material_avoid', true);
                        $notes = get_term_meta($material->term_id, '_tokraft_material_notes', true);
                        $type = tokraft_material_type($material);
                        $quote_url = add_query_arg('material', $material->slug, home_url('/quote/'));
                        ?>
                        <article class="material-library-card swiper-slide" data-material-type="<?php echo esc_attr($type); ?>" style="--swatch:<?php echo esc_attr($color); ?>">
                            <div class="material-library-image<?php echo $image_id ? ' has-image' : ''; ?>">
                                <span class="material-library-index"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                                <?php if ($image_id) {
                                    echo wp_get_attachment_image($image_id, 'large', false, array('loading' => 0 === $index ? 'eager' : 'lazy'));
                                } ?>
                            </div>
                            <div class="material-library-copy">
                                <div class="material-library-title-row">
                                    <span class="material-library-type"><?php echo esc_html($type_labels[$type]); ?></span>
                                    <span class="material-library-swatch" aria-hidden="true"></span>
                                </div>
                                <h2><?php echo esc_html($material->name); ?></h2>
                                <p class="material-library-summary"><?php echo esc_html($summary ?: __('A production material selected to balance performance, finish and repeatability.', 'tokraft')); ?></p>
                                <div class="material-library-guidance" aria-label="<?php echo esc_attr(sprintf(__('Production guidance for %s', 'tokraft'), $material->name)); ?>">
                                    <section class="material-library-block">
                                        <span>Best for</span>
                                        <p><?php echo esc_html($best_for ?: __('Functional parts with requirements confirmed during file review.', 'tokraft')); ?></p>
                                    </section>
                                    <section class="material-library-block">
                                        <span>Not ideal for</span>
                                        <p><?php echo esc_html($avoid ?: __('Applications where a different material better matches heat, flex or environmental requirements.', 'tokraft')); ?></p>
                                    </section>
                                    <section class="material-library-block is-note">
                                        <span>What we confirm at quote</span>
                                        <p><?php echo esc_html($notes ?: __('Geometry, orientation, finish and the forces the part will experience.', 'tokraft')); ?></p>
                                    </section>
                                </div>
                                <a class="btn btn-primary material-library-action" href="<?php echo esc_url($quote_url); ?>">
                                    Request a quote <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="material-library-controls">
                <p>Drag to explore <span aria-hidden="true">&rarr;</span></p>
                <div>
                    <button class="material-library-control" type="button" data-material-library-previous aria-label="Previous material"><span aria-hidden="true">&larr;</span></button>
                    <button class="material-library-control" type="button" data-material-library-next aria-label="Next material"><span aria-hidden="true">&rarr;</span></button>
                </div>
            </div>
            <div class="material-library-scrollbar" data-material-library-scrollbar></div>
        </div>
        <p class="material-library-footnote">Displayed estimate ranges on the quote page are starting points only. Geometry, orientation, support, and post-processing are confirmed after file review.</p>
    <?php else : ?>
        <p class="empty-state">Materials will be added here shortly.</p>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
