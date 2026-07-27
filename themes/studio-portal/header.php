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
<div class="sp-site-shell">
    <header class="sp-header" data-site-header>
        <a class="sp-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <span class="sp-brand-mark">R</span>
                <span><?php echo esc_html(get_bloginfo('name')); ?></span>
            <?php endif; ?>
        </a>
        <button class="sp-menu-toggle" type="button" aria-expanded="false" aria-controls="sp-primary-navigation">
            <span class="sp-menu-toggle-label"><?php esc_html_e('菜单', 'studio-portal'); ?></span>
            <span class="sp-menu-toggle-icon" aria-hidden="true"><i></i><i></i></span>
        </button>
        <nav class="sp-nav" id="sp-primary-navigation" aria-label="<?php esc_attr_e('主导航', 'studio-portal'); ?>">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container' => false,
                'items_wrap' => '%3$s',
                'fallback_cb' => 'studio_portal_primary_menu_fallback',
            ));
            ?>
        </nav>
        <a class="sp-header-cta" href="<?php echo esc_url(home_url('/journal/')); ?>"><?php esc_html_e('全部文章', 'studio-portal'); ?> <span aria-hidden="true">-&gt;</span></a>
    </header>
    <main id="primary" class="sp-main">
