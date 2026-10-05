=== Remove Category from Slug ===
Contributors: headwalluk
Tags: category, permalinks, rewrite, slug, seo
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Removes the "/category/" base from category archive URLs and 301-redirects the old URLs.

== Description ==

A small, dependency-free plugin that strips the `/category/` base from category archive URLs.

* `/category/news/` becomes `/news/`
* `/category/parent/child/` becomes `/parent/child/`
* Paged and feed URLs work as expected (`/news/page/2/`, `/news/feed/`)
* Old `/category/...` URLs are 301-redirected to the new form so links and SEO are preserved

No settings page, no telemetry, no third-party SDKs.

Distributed via GitHub releases with in-plugin auto-updates from [headwalluk/remove-category-from-slug](https://github.com/headwalluk/remove-category-from-slug). The plugin is **not** listed on wordpress.org — install via the GitHub release zip.

**Full documentation lives in the [GitHub repository](https://github.com/headwalluk/remove-category-from-slug)** — see the [`docs/`](https://github.com/headwalluk/remove-category-from-slug/tree/main/docs) directory.

== Installation ==

1. Download `remove-category-from-slug.zip` from the [latest release](https://github.com/headwalluk/remove-category-from-slug/releases/latest) and upload it through Plugins → Add New → Upload Plugin.
2. Activate the plugin through the Plugins screen.

That's it. Rewrite rules are flushed automatically on activation and whenever a category is created, edited, or deleted.

== Frequently Asked Questions ==

= Will this break my existing links? =

No. Requests to the old `/category/<slug>/` URLs are 301-redirected to the new bare-slug URLs.

= Does it support nested categories? =

Yes. A child category under `parent` is served at `/parent/child/`.

= Does it conflict with pages or posts that share a category slug? =

WordPress matches rewrite rules in order. If you have a page named `news` and a category also slugged `news`, you will need to rename one of them. This plugin does not change WordPress's slug-collision behaviour.

= How do I get updates? =

From 1.1.0 the plugin checks GitHub for new releases and offers them on the Plugins screen like any other update. To turn this off, add `add_filter( 'rcfs_updater_enabled', '__return_false' );` to a must-use plugin.

= What happens on deactivation? =

The custom rewrite rules are removed and WordPress regenerates the default rules. Your category URLs revert to `/category/<slug>/`.

== Changelog ==

= 1.1.0 =
* Added: in-plugin GitHub updater. Disable with the `rcfs_updater_enabled` filter.
* Fixed: a request with `?category_redirect[]=` no longer causes a fatal error.
* Fixed: no `TypeError` when another plugin's `wpseo_canonical` callback returns `null`.
* Fixed: a category with a broken parent chain no longer breaks rewrite-rule generation.
* Changed: minimum PHP version is now 8.2.

= 1.0.1 =
* Fixed: Yoast SEO canonical URLs on category archives now use the bare-slug form instead of the legacy `/category/<slug>/` URL.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Requires PHP 8.2. Fixes a fatal error triggered by a crafted query string, and adds automatic updates from GitHub. Sites on 1.0.x must install this release manually once.

= 1.0.1 =
Corrects the Yoast SEO canonical URL on category archives to the bare-slug form.

= 1.0.0 =
Initial release.
