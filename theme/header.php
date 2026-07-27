<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="toKraft home"><span class="brand-mark" aria-hidden="true"><i></i><i></i></span>toKraft</a>
        <nav class="nav-primary" aria-label="<?php esc_attr_e('Primary navigation', 'tokraft'); ?>">
            <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'tokraft_default_menu', 'items_wrap' => '%3$s')); ?>
        </nav>
        <div class="header-actions">
            <a class="btn btn-ghost btn-small header-search-btn" href="<?php echo esc_url(home_url('/search/')); ?>" aria-label="<?php esc_attr_e('Search', 'tokraft'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.3-4.3"></path></svg>
            </a>
            <a class="btn btn-ghost btn-small header-cart" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?>">
                Cart
                <?php if (function_exists('WC') && WC()->cart->get_cart_contents_count() > 0) : ?>
                    <span class="cart-count"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span>
                <?php endif; ?>
            </a>
            <a class="btn btn-primary btn-small header-quote" href="<?php echo esc_url(home_url('/quote/')); ?>">Get a quote</a>
            <button class="header-menu-toggle" type="button" aria-controls="site-drawer" aria-expanded="false" aria-label="<?php esc_attr_e('Open menu', 'tokraft'); ?>">
                <span class="menu-bars"><span></span><span></span><span></span></span>
            </button>
        </div>
    </div>
</header>

<aside class="site-drawer" id="site-drawer" aria-label="<?php esc_attr_e('Mobile navigation', 'tokraft'); ?>" hidden>
    <div class="drawer-header">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('toKraft home', 'tokraft'); ?>"><span class="brand-mark" aria-hidden="true"><i></i><i></i></span>toKraft</a>
        <button class="drawer-close" type="button" aria-label="<?php esc_attr_e('Close menu', 'tokraft'); ?>">×</button>
    </div>
    <nav class="drawer-nav" aria-label="<?php esc_attr_e('Mobile primary navigation', 'tokraft'); ?>">
        <?php wp_nav_menu(array(
            'theme_location' => 'primary',
            'container' => false,
            'fallback_cb' => 'tokraft_default_menu',
            'items_wrap' => '%3$s',
        )); ?>
    </nav>
    <div class="drawer-actions">
        <a class="btn btn-primary" href="<?php echo esc_url(home_url('/quote/')); ?>">Get a quote</a>
        <a class="btn btn-ghost" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?>">Cart</a>
    </div>
    <div class="drawer-meta">
        <span>hello@tokraft.ca</span>
        <span>+1 (416) 555-1234</span>
    </div>
</aside>
<div class="site-drawer-overlay" hidden aria-hidden="true"></div>

<div class="site-shell">
    <main>
