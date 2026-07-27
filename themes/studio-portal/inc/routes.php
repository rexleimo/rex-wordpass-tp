<?php
if (!defined('ABSPATH')) {
    exit;
}

function studio_portal_route_names(): array {
    return array('work', 'services', 'journal', 'about', 'contact', 'process');
}

function studio_portal_register_routes(): void {
    foreach (studio_portal_route_names() as $route) {
        add_rewrite_rule('^' . $route . '/?$', 'index.php?studio_portal_route=' . $route, 'top');
    }
}
add_action('init', 'studio_portal_register_routes');

function studio_portal_query_vars(array $vars): array {
    $vars[] = 'studio_portal_route';
    return $vars;
}
add_filter('query_vars', 'studio_portal_query_vars');

function studio_portal_route_template(string $template): string {
    $route = get_query_var('studio_portal_route');
    if (in_array($route, studio_portal_route_names(), true)) {
        return get_template_directory() . '/templates/portal-route.php';
    }
    return $template;
}
add_filter('template_include', 'studio_portal_route_template');

function studio_portal_flush_routes(): void {
    studio_portal_register_routes();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'studio_portal_flush_routes');
