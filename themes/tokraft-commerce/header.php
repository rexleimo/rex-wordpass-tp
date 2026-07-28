<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$tokraft_account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();
$tokraft_account_label = is_user_logged_in() ? __('Account', 'tokraft') : __('Sign in', 'tokraft');
?>

<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="toKraft home"><span class="brand-mark" aria-hidden="true"><i></i><i></i></span>toKraft</a>
        <nav class="nav-primary" aria-label="<?php esc_attr_e('Primary navigation', 'tokraft'); ?>">
            <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'tokraft_default_menu', 'items_wrap' => '%3$s')); ?>
        </nav>
        <div class="header-actions">
            <button class="btn btn-ghost btn-small header-search-btn" type="button" data-search-open aria-controls="tokraft-search-overlay" aria-expanded="false" aria-label="<?php esc_attr_e('Search', 'tokraft'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.3-4.3"></path></svg>
            </button>
            <a class="btn btn-ghost btn-small header-account" href="<?php echo esc_url($tokraft_account_url); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4.5 21a7.5 7.5 0 0 1 15 0"></path></svg>
                <span><?php echo esc_html($tokraft_account_label); ?></span>
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

<div class="tokraft-search-overlay" id="tokraft-search-overlay" role="dialog" aria-modal="true" aria-labelledby="tokraft-search-title" hidden>
    <div class="tokraft-search-backdrop" data-search-close></div>
    <div class="tokraft-search-panel">
        <div class="tokraft-search-panel-head">
            <div>
                <div class="eyebrow"><?php esc_html_e('Search toKraft', 'tokraft'); ?></div>
                <h2 id="tokraft-search-title"><?php esc_html_e('What are you looking for?', 'tokraft'); ?></h2>
            </div>
            <button class="tokraft-search-close" type="button" data-search-close aria-label="<?php esc_attr_e('Close search', 'tokraft'); ?>"><span aria-hidden="true">&times;</span></button>
        </div>
        <?php get_search_form(); ?>
        <div class="tokraft-search-shortcuts" aria-label="<?php esc_attr_e('Popular destinations', 'tokraft'); ?>">
            <span><?php esc_html_e('Popular:', 'tokraft'); ?></span>
            <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php esc_html_e('Filament', 'tokraft'); ?></a>
            <a href="<?php echo esc_url(home_url('/materials/')); ?>"><?php esc_html_e('Materials', 'tokraft'); ?></a>
            <a href="<?php echo esc_url(home_url('/quote/')); ?>"><?php esc_html_e('Print service', 'tokraft'); ?></a>
        </div>
    </div>
</div>

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
        <a class="btn btn-ghost" href="<?php echo esc_url($tokraft_account_url); ?>"><?php echo esc_html($tokraft_account_label); ?></a>
        <button class="btn btn-ghost" type="button" data-search-open aria-controls="tokraft-search-overlay" aria-expanded="false"><?php esc_html_e('Search', 'tokraft'); ?></button>
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
