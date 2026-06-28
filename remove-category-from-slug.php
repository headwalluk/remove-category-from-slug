<?php
/**
 * Plugin Name:       Remove Category from Slug
 * Description:       Removes the "/category/" base from category archive URLs and 301-redirects the old URLs.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Paul Faulkner
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       remove-category-from-slug
 */

defined( 'ABSPATH' ) || die();

register_activation_hook( __FILE__, 'rcfs_flush_rules' );
register_deactivation_hook( __FILE__, 'rcfs_deactivate' );

add_action( 'created_category', 'rcfs_flush_rules' );
add_action( 'edited_category', 'rcfs_flush_rules' );
add_action( 'delete_category', 'rcfs_flush_rules' );
add_action( 'init', 'rcfs_override_permastruct' );

add_filter( 'category_rewrite_rules', 'rcfs_category_rewrite_rules' );
add_filter( 'query_vars', 'rcfs_register_query_vars' );
add_filter( 'request', 'rcfs_redirect_old_urls' );
add_filter( 'wpseo_canonical', 'rcfs_filter_yoast_canonical' );

/**
 * Flush rewrite rules. Called on activation and whenever categories change.
 */
function rcfs_flush_rules(): void {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

/**
 * On deactivation, drop our custom rules before flushing so WordPress regenerates the defaults.
 */
function rcfs_deactivate(): void {
	remove_filter( 'category_rewrite_rules', 'rcfs_category_rewrite_rules' );
	rcfs_flush_rules();
}

/**
 * Strip the category base from the generated category permastruct so get_category_link() returns the bare slug.
 */
function rcfs_override_permastruct(): void {
	global $wp_rewrite;
	$wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
}

/**
 * Build category rewrite rules without the "/category/" base.
 *
 * Emits root, paged, and feed rules per category, joining parent slugs with "/".
 * Also adds a catch-all rule that maps the old base to the `category_redirect` query var
 * so legacy URLs can be 301'd by rcfs_redirect_old_urls().
 *
 * @param array<string, string> $category_rewrite Existing rules (discarded).
 * @return array<string, string>
 */
function rcfs_category_rewrite_rules( $category_rewrite ): array {
	$rules      = array();
	$categories = get_categories( array( 'hide_empty' => false ) );

	foreach ( $categories as $category ) {
		$slug = $category->slug;

		if ( $category->parent === $category->cat_ID ) {
			$category->parent = 0;
		}

		if ( 0 !== $category->parent ) {
			$slug = get_category_parents( $category->parent, false, '/', true ) . $slug;
		}

		$rules[ '(' . $slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
		$rules[ '(' . $slug . ')/page/?([0-9]{1,})/?$' ]                  = 'index.php?category_name=$matches[1]&paged=$matches[2]';
		$rules[ '(' . $slug . ')/?$' ]                                    = 'index.php?category_name=$matches[1]';
	}

	$old_base = get_option( 'category_base' );
	$old_base = $old_base ? trim( $old_base, '/' ) : 'category';

	$rules[ $old_base . '/(.*)$' ] = 'index.php?category_redirect=$matches[1]';

	return $rules;
}

/**
 * Register the `category_redirect` query var used by the legacy-URL redirect.
 *
 * @param array<int, string> $public_query_vars
 * @return array<int, string>
 */
function rcfs_register_query_vars( $public_query_vars ): array {
	$public_query_vars[] = 'category_redirect';
	return $public_query_vars;
}

/**
 * 301-redirect any request that still uses the old "/category/<slug>/" form to the bare slug URL.
 *
 * @param array<string, mixed> $query_vars
 * @return array<string, mixed>
 */
function rcfs_redirect_old_urls( $query_vars ): array {
	if ( isset( $query_vars['category_redirect'] ) ) {
		$target = trailingslashit( get_option( 'home' ) ) . user_trailingslashit( $query_vars['category_redirect'], 'category' );
		status_header( 301 );
		header( 'Location: ' . esc_url_raw( $target ) );
		exit();
	}

	return $query_vars;
}

/**
 * Re-point Yoast SEO's canonical URL at the bare-slug category link.
 *
 * Yoast computes its own canonical and caches term permalinks in its indexable tables,
 * so it keeps emitting the old "/category/<slug>/" URL even though get_category_link()
 * now returns the bare slug. On category archives, override Yoast's canonical with the
 * link core generates from our overridden permastruct. No-op off category archives and
 * (since the filter never fires) on sites without Yoast.
 *
 * @param string $canonical The canonical URL Yoast computed.
 * @return string
 */
function rcfs_filter_yoast_canonical( $canonical ): string {
	$result = $canonical;

	if ( is_category() ) {
		$category_link = get_category_link( get_queried_object_id() );

		if ( ! is_wp_error( $category_link ) && '' !== $category_link ) {
			$result = $category_link;
		}
	}

	return $result;
}
