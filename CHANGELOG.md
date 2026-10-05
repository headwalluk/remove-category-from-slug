# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-10-05

### Added
- In-plugin GitHub updater: new releases are offered on the Plugins screen. Disable with the `rcfs_updater_enabled` filter.
- `uninstall.php`, which removes the updater's cached release data.
- Release workflow refuses to build unless the plugin header, `RCFS_VERSION` and the `readme.txt` stable tag match the tag.
- `docs/` for site owners and developers, `SECURITY.md`, and `phpcs.xml`.

### Fixed
- A request with `?category_redirect[]=` no longer causes a fatal error (HTTP 500). Since 1.0.0.
- `rcfs_filter_yoast_canonical()` no longer throws a `TypeError` when another `wpseo_canonical` callback returns `null`. Since 1.0.1.
- A category whose parent chain cannot be resolved is served at its bare slug instead of causing a fatal error while rules are built. Since 1.0.0.

### Changed
- Minimum PHP version raised from 8.0 to 8.2; earlier versions are end-of-life.
- The legacy-URL redirect uses `wp_safe_redirect()` and `home_url()`.

## [1.0.1] - 2026-06-28

### Fixed
- Yoast SEO canonical URLs on category archives now use the bare-slug form instead of the legacy `/category/<slug>/` URL, via the `wpseo_canonical` filter.

## [1.0.0] - 2026-04-27

### Added
- Initial release.
- Override category permastruct to remove the `/category/` base from generated permalinks.
- Custom `category_rewrite_rules` filter emitting root, paged, and feed rules per category, with nested-category support.
- 301 redirect from legacy `/category/<slug>/` URLs to the new bare-slug form via a `category_redirect` query var.
- Automatic rewrite-rule flush on activation, deactivation, and category create/edit/delete.
