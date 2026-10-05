# Remove Category from Slug

[![Version](https://img.shields.io/github/v/release/headwalluk/remove-category-from-slug?label=version&color=blue)](https://github.com/headwalluk/remove-category-from-slug/releases/latest)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0+-21759B.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/license-GPL--2.0+-green.svg)](LICENSE)
[![Coding Standards](https://img.shields.io/badge/WordPress-Coding%20Standards-blue.svg)](https://github.com/WordPress/WordPress-Coding-Standards)

A small, dependency-free WordPress plugin that strips the `/category/` base from category archive URLs.

```
/category/news/            →  /news/
/category/parent/child/    →  /parent/child/
/category/news/page/2/     →  /news/page/2/
/category/news/feed/       →  /news/feed/
```

Old `/category/...` URLs are 301-redirected to the new form, so links and SEO carry over.

No settings page, no telemetry, no third-party SDKs.

## Install

1. Download `remove-category-from-slug.zip` from the [latest release](https://github.com/headwalluk/remove-category-from-slug/releases/latest)
2. WordPress admin → Plugins → Add New → Upload Plugin → choose the zip → Install Now → Activate

From 1.1.0 the plugin updates itself from GitHub Releases. See [Updates](docs/updates.md).

## Documentation

See [`docs/`](docs/README.md):

- [How it works](docs/how-it-works.md) — rewrite rules, redirects, Yoast SEO, slug collisions
- [Updates](docs/updates.md) — the GitHub updater and how to turn it off
- [Hooks and filters](docs/developers/hooks-and-filters.md)

## Development

PHP_CodeSniffer with WordPress Coding Standards, configured in `phpcs.xml`:

```bash
phpcs              # Check
phpcbf             # Auto-fix
phpcs              # Re-check
```

See [`CLAUDE.md`](CLAUDE.md) for architecture notes and project conventions.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).
