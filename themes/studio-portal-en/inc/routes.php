<?php
if (!defined('ABSPATH')) {
    exit;
}

function studio_portal_en_route_names(): array {
    return array('work', 'services', 'studio', 'process', 'journal', 'contact');
}

function studio_portal_en_register_routes(): void {
    foreach (studio_portal_en_route_names() as $route) {
        add_rewrite_rule('^' . $route . '/?$', 'index.php?studio_portal_en_route=' . $route, 'top');
    }
    add_rewrite_rule('^work/([^/]+)/?$', 'index.php?studio_portal_en_route=case&studio_portal_en_case=$matches[1]', 'top');
}
add_action('init', 'studio_portal_en_register_routes');

function studio_portal_en_query_vars(array $vars): array {
    $vars[] = 'studio_portal_en_route';
    $vars[] = 'studio_portal_en_case';
    return $vars;
}
add_filter('query_vars', 'studio_portal_en_query_vars');

function studio_portal_en_route_template(string $template): string {
    $route = (string) get_query_var('studio_portal_en_route');
    if (in_array($route, array_merge(studio_portal_en_route_names(), array('case')), true)) {
        return get_template_directory() . '/templates/studio-route.php';
    }
    return $template;
}
add_filter('template_include', 'studio_portal_en_route_template');

function studio_portal_en_flush_routes(): void {
    studio_portal_en_register_routes();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'studio_portal_en_flush_routes');
