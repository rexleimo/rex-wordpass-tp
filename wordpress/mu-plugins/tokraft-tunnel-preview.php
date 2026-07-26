<?php
/**
 * Plugin Name: Tokraft Tunnel Preview URLs
 * Description: Rewrites local custom-menu URLs only while viewing a Cloudflare Quick Tunnel preview.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the active temporary preview origin, or an empty string outside a Quick Tunnel request.
 *
 * @return string
 */
function tokraft_tunnel_preview_origin() {
	$host = strtolower( $_SERVER['HTTP_HOST'] ?? '' );
	$host = preg_replace( '/:\d+$/', '', $host );

	if ( ! is_string( $host ) || ! preg_match( '/^[a-z0-9-]+\.trycloudflare\.com$/', $host ) ) {
		return '';
	}

	return 'https://' . $host;
}

/**
 * Replaces only local custom-menu origins, preserving the stored URL path and suffix.
 *
 * @param array  $attributes Rendered menu-link attributes.
 * @param object $item       Menu item.
 * @param object $args       Menu arguments.
 * @return array
 */
function tokraft_rewrite_tunnel_menu_link( $attributes, $item, $args ) {
	$origin = tokraft_tunnel_preview_origin();

	if ( '' === $origin || empty( $attributes['href'] ) ) {
		return $attributes;
	}

	$url   = wp_parse_url( $attributes['href'] );
	$hosts = array( 'localhost', '127.0.0.1' );

	if ( empty( $url['host'] ) || ! in_array( strtolower( $url['host'] ), $hosts, true ) ) {
		return $attributes;
	}

	$path     = $url['path'] ?? '';
	$query    = isset( $url['query'] ) ? '?' . $url['query'] : '';
	$fragment = isset( $url['fragment'] ) ? '#' . $url['fragment'] : '';

	$attributes['href'] = $origin . $path . $query . $fragment;

	return $attributes;
}
add_filter( 'nav_menu_link_attributes', 'tokraft_rewrite_tunnel_menu_link', 10, 3 );
