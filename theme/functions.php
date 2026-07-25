<?php
/**
 * Theme setup and the quote request endpoint live together because the quote
 * form is a core capability of the toKraft theme, not generic site behavior.
 */

if (!defined('ABSPATH')) {
    exit;
}

function tokraft_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('custom-logo');
    register_nav_menus(array('primary' => __('Primary navigation', 'tokraft')));
}
add_action('after_setup_theme', 'tokraft_setup');

function tokraft_assets() {
    wp_enqueue_style(
        'tokraft-style',
        get_stylesheet_uri(),
        array(),
        (string) filemtime(get_stylesheet_directory() . '/style.css')
    );
    wp_enqueue_style(
        'tokraft-shop-product',
        get_template_directory_uri() . '/assets/shop-product.css',
        array('tokraft-style'),
        (string) filemtime(get_template_directory() . '/assets/shop-product.css')
    );
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_style(
            'tokraft-product-detail',
            get_template_directory_uri() . '/assets/product-detail.css',
            array('tokraft-shop-product'),
            (string) filemtime(get_template_directory() . '/assets/product-detail.css')
        );
    }
    wp_enqueue_script(
        'tokraft-theme',
        get_template_directory_uri() . '/assets/theme.js',
        array('jquery'),
        (string) filemtime(get_template_directory() . '/assets/theme.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'tokraft_assets');

function tokraft_theme_routes() {
    add_rewrite_rule('^quote/?$', 'index.php?tokraft_quote=1', 'top');
    add_rewrite_rule('^materials/?$', 'index.php?tokraft_material_library=1', 'top');
}
add_action('init', 'tokraft_theme_routes');

function tokraft_quote_query_var($vars) {
    $vars[] = 'tokraft_quote';
    $vars[] = 'tokraft_material_library';
    return $vars;
}
add_filter('query_vars', 'tokraft_quote_query_var');

function tokraft_quote_template($template) {
    if (get_query_var('tokraft_quote')) {
        return get_template_directory() . '/page-quote.php';
    }
    if (get_query_var('tokraft_material_library')) {
        return get_template_directory() . '/page-materials.php';
    }
    return $template;
}
add_filter('template_include', 'tokraft_quote_template');

function tokraft_flush_quote_route() {
    tokraft_theme_routes();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'tokraft_flush_quote_route');

function tokraft_default_menu() {
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $blog_url = get_option('page_for_posts') ? get_permalink((int) get_option('page_for_posts')) : home_url('/blog/');
    $case_url = get_post_type_archive_link('tokraft_case_study') ?: home_url('/case-studies/');

    echo '<a href="' . esc_url(home_url('/quote/')) . '">Print Service</a>';
    echo '<a href="' . esc_url($shop_url) . '">Shop</a>';
    echo '<a href="' . esc_url(home_url('/materials/')) . '">Materials</a>';
    echo '<a href="' . esc_url($case_url) . '">Case Studies</a>';
    echo '<a href="' . esc_url($blog_url) . '">Blog</a>';
    echo '<a href="' . esc_url(home_url('/#equipment')) . '">Equipment</a>';
}

function tokraft_quote_submission() {
    if (!isset($_POST['tokraft_quote_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokraft_quote_nonce'])), 'tokraft_quote')) {
        wp_die(__('Unable to verify this quote request. Please try again.', 'tokraft'));
    }

    $required = array('contact_name', 'contact_email', 'material', 'quantity');
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            wp_safe_redirect(add_query_arg('quote_error', 'required', wp_get_referer() ?: home_url('/quote/')));
            exit;
        }
    }

    $quote = array(
        'name' => sanitize_text_field(wp_unslash($_POST['contact_name'])),
        'email' => sanitize_email(wp_unslash($_POST['contact_email'])),
        'company' => sanitize_text_field(wp_unslash($_POST['company'] ?? '')),
        'material' => sanitize_text_field(wp_unslash($_POST['material'])),
        'color' => sanitize_text_field(wp_unslash($_POST['color'] ?? 'Natural')),
        'quantity' => absint($_POST['quantity']),
        'infill' => absint($_POST['infill'] ?? 20),
        'walls' => absint($_POST['walls'] ?? 3),
        'layer_height' => sanitize_text_field(wp_unslash($_POST['layer_height'] ?? '0.20 mm')),
        'support' => sanitize_text_field(wp_unslash($_POST['support'] ?? 'No')),
        'adhesion' => sanitize_text_field(wp_unslash($_POST['adhesion'] ?? 'None')),
        'notes' => sanitize_textarea_field(wp_unslash($_POST['notes'] ?? '')),
    );

    $uploaded_file = '';
    if (!empty($_FILES['model_file']['name'])) {
        $extension = strtolower(pathinfo(sanitize_file_name(wp_unslash($_FILES['model_file']['name'])), PATHINFO_EXTENSION));
        $allowed_extensions = array('stl', '3mf', 'step', 'stp', 'obj');
        if (!in_array($extension, $allowed_extensions, true)) {
            wp_safe_redirect(add_query_arg('quote_error', 'file', wp_get_referer() ?: home_url('/quote/')));
            exit;
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload = wp_handle_upload($_FILES['model_file'], array('test_form' => false));
        if (!empty($upload['error'])) {
            wp_safe_redirect(add_query_arg('quote_error', 'upload', wp_get_referer() ?: home_url('/quote/')));
            exit;
        }
        $uploaded_file = $upload['url'];
    }

    $message = "New toKraft print quote\n\n";
    foreach ($quote as $key => $value) {
        $message .= ucwords(str_replace('_', ' ', $key)) . ': ' . $value . "\n";
    }
    if ($uploaded_file) {
        $message .= 'File: ' . esc_url_raw($uploaded_file) . "\n";
    }
    wp_mail(get_option('admin_email'), 'New 3D printing quote request', $message, array('Reply-To: ' . $quote['email']));
    wp_safe_redirect(add_query_arg('quote_sent', '1', home_url('/quote/')));
    exit;
}
add_action('admin_post_tokraft_quote', 'tokraft_quote_submission');
add_action('admin_post_nopriv_tokraft_quote', 'tokraft_quote_submission');

function tokraft_woocommerce_product_layout() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Single product: custom template owns title/price/excerpt/cart markup.
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);

    // Shop archive: filters bar owns count/sort chrome.
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
    remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
}
add_action('wp', 'tokraft_woocommerce_product_layout');

function tokraft_hide_shop_page_title($show) {
    if (function_exists('is_shop') && (is_shop() || is_product_taxonomy())) {
        return false;
    }
    return $show;
}
add_filter('woocommerce_show_page_title', 'tokraft_hide_shop_page_title');

/**
 * Display colour swatches on filament cards (visual; first-page Bambu-style).
 */
function tokraft_product_color_swatches($product) {
    if (!$product) {
        return array();
    }

    $saved = get_post_meta($product->get_id(), '_tokraft_color_swatches', true);
    if (is_array($saved) && $saved) {
        return $saved;
    }

    $name = strtolower($product->get_name());
    $palettes = array(
        'pla silk' => array(
            array('label' => 'Gold', 'hex' => '#D4A017'),
            array('label' => 'Silver', 'hex' => '#C0C0C0'),
            array('label' => 'Red', 'hex' => '#C62828'),
            array('label' => 'Blue', 'hex' => '#1565C0'),
            array('label' => 'Green', 'hex' => '#2E7D32'),
            array('label' => 'Purple', 'hex' => '#6A1B9A'),
        ),
        'pla translucent' => array(
            array('label' => 'Clear', 'hex' => '#E8F4FF'),
            array('label' => 'Blue', 'hex' => '#90CAF9'),
            array('label' => 'Green', 'hex' => '#A5D6A7'),
            array('label' => 'Orange', 'hex' => '#FFCC80'),
            array('label' => 'Pink', 'hex' => '#F8BBD0'),
        ),
        'petg translucent' => array(
            array('label' => 'Clear', 'hex' => '#F5F7FA'),
            array('label' => 'Blue', 'hex' => '#64B5F6'),
            array('label' => 'Green', 'hex' => '#81C784'),
            array('label' => 'Orange', 'hex' => '#FFB74D'),
        ),
        'tpu' => array(
            array('label' => 'Black', 'hex' => '#1A1A1A'),
            array('label' => 'White', 'hex' => '#F5F5F5'),
            array('label' => 'Red', 'hex' => '#E53935'),
            array('label' => 'Blue', 'hex' => '#1E88E5'),
            array('label' => 'Yellow', 'hex' => '#FDD835'),
        ),
        'default' => array(
            array('label' => 'Black', 'hex' => '#1C1C1C'),
            array('label' => 'White', 'hex' => '#F7F7F7'),
            array('label' => 'Gray', 'hex' => '#9E9E9E'),
            array('label' => 'Red', 'hex' => '#C62828'),
            array('label' => 'Orange', 'hex' => '#EF6C00'),
            array('label' => 'Yellow', 'hex' => '#F9A825'),
            array('label' => 'Green', 'hex' => '#2E7D32'),
            array('label' => 'Blue', 'hex' => '#1565C0'),
        ),
    );

    foreach ($palettes as $key => $colors) {
        if ($key !== 'default' && strpos($name, $key) !== false) {
            return $colors;
        }
    }

    // Engineering / CF grades: fewer industrial colours.
    if (preg_match('/\b(cf|gf|pc|pa6|ppa|pps|abs|asa)\b/i', $name)) {
        return array(
            array('label' => 'Black', 'hex' => '#111111'),
            array('label' => 'Natural', 'hex' => '#D7D2C8'),
            array('label' => 'Gray', 'hex' => '#6B7280'),
        );
    }

    return $palettes['default'];
}

/**
 * Honour stock_status + price query args on shop/category archives.
 */
function tokraft_shop_query_filters($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    if (!(is_shop() || is_product_taxonomy())) {
        return;
    }

    $meta_query = (array) $query->get('meta_query');

    if (!empty($_GET['stock_status'])) {
        $status = wc_clean(wp_unslash($_GET['stock_status']));
        if (in_array($status, array('instock', 'outofstock', 'onbackorder'), true)) {
            $meta_query[] = array(
                'key' => '_stock_status',
                'value' => $status,
            );
        }
    }

    $min = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
    $max = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
    if ($min || $max) {
        $price = array('key' => '_price', 'type' => 'NUMERIC');
        if ($min && $max) {
            $price['value'] = array($min, $max);
            $price['compare'] = 'BETWEEN';
        } elseif ($min) {
            $price['value'] = $min;
            $price['compare'] = '>=';
        } else {
            $price['value'] = $max;
            $price['compare'] = '<=';
        }
        $meta_query[] = $price;
    }

    if ($meta_query) {
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'tokraft_shop_query_filters');

function tokraft_loop_columns() {
    return 4;
}
add_filter('loop_shop_columns', 'tokraft_loop_columns');

function tokraft_products_per_page() {
    return 16;
}
add_filter('loop_shop_per_page', 'tokraft_products_per_page', 20);

function tokraft_render_shop_filters($count = 0) {
    if (!function_exists('wc_get_page_permalink')) {
        return;
    }

    $shop_url = wc_get_page_permalink('shop');
    $current_slug = '';
    if (is_product_category()) {
        $term = get_queried_object();
        $current_slug = ($term && !is_wp_error($term)) ? $term->slug : '';
    }

    $filament = get_term_by('slug', 'filament', 'product_cat');
    $chips = array(
        array(
            'slug' => '',
            'label' => __('All filament', 'tokraft'),
            'url' => $shop_url,
        ),
    );

    if ($filament && !is_wp_error($filament)) {
        $children = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => (int) $filament->term_id,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
        if (!is_wp_error($children)) {
            foreach ($children as $child) {
                $chips[] = array(
                    'slug' => $child->slug,
                    'label' => $child->name,
                    'url' => get_term_link($child),
                );
            }
        }
    }

    $orderby = isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : 'menu_order';
    $order_options = apply_filters('woocommerce_catalog_orderby', array(
        'menu_order' => __('Sort / Featured', 'tokraft'),
        'popularity' => __('Sort / Popularity', 'tokraft'),
        'date' => __('Sort / Newest', 'tokraft'),
        'price' => __('Sort / Price: low to high', 'tokraft'),
        'price-desc' => __('Sort / Price: high to low', 'tokraft'),
    ));
    ?>
    <div class="tk-shop-filters">
        <div class="tk-shop-filter-chips" role="navigation" aria-label="<?php esc_attr_e('Filter by category', 'tokraft'); ?>">
            <?php foreach ($chips as $chip) :
                $is_active = ($chip['slug'] === $current_slug) || ('' === $chip['slug'] && '' === $current_slug && is_shop());
                $url = $chip['url'];
                if ($orderby && 'menu_order' !== $orderby) {
                    $url = add_query_arg('orderby', $orderby, $url);
                }
                ?>
                <a class="tk-shop-chip<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url($url); ?>">
                    <?php echo esc_html($chip['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="tk-shop-filter-meta">
            <div class="tk-shop-count">
                <?php
                printf(
                    esc_html(_n('%d product ready to order', '%d products ready to order', $count, 'tokraft')),
                    (int) $count
                );
                ?>
            </div>
            <form class="tk-shop-sort" method="get" action="">
                <?php
                // Preserve category context on sort.
                if (is_product_category() && $current_slug) {
                    // Term archive already targets category URL when form action is empty.
                }
                foreach ($_GET as $key => $value) {
                    if ('orderby' === $key) {
                        continue;
                    }
                    if (is_array($value)) {
                        continue;
                    }
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr(wc_clean(wp_unslash($value))) . '">';
                }
                ?>
                <label class="screen-reader-text" for="tk-orderby"><?php esc_html_e('Sort products', 'tokraft'); ?></label>
                <select name="orderby" id="tk-orderby" class="orderby" onchange="this.form.submit()">
                    <?php foreach ($order_options as $id => $label) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($orderby, $id); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
    <?php
}

function tokraft_cart_note() {
    global $product;
    if ($product instanceof WC_Product) {
        $slugs = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'slugs'));
        if (!is_wp_error($slugs) && in_array('accessories', $slugs, true)) {
            echo '<p class="tokraft-cart-note">Accessory item. Confirm compatibility with your filament system before ordering.</p>';
            return;
        }
    }
    echo '<p class="tokraft-cart-note">Colour and packaging can vary slightly by batch. Confirm material fit for your application before production use.</p>';
}
add_action('woocommerce_after_add_to_cart_form', 'tokraft_cart_note');

function tokraft_register_content_types() {
    register_post_type('tokraft_case_study', array(
        'labels' => array(
            'name' => __('应用案例', 'tokraft'),
            'singular_name' => __('应用案例', 'tokraft'),
            'add_new_item' => __('添加应用案例', 'tokraft'),
            'edit_item' => __('编辑应用案例', 'tokraft'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'case-studies'),
        'menu_icon' => 'dashicons-format-gallery',
        'show_in_menu' => 'tokraft-home',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
    ));

    register_post_type('tokraft_equipment', array(
        'labels' => array(
            'name' => __('设备管理', 'tokraft'),
            'singular_name' => __('设备', 'tokraft'),
            'add_new_item' => __('添加设备', 'tokraft'),
            'edit_item' => __('编辑设备', 'tokraft'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-admin-tools',
        'show_in_menu' => 'tokraft-home',
        'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
    ));

    register_taxonomy('tokraft_material', array('product', 'tokraft_case_study'), array(
        'labels' => array(
            'name' => __('材料库', 'tokraft'),
            'singular_name' => __('材料', 'tokraft'),
            'add_new_item' => __('添加材料', 'tokraft'),
            'edit_item' => __('编辑材料', 'tokraft'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'materials'),
        'show_admin_column' => true,
        'show_in_menu' => 'tokraft-home',
    ));
}
add_action('init', 'tokraft_register_content_types', 20);

function tokraft_home_settings_defaults() {
    return array(
        'hero_eyebrow' => 'PRECISION PARTS. LOCAL SERVICE.',
        'hero_title' => 'High-precision 3D printing made in',
        'hero_accent' => 'Alberta, Canada.',
        'hero_description' => "Functional prototypes, replacement parts, and end-use components.\nMulti-material. High accuracy. Fast turnaround.",
        'hero_quote_label' => 'Get a Print Quote',
        'hero_quote_url' => '/quote/',
        'hero_shop_label' => 'Browse Shop',
        'hero_shop_url' => '/shop/',
        'hero_visual_mode' => 'single',
        'hero_image' => 0,
        'hero_slides' => '',
        'hero_proof' => "High Precision | +/-0.2 mm accuracy\nMulti-Material | 20+ engineering materials\nFast Turnaround | Quote in 24h, ship fast\nMade in Alberta | Local business, Canada",
        'service_title' => '3D Printing Service',
        'service_text' => "Custom parts printed to your specifications.\nUpload a model and get a quote in 24 hours.",
        'service_points' => "FDM Multi-material Printing\nEngineering Grade Materials\nTight Tolerances & Accuracy\nFunctional Prototypes & End-use Parts",
        'service_button_label' => 'Get a Print Quote',
        'service_image' => 0,
        'shop_title' => 'Shop Ready-to-Order Parts',
        'shop_text' => "Useful parts for makers, repairs, and everyday projects.\nReady to ship across Canada.",
        'shop_points' => "Connectors & Components\nDIY Kits & Accessories\nPractical Tools & Parts\nQuality You Can Trust",
        'shop_button_label' => 'Browse Shop',
        'shop_image' => 0,
        'equipment_eyebrow' => 'BUILT WITH CAPABILITY',
        'equipment_title' => 'Powered by Top-Tier Equipment',
        'equipment_text' => 'Our production equipment is selected for repeatable quality, accurate parts and dependable delivery.',
        'equipment_count' => 5,
        'materials_eyebrow' => 'MATERIALS THAT WORK',
        'materials_title' => '20+ Engineering Materials',
        'materials_text' => 'From everyday plastics to performance materials. Choose the right one for your project.',
        'materials_count' => 4,
        'materials_button_label' => 'View All Materials',
        'materials_button_url' => '/materials/',
        'cases_eyebrow' => 'RECENT WORK',
        'cases_title' => 'Real Parts. Real Results.',
        'cases_count' => 6,
        'cases_button_label' => 'View More Projects',
        'metrics_eyebrow' => 'LOCAL. RELIABLE. CANADIAN',
        'metrics_title' => 'Proudly Based in Alberta',
        'metrics_text' => 'We are a small business in Canada, committed to providing high-quality 3D printing services and parts with fast, reliable, and friendly support.',
        'metric_one_value' => '1000+',
        'metric_one_label' => 'Parts Printed',
        'metric_two_value' => '500+',
        'metric_two_label' => 'Happy Customers',
        'metric_three_value' => '98%',
        'metric_three_label' => 'On-time Delivery',
    );
}

function tokraft_home_settings() {
    return wp_parse_args((array) get_option('tokraft_home_settings', array()), tokraft_home_settings_defaults());
}

function tokraft_home_blocks_config() {
    // Keep the public page in the same sequence as the Block Manager.
    return array(
        'order' => array('hero', 'routes', 'equipment', 'materials', 'showcase', 'trust'),
        'visible' => array(
            'hero' => true,
            'routes' => true,
            'equipment' => true,
            'materials' => true,
            'showcase' => true,
            'trust' => false,
        ),
    );
}

function tokraft_home_value($key) {
    $settings = tokraft_home_settings();
    return isset($settings[$key]) ? $settings[$key] : '';
}

function tokraft_home_url($key) {
    $url = tokraft_home_value($key);
    return 0 === strpos($url, '/') ? home_url($url) : $url;
}

function tokraft_home_image_url($key, $size = 'full') {
    $image_id = absint(tokraft_home_value($key));
    return $image_id ? wp_get_attachment_image_url($image_id, $size) : '';
}

function tokraft_home_image_ids($key, $limit = 5) {
    $ids = preg_split('/[\s,]+/', (string) tokraft_home_value($key));
    $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));
    return array_slice($ids, 0, absint($limit));
}

function tokraft_lines($value) {
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));
}

function tokraft_featured_materials($limit = 4) {
    $featured = get_terms(array(
        'taxonomy' => 'tokraft_material',
        'hide_empty' => false,
        'number' => absint($limit),
        'meta_key' => '_tokraft_material_featured',
        'meta_value' => '1',
        'orderby' => 'name',
        'order' => 'ASC',
    ));
    if (!is_wp_error($featured) && $featured) {
        return $featured;
    }

    $materials = get_terms(array(
        'taxonomy' => 'tokraft_material',
        'hide_empty' => false,
        'number' => absint($limit),
        'orderby' => 'name',
        'order' => 'ASC',
    ));
    return is_wp_error($materials) ? array() : $materials;
}

function tokraft_home_cases($limit = 6) {
    $args = array(
        'post_type' => 'tokraft_case_study',
        'post_status' => 'publish',
        'posts_per_page' => absint($limit),
        'meta_key' => '_tokraft_case_featured',
        'meta_value' => '1',
        'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'),
    );
    $cases = get_posts($args);
    if ($cases) {
        return $cases;
    }

    unset($args['meta_key'], $args['meta_value']);
    return get_posts($args);
}

function tokraft_home_equipment($limit = 5) {
    return get_posts(array(
        'post_type' => 'tokraft_equipment',
        'post_status' => 'publish',
        'posts_per_page' => absint($limit),
        'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'),
    ));
}

function tokraft_material_quote_rate($term) {
    $term_id = is_object($term) ? $term->term_id : absint($term);
    $rate = (float) get_term_meta($term_id, '_tokraft_material_quote_rate', true);
    if ($rate > 0) {
        return $rate;
    }
    $name = is_object($term) ? strtoupper($term->name) : '';
    $defaults = array('PETG' => 29, 'ASA' => 32, 'TPU' => 37);
    return isset($defaults[$name]) ? $defaults[$name] : 24;
}

function tokraft_sanitize_home_settings($values) {
    $defaults = tokraft_home_settings_defaults();
    $sanitized = array();
    $textarea_fields = array('hero_description', 'hero_proof', 'service_text', 'service_points', 'shop_text', 'shop_points', 'materials_text', 'metrics_text');
    $image_fields = array('hero_image', 'service_image', 'shop_image');
    $image_list_fields = array('hero_slides');
    $number_fields = array('equipment_count', 'materials_count', 'cases_count');
    $url_fields = array('hero_quote_url', 'hero_shop_url', 'materials_button_url');

    foreach ($defaults as $key => $default) {
        $value = isset($values[$key]) ? $values[$key] : $default;
        if (in_array($key, $textarea_fields, true)) {
            $sanitized[$key] = sanitize_textarea_field($value);
        } elseif (in_array($key, $image_fields, true)) {
            $sanitized[$key] = absint($value);
        } elseif (in_array($key, $image_list_fields, true)) {
            $ids = preg_split('/[\s,]+/', is_array($value) ? implode(',', $value) : (string) $value);
            $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));
            $sanitized[$key] = implode(',', array_slice($ids, 0, 5));
        } elseif ('hero_visual_mode' === $key) {
            $sanitized[$key] = in_array($value, array('single', 'carousel'), true) ? $value : 'single';
        } elseif (in_array($key, $number_fields, true)) {
            $sanitized[$key] = min(12, max(1, absint($value)));
        } elseif (in_array($key, $url_fields, true)) {
            $sanitized[$key] = esc_url_raw($value);
        } else {
            $sanitized[$key] = sanitize_text_field($value);
        }
    }
    return $sanitized;
}

function tokraft_register_home_settings() {
    register_setting('tokraft_home_settings_group', 'tokraft_home_settings', 'tokraft_sanitize_home_settings');
}
add_action('admin_init', 'tokraft_register_home_settings');

function tokraft_register_admin_menu() {
    add_menu_page(__('toKraft 内容管理', 'tokraft'), __('toKraft', 'tokraft'), 'edit_others_posts', 'tokraft-home', 'tokraft_render_home_settings_page', 'dashicons-admin-customizer', 25);
    // Keep the custom landing page as the first submenu. Otherwise WordPress opens the first post type below it.
    add_submenu_page('tokraft-home', __('首页内容区块', 'tokraft'), __('首页内容区块', 'tokraft'), 'edit_others_posts', 'tokraft-home', 'tokraft_render_home_settings_page');
    add_submenu_page('tokraft-home', __('材料库', 'tokraft'), __('材料库', 'tokraft'), 'manage_categories', 'edit-tags.php?taxonomy=tokraft_material&post_type=product');
}
add_action('admin_menu', 'tokraft_register_admin_menu');

function tokraft_admin_media_field($key, $args = array()) {
    $args = wp_parse_args($args, array(
        'label' => '',
        'description' => '',
        'specification' => '',
    ));
    $image_id = absint(tokraft_home_value($key));
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
    $field_id = 'tokraft_home_settings_' . $key;
    echo '<div class="tokraft-admin-field tokraft-admin-media-field">';
    echo '<label for="' . esc_attr($field_id) . '"><span class="tokraft-admin-field-label">' . esc_html($args['label']) . '</span>';
    if ($args['description']) {
        echo '<span class="tokraft-admin-field-description">' . esc_html($args['description']) . '</span>';
    }
    echo '</label>';
    echo '<input type="hidden" id="' . esc_attr($field_id) . '" name="tokraft_home_settings[' . esc_attr($key) . ']" value="' . esc_attr($image_id) . '">';
    echo '<p class="tokraft-admin-media-actions"><button type="button" class="button button-secondary tokraft-media-select" data-target="' . esc_attr($field_id) . '">选择或替换图片</button> <button type="button" class="button button-secondary tokraft-media-clear tokraft-admin-button-danger" data-target="' . esc_attr($field_id) . '">移除图片</button></p>';
    if ($args['specification']) {
        echo '<p class="tokraft-admin-specification"><strong>推荐规格：</strong>' . esc_html($args['specification']) . '</p>';
    }
    echo '<img class="tokraft-admin-media-preview" id="' . esc_attr($field_id) . '-preview" src="' . esc_url($image_url) . '" alt="" style="' . ($image_url ? '' : 'display:none;') . '">';
    echo '</div>';
}

function tokraft_admin_media_gallery_field($key, $args = array()) {
    $args = wp_parse_args($args, array(
        'label' => '',
        'description' => '',
        'specification' => '',
        'max_items' => 5,
    ));
    $image_ids = tokraft_home_image_ids($key, $args['max_items']);
    $field_id = 'tokraft_home_settings_' . $key;
    echo '<div class="tokraft-admin-field tokraft-admin-media-gallery-field">';
    echo '<label for="' . esc_attr($field_id) . '"><span class="tokraft-admin-field-label">' . esc_html($args['label']) . '</span>';
    if ($args['description']) {
        echo '<span class="tokraft-admin-field-description">' . esc_html($args['description']) . '</span>';
    }
    echo '</label>';
    echo '<input type="hidden" id="' . esc_attr($field_id) . '" name="tokraft_home_settings[' . esc_attr($key) . ']" value="' . esc_attr(implode(',', $image_ids)) . '">';
    echo '<p class="tokraft-admin-media-actions"><button type="button" class="button button-secondary tokraft-media-gallery-select" data-target="' . esc_attr($field_id) . '" data-max-items="' . esc_attr($args['max_items']) . '">选择轮播图片</button> <button type="button" class="button button-secondary tokraft-media-gallery-clear tokraft-admin-button-danger" data-target="' . esc_attr($field_id) . '">清空轮播图片</button></p>';
    if ($args['specification']) {
        echo '<p class="tokraft-admin-specification"><strong>推荐规格：</strong>' . esc_html($args['specification']) . '</p>';
    }
    echo '<div class="tokraft-admin-media-gallery-preview" id="' . esc_attr($field_id) . '-preview">';
    foreach ($image_ids as $index => $image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'medium');
        if ($image_url) {
            echo '<figure><img src="' . esc_url($image_url) . '" alt=""><figcaption>第 ' . esc_html($index + 1) . ' 张</figcaption></figure>';
        }
    }
    echo '</div></div>';
}

function tokraft_admin_select_field($key, $args = array()) {
    $args = wp_parse_args($args, array(
        'label' => '',
        'description' => '',
        'options' => array(),
        'class' => '',
    ));
    $field_id = 'tokraft_home_settings_' . $key;
    $value = tokraft_home_value($key);
    echo '<div class="tokraft-admin-field ' . esc_attr($args['class']) . '">';
    echo '<label for="' . esc_attr($field_id) . '"><span class="tokraft-admin-field-label">' . esc_html($args['label']) . '</span>';
    if ($args['description']) {
        echo '<span class="tokraft-admin-field-description">' . esc_html($args['description']) . '</span>';
    }
    echo '</label><select id="' . esc_attr($field_id) . '" name="tokraft_home_settings[' . esc_attr($key) . ']" data-tokraft-hero-visual-mode>';
    foreach ($args['options'] as $option_value => $option_label) {
        echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
    }
    echo '</select></div>';
}

function tokraft_admin_text_field($key, $args = array()) {
    $args = wp_parse_args($args, array(
        'label' => '',
        'description' => '',
        'type' => 'text',
        'placeholder' => '',
        'class' => '',
    ));
    $value = tokraft_home_value($key);
    $field_id = 'tokraft_home_settings_' . $key;
    echo '<div class="tokraft-admin-field ' . esc_attr($args['class']) . '">';
    echo '<label for="' . esc_attr($field_id) . '"><span class="tokraft-admin-field-label">' . esc_html($args['label']) . '</span>';
    if ($args['description']) {
        echo '<span class="tokraft-admin-field-description">' . esc_html($args['description']) . '</span>';
    }
    echo '</label>';
    if ('textarea' === $args['type']) {
        echo '<textarea class="large-text" rows="4" id="' . esc_attr($field_id) . '" name="tokraft_home_settings[' . esc_attr($key) . ']" placeholder="' . esc_attr($args['placeholder']) . '">' . esc_textarea($value) . '</textarea>';
    } else {
        echo '<input class="regular-text" type="' . esc_attr($args['type']) . '" id="' . esc_attr($field_id) . '" name="tokraft_home_settings[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" placeholder="' . esc_attr($args['placeholder']) . '">';
    }
    echo '</div>';
}

function tokraft_admin_section_open($id, $step, $title, $description, $action = '') {
    echo '<section class="tokraft-admin-section" id="' . esc_attr($id) . '"><div class="tokraft-admin-section-heading"><span class="tokraft-admin-step">' . esc_html($step) . '</span><div><h2>' . esc_html($title) . '</h2><p>' . esc_html($description) . '</p></div>' . $action . '</div>';
}

function tokraft_admin_section_close() {
    echo '</section>';
}

function tokraft_admin_manage_link($url, $label) {
    return '<a class="button button-secondary tokraft-admin-manage-link" href="' . esc_url($url) . '">' . esc_html($label) . ' <span aria-hidden="true">&rarr;</span></a>';
}

function tokraft_admin_content_counts() {
    $product_count = 0;
    if (post_type_exists('product')) {
        $counts = wp_count_posts('product');
        $product_count = isset($counts->publish) ? (int) $counts->publish : 0;
    }

    $case_counts = wp_count_posts('tokraft_case_study');
    $equipment_counts = wp_count_posts('tokraft_equipment');
    $post_counts = wp_count_posts('post');
    $materials = get_terms(array(
        'taxonomy' => 'tokraft_material',
        'hide_empty' => false,
        'fields' => 'count',
    ));

    $order_total = 0;
    if (post_type_exists('shop_order')) {
        $orders = wp_count_posts('shop_order');
        foreach (array('processing', 'completed', 'on-hold', 'pending') as $status) {
            if (isset($orders->$status)) {
                $order_total += (int) $orders->$status;
            }
        }
    }

    return array(
        'products' => $product_count,
        'cases' => isset($case_counts->publish) ? (int) $case_counts->publish : 0,
        'equipment' => isset($equipment_counts->publish) ? (int) $equipment_counts->publish : 0,
        'materials' => is_wp_error($materials) ? 0 : (int) $materials,
        'posts' => isset($post_counts->publish) ? (int) $post_counts->publish : 0,
        'orders' => $order_total,
    );
}

function tokraft_render_admin_overview() {
    $counts = tokraft_admin_content_counts();
    $cards = array(
        array(
            'label' => __('Products / 商品', 'tokraft'),
            'count' => $counts['products'],
            'help' => __('WooCommerce ready-to-order parts', 'tokraft'),
            'url' => admin_url('edit.php?post_type=product'),
            'cta' => __('Manage products', 'tokraft'),
        ),
        array(
            'label' => __('Case studies / 应用案例', 'tokraft'),
            'count' => $counts['cases'],
            'help' => __('Featured applications on the homepage', 'tokraft'),
            'url' => admin_url('edit.php?post_type=tokraft_case_study'),
            'cta' => __('Manage cases', 'tokraft'),
        ),
        array(
            'label' => __('Materials / 材料库', 'tokraft'),
            'count' => $counts['materials'],
            'help' => __('Used by quote form and material library', 'tokraft'),
            'url' => admin_url('edit-tags.php?taxonomy=tokraft_material&post_type=product'),
            'cta' => __('Manage materials', 'tokraft'),
        ),
        array(
            'label' => __('Equipment / 设备', 'tokraft'),
            'count' => $counts['equipment'],
            'help' => __('Printer profiles shown on homepage', 'tokraft'),
            'url' => admin_url('edit.php?post_type=tokraft_equipment'),
            'cta' => __('Manage equipment', 'tokraft'),
        ),
        array(
            'label' => __('Blog / 博客', 'tokraft'),
            'count' => $counts['posts'],
            'help' => __('Articles linked from the primary nav', 'tokraft'),
            'url' => admin_url('edit.php'),
            'cta' => __('Manage posts', 'tokraft'),
        ),
        array(
            'label' => __('Orders / 订单', 'tokraft'),
            'count' => $counts['orders'],
            'help' => __('Shop checkout orders in WooCommerce', 'tokraft'),
            'url' => admin_url('edit.php?post_type=shop_order'),
            'cta' => __('View orders', 'tokraft'),
        ),
    );

    echo '<section class="tokraft-admin-overview" aria-label="' . esc_attr__('Content management map', 'tokraft') . '">';
    echo '<div class="tokraft-admin-overview__heading"><div><span>' . esc_html__('CONTENT MAP / 内容入口', 'tokraft') . '</span><h2>' . esc_html__('Where to manage the storefront', 'tokraft') . '</h2><p>' . esc_html__('Two businesses live here: custom print quotes and ready-to-order shop products. Use the cards below when you need to change content outside the homepage blocks.', 'tokraft') . '</p></div>';
    echo '<div class="tokraft-admin-overview__actions">';
    echo '<a class="button button-primary" href="' . esc_url(home_url('/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('View homepage', 'tokraft') . '</a>';
    echo '<a class="button" href="' . esc_url(home_url('/quote/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open quote page', 'tokraft') . '</a>';
    echo '<a class="button" href="' . esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open shop', 'tokraft') . '</a>';
    echo '</div></div>';
    echo '<div class="tokraft-admin-overview__grid">';
    foreach ($cards as $card) {
        echo '<article class="tokraft-admin-overview__card">';
        echo '<div class="tokraft-admin-overview__card-top"><span>' . esc_html($card['label']) . '</span><strong>' . esc_html((string) $card['count']) . '</strong></div>';
        echo '<p>' . esc_html($card['help']) . '</p>';
        echo '<a href="' . esc_url($card['url']) . '">' . esc_html($card['cta']) . ' <span aria-hidden="true">&rarr;</span></a>';
        echo '</article>';
    }
    echo '</div></section>';
}

function tokraft_render_home_settings_page() {
    if (!current_user_can('edit_others_posts')) {
        return;
    }
    $equipment_url = admin_url('edit.php?post_type=tokraft_equipment');
    $materials_url = admin_url('edit-tags.php?taxonomy=tokraft_material&post_type=product');
    $cases_url = admin_url('edit.php?post_type=tokraft_case_study');
    $products_url = admin_url('edit.php?post_type=product');

    echo '<div class="wrap tokraft-home-settings">';
    if (isset($_GET['settings-updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>首页配置已保存。</p></div>';
    }
    tokraft_render_admin_overview();
    echo '<form method="post" action="options.php">';
    settings_fields('tokraft_home_settings_group');
    tokraft_admin_section_open('tokraft-hero', '首页第 1 屏', '首屏：先让客户知道要做什么', '左侧是标题和两个行动按钮，右侧是设备或打印过程图片。建议保持短句、明确业务。');
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-two">';
    tokraft_admin_text_field('hero_eyebrow', array('label' => '主标题上方的小字', 'description' => '例如：CUSTOM 3D PRINTING & READY-TO-ORDER PARTS。建议一句话概括业务。'));
    tokraft_admin_text_field('hero_title', array('label' => '主标题第一部分', 'description' => '显示为主标题的深色文字。'));
    tokraft_admin_text_field('hero_accent', array('label' => '主标题重点部分', 'description' => '显示为金色强调文字，例如：real-world use。'));
    echo '</div>';
    tokraft_admin_text_field('hero_description', array('label' => '首屏说明', 'description' => '用 1-2 句解释客户可以“上传模型询价”或“直接购买产品”。换行会保留在前台。', 'type' => 'textarea'));
    echo '<div class="tokraft-admin-callout"><strong>两个按钮怎么填？</strong><span>第一个按钮应通向询价页，第二个按钮应通向商店页。链接可保留默认的 <code>/quote/</code> 和 <code>/shop/</code>。</span></div>';
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-four">';
    tokraft_admin_text_field('hero_quote_label', array('label' => '询价按钮文字', 'description' => '例如：申请打印报价'));
    tokraft_admin_text_field('hero_quote_url', array('label' => '询价按钮链接', 'description' => '默认：/quote/', 'placeholder' => '/quote/'));
    tokraft_admin_text_field('hero_shop_label', array('label' => '商店按钮文字', 'description' => '例如：浏览产品商店'));
    tokraft_admin_text_field('hero_shop_url', array('label' => '商店按钮链接', 'description' => '默认：/shop/', 'placeholder' => '/shop/'));
    echo '</div>';
    tokraft_admin_select_field('hero_visual_mode', array('label' => '首屏右侧视觉方式', 'description' => '单图适合突出一台设备或一个产品；轮播适合依次展示设备、打印过程和成品。左侧标题和两个业务入口保持不变。', 'options' => array('single' => '单张主图', 'carousel' => '多图轮播（建议 2-5 张）')));
    echo '<div class="tokraft-admin-hero-visual-option" data-tokraft-hero-visual-option="single">';
    tokraft_admin_media_field('hero_image', array('label' => '首屏右侧主图', 'description' => '选择设备、打印过程或代表性成品图。请在媒体库中同时填写图片替代文本。', 'specification' => '1600 x 1600 px 以上，1:1，JPG/WebP，建议小于 2 MB。'));
    echo '</div><div class="tokraft-admin-hero-visual-option" data-tokraft-hero-visual-option="carousel">';
    tokraft_admin_media_gallery_field('hero_slides', array('label' => '首屏轮播图片', 'description' => '按选择顺序展示，建议先放设备，再放打印过程和成品。至少选择 2 张才会启用轮播。', 'specification' => '1600 x 1600 px 以上，1:1，JPG/WebP；最多 5 张，每张建议小于 2 MB。', 'max_items' => 5));
    echo '</div>';
    tokraft_admin_text_field('hero_proof', array('label' => '首屏下方的四条信息', 'description' => '一行一条，使用“标题 | 说明”的格式。示例：Accepted files | STL, 3MF, STEP & OBJ。建议填文件格式、材料、审核或交付说明。', 'type' => 'textarea', 'placeholder' => "Accepted files | STL, 3MF, STEP & OBJ\nFile review | Final quote confirmed after inspection"));
    tokraft_admin_section_close();

    tokraft_admin_section_open('tokraft-routes', '首页第 2 区', '两个业务入口：打印服务与产品商店', '这一部分的目标是让客户在几秒内决定：上传文件询价，还是直接进入商店购买。');
    echo '<div class="tokraft-admin-route-grid">';
    foreach (array(
        'service' => array('title' => '左卡：Print Service（打印服务）', 'description' => '引导客户上传 STL、3MF、STEP 或 OBJ 并提交询价。', 'image_specification' => '1200 x 1500 px 以上，4:5 竖图，JPG/WebP，建议小于 2 MB。'),
        'shop' => array('title' => '右卡：Shop（产品商店）', 'description' => '引导客户进入标准电商流程：选规格、加入购物车并结账。', 'image_specification' => '1200 x 1500 px 以上，4:5 竖图，JPG/WebP，建议小于 2 MB。'),
    ) as $prefix => $route) {
        echo '<div class="tokraft-admin-route-card"><h3>' . esc_html($route['title']) . '</h3><p>' . esc_html($route['description']) . '</p>';
        tokraft_admin_text_field($prefix . '_title', array('label' => '卡片标题', 'description' => '客户在首页看到的业务名称。'));
        tokraft_admin_text_field($prefix . '_text', array('label' => '卡片说明', 'description' => '建议 1-2 句说明可获得的服务或产品。', 'type' => 'textarea'));
        tokraft_admin_text_field($prefix . '_points', array('label' => '卡片要点', 'description' => '一行一条，建议最多 4 条，例如：材料、文件格式、结账方式。', 'type' => 'textarea'));
        tokraft_admin_text_field($prefix . '_button_label', array('label' => '按钮文字', 'description' => '服务卡建议为“开始询价”；商店卡建议为“浏览商店”。'));
        tokraft_admin_media_field($prefix . '_image', array('label' => '卡片图片', 'description' => '服务卡使用打印过程，商店卡使用真实产品或使用场景。', 'specification' => $route['image_specification']));
        echo '</div>';
    }
    echo '</div>';
    tokraft_admin_section_close();

    tokraft_admin_section_open('tokraft-equipment', '首页第 3 区', '设备能力：展示实际机队', '这里仅控制首页区块标题和显示数量；每台设备的型号、照片和参数请到“设备管理”编辑。', tokraft_admin_manage_link($equipment_url, '管理设备'));
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-two">';
    tokraft_admin_text_field('equipment_eyebrow', array('label' => '设备区小标题', 'description' => '显示在设备大标题上方。'));
    tokraft_admin_text_field('equipment_title', array('label' => '设备区主标题', 'description' => '例如：Equipment Profiles。'));
    echo '</div>';
    tokraft_admin_text_field('equipment_text', array('label' => '设备区说明', 'description' => '说明设备能力，但上线前请确保与实际机队一致。', 'type' => 'textarea'));
    tokraft_admin_text_field('equipment_count', array('label' => '首页显示几台设备', 'description' => '建议 3-6 台。具体内容按设备管理中的排序显示。', 'type' => 'number', 'class' => 'tokraft-admin-field-narrow'));
    tokraft_admin_section_close();

    tokraft_admin_section_open('tokraft-materials', '首页第 4 区', '材料库与应用示例', '材料和应用卡片会自动读取后台数据；这里只设置首页的区块文案、按钮和显示数量。');
    echo '<div class="tokraft-admin-split-section">';
    echo '<div><div class="tokraft-admin-subheading"><h3>材料库</h3>' . tokraft_admin_manage_link($materials_url, '管理材料') . '</div>';
    tokraft_admin_text_field('materials_eyebrow', array('label' => '材料区小标题', 'description' => '显示在材料区大标题上方。'));
    tokraft_admin_text_field('materials_title', array('label' => '材料区主标题', 'description' => '例如：Core Print Materials。'));
    tokraft_admin_text_field('materials_text', array('label' => '材料区说明', 'description' => '用一句话说明客户可在材料库和询价页选择材料。', 'type' => 'textarea'));
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-two">';
    tokraft_admin_text_field('materials_count', array('label' => '首页显示几种材料', 'description' => '建议 4 种。', 'type' => 'number'));
    tokraft_admin_text_field('materials_button_label', array('label' => '材料按钮文字', 'description' => '例如：查看材料库。'));
    tokraft_admin_text_field('materials_button_url', array('label' => '材料按钮链接', 'description' => '默认：/materials/', 'placeholder' => '/materials/'));
    echo '</div></div>';
    echo '<div><div class="tokraft-admin-subheading"><h3>应用示例 / 案例</h3>' . tokraft_admin_manage_link($cases_url, '管理应用示例') . '</div>';
    tokraft_admin_text_field('cases_eyebrow', array('label' => '应用区小标题', 'description' => '建议使用“APPLICATION EXAMPLES”，避免把示例误称为客户案例。'));
    tokraft_admin_text_field('cases_title', array('label' => '应用区主标题', 'description' => '例如：Functional parts, clearly specified。'));
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-two">';
    tokraft_admin_text_field('cases_count', array('label' => '首页显示几条应用', 'description' => '建议 4-6 条。', 'type' => 'number'));
    tokraft_admin_text_field('cases_button_label', array('label' => '应用按钮文字', 'description' => '例如：查看应用示例。'));
    echo '</div><div class="tokraft-admin-callout tokraft-admin-callout-muted"><strong>注意</strong><span>当前应用内容是演示资料。获得客户和图片授权后，再将其替换为真实项目。</span></div></div>';
    echo '</div>';
    tokraft_admin_section_close();

    tokraft_admin_section_open('tokraft-trust', '首页第 5 区', '底部说明：把关键信息说清楚', '这个深色区块适合填文件格式、可用材料、人工审核和报价确认方式；不要填写未经证实的订单量或客户数量。');
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-two">';
    tokraft_admin_text_field('metrics_eyebrow', array('label' => '底部区小标题', 'description' => '显示在大标题上方。'));
    tokraft_admin_text_field('metrics_title', array('label' => '底部区主标题', 'description' => '建议说明清晰的询价或下单流程。'));
    echo '</div>';
    tokraft_admin_text_field('metrics_text', array('label' => '底部区说明', 'description' => '说明文件审核、最终报价或生产确认流程。', 'type' => 'textarea'));
    echo '<div class="tokraft-admin-metric-grid">';
    foreach (array('one' => '第一项', 'two' => '第二项', 'three' => '第三项') as $number => $label) {
        echo '<div class="tokraft-admin-metric-card"><h3>' . esc_html($label) . '</h3>';
        tokraft_admin_text_field('metric_' . $number . '_value', array('label' => '大字内容', 'description' => '例如：STL / 3MF / STEP。'));
        tokraft_admin_text_field('metric_' . $number . '_label', array('label' => '解释文字', 'description' => '例如：Accepted model formats。'));
        echo '</div>';
    }
    echo '</div>';
    tokraft_admin_section_close();

    echo '</form></div>';
}

function tokraft_material_image_spec_text() {
    return 'Recommended image: 1200 × 900 px (4:3 landscape), JPG or WebP, under 1.5 MB. The front page crops to a fixed 4:3 frame — avoid tall portrait photos. Same image is reused on the homepage material cards.';
}

function tokraft_material_add_fields() {
    echo '<div class="form-field"><label for="tokraft_material_color">Brand color</label><input type="text" id="tokraft_material_color" name="tokraft_material_color" value="#d9d9d9" class="regular-text"><p>Fallback color when no material image is uploaded.</p></div>';
    echo '<div class="form-field"><label for="tokraft_material_short_description">One-line summary</label><textarea id="tokraft_material_short_description" name="tokraft_material_short_description" rows="2"></textarea><p>Used on the homepage cards and quote dropdown. Keep it to one clear sentence.</p></div>';
    echo '<div class="form-field"><label for="tokraft_material_best_for">Best for</label><textarea id="tokraft_material_best_for" name="tokraft_material_best_for" rows="3"></textarea><p>Typical jobs this material is recommended for.</p></div>';
    echo '<div class="form-field"><label for="tokraft_material_avoid">Not ideal for</label><textarea id="tokraft_material_avoid" name="tokraft_material_avoid" rows="3"></textarea><p>Situations where another material is usually better.</p></div>';
    echo '<div class="form-field"><label for="tokraft_material_notes">What we confirm at quote</label><textarea id="tokraft_material_notes" name="tokraft_material_notes" rows="3"></textarea><p>Process or review notes the team should discuss with the customer.</p></div>';
    echo '<div class="form-field"><label for="tokraft_material_quote_rate">Quote starting estimate (CAD)</label><input type="number" min="1" step="0.01" id="tokraft_material_quote_rate" name="tokraft_material_quote_rate" value="24"><p>Used only for the quotation-page estimate. Final pricing still requires file review.</p></div>';
    echo '<div class="form-field"><label>Material image</label><input type="hidden" id="tokraft_material_image_id" name="tokraft_material_image_id" value=""><button type="button" class="button tokraft-media-select" data-target="tokraft_material_image_id">Select image</button> <button type="button" class="button tokraft-media-clear" data-target="tokraft_material_image_id">Clear</button>';
    echo '<p class="description"><strong>Image size:</strong> ' . esc_html(tokraft_material_image_spec_text()) . '</p>';
    echo '<p><img id="tokraft_material_image_id-preview" src="" alt="" style="display:none;max-width:260px;max-height:180px;object-fit:cover;"></p></div>';
    echo '<div class="form-field"><label><input type="checkbox" name="tokraft_material_featured" value="1" checked> Show on homepage</label></div>';
}
add_action('tokraft_material_add_form_fields', 'tokraft_material_add_fields');

function tokraft_material_edit_fields($term) {
    $color = get_term_meta($term->term_id, '_tokraft_material_color', true) ?: '#d9d9d9';
    $short_description = get_term_meta($term->term_id, '_tokraft_material_short_description', true);
    $best_for = get_term_meta($term->term_id, '_tokraft_material_best_for', true);
    $avoid = get_term_meta($term->term_id, '_tokraft_material_avoid', true);
    $notes = get_term_meta($term->term_id, '_tokraft_material_notes', true);
    $quote_rate = tokraft_material_quote_rate($term);
    $image_id = absint(get_term_meta($term->term_id, '_tokraft_material_image_id', true));
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
    $featured = get_term_meta($term->term_id, '_tokraft_material_featured', true);
    echo '<tr class="form-field"><th scope="row"><label for="tokraft_material_color">Brand color</label></th><td><input type="text" id="tokraft_material_color" name="tokraft_material_color" value="' . esc_attr($color) . '" class="regular-text"><p class="description">Fallback color when no image is uploaded.</p></td></tr>';
    echo '<tr class="form-field"><th scope="row"><label for="tokraft_material_short_description">One-line summary</label></th><td><textarea id="tokraft_material_short_description" name="tokraft_material_short_description" rows="2" class="large-text">' . esc_textarea($short_description) . '</textarea><p class="description">Homepage cards and quote dropdown. One clear sentence.</p></td></tr>';
    echo '<tr class="form-field"><th scope="row"><label for="tokraft_material_best_for">Best for</label></th><td><textarea id="tokraft_material_best_for" name="tokraft_material_best_for" rows="3" class="large-text">' . esc_textarea($best_for) . '</textarea></td></tr>';
    echo '<tr class="form-field"><th scope="row"><label for="tokraft_material_avoid">Not ideal for</label></th><td><textarea id="tokraft_material_avoid" name="tokraft_material_avoid" rows="3" class="large-text">' . esc_textarea($avoid) . '</textarea></td></tr>';
    echo '<tr class="form-field"><th scope="row"><label for="tokraft_material_notes">What we confirm at quote</label></th><td><textarea id="tokraft_material_notes" name="tokraft_material_notes" rows="3" class="large-text">' . esc_textarea($notes) . '</textarea></td></tr>';
    echo '<tr class="form-field"><th scope="row"><label for="tokraft_material_quote_rate">Quote starting estimate (CAD)</label></th><td><input type="number" min="1" step="0.01" id="tokraft_material_quote_rate" name="tokraft_material_quote_rate" value="' . esc_attr($quote_rate) . '"><p class="description">Used only for the quotation-page estimate. Final pricing still requires file review.</p></td></tr>';
    echo '<tr class="form-field"><th scope="row">Material image</th><td><input type="hidden" id="tokraft_material_image_id" name="tokraft_material_image_id" value="' . esc_attr($image_id) . '"><button type="button" class="button tokraft-media-select" data-target="tokraft_material_image_id">Select image</button> <button type="button" class="button tokraft-media-clear" data-target="tokraft_material_image_id">Clear</button>';
    echo '<p class="description"><strong>Image size:</strong> ' . esc_html(tokraft_material_image_spec_text()) . '</p>';
    echo '<p><img id="tokraft_material_image_id-preview" src="' . esc_url($image_url) . '" alt="" style="max-width:260px;max-height:180px;object-fit:cover;' . ($image_url ? '' : 'display:none;') . '"></p></td></tr>';
    echo '<tr class="form-field"><th scope="row">Homepage</th><td><label><input type="checkbox" name="tokraft_material_featured" value="1" ' . checked($featured, '1', false) . '> Show on homepage</label></td></tr>';
}
add_action('tokraft_material_edit_form_fields', 'tokraft_material_edit_fields');

function tokraft_save_material_fields($term_id) {
    if (isset($_POST['tokraft_material_color'])) {
        update_term_meta($term_id, '_tokraft_material_color', sanitize_hex_color(wp_unslash($_POST['tokraft_material_color'])) ?: '#d9d9d9');
    }
    if (isset($_POST['tokraft_material_short_description'])) {
        update_term_meta($term_id, '_tokraft_material_short_description', sanitize_textarea_field(wp_unslash($_POST['tokraft_material_short_description'])));
    }
    if (isset($_POST['tokraft_material_best_for'])) {
        update_term_meta($term_id, '_tokraft_material_best_for', sanitize_textarea_field(wp_unslash($_POST['tokraft_material_best_for'])));
    }
    if (isset($_POST['tokraft_material_avoid'])) {
        update_term_meta($term_id, '_tokraft_material_avoid', sanitize_textarea_field(wp_unslash($_POST['tokraft_material_avoid'])));
    }
    if (isset($_POST['tokraft_material_notes'])) {
        update_term_meta($term_id, '_tokraft_material_notes', sanitize_textarea_field(wp_unslash($_POST['tokraft_material_notes'])));
    }
    if (isset($_POST['tokraft_material_quote_rate'])) {
        update_term_meta($term_id, '_tokraft_material_quote_rate', max(1, (float) wp_unslash($_POST['tokraft_material_quote_rate'])));
    }
    if (isset($_POST['tokraft_material_image_id'])) {
        update_term_meta($term_id, '_tokraft_material_image_id', absint($_POST['tokraft_material_image_id']));
    }
    update_term_meta($term_id, '_tokraft_material_featured', isset($_POST['tokraft_material_featured']) ? '1' : '0');
}
add_action('created_tokraft_material', 'tokraft_save_material_fields');
add_action('edited_tokraft_material', 'tokraft_save_material_fields');

function tokraft_add_case_meta_box() {
    add_meta_box('tokraft_case_details', __('Case Study Details', 'tokraft'), 'tokraft_render_case_meta_box', 'tokraft_case_study', 'side');
}
add_action('add_meta_boxes', 'tokraft_add_case_meta_box');

function tokraft_render_case_meta_box($post) {
    wp_nonce_field('tokraft_case_details', 'tokraft_case_details_nonce');
    $industry = get_post_meta($post->ID, '_tokraft_case_industry', true);
    $featured = get_post_meta($post->ID, '_tokraft_case_featured', true);
    echo '<p><label for="tokraft_case_industry">Industry or use</label><input class="widefat" id="tokraft_case_industry" name="tokraft_case_industry" value="' . esc_attr($industry) . '" placeholder="Industrial Equipment"></p>';
    echo '<p><label><input type="checkbox" name="tokraft_case_featured" value="1" ' . checked($featured, '1', false) . '> Show on homepage</label></p>';
    echo '<p class="description">Use the Material box and Featured Image to complete this card.</p>';
}

function tokraft_save_case_meta_box($post_id) {
    if (!isset($_POST['tokraft_case_details_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokraft_case_details_nonce'])), 'tokraft_case_details') || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || !current_user_can('edit_post', $post_id)) {
        return;
    }
    update_post_meta($post_id, '_tokraft_case_industry', sanitize_text_field(wp_unslash($_POST['tokraft_case_industry'] ?? '')));
    update_post_meta($post_id, '_tokraft_case_featured', isset($_POST['tokraft_case_featured']) ? '1' : '0');
}
add_action('save_post_tokraft_case_study', 'tokraft_save_case_meta_box');

function tokraft_add_product_details_meta_box() {
    add_meta_box('tokraft_product_details', __('toKraft Product Setup', 'tokraft'), 'tokraft_render_product_details_meta_box', 'product', 'side', 'high');
}
add_action('add_meta_boxes_product', 'tokraft_add_product_details_meta_box');

function tokraft_render_product_details_meta_box($post) {
    wp_nonce_field('tokraft_product_details', 'tokraft_product_details_nonce');
    $dimensions_note = get_post_meta($post->ID, '_tokraft_display_dimensions', true);
    echo '<p><label for="tokraft_display_dimensions"><strong>Front-end size description</strong></label><textarea class="widefat" rows="4" id="tokraft_display_dimensions" name="tokraft_display_dimensions" placeholder="For example: Single: 58 x 34 x 22 mm&#10;Double: confirm dimensions before ordering.">' . esc_textarea($dimensions_note) . '</textarea></p>';
    echo '<p class="description">This is shown next to the purchase options. For shipping calculations, also enter the actual length, width and height in <strong>Product data &rarr; Shipping</strong>.</p>';
    echo '<hr><p><strong>Product image specification</strong></p><ul class="ul-disc" style="margin-left:18px"><li>Main image: 1600 x 1600 px, square, JPG or WebP, under 2 MB.</li><li>Gallery: 1600 x 1200 px minimum, consistent lighting and background.</li><li>Keep the product within 70-85% of the frame; use sharp, well-lit images.</li><li>Add the main image in the Product image panel and extra views in Product gallery.</li></ul>';
}

function tokraft_save_product_details_meta_box($post_id) {
    if (!isset($_POST['tokraft_product_details_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokraft_product_details_nonce'])), 'tokraft_product_details') || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || !current_user_can('edit_post', $post_id)) {
        return;
    }
    update_post_meta($post_id, '_tokraft_display_dimensions', sanitize_textarea_field(wp_unslash($_POST['tokraft_display_dimensions'] ?? '')));
}
add_action('save_post_product', 'tokraft_save_product_details_meta_box');

function tokraft_render_product_dimensions_summary() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $dimensions_note = trim((string) get_post_meta($product->get_id(), '_tokraft_display_dimensions', true));
    if (!$dimensions_note && $product->has_dimensions()) {
        $dimensions_note = wc_format_dimensions($product->get_dimensions(false));
    }
    if ($dimensions_note) {
        echo '<p class="tokraft-product-dimensions" data-default-dimensions="' . esc_attr($dimensions_note) . '"><span>Size</span><strong>' . esc_html($dimensions_note) . '</strong></p>';
    }
}
add_action('woocommerce_single_product_summary', 'tokraft_render_product_dimensions_summary', 25);

function tokraft_admin_ui_strings() {
    $locale = strtolower(function_exists('determine_locale') ? determine_locale() : get_locale());
    $is_chinese = 0 === strpos($locale, 'zh_') || 0 === strpos($locale, 'zh-') || 'zh' === $locale;

    $english = array(
        'select_image' => 'Select image',
        'use_this_image' => 'Use this image',
        'image_number' => 'Image %s',
        'replace_carousel_image' => 'Replace carousel image',
        'select_carousel_images' => 'Select carousel images',
        'replace_image' => 'Replace image',
        'use_these_images' => 'Use these images',
        'carousel_image_limit' => 'Keep the first %s images for this carousel.',
        'change' => 'Change',
        'carousel_slides' => 'CAROUSEL SLIDES',
        'add_slide' => 'Add slide',
        'carousel_mode' => 'Carousel mode',
        'single_image_mode' => 'Single image mode',
        'carousel_no_slides' => 'No slides will rotate on the home page',
        'carousel_one_slide' => '1 slide will rotate on the home page',
        'carousel_many_slides' => '%s slides will rotate on the home page',
        'single_image_homepage' => 'One image is shown on the home page',
        'slide_number' => 'SLIDE %s',
        'homepage_slide' => 'Homepage slide',
        'active_first_slide' => 'Active / first slide',
        'slide_short_number' => 'Slide %s',
        'edit' => 'Edit',
        'no_carousel_images' => 'No carousel images yet. Add up to five homepage slides.',
        'selected_slide' => 'SLIDE %s%s',
        'active_suffix' => ' / ACTIVE',
        'replace' => 'Replace',
        'image_description' => 'IMAGE DESCRIPTION',
        'image_alt_text_help' => 'Update the image alt text in the WordPress Media Library.',
        'visible_on_homepage' => 'Visible on homepage',
        'saved_carousel_visibility' => 'This selected image appears when carousel mode is saved.',
        'single_image_selected' => 'Single image selected',
        'single_image_help' => 'Use Change to switch to a carousel, or select the homepage image here.',
        'choose_image' => 'Choose image',
        'slide_count' => '%s slides',
        'hero_title' => 'Hero & carousel',
        'hero_description' => 'Message, CTAs and image slides',
        'one_image' => '1 image',
        'business_routes_title' => 'Business routes',
        'business_routes_description' => 'Print Service and Shop entry points',
        'two_routes' => '2 routes',
        'equipment_title' => 'Equipment',
        'equipment_description' => 'Printing capability and machines',
        'card_count' => '%s cards',
        'materials_cases_title' => 'Materials & cases',
        'materials_cases_description' => 'Materials, case studies and proof',
        'item_count' => '%s items',
        'final_cta_title' => 'Final CTA',
        'final_cta_description' => 'Closing conversion message',
        'one_block' => '1 block',
        'content_website' => 'CONTENT / WEBSITE',
        'home_page_blocks' => 'Home page blocks',
        'home_page_blocks_help' => 'Manage the order and visibility of your homepage. Open a block only when you need to change its content.',
        'published_version' => 'PUBLISHED VERSION',
        'live_homepage_content' => 'Live homepage content',
        'view_homepage' => 'View homepage',
        'homepage_url' => home_url('/'),
        'page_composition' => 'PAGE COMPOSITION',
        'page_composition_help' => 'Drag a row to change its page position. Changes remain a draft until published.',
        'all_blocks' => 'All blocks · 5',
        'order' => 'ORDER',
        'block' => 'BLOCK',
        'visibility' => 'VISIBILITY',
        'status' => 'STATUS',
        'edit_column' => 'EDIT',
        'blocks_footer' => '5 blocks · 4 visible · Homepage content is saved from the editor',
        'hidden_on_homepage' => 'Hidden on homepage',
        'published' => 'PUBLISHED',
        'draft' => 'DRAFT',
        'edit_block' => 'EDIT BLOCK',
        'close_editor' => 'Close editor',
        'hero_editor_tabs' => 'Hero editor tabs',
        'content' => 'Content',
        'actions' => 'Actions',
        'slides_label' => 'Slides',
        'cancel' => 'Cancel',
        'save_homepage_changes' => 'Save homepage changes',
        'slides_with_count' => 'Slides · %s',
    );

    if (!$is_chinese) {
        return $english;
    }

    return array(
        'select_image' => '选择图片',
        'use_this_image' => '使用此图片',
        'image_number' => '图片 %s',
        'replace_carousel_image' => '替换轮播图片',
        'select_carousel_images' => '选择轮播图片',
        'replace_image' => '替换图片',
        'use_these_images' => '使用这些图片',
        'carousel_image_limit' => '轮播图最多保留前 %s 张图片。',
        'change' => '切换',
        'carousel_slides' => '轮播图片',
        'add_slide' => '添加图片',
        'carousel_mode' => '轮播模式',
        'single_image_mode' => '单图模式',
        'carousel_no_slides' => '当前没有轮播图片',
        'carousel_one_slide' => '首页将轮播 1 张图片',
        'carousel_many_slides' => '首页将轮播 %s 张图片',
        'single_image_homepage' => '首页将显示一张图片',
        'slide_number' => '轮播图 %s',
        'homepage_slide' => '首页轮播图',
        'active_first_slide' => '当前启用 / 首张图片',
        'slide_short_number' => '图片 %s',
        'edit' => '编辑',
        'no_carousel_images' => '暂未添加轮播图片，最多可添加 5 张首页图片。',
        'selected_slide' => '轮播图 %s%s',
        'active_suffix' => ' / 当前启用',
        'replace' => '替换',
        'image_description' => '图片说明',
        'image_alt_text_help' => '请在 WordPress 媒体库中修改图片的替代文本。',
        'visible_on_homepage' => '在首页显示',
        'saved_carousel_visibility' => '保存为轮播模式后，这张图片会在首页显示。',
        'single_image_selected' => '已选择单张首页图片',
        'single_image_help' => '点击“切换”可改为轮播模式，也可以在这里选择首页图片。',
        'choose_image' => '选择图片',
        'slide_count' => '%s 张图片',
        'hero_title' => '首屏与轮播图',
        'hero_description' => '首屏文案、行动按钮和图片轮播',
        'one_image' => '1 张图片',
        'business_routes_title' => '业务入口',
        'business_routes_description' => '打印服务与产品商店的入口',
        'two_routes' => '2 个入口',
        'equipment_title' => '设备能力',
        'equipment_description' => '打印能力与设备信息',
        'card_count' => '%s 张卡片',
        'materials_cases_title' => '材料与应用案例',
        'materials_cases_description' => '材料、应用案例与能力证明',
        'item_count' => '%s 项内容',
        'final_cta_title' => '底部行动区',
        'final_cta_description' => '首页底部的转化提示',
        'one_block' => '1 个区块',
        'content_website' => '内容 / 网站',
        'home_page_blocks' => '首页内容区块',
        'home_page_blocks_help' => '管理首页区块的顺序和可见性；需要修改内容时，打开对应区块即可。',
        'published_version' => '已发布版本',
        'live_homepage_content' => '线上首页内容',
        'view_homepage' => '查看首页',
        'homepage_url' => home_url('/'),
        'page_composition' => '页面组成',
        'page_composition_help' => '拖动一行可调整其在首页的位置，修改后请在编辑器中保存。',
        'all_blocks' => '全部区块 · 5',
        'order' => '顺序',
        'block' => '区块',
        'visibility' => '可见性',
        'status' => '状态',
        'edit_column' => '编辑',
        'blocks_footer' => '5 个区块 · 4 个可见 · 首页内容请在编辑器中保存',
        'hidden_on_homepage' => '不在首页显示',
        'published' => '已发布',
        'draft' => '草稿',
        'edit_block' => '编辑区块',
        'close_editor' => '关闭编辑器',
        'hero_editor_tabs' => '首屏编辑器标签',
        'content' => '内容',
        'actions' => '行动按钮',
        'slides_label' => '轮播图',
        'cancel' => '取消',
        'save_homepage_changes' => '保存首页修改',
        'slides_with_count' => '轮播图 · %s',
    );
}

function tokraft_admin_assets($hook) {
    $screen = get_current_screen();
    if ('toplevel_page_tokraft-home' !== $hook && (!$screen || 'tokraft_material' !== $screen->taxonomy)) {
        return;
    }
    wp_enqueue_style(
        'tokraft-admin',
        get_template_directory_uri() . '/assets/admin.css',
        array(),
        '2.5.5'
    );
    wp_enqueue_media();
    wp_enqueue_script('tokraft-admin', get_template_directory_uri() . '/assets/admin.js', array('jquery'), '2.5.5', true);
    wp_localize_script('tokraft-admin', 'tokraftAdminI18n', tokraft_admin_ui_strings());
}
add_action('admin_enqueue_scripts', 'tokraft_admin_assets');

function tokraft_seed_content() {
    if (get_option('tokraft_content_seeded')) {
        return;
    }
    $materials = array(
        'PLA' => array('Easy to print, great for models', '#e5e4df', 24),
        'PETG' => array('Strong, durable, chemical resistant', '#2358a5', 29),
        'TPU' => array('Flexible, shock absorbent', '#1f2831', 37),
        'ASA' => array('UV and weather resistant', '#efa917', 32),
    );
    foreach ($materials as $name => $details) {
        $term = term_exists($name, 'tokraft_material');
        if (!$term) {
            $term = wp_insert_term($name, 'tokraft_material');
        }
        if (!is_wp_error($term)) {
            $term_id = is_array($term) ? $term['term_id'] : $term;
            update_term_meta($term_id, '_tokraft_material_short_description', $details[0]);
            update_term_meta($term_id, '_tokraft_material_color', $details[1]);
            update_term_meta($term_id, '_tokraft_material_quote_rate', $details[2]);
            update_term_meta($term_id, '_tokraft_material_featured', '1');
        }
    }
    update_option('tokraft_content_seeded', '1');
}
add_action('init', 'tokraft_seed_content', 30);

function tokraft_refresh_content_rewrites() {
    if ('1.2.0' === get_option('tokraft_content_rewrite_version')) {
        return;
    }
    tokraft_register_content_types();
    tokraft_theme_routes();
    flush_rewrite_rules();
    update_option('tokraft_content_rewrite_version', '1.2.0');
}
add_action('init', 'tokraft_refresh_content_rewrites', 99);
