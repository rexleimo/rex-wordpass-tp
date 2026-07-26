<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site-shell">
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="toKraft home"><span class="brand-mark" aria-hidden="true"><i></i><i></i></span>toKraft</a>
            <nav class="nav-primary" aria-label="<?php esc_attr_e('Primary navigation', 'tokraft'); ?>">
                <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'tokraft_default_menu', 'items_wrap' => '%3$s')); ?>
            </nav>
            <div class="header-actions">
                <a class="btn btn-ghost btn-small" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?>">Cart</a>
                <a class="btn btn-primary btn-small" href="<?php echo esc_url(home_url('/materials/')); ?>">Get a quote</a>
            </div>
        </div>
    </header>
    <main>
