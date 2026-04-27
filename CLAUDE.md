# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this plugin is

A small WordPress plugin that removes the `/category/` base from category archive URLs (e.g. `/category/news/` → `/news/`). It is intentionally minimal — a few hooks, no admin UI, no telemetry, no SDK dependencies. If a change pulls in vendor libraries, settings pages, or admin notices, that is a sign the scope has drifted.

The reference implementation we are deliberately *not* copying is at `/var/www/bench1.local/web/wp-content/plugins/remove-category-url/`. It does the same job but bundles the Themeisle SDK, Black Friday promo filters, and review-prompt nags. Read its `remove-category-url.php` for the rewrite logic, ignore everything else.

## Architecture

The plugin is built on three WordPress rewrite hooks:

1. **`init` → override the category permastruct.** Setting `$wp_rewrite->extra_permastructs['category']['struct'] = '%category%'` strips the base from generated category permalinks (so `get_category_link()` returns the bare slug).
2. **`category_rewrite_rules` filter → rebuild rules per category.** Iterate `get_categories()` and emit three rules per category (root, paged, feed) that match the bare slug. Parent slugs are joined with `/` via `get_category_parents()`.
3. **301 redirect for old URLs.** Add a `category_redirect` query var, then catch any leftover `/<old_base>/(.*)` requests in the `request` filter and redirect to the new URL. This preserves SEO and bookmarks.

Rewrite rules are cached, so they must be flushed on activation, deactivation, and whenever a category is created/edited/deleted (`created_category`, `edited_category`, `delete_category`).

WPML support is intentionally omitted. WP version checks (the old `>= 3.4` branch in the reference plugin) are also omitted — we target modern PHP/WP only.

## Conventions

The authoritative standards doc is `.github/copilot-instructions.md` — read it before writing code. Key rules that apply here:

- **PHP 8.0+**, but **no `declare(strict_types=1);`** (breaks WP hook interop).
- **Single-entry single-exit functions** — one `return` at the end, accumulate into a variable.
- **Function prefix `rcfs_`** for global functions in this plugin (matches the `remove-category-from-slug` slug). If a `pwpl/` directory ever appears, treat it as sealed third-party code per the standards doc.
- **Translation-ready** with text domain `remove-category-from-slug`.
- **No inline HTML/JS** in PHP — but this plugin currently has no output, so the rule is mostly precautionary.

For anything beyond the single main file (settings UI, custom tables, admin tabs, etc.), check `dev-notes/patterns/` for the project's house pattern before inventing one.

## Distribution

`.distignore` controls what ships in the WordPress.org zip. `dev-notes/`, `.github/`, `phpcs.xml`, `CLAUDE.md`, and `README.md` are dev-only and excluded. Anything that should ship must live outside those paths.

## Commands

```bash
phpcs              # Check WordPress Coding Standards
phpcbf             # Auto-fix what's fixable
phpcs              # Re-check after auto-fix
```

There is no build step, no test suite, and no package manager wired up yet. If you add one, document it here.

## Commit messages

Conventional-commit prefixes — `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`, `style:`, `test:`. Title under 50 chars, body as bullet points explaining *why*. Full workflow in `dev-notes/workflows/commit-to-git.md`.
