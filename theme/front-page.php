<?php
get_header();

$hero_image_id = absint(tokraft_home_value('hero_image'));
$hero_image = $hero_image_id ? wp_get_attachment_image_url($hero_image_id, 'full') : '';
$hero_image_alt = $hero_image_id ? get_post_meta($hero_image_id, '_wp_attachment_image_alt', true) : '';
$hero_image_alt = $hero_image_alt ?: tokraft_home_value('hero_title');
$hero_slides = array();

if ('carousel' === tokraft_home_value('hero_visual_mode')) {
    foreach (tokraft_home_image_ids('hero_slides', 5) as $slide_id) {
        $slide_url = wp_get_attachment_image_url($slide_id, 'full');
        if ($slide_url) {
            $slide_alt = get_post_meta($slide_id, '_wp_attachment_image_alt', true);
            $hero_slides[] = array(
                'url' => $slide_url,
                'alt' => $slide_alt ?: tokraft_home_value('hero_title'),
            );
        }
    }
}

$hero_uses_carousel = count($hero_slides) > 1;
if (!$hero_uses_carousel && $hero_slides) {
    $hero_image = $hero_slides[0]['url'];
    $hero_image_alt = $hero_slides[0]['alt'];
}

$service_image = tokraft_home_image_url('service_image', 'large');
$shop_image = tokraft_home_image_url('shop_image', 'large');
$materials = tokraft_featured_materials(6);
$cases = tokraft_home_cases((int) tokraft_home_value('cases_count'));
$equipment = tokraft_home_equipment(2);
$proof_points = tokraft_lines(tokraft_home_value('hero_proof'));
$materials_url = tokraft_home_url('materials_button_url');
$cases_url = get_post_type_archive_link('tokraft_case_study');
$shop_products = function_exists('wc_get_products') ? wc_get_products(array(
    'limit' => 3,
    'status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
)) : array();

if (!$shop_image && $shop_products) {
    $shop_image = wp_get_attachment_image_url($shop_products[0]->get_image_id(), 'large');
}
$home_blocks = tokraft_home_blocks_config();
$home_sections = array();
ob_start();
?>

<section class="home-hero" aria-labelledby="home-hero-title">
    <div class="home-hero-copy">
        <div class="home-section-tag"><span>01</span><?php echo esc_html(tokraft_home_value('hero_eyebrow')); ?></div>
        <h1 id="home-hero-title"><?php echo esc_html(tokraft_home_value('hero_title')); ?> <em><?php echo esc_html(tokraft_home_value('hero_accent')); ?></em></h1>
        <p><?php echo nl2br(esc_html(tokraft_home_value('hero_description'))); ?></p>
        <div class="home-hero-actions">
            <a class="home-button home-button-primary" href="<?php echo esc_url(tokraft_home_url('hero_quote_url')); ?>"><?php echo esc_html(tokraft_home_value('hero_quote_label')); ?><span aria-hidden="true">&rarr;</span></a>
            <a class="home-button home-button-secondary" href="<?php echo esc_url(tokraft_home_url('hero_shop_url')); ?>"><?php echo esc_html(tokraft_home_value('hero_shop_label')); ?><span aria-hidden="true">&rarr;</span></a>
        </div>
        <div class="home-hero-note"><span aria-hidden="true"></span><?php esc_html_e('Upload a file for review, or order a ready-made part.', 'tokraft'); ?></div>
    </div>

    <div class="home-hero-stage<?php echo ($hero_image || $hero_uses_carousel) ? ' has-image' : ''; ?>" aria-label="<?php esc_attr_e('Featured 3D printing work', 'tokraft'); ?>">
        <div class="home-stage-grid" aria-hidden="true"></div>
        <div class="home-stage-corner home-stage-corner-top" aria-hidden="true"><span>TK / PRODUCTION</span><i></i></div>
        <?php if ($hero_uses_carousel) : ?>
            <div class="hero-carousel home-stage-carousel" data-hero-carousel aria-roledescription="carousel" aria-label="<?php esc_attr_e('Featured 3D printing work', 'tokraft'); ?>">
                <div class="hero-carousel-track">
                    <?php foreach ($hero_slides as $index => $hero_slide) : ?>
                        <figure class="hero-carousel-slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-hero-carousel-slide aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
                            <img src="<?php echo esc_url($hero_slide['url']); ?>" alt="<?php echo esc_attr($hero_slide['alt']); ?>" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>" decoding="async">
                        </figure>
                    <?php endforeach; ?>
                </div>
                <div class="hero-carousel-controls home-stage-controls">
                    <button type="button" class="hero-carousel-arrow" data-hero-carousel-previous aria-label="<?php esc_attr_e('Previous image', 'tokraft'); ?>"><span aria-hidden="true">&larr;</span></button>
                    <div class="hero-carousel-dots" aria-label="<?php esc_attr_e('Choose hero image', 'tokraft'); ?>">
                        <?php foreach ($hero_slides as $index => $hero_slide) : ?>
                            <button type="button" class="hero-carousel-dot<?php echo 0 === $index ? ' is-active' : ''; ?>" data-hero-carousel-dot="<?php echo esc_attr($index); ?>" aria-label="<?php echo esc_attr(sprintf(__('Show image %d', 'tokraft'), $index + 1)); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <span class="hero-carousel-status"><span data-hero-carousel-current>1</span> / <?php echo esc_html(count($hero_slides)); ?></span>
                    <button type="button" class="hero-carousel-arrow" data-hero-carousel-next aria-label="<?php esc_attr_e('Next image', 'tokraft'); ?>"><span aria-hidden="true">&rarr;</span></button>
                </div>
            </div>
        <?php elseif ($hero_image) : ?>
            <figure class="home-stage-single"><img src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr($hero_image_alt); ?>" loading="eager"></figure>
        <?php else : ?>
            <div class="home-stage-placeholder" aria-hidden="true"><span>H2D</span><i></i><b></b></div>
        <?php endif; ?>
        <div class="home-stage-caption"><span>FEATURE / <b><?php echo $hero_uses_carousel ? '0<span data-hero-carousel-current>1</span>' : '01'; ?></b></span><span><?php esc_html_e('INSPECTED IN-HOUSE', 'tokraft'); ?></span></div>
    </div>
</section>

<?php if ($proof_points) : ?>
    <section class="home-proof-strip" data-home-proof aria-label="<?php esc_attr_e('Production capabilities', 'tokraft'); ?>">
        <button class="home-proof-control home-proof-control-previous" type="button" data-home-proof-previous aria-label="<?php esc_attr_e('Show previous step', 'tokraft'); ?>"><span aria-hidden="true">&larr;</span></button>
        <div class="home-proof-rail" data-home-proof-rail tabindex="0">
            <?php foreach ($proof_points as $index => $proof_point) :
                $parts = array_map('trim', explode('|', $proof_point, 2)); ?>
                <div class="home-proof-item"><span>0<?php echo esc_html($index + 1); ?></span><strong><?php echo esc_html($parts[0]); ?></strong><small><?php echo esc_html($parts[1] ?? ''); ?></small></div>
            <?php endforeach; ?>
        </div>
        <button class="home-proof-control home-proof-control-next" type="button" data-home-proof-next aria-label="<?php esc_attr_e('Show next step', 'tokraft'); ?>"><span aria-hidden="true">&rarr;</span></button>
    </section>
<?php endif; ?>
<?php $home_sections['hero'] = ob_get_clean(); ob_start(); ?>

<section class="home-routes" aria-labelledby="routes-heading">
    <div class="home-section-heading home-routes-heading">
        <div>
            <div class="home-section-tag"><span>02</span><?php esc_html_e('CHOOSE YOUR ROUTE', 'tokraft'); ?></div>
            <h2 id="routes-heading"><?php esc_html_e('Start with the way you buy.', 'tokraft'); ?></h2>
        </div>
        <p><?php esc_html_e('Have a model and a specific requirement? Send it for review. Need a proven item? Purchase it directly from the shop.', 'tokraft'); ?></p>
    </div>
    <div class="home-route-grid">
        <?php foreach (array(
            'service' => array('index' => '01', 'class' => 'is-service', 'url' => tokraft_home_url('hero_quote_url')),
            'shop' => array('index' => '02', 'class' => 'is-shop', 'url' => tokraft_home_url('hero_shop_url')),
        ) as $prefix => $route) :
            $route_image = 'service' === $prefix ? $service_image : $shop_image;
            $points = array_slice(tokraft_lines(tokraft_home_value($prefix . '_points')), 0, 3); ?>
            <article class="home-route-card <?php echo esc_attr($route['class']); ?>">
                <div class="home-route-media<?php echo $route_image ? ' has-image' : ''; ?>">
                    <?php if ($route_image) : ?>
                        <img src="<?php echo esc_url($route_image); ?>" alt="<?php echo esc_attr(tokraft_home_value($prefix . '_title')); ?>" loading="lazy">
                    <?php else : ?>
                        <span class="home-route-object" aria-hidden="true"></span>
                    <?php endif; ?>
                    <span class="home-route-index"><?php echo esc_html($route['index']); ?></span>
                </div>
                <div class="home-route-content">
                    <h3><?php echo esc_html(tokraft_home_value($prefix . '_title')); ?></h3>
                    <p><?php echo nl2br(esc_html(tokraft_home_value($prefix . '_text'))); ?></p>
                    <?php if ($points) : ?><ul><?php foreach ($points as $point) : ?><li><?php echo esc_html($point); ?></li><?php endforeach; ?></ul><?php endif; ?>
                    <a class="home-text-link" href="<?php echo esc_url($route['url']); ?>"><?php echo esc_html(tokraft_home_value($prefix . '_button_label')); ?><span aria-hidden="true">&rarr;</span></a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php $home_sections['routes'] = ob_get_clean(); ob_start(); ?>

<?php if ($equipment) : ?>
    <section class="home-fleet" id="equipment" aria-labelledby="fleet-heading">
        <div class="home-section-heading">
            <div>
                <div class="home-section-tag"><span>03</span><?php echo esc_html(tokraft_home_value('equipment_eyebrow')); ?></div>
                <h2 id="fleet-heading"><?php echo esc_html(tokraft_home_value('equipment_title')); ?></h2>
            </div>
            <p><?php echo esc_html(tokraft_home_value('equipment_text')); ?></p>
        </div>
        <div class="home-config-grid">
            <?php foreach ($equipment as $index => $item) : ?>
                <a class="home-config-card" href="<?php echo esc_url(get_permalink($item)); ?>">
                    <div class="home-config-image<?php echo has_post_thumbnail($item) ? ' has-image' : ''; ?>"><?php if (has_post_thumbnail($item)) { echo get_the_post_thumbnail($item, 'large', array('loading' => 'lazy')); } ?></div>
                    <div class="home-config-copy">
                        <span class="home-config-index">CONFIG / 0<?php echo esc_html($index + 1); ?></span>
                        <h3><?php echo esc_html(get_the_title($item)); ?></h3>
                        <p><?php echo esc_html(wp_trim_words(wp_strip_all_tags($item->post_content), 26)); ?></p>
                        <span class="home-config-link"><?php esc_html_e('View equipment profile', 'tokraft'); ?><b aria-hidden="true">&nearr;</b></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<?php $home_sections['equipment'] = ob_get_clean(); ob_start(); ?>

<section class="home-materials" id="materials" aria-labelledby="materials-heading">
    <div class="home-materials-intro">
        <div class="home-section-tag"><span>04</span><?php echo esc_html(tokraft_home_value('materials_eyebrow')); ?></div>
        <div class="home-materials-intro-content">
            <h2 id="materials-heading"><?php echo esc_html(tokraft_home_value('materials_title')); ?></h2>
            <div class="home-materials-intro-support">
                <p><?php echo esc_html(tokraft_home_value('materials_text')); ?></p>
                <a class="home-text-link" href="<?php echo esc_url($materials_url); ?>"><?php echo esc_html(tokraft_home_value('materials_button_label')); ?><span aria-hidden="true">&rarr;</span></a>
            </div>
        </div>
    </div>
    <div class="home-material-tabs" role="tablist" aria-label="<?php esc_attr_e('Choose a material card', 'tokraft'); ?>">
        <?php foreach ($materials as $index => $material) : ?>
            <button type="button" role="tab" data-home-material-target="material-<?php echo esc_attr($material->slug); ?>" aria-controls="material-<?php echo esc_attr($material->slug); ?>" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"><?php echo esc_html($material->name); ?></button>
        <?php endforeach; ?>
    </div>
    <div class="home-material-swiper swiper" data-home-material-swiper>
        <div class="home-material-rail swiper-wrapper" role="list">
            <?php foreach ($materials as $index => $material) :
                $image_id = absint(get_term_meta($material->term_id, '_tokraft_material_image_id', true));
                $color = sanitize_hex_color(get_term_meta($material->term_id, '_tokraft_material_color', true)) ?: '#d9d9d9';
                $description = get_term_meta($material->term_id, '_tokraft_material_short_description', true);
                $quote_url = add_query_arg('material', $material->slug, home_url('/quote/')); ?>
                <a id="material-<?php echo esc_attr($material->slug); ?>" class="home-material-card swiper-slide<?php echo $image_id ? ' has-image' : ''; ?>" href="<?php echo esc_url($quote_url); ?>" style="--material-color: <?php echo esc_attr($color); ?>" role="listitem">
                    <div class="home-material-image"><?php if ($image_id) { echo wp_get_attachment_image($image_id, 'medium_large', false, array('loading' => 'lazy')); } ?></div>
                    <span class="home-material-card-number" aria-hidden="true">0<?php echo esc_html($index + 1); ?></span>
                    <span class="home-material-card-title"><?php echo esc_html($material->name); ?></span>
                    <small><?php echo esc_html($description); ?></small>
                    <span class="home-material-card-action"><?php esc_html_e('Start a quote', 'tokraft'); ?><b aria-hidden="true">&rarr;</b></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="home-material-scrollbar swiper-scrollbar" data-home-material-scrollbar aria-hidden="true"></div>
    </div>
</section>

<?php if ($shop_products) : ?>
    <section class="home-shop-preview" aria-labelledby="shop-heading">
        <div class="home-section-heading">
            <div>
                <div class="home-section-tag"><span>05</span><?php esc_html_e('READY TO ORDER', 'tokraft'); ?></div>
                <h2 id="shop-heading"><?php esc_html_e('Useful parts, ready when you are.', 'tokraft'); ?></h2>
            </div>
            <a class="home-text-link" href="<?php echo esc_url(tokraft_home_url('hero_shop_url')); ?>"><?php esc_html_e('View all products', 'tokraft'); ?><span aria-hidden="true">&rarr;</span></a>
        </div>
        <div class="home-product-grid">
            <?php foreach ($shop_products as $product) :
                $product_materials = get_the_terms($product->get_id(), 'tokraft_material');
                $material_name = !is_wp_error($product_materials) && $product_materials ? $product_materials[0]->name : __('Ready to order', 'tokraft'); ?>
                <article class="home-product-card">
                    <a class="home-product-image" href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo $product->get_image('medium_large', array('loading' => 'lazy')); ?></a>
                    <div class="home-product-meta"><span><?php echo esc_html($material_name); ?></span><strong><?php echo wp_kses_post($product->get_price_html()); ?></strong></div>
                    <h3><a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($cases) : ?>
    <section class="home-cases" aria-labelledby="cases-heading">
        <div class="home-section-heading">
            <div>
                <div class="home-section-tag"><span>06</span><?php echo esc_html(tokraft_home_value('cases_eyebrow')); ?></div>
                <h2 id="cases-heading"><?php echo esc_html(tokraft_home_value('cases_title')); ?></h2>
            </div>
            <?php if ($cases_url) : ?><a class="home-text-link" href="<?php echo esc_url($cases_url); ?>"><?php echo esc_html(tokraft_home_value('cases_button_label')); ?><span aria-hidden="true">&rarr;</span></a><?php endif; ?>
        </div>
        <div class="home-case-grid">
            <?php foreach ($cases as $index => $case) :
                $case_materials = get_the_terms($case, 'tokraft_material');
                $material_name = !is_wp_error($case_materials) && $case_materials ? $case_materials[0]->name : '';
                $industry = get_post_meta($case->ID, '_tokraft_case_industry', true); ?>
                <article class="home-case-card<?php echo 0 === $index ? ' is-featured' : ''; ?>">
                    <a class="home-case-image" href="<?php echo esc_url(get_permalink($case)); ?>"><?php if (has_post_thumbnail($case)) { echo get_the_post_thumbnail($case, 'medium_large', array('loading' => 'lazy')); } ?></a>
                    <div class="home-case-meta"><span><?php echo esc_html($material_name ?: __('Production case', 'tokraft')); ?></span><span><?php echo esc_html($industry); ?></span></div>
                    <h3><a href="<?php echo esc_url(get_permalink($case)); ?>"><?php echo esc_html(get_the_title($case)); ?></a></h3>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<?php $home_sections['showcase'] = ob_get_clean(); ob_start(); ?>

<section class="home-final-cta">
    <div class="home-final-copy">
        <div class="home-section-tag"><span>07</span><?php echo esc_html(tokraft_home_value('metrics_eyebrow')); ?></div>
        <h2><?php echo esc_html(tokraft_home_value('metrics_title')); ?></h2>
        <p><?php echo esc_html(tokraft_home_value('metrics_text')); ?></p>
        <div class="home-hero-actions">
            <a class="home-button home-button-gold" href="<?php echo esc_url(tokraft_home_url('hero_quote_url')); ?>"><?php echo esc_html(tokraft_home_value('hero_quote_label')); ?><span aria-hidden="true">&rarr;</span></a>
            <a class="home-button home-button-dark-outline" href="<?php echo esc_url(tokraft_home_url('hero_shop_url')); ?>"><?php echo esc_html(tokraft_home_value('hero_shop_label')); ?><span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
    <div class="home-final-facts">
        <?php foreach (array('one', 'two', 'three') as $number) : ?>
            <div><span><?php echo esc_html(tokraft_home_value('metric_' . $number . '_value')); ?></span><small><?php echo esc_html(tokraft_home_value('metric_' . $number . '_label')); ?></small></div>
        <?php endforeach; ?>
    </div>
</section>

<?php
$home_sections['trust'] = ob_get_clean();
foreach ($home_blocks['order'] as $block_key) {
    if (!empty($home_blocks['visible'][$block_key]) && !empty($home_sections[$block_key])) {
        echo $home_sections[$block_key]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
get_footer();
?>
