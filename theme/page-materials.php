<?php
get_header();
$materials = get_terms(array(
    'taxonomy' => 'tokraft_material',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
));
?>
<section class="catalog-hero">
    <div>
        <span class="route-kicker">Material library</span>
        <h1>Choose the right material for the job.</h1>
        <p>Each material below is written for a production conversation: what it is good at, when to avoid it, and what to confirm before quoting. Final process settings are still reviewed against your file.</p>
    </div>
</section>
<main class="material-library">
    <?php if (!is_wp_error($materials) && $materials) : ?>
        <div class="material-library-grid">
            <?php foreach ($materials as $material) :
                $image_id = absint(get_term_meta($material->term_id, '_tokraft_material_image_id', true));
                $color = sanitize_hex_color(get_term_meta($material->term_id, '_tokraft_material_color', true)) ?: '#d9d9d9';
                $summary = get_term_meta($material->term_id, '_tokraft_material_short_description', true);
                $best_for = get_term_meta($material->term_id, '_tokraft_material_best_for', true);
                $avoid = get_term_meta($material->term_id, '_tokraft_material_avoid', true);
                $notes = get_term_meta($material->term_id, '_tokraft_material_notes', true);
                $quote_url = add_query_arg('material', $material->slug, home_url('/quote/'));
                ?>
                <article class="material-library-card" style="--swatch:<?php echo esc_attr($color); ?>">
                    <div class="material-library-image<?php echo $image_id ? ' has-image' : ''; ?>">
                        <?php if ($image_id) {
                            echo wp_get_attachment_image($image_id, 'medium_large', false, array('loading' => 'lazy'));
                        } ?>
                    </div>
                    <div class="material-library-copy">
                        <div class="material-library-title-row">
                            <h2><?php echo esc_html($material->name); ?></h2>
                            <span class="material-library-swatch" aria-hidden="true"></span>
                        </div>
                        <?php if ($summary) : ?>
                            <p class="material-library-summary"><?php echo esc_html($summary); ?></p>
                        <?php endif; ?>

                        <?php if ($best_for) : ?>
                            <div class="material-library-block">
                                <span>Best for</span>
                                <p><?php echo esc_html($best_for); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($avoid) : ?>
                            <div class="material-library-block">
                                <span>Not ideal for</span>
                                <p><?php echo esc_html($avoid); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($notes) : ?>
                            <div class="material-library-block is-note">
                                <span>What we confirm at quote</span>
                                <p><?php echo esc_html($notes); ?></p>
                            </div>
                        <?php endif; ?>

                        <a class="btn btn-primary btn-small" href="<?php echo esc_url($quote_url); ?>">
                            Request a quote with <?php echo esc_html($material->name); ?>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="material-library-footnote">Displayed estimate ranges on the quote page are starting points only. Geometry, orientation, support, and post-processing are confirmed after file review.</p>
    <?php else : ?>
        <p class="empty-state">Materials will be added here shortly.</p>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
