<?php
get_header();
$materials = get_terms(array('taxonomy' => 'tokraft_material', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC'));
?>
<section class="catalog-hero">
    <div><span class="route-kicker">Material library</span><h1>Choose the right material.</h1><p>Compare the materials available for custom print requests. Each final choice is reviewed against your model, use case and finish requirements.</p></div>
</section>
<main class="material-library">
    <?php if (!is_wp_error($materials) && $materials) : ?><div class="material-library-grid">
        <?php foreach ($materials as $material) :
            $image_id = absint(get_term_meta($material->term_id, '_tokraft_material_image_id', true));
            $color = sanitize_hex_color(get_term_meta($material->term_id, '_tokraft_material_color', true)) ?: '#d9d9d9';
            $description = get_term_meta($material->term_id, '_tokraft_material_short_description', true); ?>
            <article class="material-library-card" style="--swatch:<?php echo esc_attr($color); ?>">
                <div class="material-library-image<?php echo $image_id ? ' has-image' : ''; ?>"><?php if ($image_id) { echo wp_get_attachment_image($image_id, 'medium_large', false, array('loading' => 'lazy')); } ?></div>
                <h2><?php echo esc_html($material->name); ?></h2><p><?php echo esc_html($description); ?></p>
                <a class="btn btn-primary btn-small" href="<?php echo esc_url(add_query_arg('material', $material->slug, home_url('/quote/'))); ?>">Request a quote <span aria-hidden="true">&rarr;</span></a>
            </article>
        <?php endforeach; ?>
    </div><?php else : ?><p class="empty-state">Materials will be added here shortly.</p><?php endif; ?>
</main>
<?php get_footer(); ?>
