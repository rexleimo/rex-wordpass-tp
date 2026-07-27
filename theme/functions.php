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
    // Product and case-study bodies are authored with blocks, so wide/full
    // alignments and responsive embeds have to be declared or they overflow.
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    register_nav_menus(array('primary' => __('Primary navigation', 'tokraft')));
}
add_action('after_setup_theme', 'tokraft_setup');

/**
 * WooCommerce force-disables the block editor for products via
 * WC_Post_Types::gutenberg_can_edit_post_type() at priority 10. Nothing else
 * competes for these filters, so overriding at a later priority gives products
 * the same editor posts and case studies already use.
 */
function tokraft_enable_product_block_editor($use_block_editor, $post_type) {
    return 'product' === $post_type ? true : $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'tokraft_enable_product_block_editor', 20, 2);
add_filter('gutenberg_can_edit_post_type', 'tokraft_enable_product_block_editor', 20, 2);

/**
 * strtoupper() works a byte at a time, so it shreds the UTF-8 sequences in
 * editor-supplied labels (industry names, attribute values) and the page shows
 * mojibake. Use the multibyte version whenever the ext is available.
 */
function tokraft_uppercase($text) {
    $text = (string) $text;
    return function_exists('mb_strtoupper') ? mb_strtoupper($text, 'UTF-8') : strtoupper($text);
}

function tokraft_assets() {
    $theme_script_dependencies = array('jquery');
    $is_material_library = (bool) get_query_var('tokraft_material_library');
    $uses_swiper = is_front_page() || $is_material_library;
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
    if (function_exists('is_shop') && is_shop() && 'showcase' === tokraft_shop_layout()) {
        wp_enqueue_style(
            'tokraft-shop-showcase',
            get_template_directory_uri() . '/assets/shop-showcase.css',
            array('tokraft-shop-product'),
            (string) filemtime(get_template_directory() . '/assets/shop-showcase.css')
        );
    }
    if (is_singular('tokraft_case_study')) {
        wp_enqueue_style(
            'tokraft-case-study',
            get_template_directory_uri() . '/assets/case-study.css',
            array('tokraft-style'),
            (string) filemtime(get_template_directory() . '/assets/case-study.css')
        );
    }
    // Both bodies render block editor output, so they share one stylesheet.
    if ((function_exists('is_product') && is_product()) || is_singular('tokraft_case_study')) {
        wp_enqueue_style(
            'tokraft-block-content',
            get_template_directory_uri() . '/assets/block-content.css',
            array('tokraft-style'),
            (string) filemtime(get_template_directory() . '/assets/block-content.css')
        );
    }
    if ($is_material_library) {
        wp_enqueue_style(
            'tokraft-material-library',
            get_template_directory_uri() . '/assets/material-library.css',
            array('tokraft-style'),
            (string) filemtime(get_template_directory() . '/assets/material-library.css')
        );
    }
    if ($uses_swiper) {
        wp_enqueue_style(
            'tokraft-swiper',
            get_template_directory_uri() . '/assets/vendor/swiper/swiper-bundle.min.css',
            array('tokraft-style'),
            (string) filemtime(get_template_directory() . '/assets/vendor/swiper/swiper-bundle.min.css')
        );
        wp_enqueue_script(
            'tokraft-swiper',
            get_template_directory_uri() . '/assets/vendor/swiper/swiper-bundle.min.js',
            array(),
            (string) filemtime(get_template_directory() . '/assets/vendor/swiper/swiper-bundle.min.js'),
            true
        );
        $theme_script_dependencies[] = 'tokraft-swiper';
    }
    wp_enqueue_script(
        'tokraft-theme',
        get_template_directory_uri() . '/assets/theme.js',
        $theme_script_dependencies,
        (string) filemtime(get_template_directory() . '/assets/theme.js'),
        true
    );
    if (is_page_template('page-quote.php')) {
        wp_localize_script('tokraft-theme', 'tokraftQuoteConfig', tokraft_quote_js_config());
    }
}
add_action('wp_enqueue_scripts', 'tokraft_assets');

function tokraft_theme_routes() {
    add_rewrite_rule('^quote/?$', 'index.php?tokraft_quote=1', 'top');
    add_rewrite_rule('^materials/?$', 'index.php?tokraft_material_library=1', 'top');
    add_rewrite_rule('^studio/?$', 'index.php?tokraft_studio=1', 'top');
    add_rewrite_rule('^contact/?$', 'index.php?tokraft_contact=1', 'top');
}
add_action('init', 'tokraft_theme_routes');

function tokraft_quote_query_var($vars) {
    $vars[] = 'tokraft_quote';
    $vars[] = 'tokraft_material_library';
    $vars[] = 'tokraft_studio';
    $vars[] = 'tokraft_contact';
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
    if (get_query_var('tokraft_studio')) {
        return get_template_directory() . '/page-studio.php';
    }
    if (get_query_var('tokraft_contact')) {
        return get_template_directory() . '/page-contact.php';
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
    echo '<a href="' . esc_url(home_url('/studio/')) . '">Studio</a>';
    echo '<a href="' . esc_url(home_url('/contact/')) . '">Contact</a>';
}

function tokraft_contact_submission() {
    if (!isset($_POST['tokraft_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokraft_contact_nonce'])), 'tokraft_contact')) {
        wp_die(__('Unable to verify this contact request. Please try again.', 'tokraft'));
    }

    if (!empty($_POST['air_confirm'])) {
        wp_safe_redirect(add_query_arg('air_error', '1', wp_get_referer() ?: home_url('/contact/')));
        exit;
    }

    $errors = array();
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    if ('' === $name) {
        $errors[] = 'name';
    }
    if ('' === $email || !is_email($email)) {
        $errors[] = 'email';
    }
    if ('' === $message) {
        $errors[] = 'message';
    }

    if ($errors) {
        wp_safe_redirect(add_query_arg('contact_error', implode(',', $errors), wp_get_referer() ?: home_url('/contact/')));
        exit;
    }

    $body = "New toKraft contact message\n\n";
    $body .= 'Name: ' . $name . "\n";
    $body .= 'Email: ' . $email . "\n";
    $body .= 'Subject: ' . ($subject ?: 'General inquiry') . "\n";
    $body .= "Message:\n" . $message . "\n";
    $body .= 'From page: ' . esc_url_raw(wp_get_referer() ?: home_url('/contact/')) . "\n";

    $to = get_option('admin_email');
    wp_mail($to, 'New toKraft contact message: ' . ($subject ?: 'General inquiry'), $body, array('Reply-To: ' . $email));

    wp_safe_redirect(add_query_arg('contact_sent', '1', home_url('/contact/')));
    exit;
}
add_action('admin_post_tokraft_contact', 'tokraft_contact_submission');
add_action('admin_post_nopriv_tokraft_contact', 'tokraft_contact_submission');

function tokraft_quote_submission() {
    if (!isset($_POST['tokraft_quote_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokraft_quote_nonce'])), 'tokraft_quote')) {
        wp_die(__('Unable to verify this quote request. Please try again.', 'tokraft'));
    }

    $required = array('contact_first_name', 'contact_last_name', 'contact_email', 'material', 'quantity');
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            wp_safe_redirect(add_query_arg('quote_error', 'required', wp_get_referer() ?: home_url('/quote/')));
            exit;
        }
    }

    // Colours are a multi-select tied to the chosen material; at least one is required.
    $colors = array();
    foreach ((array) wp_unslash($_POST['color'] ?? array()) as $color) {
        $color = sanitize_text_field((string) $color);
        if ('' !== $color && !in_array($color, $colors, true)) {
            $colors[] = $color;
        }
    }
    if (!$colors) {
        wp_safe_redirect(add_query_arg('quote_error', 'required', wp_get_referer() ?: home_url('/quote/')));
        exit;
    }

    $first_name = sanitize_text_field(wp_unslash($_POST['contact_first_name']));
    $last_name = sanitize_text_field(wp_unslash($_POST['contact_last_name']));
    $layer_height = tokraft_quote_clamp('layer', $_POST['layer_height'] ?? null);

    $quote = array(
        // Keep the joined name so quote titles, list columns and emails need no legacy fallback.
        'name' => trim($first_name . ' ' . $last_name),
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => sanitize_email(wp_unslash($_POST['contact_email'])),
        'company' => sanitize_text_field(wp_unslash($_POST['company'] ?? '')),
        'material' => sanitize_text_field(wp_unslash($_POST['material'])),
        'color' => implode(', ', $colors),
        'quantity' => absint($_POST['quantity']),
        'infill' => tokraft_quote_clamp('infill', $_POST['infill'] ?? null),
        'walls' => tokraft_quote_clamp('walls', $_POST['walls'] ?? null),
        'layer_height' => number_format($layer_height, 2) . ' mm',
        'support' => tokraft_quote_choice_value('support', $_POST['support'] ?? null),
        'adhesion' => tokraft_quote_choice_value('adhesion', $_POST['adhesion'] ?? null),
        'notes' => sanitize_textarea_field(wp_unslash($_POST['notes'] ?? '')),
    );

    $uploaded_file = '';
    $uploaded_file_path = '';
    $uploaded_file_type = '';
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
        $uploaded_file_path = $upload['file'];
        $uploaded_file_type = $upload['type'];
    }

    // Store every enquiry as an admin-managed record as well as notifying by email.
    $quote_id = wp_insert_post(array(
        'post_type' => 'tokraft_quote',
        'post_status' => 'publish',
        'post_title' => sprintf(__('New quote - %s', 'tokraft'), $quote['name']),
    ), true);
    if (is_wp_error($quote_id)) {
        wp_safe_redirect(add_query_arg('quote_error', 'storage', wp_get_referer() ?: home_url('/quote/')));
        exit;
    }

    $quote_number = 'TKQ-' . str_pad((string) $quote_id, 6, '0', STR_PAD_LEFT);
    wp_update_post(array(
        'ID' => $quote_id,
        'post_title' => $quote_number . ' - ' . $quote['name'],
    ));
    foreach ($quote as $key => $value) {
        update_post_meta($quote_id, '_tokraft_quote_' . $key, $value);
    }
    update_post_meta($quote_id, '_tokraft_quote_colors', $colors);
    update_post_meta($quote_id, '_tokraft_quote_number', $quote_number);
    update_post_meta($quote_id, '_tokraft_quote_status', 'new');

    if ($uploaded_file) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => $uploaded_file_type,
            'post_title' => sanitize_file_name(basename($uploaded_file_path)),
            'post_status' => 'inherit',
            'post_parent' => $quote_id,
        ), $uploaded_file_path, $quote_id);
        if (!is_wp_error($attachment_id)) {
            $metadata = wp_generate_attachment_metadata($attachment_id, $uploaded_file_path);
            if (!empty($metadata)) {
                wp_update_attachment_metadata($attachment_id, $metadata);
            }
            update_post_meta($quote_id, '_tokraft_quote_file_attachment', $attachment_id);
        }
        update_post_meta($quote_id, '_tokraft_quote_file_url', esc_url_raw($uploaded_file));
    }

    $message = "New toKraft print quote\n\n";
    $message .= 'Quote reference: ' . $quote_number . "\n";
    foreach ($quote as $key => $value) {
        $message .= ucwords(str_replace('_', ' ', $key)) . ': ' . $value . "\n";
    }
    if ($uploaded_file) {
        $message .= 'File: ' . esc_url_raw($uploaded_file) . "\n";
    }
    $message .= 'Admin record: ' . admin_url('post.php?post=' . $quote_id . '&action=edit') . "\n";
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

    register_post_type('tokraft_quote', array(
        'labels' => array(
            'name' => __('报价记录', 'tokraft'),
            'singular_name' => __('报价记录', 'tokraft'),
            'menu_name' => __('报价记录', 'tokraft'),
            'edit_item' => __('查看报价记录', 'tokraft'),
            'not_found' => __('暂无报价记录', 'tokraft'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'tokraft-home',
        'show_in_admin_bar' => false,
        'exclude_from_search' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'supports' => array('title'),
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

function tokraft_quote_status_labels() {
    return array(
        'new' => __('待处理', 'tokraft'),
        'quoted' => __('已报价', 'tokraft'),
        'won' => __('已成交', 'tokraft'),
        'lost' => __('未成交', 'tokraft'),
    );
}

function tokraft_quote_columns($columns) {
    return array(
        'cb' => $columns['cb'],
        'title' => __('报价编号', 'tokraft'),
        'tokraft_quote_contact' => __('客户', 'tokraft'),
        'tokraft_quote_material' => __('材料与数量', 'tokraft'),
        'tokraft_quote_status' => __('状态', 'tokraft'),
        'tokraft_quote_owner' => __('负责人', 'tokraft'),
        'tokraft_quote_file' => __('模型文件', 'tokraft'),
        'date' => $columns['date'],
    );
}
add_filter('manage_tokraft_quote_posts_columns', 'tokraft_quote_columns');

function tokraft_quote_column_content($column, $post_id) {
    if ('tokraft_quote_contact' === $column) {
        $name = get_post_meta($post_id, '_tokraft_quote_name', true);
        $email = get_post_meta($post_id, '_tokraft_quote_email', true);
        echo esc_html($name ?: '—');
        if ($email) {
            echo '<br><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        return;
    }
    if ('tokraft_quote_material' === $column) {
        $material = get_post_meta($post_id, '_tokraft_quote_material', true);
        $quantity = get_post_meta($post_id, '_tokraft_quote_quantity', true);
        echo esc_html($material ?: '—');
        if ($quantity) {
            echo '<br>' . esc_html(sprintf(__('数量：%s', 'tokraft'), $quantity));
        }
        return;
    }
    if ('tokraft_quote_status' === $column) {
        $statuses = tokraft_quote_status_labels();
        $status = get_post_meta($post_id, '_tokraft_quote_status', true) ?: 'new';
        echo esc_html($statuses[$status] ?? $status);
        return;
    }
    if ('tokraft_quote_owner' === $column) {
        $owner_id = absint(get_post_meta($post_id, '_tokraft_quote_owner', true));
        $owner = $owner_id ? get_userdata($owner_id) : false;
        echo esc_html($owner ? $owner->display_name : '—');
        return;
    }
    if ('tokraft_quote_file' === $column) {
        $attachment_id = absint(get_post_meta($post_id, '_tokraft_quote_file_attachment', true));
        $file_url = get_post_meta($post_id, '_tokraft_quote_file_url', true);
        if ($attachment_id) {
            echo '<a href="' . esc_url(get_edit_post_link($attachment_id)) . '">' . esc_html__('查看附件', 'tokraft') . '</a>';
        } elseif ($file_url) {
            echo '<a href="' . esc_url($file_url) . '" target="_blank" rel="noopener">' . esc_html__('查看文件', 'tokraft') . '</a>';
        } else {
            echo '—';
        }
    }
}
add_action('manage_tokraft_quote_posts_custom_column', 'tokraft_quote_column_content', 10, 2);

function tokraft_add_quote_meta_box() {
    add_meta_box('tokraft_quote_details', __('报价详情与跟进', 'tokraft'), 'tokraft_render_quote_meta_box', 'tokraft_quote', 'normal', 'high');
}
add_action('add_meta_boxes_tokraft_quote', 'tokraft_add_quote_meta_box');

function tokraft_render_quote_meta_box($post) {
    $get = static function ($key) use ($post) {
        return get_post_meta($post->ID, '_tokraft_quote_' . $key, true);
    };
    $statuses = tokraft_quote_status_labels();
    $status = $get('status') ?: 'new';
    $owner_id = absint($get('owner'));
    $file_url = $get('file_url');
    $attachment_id = absint($get('file_attachment'));
    $fields = array(
        __('联系人', 'tokraft') => $get('name'),
        // Split names only exist on quotes submitted after the form split the field.
        __('名 (First name)', 'tokraft') => $get('first_name'),
        __('姓 (Last name)', 'tokraft') => $get('last_name'),
        __('邮箱', 'tokraft') => $get('email'),
        __('公司', 'tokraft') => $get('company'),
        __('材料 / 颜色', 'tokraft') => trim($get('material') . ' / ' . $get('color'), ' / '),
        __('数量', 'tokraft') => $get('quantity'),
        __('工艺参数', 'tokraft') => sprintf(__('填充 %1$s%%；壁厚 %2$s；层高 %3$s；支撑 %4$s；附着 %5$s', 'tokraft'), $get('infill'), $get('walls'), $get('layer_height'), $get('support'), $get('adhesion')),
        __('客户备注', 'tokraft') => $get('notes'),
    );
    wp_nonce_field('tokraft_quote_follow_up', 'tokraft_quote_follow_up_nonce');
    echo '<table class="form-table" role="presentation">';
    foreach ($fields as $label => $value) {
        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>' . ($value ? nl2br(esc_html($value)) : '—') . '</td></tr>';
    }
    echo '<tr><th scope="row">' . esc_html__('模型文件', 'tokraft') . '</th><td>';
    if ($attachment_id) {
        echo '<a href="' . esc_url(get_edit_post_link($attachment_id)) . '">' . esc_html__('在媒体库查看附件', 'tokraft') . '</a>';
    } elseif ($file_url) {
        echo '<a href="' . esc_url($file_url) . '" target="_blank" rel="noopener">' . esc_html__('打开上传文件', 'tokraft') . '</a>';
    } else {
        echo '—';
    }
    echo '</td></tr>';
    echo '<tr><th scope="row"><label for="tokraft_quote_status">' . esc_html__('跟进状态', 'tokraft') . '</label></th><td><select id="tokraft_quote_status" name="tokraft_quote_status">';
    foreach ($statuses as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($status, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th scope="row"><label for="tokraft_quote_owner">' . esc_html__('负责人', 'tokraft') . '</label></th><td><select id="tokraft_quote_owner" name="tokraft_quote_owner"><option value="0">' . esc_html__('未分配', 'tokraft') . '</option>';
    foreach (get_users(array('fields' => array('ID', 'display_name'), 'orderby' => 'display_name')) as $user) {
        echo '<option value="' . esc_attr($user->ID) . '" ' . selected($owner_id, $user->ID, false) . '>' . esc_html($user->display_name) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th scope="row"><label for="tokraft_quote_price">' . esc_html__('正式报价（CAD）', 'tokraft') . '</label></th><td><input class="regular-text" type="number" min="0" step="0.01" id="tokraft_quote_price" name="tokraft_quote_price" value="' . esc_attr($get('price')) . '"></td></tr>';
    echo '<tr><th scope="row"><label for="tokraft_quote_follow_up_note">' . esc_html__('内部跟进备注', 'tokraft') . '</label></th><td><textarea class="large-text" rows="5" id="tokraft_quote_follow_up_note" name="tokraft_quote_follow_up_note">' . esc_textarea($get('follow_up_note')) . '</textarea></td></tr>';
    echo '</table>';
}

function tokraft_save_quote_meta_box($post_id) {
    if (!isset($_POST['tokraft_quote_follow_up_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokraft_quote_follow_up_nonce'])), 'tokraft_quote_follow_up') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $statuses = tokraft_quote_status_labels();
    $status = sanitize_key(wp_unslash($_POST['tokraft_quote_status'] ?? 'new'));
    update_post_meta($post_id, '_tokraft_quote_status', isset($statuses[$status]) ? $status : 'new');
    update_post_meta($post_id, '_tokraft_quote_owner', absint($_POST['tokraft_quote_owner'] ?? 0));
    $price = wp_unslash($_POST['tokraft_quote_price'] ?? '');
    $price = function_exists('wc_format_decimal') ? wc_format_decimal($price, 2) : (string) max(0, (float) $price);
    update_post_meta($post_id, '_tokraft_quote_price', $price);
    update_post_meta($post_id, '_tokraft_quote_follow_up_note', sanitize_textarea_field(wp_unslash($_POST['tokraft_quote_follow_up_note'] ?? '')));
}
add_action('save_post_tokraft_quote', 'tokraft_save_quote_meta_box');

function tokraft_home_settings_defaults() {
    return array(
        'hero_eyebrow' => 'PRECISION PARTS. LOCAL SERVICE.',
        'hero_title' => 'High-precision 3D printing made in',
        'hero_accent' => 'Alberta, Canada.',
        'hero_description' => "Functional prototypes, replacement parts, and end-use components.\nMulti-material. High accuracy. Fast turnaround.",
        'hero_quote_label' => 'Get a Print Quote',
        'hero_quote_url' => '/materials/',
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

        // Shop presentation. The catalogue template stays in the theme for when
        // the range grows; showcase is the two-printer cover-led layout.
        'shop_layout' => 'showcase',
        'shop_showcase_eyebrow' => 'THE MACHINES',
        'shop_showcase_title' => 'Two printers. Everything we make.',
        'shop_showcase_text' => 'We run a deliberately short line-up so every machine is one we know inside out — installed, calibrated and supported from Alberta.',
        'shop_showcase_button_label' => 'Request a custom print',
        'shop_showcase_button_url' => '/quote/',
        'discover_enabled' => '0',
    );
}

function tokraft_home_settings() {
    return wp_parse_args((array) get_option('tokraft_home_settings', array()), tokraft_home_settings_defaults());
}

function tokraft_home_blocks_config() {
    // Keep the public page in the same sequence as the Block Manager.
    return array(
        'order' => array('hero', 'equipment', 'routes', 'materials', 'showcase', 'trust'),
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

function tokraft_home_enabled($key) {
    return '1' === (string) tokraft_home_value($key);
}

/**
 * Which shop archive template to use. 'showcase' is the cover-led two-product
 * layout; 'catalog' is the original filtered grid in woocommerce.php.
 */
function tokraft_shop_layout() {
    $layout = (string) tokraft_home_value('shop_layout');
    return 'catalog' === $layout ? 'catalog' : 'showcase';
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

function tokraft_material_type_choices() {
    return array(
        'general-purpose' => __('General purpose', 'tokraft'),
        'engineering' => __('Engineering', 'tokraft'),
        'flexible' => __('Flexible', 'tokraft'),
        'outdoor' => __('Outdoor', 'tokraft'),
    );
}

function tokraft_material_type($term) {
    $term_id = is_object($term) ? $term->term_id : absint($term);
    $types = tokraft_material_type_choices();
    $type = sanitize_key(get_term_meta($term_id, '_tokraft_material_type', true));
    if (isset($types[$type])) {
        return $type;
    }

    // Keep seeded materials filterable until an editor explicitly selects a type.
    $name = is_object($term) ? strtoupper($term->name) : '';
    $fallbacks = array(
        'ABS' => 'engineering',
        'ASA' => 'outdoor',
        'NYLON (PA)' => 'engineering',
        'TPU' => 'flexible',
    );
    return $fallbacks[$name] ?? 'general-purpose';
}

/**
 * Fallback palette used until an editor configures colours on the material term.
 */
function tokraft_material_default_colors() {
    return array(
        array('label' => __('Natural', 'tokraft'), 'hex' => '#e8e7df'),
        array('label' => __('Black', 'tokraft'), 'hex' => '#18202b'),
        array('label' => __('White', 'tokraft'), 'hex' => '#ffffff'),
        array('label' => __('Blue', 'tokraft'), 'hex' => '#1a5796'),
    );
}

/**
 * Printable colours configured for a material term. Falls back to the default
 * palette so material cards and the quote form are never empty.
 */
function tokraft_material_colors($term) {
    $term_id = is_object($term) ? $term->term_id : absint($term);
    $stored = get_term_meta($term_id, '_tokraft_material_colors', true);
    $colors = array();
    if (is_array($stored)) {
        foreach ($stored as $color) {
            $label = isset($color['label']) ? trim((string) $color['label']) : '';
            $hex = isset($color['hex']) ? sanitize_hex_color((string) $color['hex']) : '';
            if ('' === $label || !$hex) {
                continue;
            }
            $colors[] = array('label' => $label, 'hex' => $hex);
        }
    }

    return $colors ?: tokraft_material_default_colors();
}

function tokraft_material_color_row_markup($index, $label = '', $hex = '#cccccc') {
    ob_start();
    echo '<div class="tokraft-color-row" data-color-row>';
    echo '<input type="text" class="tokraft-color-label" name="tokraft_material_colors[' . esc_attr($index) . '][label]" value="' . esc_attr($label) . '" placeholder="' . esc_attr__('Colour name', 'tokraft') . '">';
    echo '<input type="text" class="tokraft-color-field" name="tokraft_material_colors[' . esc_attr($index) . '][hex]" value="' . esc_attr($hex) . '" data-default-color="' . esc_attr($hex) . '">';
    echo '<button type="button" class="button tokraft-color-remove" data-color-remove aria-label="' . esc_attr__('Remove colour', 'tokraft') . '">&times;</button>';
    echo '</div>';

    return ob_get_clean();
}

/**
 * Repeatable colour rows shared by the add-term and edit-term forms.
 */
function tokraft_material_colors_control($term = null) {
    $stored = $term ? get_term_meta(is_object($term) ? $term->term_id : absint($term), '_tokraft_material_colors', true) : array();
    $rows = is_array($stored) ? $stored : array();

    echo '<div class="tokraft-color-repeater" data-color-repeater>';
    echo '<div class="tokraft-color-rows" data-color-rows>';
    if ($rows) {
        foreach ($rows as $index => $color) {
            echo tokraft_material_color_row_markup($index, $color['label'] ?? '', $color['hex'] ?? '#cccccc'); // phpcs:ignore WordPress.Security.EscapeOutput
        }
    } else {
        foreach (tokraft_material_default_colors() as $index => $color) {
            echo tokraft_material_color_row_markup($index, $color['label'], $color['hex']); // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }
    echo '</div>';
    echo '<p><button type="button" class="button tokraft-color-add" data-color-add>' . esc_html__('Add colour', 'tokraft') . '</button></p>';
    echo '<script type="text/html" data-color-template>' . tokraft_material_color_row_markup('__index__') . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</div>';
}

function tokraft_sanitize_home_settings($values) {
    $defaults = tokraft_home_settings_defaults();
    $sanitized = array();
    $textarea_fields = array('hero_description', 'hero_proof', 'service_text', 'service_points', 'shop_text', 'shop_points', 'materials_text', 'metrics_text', 'shop_showcase_text');
    $image_fields = array('hero_image', 'service_image', 'shop_image');
    $image_list_fields = array('hero_slides');
    $number_fields = array('equipment_count', 'materials_count', 'cases_count');
    $url_fields = array('hero_quote_url', 'hero_shop_url', 'materials_button_url', 'shop_showcase_button_url');
    $toggle_fields = array('discover_enabled');

    foreach ($defaults as $key => $default) {
        $value = isset($values[$key]) ? $values[$key] : $default;
        if (in_array($key, $textarea_fields, true)) {
            $sanitized[$key] = sanitize_textarea_field($value);
        } elseif (in_array($key, $toggle_fields, true)) {
            $sanitized[$key] = '1' === (string) $value ? '1' : '0';
        } elseif ('shop_layout' === $key) {
            $sanitized[$key] = in_array($value, array('showcase', 'catalog'), true) ? $value : 'showcase';
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

/**
 * Quote form configuration. Defaults reproduce the values that used to be
 * hard-coded in page-quote.php and assets/theme.js.
 */
function tokraft_quote_settings_defaults() {
    return array(
        // Off by default: the live estimate panel is hidden until the pricing
        // model is settled, but stays one checkbox away from coming back.
        'summary_enabled' => '0',

        'infill_enabled' => '1',
        'infill_label' => 'Infill density',
        'infill_help' => 'Infill is the pattern inside a printed part. More infill increases strength and material use, which also increases cost and printing time.',
        'infill_min' => '10',
        'infill_max' => '100',
        'infill_step' => '5',
        'infill_default' => '20',
        'infill_impact_low' => 'Balanced strength, efficient material use and standard lead time.',
        'infill_impact_mid' => 'Stronger internal structure with more material and print time.',
        'infill_impact_high' => 'High-density part: maximum strength, material use and production time.',

        'walls_enabled' => '1',
        'walls_label' => 'Wall perimeters',
        'walls_help' => 'Walls are the outer shells of a part. Extra walls improve strength and surface durability, especially around holes and load-bearing features.',
        'walls_min' => '2',
        'walls_max' => '6',
        'walls_step' => '1',
        'walls_default' => '3',
        'walls_impact_low' => 'A dependable balance for functional prototypes.',
        'walls_impact_mid' => 'Extra shell strength around holes and load-bearing features.',
        'walls_impact_high' => 'Maximum surface durability; noticeably longer print time.',

        'layer_enabled' => '1',
        'layer_label' => 'Layer height / detail',
        'layer_help' => 'Thinner layers capture smoother curves and finer features. They also make the print take longer; larger layers are faster and more economical.',
        'layer_min' => '0.12',
        'layer_max' => '0.32',
        'layer_step' => '0.04',
        'layer_default' => '0.20',
        'layer_impact_low' => 'Fine detail and smoother curves with a longer production time.',
        'layer_impact_mid' => 'Standard detail with a balanced production time.',
        'layer_impact_high' => 'Faster and more economical with visible layer lines.',

        'support_enabled' => '1',
        'support_label' => 'Support material',
        'support_help' => 'Supports are temporary structures used under overhangs. They allow complex geometry but can leave small marks where they are removed.',
        'support_options' => "No support|No\nUse support|Yes",
        'support_default' => 'No',

        'adhesion_enabled' => '1',
        'adhesion_label' => 'Brim or raft',
        'adhesion_help' => 'A brim or raft improves bed adhesion for tall, narrow or warp-prone parts. It is removed after the print is complete.',
        'adhesion_options' => "None|None\nBrim / raft|Brim / Raft",
        'adhesion_default' => 'None',

        'estimate_infill_coefficient' => '0.006',
        'estimate_wall_coefficient' => '0.1',
        'estimate_layer_coefficient' => '1.1',
        'estimate_layer_baseline' => '0.2',
        'estimate_high_multiplier' => '1.4',
        'estimate_minimum' => '10',
    );
}

function tokraft_quote_settings() {
    return wp_parse_args((array) get_option('tokraft_quote_settings', array()), tokraft_quote_settings_defaults());
}

function tokraft_quote_value($key) {
    $settings = tokraft_quote_settings();

    return $settings[$key] ?? '';
}

function tokraft_quote_enabled($group) {
    return '1' === (string) tokraft_quote_value($group . '_enabled');
}

/**
 * Parse a "Visible label|submitted value" list into option pairs.
 */
function tokraft_quote_choice_options($key) {
    $options = array();
    foreach (tokraft_lines(tokraft_quote_value($key)) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        $label = $parts[0];
        if ('' === $label) {
            continue;
        }
        $options[] = array('label' => $label, 'value' => isset($parts[1]) && '' !== $parts[1] ? $parts[1] : $label);
    }

    return $options;
}

/**
 * Snap a submitted slider value back into the configured range so a tampered
 * or stale form cannot push the estimate outside what the shop offers.
 */
function tokraft_quote_clamp($group, $value) {
    $settings = tokraft_quote_settings();
    $min = (float) $settings[$group . '_min'];
    $max = (float) $settings[$group . '_max'];
    if (null === $value || '' === $value || !is_numeric($value)) {
        return (float) $settings[$group . '_default'];
    }

    return min($max, max($min, (float) $value));
}

/**
 * Accept a submitted radio value only when it is one of the configured options.
 */
function tokraft_quote_choice_value($group, $value) {
    $options = tokraft_quote_choice_options($group . '_options');
    if (!$options) {
        return '';
    }
    $value = null === $value ? '' : sanitize_text_field((string) wp_unslash($value));
    $allowed = wp_list_pluck($options, 'value');
    if (in_array($value, $allowed, true)) {
        return $value;
    }
    $default = (string) tokraft_quote_value($group . '_default');

    return in_array($default, $allowed, true) ? $default : $allowed[0];
}

function tokraft_sanitize_quote_settings($values) {
    $defaults = tokraft_quote_settings_defaults();
    $values = (array) $values;
    $sanitized = array();

    $toggles = array('summary_enabled', 'infill_enabled', 'walls_enabled', 'layer_enabled', 'support_enabled', 'adhesion_enabled');
    $textareas = array(
        'infill_help', 'infill_impact_low', 'infill_impact_mid', 'infill_impact_high',
        'walls_help', 'walls_impact_low', 'walls_impact_mid', 'walls_impact_high',
        'layer_help', 'layer_impact_low', 'layer_impact_mid', 'layer_impact_high',
        'support_help', 'support_options', 'adhesion_help', 'adhesion_options',
    );
    $numbers = array(
        'infill_min', 'infill_max', 'infill_step', 'infill_default',
        'walls_min', 'walls_max', 'walls_step', 'walls_default',
        'layer_min', 'layer_max', 'layer_step', 'layer_default',
        'estimate_infill_coefficient', 'estimate_wall_coefficient', 'estimate_layer_coefficient',
        'estimate_layer_baseline', 'estimate_high_multiplier', 'estimate_minimum',
    );

    foreach ($defaults as $key => $default) {
        $value = $values[$key] ?? $default;
        if (in_array($key, $toggles, true)) {
            $sanitized[$key] = '1' === (string) $value ? '1' : '0';
        } elseif (in_array($key, $textareas, true)) {
            $sanitized[$key] = sanitize_textarea_field(wp_unslash($value));
        } elseif (in_array($key, $numbers, true)) {
            $number = (float) $value;
            $sanitized[$key] = $number > 0 ? (string) $number : $default;
        } else {
            $sanitized[$key] = sanitize_text_field(wp_unslash($value));
        }
    }

    // A slider needs max > min and a default inside the range, otherwise the front end breaks.
    foreach (array('infill', 'walls', 'layer') as $group) {
        $min = (float) $sanitized[$group . '_min'];
        $max = (float) $sanitized[$group . '_max'];
        if ($max <= $min) {
            $sanitized[$group . '_min'] = $defaults[$group . '_min'];
            $sanitized[$group . '_max'] = $defaults[$group . '_max'];
            $min = (float) $defaults[$group . '_min'];
            $max = (float) $defaults[$group . '_max'];
        }
        $sanitized[$group . '_default'] = (string) min($max, max($min, (float) $sanitized[$group . '_default']));
    }

    return $sanitized;
}

function tokraft_register_quote_settings() {
    register_setting('tokraft_quote_settings_group', 'tokraft_quote_settings', 'tokraft_sanitize_quote_settings');
}
add_action('admin_init', 'tokraft_register_quote_settings');

/**
 * Values handed to assets/theme.js so the live estimate matches the admin config.
 */
function tokraft_quote_js_config() {
    $settings = tokraft_quote_settings();

    return array(
        'infill' => array(
            'enabled' => tokraft_quote_enabled('infill'),
            'impact' => array($settings['infill_impact_low'], $settings['infill_impact_mid'], $settings['infill_impact_high']),
        ),
        'walls' => array(
            'enabled' => tokraft_quote_enabled('walls'),
            'impact' => array($settings['walls_impact_low'], $settings['walls_impact_mid'], $settings['walls_impact_high']),
        ),
        'layer' => array(
            'enabled' => tokraft_quote_enabled('layer'),
            'impact' => array($settings['layer_impact_low'], $settings['layer_impact_mid'], $settings['layer_impact_high']),
        ),
        'estimate' => array(
            'infillCoefficient' => (float) $settings['estimate_infill_coefficient'],
            'wallCoefficient' => (float) $settings['estimate_wall_coefficient'],
            'layerCoefficient' => (float) $settings['estimate_layer_coefficient'],
            'layerBaseline' => (float) $settings['estimate_layer_baseline'],
            'highMultiplier' => (float) $settings['estimate_high_multiplier'],
            'minimum' => (float) $settings['estimate_minimum'],
            'infillBaseline' => (float) $settings['infill_default'],
            'wallBaseline' => (float) $settings['walls_default'],
        ),
        'labels' => array(
            'noColor' => __('Select a colour', 'tokraft'),
        ),
    );
}

function tokraft_register_admin_menu() {
    add_menu_page(__('toKraft 内容管理', 'tokraft'), __('toKraft', 'tokraft'), 'edit_others_posts', 'tokraft-home', 'tokraft_render_home_settings_page', 'dashicons-admin-customizer', 25);
    // Keep the custom landing page as the first submenu. Otherwise WordPress opens the first post type below it.
    add_submenu_page('tokraft-home', __('首页内容区块', 'tokraft'), __('首页内容区块', 'tokraft'), 'edit_others_posts', 'tokraft-home', 'tokraft_render_home_settings_page');
    add_submenu_page('tokraft-home', __('材料库', 'tokraft'), __('材料库', 'tokraft'), 'manage_categories', 'edit-tags.php?taxonomy=tokraft_material&post_type=product');
    add_submenu_page('tokraft-home', __('报价表单', 'tokraft'), __('报价表单', 'tokraft'), 'edit_others_posts', 'tokraft-quote-settings', 'tokraft_render_quote_settings_page');
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

function tokraft_admin_option_value($option, $key) {
    if ('tokraft_quote_settings' === $option) {
        return tokraft_quote_value($key);
    }

    return tokraft_home_value($key);
}

function tokraft_admin_select_field($key, $args = array()) {
    $args = wp_parse_args($args, array(
        'label' => '',
        'description' => '',
        'options' => array(),
        'class' => '',
        'option' => 'tokraft_home_settings',
    ));
    $field_id = $args['option'] . '_' . $key;
    $value = tokraft_admin_option_value($args['option'], $key);
    echo '<div class="tokraft-admin-field ' . esc_attr($args['class']) . '">';
    echo '<label for="' . esc_attr($field_id) . '"><span class="tokraft-admin-field-label">' . esc_html($args['label']) . '</span>';
    if ($args['description']) {
        echo '<span class="tokraft-admin-field-description">' . esc_html($args['description']) . '</span>';
    }
    echo '</label><select id="' . esc_attr($field_id) . '" name="' . esc_attr($args['option']) . '[' . esc_attr($key) . ']"' . ('hero_visual_mode' === $key ? ' data-tokraft-hero-visual-mode' : '') . '>';
    foreach ($args['options'] as $option_value => $option_label) {
        echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
    }
    echo '</select></div>';
}

function tokraft_admin_checkbox_field($key, $args = array()) {
    $args = wp_parse_args($args, array(
        'label' => '',
        'description' => '',
        'checkbox_label' => '',
        'class' => '',
        'option' => 'tokraft_home_settings',
    ));
    $field_id = $args['option'] . '_' . $key;
    $value = tokraft_admin_option_value($args['option'], $key);
    echo '<div class="tokraft-admin-field ' . esc_attr($args['class']) . '">';
    echo '<span class="tokraft-admin-field-label">' . esc_html($args['label']) . '</span>';
    if ($args['description']) {
        echo '<span class="tokraft-admin-field-description">' . esc_html($args['description']) . '</span>';
    }
    echo '<label class="tokraft-admin-checkbox" for="' . esc_attr($field_id) . '">';
    echo '<input type="hidden" name="' . esc_attr($args['option']) . '[' . esc_attr($key) . ']" value="0">';
    echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($args['option']) . '[' . esc_attr($key) . ']" value="1" ' . checked($value, '1', false) . '>';
    echo '<span>' . esc_html($args['checkbox_label']) . '</span></label>';
    echo '</div>';
}

function tokraft_admin_text_field($key, $args = array()) {
    $args = wp_parse_args($args, array(
        'label' => '',
        'description' => '',
        'type' => 'text',
        'placeholder' => '',
        'class' => '',
        'option' => 'tokraft_home_settings',
        'input_attributes' => array(),
    ));
    $value = tokraft_admin_option_value($args['option'], $key);
    $field_id = $args['option'] . '_' . $key;
    $name = $args['option'] . '[' . $key . ']';
    $extra = '';
    foreach ($args['input_attributes'] as $attribute => $attribute_value) {
        $extra .= ' ' . esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
    }
    echo '<div class="tokraft-admin-field ' . esc_attr($args['class']) . '">';
    echo '<label for="' . esc_attr($field_id) . '"><span class="tokraft-admin-field-label">' . esc_html($args['label']) . '</span>';
    if ($args['description']) {
        echo '<span class="tokraft-admin-field-description">' . esc_html($args['description']) . '</span>';
    }
    echo '</label>';
    if ('textarea' === $args['type']) {
        echo '<textarea class="large-text" rows="4" id="' . esc_attr($field_id) . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($args['placeholder']) . '">' . esc_textarea($value) . '</textarea>';
    } else {
        echo '<input class="regular-text" type="' . esc_attr($args['type']) . '" id="' . esc_attr($field_id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($args['placeholder']) . '"' . $extra . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
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
    echo '<div class="tokraft-admin-callout"><strong>两个按钮怎么填？</strong><span>第一个按钮通向材料库（客户先选材料，再进入定制询价），第二个按钮通向商店页。链接可保留默认的 <code>/materials/</code> 和 <code>/shop/</code>。</span></div>';
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-four">';
    tokraft_admin_text_field('hero_quote_label', array('label' => '询价按钮文字', 'description' => '例如：申请打印报价'));
    tokraft_admin_text_field('hero_quote_url', array('label' => '询价按钮链接', 'description' => '默认：/materials/（先进材料库选材料，再进入 /quote/ 定制）', 'placeholder' => '/materials/'));
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

    tokraft_admin_section_open('tokraft-shop-layout', '商城', '商城页面布局', '控制 /shop/ 使用哪一套模板。商品变多以后切回“目录模式”即可恢复带筛选侧栏的网格页，两套模板同时保留在主题里。', tokraft_admin_manage_link(admin_url('edit.php?post_type=product'), '管理商品'));
    tokraft_admin_select_field('shop_layout', array(
        'label' => '商城页模板',
        'description' => '展示模式：以封面大图为主的两款机器展示页。目录模式：原有的带筛选、排序和搜索的商品网格。商品分类归档页始终使用目录模式。',
        'options' => array(
            'showcase' => '展示模式（封面大图，适合少量产品）',
            'catalog' => '目录模式（筛选网格，适合商品较多）',
        ),
    ));
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-two">';
    tokraft_admin_text_field('shop_showcase_eyebrow', array('label' => '展示页小标题', 'description' => '显示在展示页大标题上方。'));
    tokraft_admin_text_field('shop_showcase_title', array('label' => '展示页主标题', 'description' => '例如：Two printers. Everything we make.'));
    echo '</div>';
    tokraft_admin_text_field('shop_showcase_text', array('label' => '展示页说明', 'description' => '一到两句话，说明为什么只上这几款机器。', 'type' => 'textarea'));
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-two">';
    tokraft_admin_text_field('shop_showcase_button_label', array('label' => '展示页按钮文字', 'description' => '例如：Request a custom print。'));
    tokraft_admin_text_field('shop_showcase_button_url', array('label' => '展示页按钮链接', 'description' => '默认：/quote/', 'placeholder' => '/quote/'));
    echo '</div>';
    tokraft_admin_checkbox_field('discover_enabled', array(
        'label' => '商品详情页配件推荐',
        'checkbox_label' => '在商品详情页显示“Discover More Here!”配件推荐区',
        'description' => '关闭后详情页不再输出该区块，已在商品里配置好的推荐商品会保留，随时可以打开。',
    ));
    tokraft_admin_section_close();

    echo '</form></div>';
}

/**
 * Renders the "报价表单" settings screen: slider ranges, choice options,
 * help copy and the live-estimate coefficients used by assets/theme.js.
 */
function tokraft_render_quote_settings_page() {
    if (!current_user_can('edit_others_posts')) {
        return;
    }

    $slider_groups = array(
        'infill' => array(
            'title' => __('填充率 Infill', 'tokraft'),
            'description' => __('控制零件内部填充密度滑块的取值范围、默认值与说明文案。', 'tokraft'),
            'step' => '01',
            'number_step' => '1',
        ),
        'walls' => array(
            'title' => __('壁厚 Wall perimeters', 'tokraft'),
            'description' => __('控制外壁层数滑块的取值范围、默认值与说明文案。', 'tokraft'),
            'step' => '02',
            'number_step' => '1',
        ),
        'layer' => array(
            'title' => __('层高 Layer height', 'tokraft'),
            'description' => __('控制层高滑块的取值范围、默认值与说明文案，单位毫米。', 'tokraft'),
            'step' => '03',
            'number_step' => '0.01',
        ),
    );

    echo '<div class="wrap tokraft-home-settings">';
    if (isset($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('报价表单配置已保存。', 'tokraft') . '</p></div>';
    }
    echo '<h1>' . esc_html__('报价表单配置', 'tokraft') . '</h1>';
    echo '<p class="description">' . esc_html(sprintf(__('这些设置直接驱动 %s 上的打印参数与实时估价，保存后前台立即生效。', 'tokraft'), home_url('/quote/'))) . '</p>';
    echo '<form method="post" action="options.php">';
    settings_fields('tokraft_quote_settings_group');

    tokraft_admin_section_open(
        'tokraft-quote-summary',
        '00',
        __('实时估价面板', 'tokraft'),
        __('报价表单右侧的「Your print summary」估价卡片。关闭后表单单列居中显示，估价逻辑仍在后台运行。', 'tokraft')
    );
    tokraft_admin_checkbox_field('summary_enabled', array(
        'option' => 'tokraft_quote_settings',
        'label' => __('显示状态', 'tokraft'),
        'checkbox_label' => __('在报价表单右侧显示实时估价面板', 'tokraft'),
    ));
    tokraft_admin_section_close();

    foreach ($slider_groups as $group => $meta) {
        tokraft_admin_section_open('tokraft-quote-' . $group, $meta['step'], $meta['title'], $meta['description']);
        tokraft_admin_checkbox_field($group . '_enabled', array(
            'option' => 'tokraft_quote_settings',
            'label' => __('显示状态', 'tokraft'),
            'checkbox_label' => __('在报价表单上显示这个参数', 'tokraft'),
        ));
        echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-four">';
        tokraft_admin_text_field($group . '_label', array('option' => 'tokraft_quote_settings', 'label' => __('前台标题', 'tokraft')));
        tokraft_admin_text_field($group . '_min', array('option' => 'tokraft_quote_settings', 'label' => __('最小值', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => $meta['number_step'])));
        tokraft_admin_text_field($group . '_max', array('option' => 'tokraft_quote_settings', 'label' => __('最大值', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => $meta['number_step'])));
        tokraft_admin_text_field($group . '_step', array('option' => 'tokraft_quote_settings', 'label' => __('步长', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => $meta['number_step'])));
        tokraft_admin_text_field($group . '_default', array('option' => 'tokraft_quote_settings', 'label' => __('默认值', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => $meta['number_step'])));
        echo '</div>';
        tokraft_admin_text_field($group . '_help', array('option' => 'tokraft_quote_settings', 'label' => __('问号提示文案', 'tokraft'), 'type' => 'textarea', 'description' => __('点击参数旁的问号时弹出的解释。', 'tokraft')));
        echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-four">';
        tokraft_admin_text_field($group . '_impact_low', array('option' => 'tokraft_quote_settings', 'label' => __('低档影响描述', 'tokraft'), 'type' => 'textarea'));
        tokraft_admin_text_field($group . '_impact_mid', array('option' => 'tokraft_quote_settings', 'label' => __('中档影响描述', 'tokraft'), 'type' => 'textarea'));
        tokraft_admin_text_field($group . '_impact_high', array('option' => 'tokraft_quote_settings', 'label' => __('高档影响描述', 'tokraft'), 'type' => 'textarea'));
        echo '</div>';
        tokraft_admin_section_close();
    }

    $choice_groups = array(
        'support' => array('title' => __('支撑 Support', 'tokraft'), 'step' => '04'),
        'adhesion' => array('title' => __('附着 Brim / Raft', 'tokraft'), 'step' => '05'),
    );
    foreach ($choice_groups as $group => $meta) {
        tokraft_admin_section_open('tokraft-quote-' . $group, $meta['step'], $meta['title'], __('二选一按钮组：显示状态、标题、提示文案与可选项。', 'tokraft'));
        tokraft_admin_checkbox_field($group . '_enabled', array(
            'option' => 'tokraft_quote_settings',
            'label' => __('显示状态', 'tokraft'),
            'checkbox_label' => __('在报价表单上显示这个参数', 'tokraft'),
        ));
        echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-two">';
        tokraft_admin_text_field($group . '_label', array('option' => 'tokraft_quote_settings', 'label' => __('前台标题', 'tokraft')));
        tokraft_admin_text_field($group . '_default', array('option' => 'tokraft_quote_settings', 'label' => __('默认选中的提交值', 'tokraft')));
        echo '</div>';
        tokraft_admin_text_field($group . '_options', array(
            'option' => 'tokraft_quote_settings',
            'label' => __('可选项', 'tokraft'),
            'type' => 'textarea',
            'description' => __('每行一个选项，格式「前台显示文案|提交值」。省略竖线时两者相同。', 'tokraft'),
        ));
        tokraft_admin_text_field($group . '_help', array('option' => 'tokraft_quote_settings', 'label' => __('问号提示文案', 'tokraft'), 'type' => 'textarea'));
        tokraft_admin_section_close();
    }

    tokraft_admin_section_open('tokraft-quote-estimate', '06', __('实时估价公式', 'tokraft'), __('右侧「Live estimate」的计算系数。系数 = 1 + (填充 − 默认填充) × A + (壁厚 − 默认壁厚) × B + (基准层高 − 层高) × C；低价 = max(最低价, 材料单价 × 数量 × 系数)，高价 = 低价 × 上限倍率。', 'tokraft'));
    echo '<div class="tokraft-admin-field-grid tokraft-admin-field-grid-four">';
    tokraft_admin_text_field('estimate_infill_coefficient', array('option' => 'tokraft_quote_settings', 'label' => __('A 填充系数', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => '0.001')));
    tokraft_admin_text_field('estimate_wall_coefficient', array('option' => 'tokraft_quote_settings', 'label' => __('B 壁厚系数', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => '0.01')));
    tokraft_admin_text_field('estimate_layer_coefficient', array('option' => 'tokraft_quote_settings', 'label' => __('C 层高系数', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => '0.1')));
    tokraft_admin_text_field('estimate_layer_baseline', array('option' => 'tokraft_quote_settings', 'label' => __('基准层高（mm）', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => '0.01')));
    tokraft_admin_text_field('estimate_high_multiplier', array('option' => 'tokraft_quote_settings', 'label' => __('高位上限倍率', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => '0.05')));
    tokraft_admin_text_field('estimate_minimum', array('option' => 'tokraft_quote_settings', 'label' => __('最低报价（CAD）', 'tokraft'), 'type' => 'number', 'input_attributes' => array('step' => '1')));
    echo '</div>';
    echo '<p class="description">' . esc_html__('每个材料自己的起步单价在「材料库」里逐个设置。', 'tokraft') . '</p>';
    tokraft_admin_section_close();

    submit_button(__('保存报价表单配置', 'tokraft'));
    echo '</form></div>';
}

function tokraft_material_image_spec_text() {
    return 'Recommended image: 1200 × 900 px (4:3 landscape), JPG or WebP, under 1.5 MB. The front page crops to a fixed 4:3 frame — avoid tall portrait photos. Same image is reused on the homepage material cards.';
}

function tokraft_material_add_fields() {
    $types = tokraft_material_type_choices();
    echo '<div class="form-field"><label for="tokraft_material_type">Material type</label><select id="tokraft_material_type" name="tokraft_material_type">';
    foreach ($types as $value => $label) {
        echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
    }
    echo '</select><p>Used by the filter on the material library page.</p></div>';
    echo '<div class="form-field"><label for="tokraft_material_color">Brand color</label><input type="text" id="tokraft_material_color" name="tokraft_material_color" value="#d9d9d9" class="tokraft-color-field regular-text" data-default-color="#d9d9d9"><p>Fallback color when no material image is uploaded.</p></div>';
    echo '<div class="form-field"><label>Printable colours</label>';
    tokraft_material_colors_control();
    echo '<p>Shown as swatches on the material library card and used as the colour choices on the quote form.</p></div>';
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
    $types = tokraft_material_type_choices();
    $type = tokraft_material_type($term);
    $color = get_term_meta($term->term_id, '_tokraft_material_color', true) ?: '#d9d9d9';
    $short_description = get_term_meta($term->term_id, '_tokraft_material_short_description', true);
    $best_for = get_term_meta($term->term_id, '_tokraft_material_best_for', true);
    $avoid = get_term_meta($term->term_id, '_tokraft_material_avoid', true);
    $notes = get_term_meta($term->term_id, '_tokraft_material_notes', true);
    $quote_rate = tokraft_material_quote_rate($term);
    $image_id = absint(get_term_meta($term->term_id, '_tokraft_material_image_id', true));
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
    $featured = get_term_meta($term->term_id, '_tokraft_material_featured', true);
    echo '<tr class="form-field"><th scope="row"><label for="tokraft_material_type">Material type</label></th><td><select id="tokraft_material_type" name="tokraft_material_type">';
    foreach ($types as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($type, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select><p class="description">Used by the filter on the material library page.</p></td></tr>';
    echo '<tr class="form-field"><th scope="row"><label for="tokraft_material_color">Brand color</label></th><td><input type="text" id="tokraft_material_color" name="tokraft_material_color" value="' . esc_attr($color) . '" class="tokraft-color-field regular-text" data-default-color="' . esc_attr($color) . '"><p class="description">Fallback color when no image is uploaded.</p></td></tr>';
    echo '<tr class="form-field"><th scope="row">Printable colours</th><td>';
    tokraft_material_colors_control($term);
    echo '<p class="description">Shown as swatches on the material library card and used as the colour choices on the quote form.</p></td></tr>';
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
    if (isset($_POST['tokraft_material_type'])) {
        $type = sanitize_key(wp_unslash($_POST['tokraft_material_type']));
        update_term_meta($term_id, '_tokraft_material_type', array_key_exists($type, tokraft_material_type_choices()) ? $type : 'general-purpose');
    }
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
    if (isset($_POST['tokraft_material_colors'])) {
        $colors = array();
        $seen = array();
        foreach ((array) wp_unslash($_POST['tokraft_material_colors']) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = sanitize_text_field($row['label'] ?? '');
            $hex = sanitize_hex_color($row['hex'] ?? '');
            $key = strtolower($label);
            if ('' === $label || !$hex || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $colors[] = array('label' => $label, 'hex' => $hex);
        }
        update_term_meta($term_id, '_tokraft_material_colors', $colors);
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
    $showcase_cover_id = absint(get_post_meta($post->ID, '_tokraft_showcase_cover_id', true));
    $video_id = absint(get_post_meta($post->ID, '_tokraft_product_video_id', true));
    $video_poster_id = absint(get_post_meta($post->ID, '_tokraft_product_video_poster_id', true));
    $showcase_cover_url = $showcase_cover_id ? wp_get_attachment_image_url($showcase_cover_id, 'medium') : '';
    $video_poster_url = $video_poster_id ? wp_get_attachment_image_url($video_poster_id, 'medium') : '';
    $video_url = $video_id ? wp_get_attachment_url($video_id) : '';

    echo '<p><label for="tokraft_display_dimensions"><strong>Front-end size description</strong></label><textarea class="widefat" rows="4" id="tokraft_display_dimensions" name="tokraft_display_dimensions" placeholder="For example: Single: 58 x 34 x 22 mm&#10;Double: confirm dimensions before ordering.">' . esc_textarea($dimensions_note) . '</textarea></p>';
    echo '<p class="description">This is shown next to the purchase options. For shipping calculations, also enter the actual length, width and height in <strong>Product data &rarr; Shipping</strong>.</p>';

    echo '<hr><p><strong>Showcase cover</strong></p>';
    echo '<p class="description">Optional full-bleed cover for the /shop/ showcase layout. Falls back to the product image when empty.</p>';
    echo '<input type="hidden" id="tokraft_showcase_cover_id" name="tokraft_showcase_cover_id" value="' . esc_attr($showcase_cover_id) . '">';
    echo '<p class="tokraft-admin-media-actions"><button type="button" class="button tokraft-media-select" data-target="tokraft_showcase_cover_id">Select cover image</button> <button type="button" class="button tokraft-media-clear" data-target="tokraft_showcase_cover_id">Clear</button></p>';
    echo '<img class="tokraft-admin-media-preview" id="tokraft_showcase_cover_id-preview" src="' . esc_url($showcase_cover_url) . '" alt="" style="max-width:100%;' . ($showcase_cover_url ? '' : 'display:none;') . '">';

    echo '<hr><p><strong>Product video</strong></p>';
    echo '<p class="description">Upload a local MP4 from the media library. Do not paste a YouTube URL — the player only accepts media library attachments.</p>';
    echo '<input type="hidden" id="tokraft_product_video_id" name="tokraft_product_video_id" value="' . esc_attr($video_id) . '">';
    echo '<p class="tokraft-admin-media-actions"><button type="button" class="button tokraft-media-select" data-target="tokraft_product_video_id" data-media-type="video">Select video</button> <button type="button" class="button tokraft-media-clear" data-target="tokraft_product_video_id">Clear</button></p>';
    if ($video_url) {
        echo '<p class="description" id="tokraft_product_video_id-preview"><a href="' . esc_url($video_url) . '" target="_blank" rel="noopener">' . esc_html(basename($video_url)) . '</a></p>';
    } else {
        echo '<p class="description" id="tokraft_product_video_id-preview" style="display:none"></p>';
    }

    echo '<p><strong>Video poster</strong></p>';
    echo '<input type="hidden" id="tokraft_product_video_poster_id" name="tokraft_product_video_poster_id" value="' . esc_attr($video_poster_id) . '">';
    echo '<p class="tokraft-admin-media-actions"><button type="button" class="button tokraft-media-select" data-target="tokraft_product_video_poster_id">Select poster image</button> <button type="button" class="button tokraft-media-clear" data-target="tokraft_product_video_poster_id">Clear</button></p>';
    echo '<img class="tokraft-admin-media-preview" id="tokraft_product_video_poster_id-preview" src="' . esc_url($video_poster_url) . '" alt="" style="max-width:100%;' . ($video_poster_url ? '' : 'display:none;') . '">';

    echo '<hr><p><strong>Product image specification</strong></p><ul class="ul-disc" style="margin-left:18px"><li>Main image: 1600 x 1600 px, square, JPG or WebP, under 2 MB.</li><li>Gallery: 1600 x 1200 px minimum, consistent lighting and background.</li><li>Keep the product within 70-85% of the frame; use sharp, well-lit images.</li><li>Add the main image in the Product image panel and extra views in Product gallery.</li></ul>';
}

function tokraft_save_product_details_meta_box($post_id) {
    if (!isset($_POST['tokraft_product_details_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokraft_product_details_nonce'])), 'tokraft_product_details') || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || !current_user_can('edit_post', $post_id)) {
        return;
    }
    update_post_meta($post_id, '_tokraft_display_dimensions', sanitize_textarea_field(wp_unslash($_POST['tokraft_display_dimensions'] ?? '')));
    update_post_meta($post_id, '_tokraft_showcase_cover_id', absint($_POST['tokraft_showcase_cover_id'] ?? 0));
    update_post_meta($post_id, '_tokraft_product_video_id', absint($_POST['tokraft_product_video_id'] ?? 0));
    update_post_meta($post_id, '_tokraft_product_video_poster_id', absint($_POST['tokraft_product_video_poster_id'] ?? 0));
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
    $tokraft_pages = array('toplevel_page_tokraft-home', 'tokraft_page_tokraft-quote-settings');
    $is_material_taxonomy = $screen && 'tokraft_material' === $screen->taxonomy;
    $is_product_edit = $screen && 'product' === $screen->post_type && in_array($hook, array('post.php', 'post-new.php'), true);
    if (!in_array($hook, $tokraft_pages, true) && !$is_material_taxonomy && !$is_product_edit) {
        return;
    }
    wp_enqueue_style(
        'tokraft-admin',
        get_template_directory_uri() . '/assets/admin.css',
        array(),
        '2.7.0'
    );
    wp_enqueue_media();
    if ($is_material_taxonomy) {
        wp_enqueue_style('wp-color-picker');
    }
    wp_enqueue_script('tokraft-admin', get_template_directory_uri() . '/assets/admin.js', array('jquery', 'wp-color-picker'), '2.7.0', true);
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

/**
 * toKraft only ships to the US and Canada, so keep both the classic and Store API
 * country lists to those two. WooCommerce Blocks checkout reads these filters.
 */
function tokraft_allowed_countries($countries) {
    return array_intersect_key((array) $countries, array_flip(array('US', 'CA')));
}
add_filter('woocommerce_countries_allowed_countries', 'tokraft_allowed_countries');
add_filter('woocommerce_countries_shipping_countries', 'tokraft_allowed_countries');

/**
 * Mirror the filters above into the WooCommerce settings so the admin UI agrees
 * with what customers actually see at checkout. Runs once per version bump.
 */
function tokraft_sync_selling_locations() {
    if ('1.0.0' === get_option('tokraft_selling_locations_version')) {
        return;
    }
    update_option('woocommerce_allowed_countries', 'specific');
    update_option('woocommerce_specific_allowed_countries', array('US', 'CA'));
    update_option('woocommerce_ship_to_countries', 'specific');
    update_option('woocommerce_specific_ship_to_countries', array('US', 'CA'));
    update_option('tokraft_selling_locations_version', '1.0.0');
}
add_action('init', 'tokraft_sync_selling_locations', 31);
