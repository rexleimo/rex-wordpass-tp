<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="en-skip-link" href="#primary"><?php esc_html_e('Skip to content', 'studio-portal-en'); ?></a>
<div class="en-shell">
    <header class="en-header" data-en-header>
        <a class="en-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('Studio International home', 'studio-portal-en'); ?>">STUDIO<sup>TM</sup><small>INTERNATIONAL</small></a>
        <nav class="en-nav" id="en-navigation" aria-label="<?php esc_attr_e('Primary navigation', 'studio-portal-en'); ?>">
            <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'items_wrap' => '<ul class="en-nav-list">%3$s</ul>', 'fallback_cb' => 'studio_portal_en_menu_fallback')); ?>
        </nav>
        <a class="en-header-cta" href="<?php echo esc_url(home_url('/#newsletter')); ?>"><span class="en-cta-label">Get the briefing</span><span aria-hidden="true">&rarr;</span></a>
        <button class="en-menu-toggle" type="button" aria-controls="en-navigation" aria-expanded="false" aria-label="<?php esc_attr_e('Open menu', 'studio-portal-en'); ?>"><span aria-hidden="true"></span></button>
    </header>
    <main class="en-main" id="primary">
