=== Remove Category from Slug ===
Contributors: paulfaulkner
Tags: category, permalinks, rewrite, slug, seo
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.1
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

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install through the Plugins screen in WordPress.
2. Activate the plugin through the Plugins screen.

That's it. Rewrite rules are flushed automatically on activation and whenever a category is created, edited, or deleted.

== Frequently Asked Questions ==

= Will this break my existing links? =

No. Requests to the old `/category/<slug>/` URLs are 301-redirected to the new bare-slug URLs.

= Does it support nested categories? =

Yes. A child category under `parent` is served at `/parent/child/`.

= Does it conflict with pages or posts that share a category slug? =

WordPress matches rewrite rules in order. If you have a page named `news` and a category also slugged `news`, you will need to rename one of them. This plugin does not change WordPress's slug-collision behaviour.

= What happens on deactivation? =

The custom rewrite rules are removed and WordPress regenerates the default rules. Your category URLs revert to `/category/<slug>/`.

== Changelog ==

= 1.0.1 =
* Fixed: Yoast SEO canonical URLs on category archives now use the bare-slug form instead of the legacy `/category/<slug>/` URL.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 =
Corrects the Yoast SEO canonical URL on category archives to the bare-slug form.

= 1.0.0 =
Initial release.
