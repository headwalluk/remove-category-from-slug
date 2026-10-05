<?php
/**
 * Plugin Name:       Remove Category from Slug
 * Plugin URI:        https://github.com/headwalluk/remove-category-from-slug
 * Description:       Removes the "/category/" base from category archive URLs and 301-redirects the old URLs.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Paul Faulkner
 * Author URI:        https://headwall-hosting.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       remove-category-from-slug
 *
 * @package Remove_Category_From_Slug
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

define( 'RCFS_VERSION', '1.1.0' );
define( 'RCFS_FILE', __FILE__ );
define( 'RCFS_PATH', plugin_dir_path( __FILE__ ) );
define( 'RCFS_BASENAME', plugin_basename( __FILE__ ) );

require_once RCFS_PATH . 'constants.php';

// GitHub auto-updates (admin, cron and WP-CLI only — no need to load on front-end requests).
if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once RCFS_PATH . 'includes/class-github-updater.php';
	new Remove_Category_From_Slug\Github_Updater();
}

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
 *
 * @since 1.0.0
 */
function rcfs_flush_rules(): void {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

/**
 * On deactivation, drop our custom rules before flushing so WordPress regenerates the defaults.
 *
 * @since 1.0.0
 */
function rcfs_deactivate(): void {
	remove_filter( 'category_rewrite_rules', 'rcfs_category_rewrite_rules' );
	rcfs_flush_rules();
}

/**
 * Strip the category base from the generated category permastruct so get_category_link() returns the bare slug.
 *
 * @since 1.0.0
 */
function rcfs_override_permastruct(): void {
	global $wp_rewrite;
	$wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
}

/**
 * Build category rewrite rules without the "/category/" base.
 *
 * Emits root, paged and feed rules per category, joining parent slugs with "/", plus a
 * catch-all that maps the old base to QUERY_VAR_REDIRECT for rcfs_redirect_old_urls().
 *
 * @since 1.0.0
 *
 * @param mixed $category_rewrite Existing rules (discarded).
 * @return array<string, string>
 */
function rcfs_category_rewrite_rules( mixed $category_rewrite ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Filter signature; core's rules are replaced, not extended.
	$rules      = array();
	$categories = get_categories( array( 'hide_empty' => false ) );

	foreach ( $categories as $category ) {
		$slug = $category->slug;

		if ( $category->parent === $category->cat_ID ) {
			$category->parent = 0;
		}

		if ( 0 !== $category->parent ) {
			$parent_path = get_category_parents( $category->parent, false, '/', true );

			// A broken parent chain returns WP_Error; serve the category at its bare slug.
			if ( is_string( $parent_path ) ) {
				$slug = $parent_path . $slug;
			}
		}

		$rules[ '(' . $slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
		$rules[ '(' . $slug . ')/page/?([0-9]{1,})/?$' ]                  = 'index.php?category_name=$matches[1]&paged=$matches[2]';
		$rules[ '(' . $slug . ')/?$' ]                                    = 'index.php?category_name=$matches[1]';
	}

	$old_base = (string) get_option( 'category_base' );
	$old_base = '' !== $old_base ? trim( $old_base, '/' ) : Remove_Category_From_Slug\DEFAULT_CATEGORY_BASE;

	$rules[ $old_base . '/(.*)$' ] = 'index.php?' . Remove_Category_From_Slug\QUERY_VAR_REDIRECT . '=$matches[1]';

	return $rules;
}

/**
 * Register the query var used by the legacy-URL redirect.
 *
 * @since 1.0.0
 *
 * @param mixed $public_query_vars Public query var names.
 * @return mixed The list with our query var added, or the input unchanged if it isn't an array.
 */
function rcfs_register_query_vars( mixed $public_query_vars ): mixed {
	if ( is_array( $public_query_vars ) ) {
		$public_query_vars[] = Remove_Category_From_Slug\QUERY_VAR_REDIRECT;
	}

	return $public_query_vars;
}

/**
 * 301-redirect any request that still uses the old "/category/<slug>/" form to the bare slug URL.
 *
 * @since 1.0.0
 *
 * @param mixed $query_vars Parsed query vars.
 * @return mixed The query vars, without ours if it held anything other than a path.
 */
function rcfs_redirect_old_urls( mixed $query_vars ): mixed {
	$redirect_path = is_array( $query_vars ) ? ( $query_vars[ Remove_Category_From_Slug\QUERY_VAR_REDIRECT ] ?? null ) : null;

	if ( null === $redirect_path ) {
		// Not a legacy category URL.
	} elseif ( is_string( $redirect_path ) && '' !== $redirect_path ) {
		$target = trailingslashit( home_url() ) . user_trailingslashit( $redirect_path, 'category' );
		wp_safe_redirect( $target, 301 );
		exit();
	} else {
		// The query var is public, so "?category_redirect[]=" arrives as an array.
		unset( $query_vars[ Remove_Category_From_Slug\QUERY_VAR_REDIRECT ] );
	}

	return $query_vars;
}

/**
 * Re-point Yoast SEO's canonical URL at the bare-slug category link.
 *
 * Yoast caches term permalinks in its indexables, so it keeps emitting "/category/<slug>/".
 * See docs/how-it-works.md.
 *
 * @since 1.0.1
 *
 * @param mixed $canonical The canonical URL Yoast computed, or false to omit it.
 * @return mixed The bare-slug link on category archives, otherwise $canonical unchanged.
 */
function rcfs_filter_yoast_canonical( mixed $canonical ): mixed {
	$result = $canonical;

	if ( is_category() ) {
		$category_link = get_category_link( get_queried_object_id() );

		if ( is_string( $category_link ) && '' !== $category_link ) {
			$result = $category_link;
		}
	}

	return $result;
}
