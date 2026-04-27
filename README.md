# Remove Category from Slug

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)

A small, dependency-free WordPress plugin that strips the `/category/` base from category archive URLs.

```
/category/news/            →  /news/
/category/parent/child/    →  /parent/child/
/category/news/page/2/     →  /news/page/2/
/category/news/feed/       →  /news/feed/
```

Old `/category/...` URLs are 301-redirected to the new form, so links and SEO carry over.

No settings page, no telemetry, no third-party SDKs. The whole plugin is one file, ~80 lines.

## How it works

Three WordPress rewrite hooks do all the work:

1. **`init`** — overrides the category permastruct to `%category%` so `get_category_link()` returns the bare slug.
2. **`category_rewrite_rules`** — rebuilds the rule set, emitting root, paged, and feed rules per category. Parent slugs are joined with `/`. A catch-all rule maps any leftover `/<old_base>/...` request into a `category_redirect` query var.
3. **`request`** — sees `category_redirect` and issues a 301 to the new URL.

Rewrite rules are flushed automatically on activation, deactivation, and whenever a category is created, edited, or deleted.

## Installation

1. Copy the plugin folder to `wp-content/plugins/`.
2. Activate it from the Plugins screen.

## Development

PHP_CodeSniffer with WordPress Coding Standards:

```bash
phpcs              # Check
phpcbf             # Auto-fix
phpcs              # Re-check
```

See [`CLAUDE.md`](CLAUDE.md) for architecture notes and project conventions.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).
