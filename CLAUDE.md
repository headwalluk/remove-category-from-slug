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

<!-- wp-translate:begin v=1.1.0 hash=5fd2266ac6337816876ee09513455ef4200bf8885d6e560e9c681865de5ab064 -->
## Translating this plugin (wp-translate conventions)

This plugin's `.po`/`.mo` files are generated from source by
[wp-translate](https://github.com/headwalluk/wp-translate-tool), which
machine-translates strings with DeepL. Machine translation is only as good as
the strings you give it — follow these conventions when adding or editing
user-facing text.

### 1. Disambiguate short or ambiguous strings with `_x()`

DeepL handles full sentences well but guesses badly on short, context-free
labels. Give it context with `_x()` (or `esc_html_x()`, `_ex()`):

```php
// Ambiguous out of context — DeepL may read "Sent" as "late", "Folder" as "leaflet"
__( 'Sent', 'remove-category-from-slug' );

// Disambiguated — the context is passed to the translator and to DeepL
_x( 'Sent', 'email delivery status', 'remove-category-from-slug' );
_x( 'Folder', 'IMAP mailbox', 'remove-category-from-slug' );
_x( 'Open', 'verb; button label', 'remove-category-from-slug' );
```

The context (2nd argument) is never shown to users. Use it whenever a string is a
single word, a short label, or has more than one plausible meaning.

### 2. Use placeholders, never concatenation

Build dynamic text with `printf`/`sprintf` so the whole sentence translates as a
unit, and add a `translators:` comment to explain each placeholder:

```php
/* translators: %s is the user's display name */
printf( esc_html__( 'Welcome back, %s', 'remove-category-from-slug' ), $name );
```

Never split a sentence across multiple translation calls — word order differs
between languages.

### 3. Acronyms and technical tokens

wp-translate keeps common acronyms (`TLS`, `API`, `SMTP`, `URL`, `ID`, `UTC`, …)
verbatim automatically. If you introduce an unusual acronym or product name that
must not be translated, keep it as its own standalone string so it is recognised,
or ask the maintainer to add it to the tool's acronym list.

### 4. Don't translate dates — let WordPress localise them

Never add month or day-of-week names (full or abbreviated) as translatable
strings. DeepL frequently mistranslates short forms like `Mon`, `Tue`, `Jan`,
`Feb` even with context hints. WordPress already ships locale-aware names — use
`$wp_locale`:

```php
global $wp_locale;
$wp_locale->get_month( $month_number );        // "January" (1-based)
$wp_locale->get_month_abbrev( $month_name );   // "Jan"
$wp_locale->get_weekday( $weekday_number );     // "Monday" (0 = Sunday)
$wp_locale->get_weekday_abbrev( $weekday_name ); // "Mon"
```

For formatted dates, prefer `wp_date()` / `date_i18n()`, which localise month and
day names automatically.

### 5. English source dialect

Write source strings in standard English. wp-translate handles English targets
locally (no DeepL): `en`/`en_US` use the source as-is, and `en_GB`/`en_AU`/… get
American spellings converted to British automatically (`color` → `colour`).

### Running wp-translate

After changing strings, regenerate translations:

```bash
wp-translate /path/to/this-plugin              # auto-detect locales from languages/
wp-translate /path/to/this-plugin en_GB,fr_FR  # explicit locales
wp-translate /path/to/this-plugin --dry-run    # preview; no API calls, no writes
```

Requires WP-CLI (`wp`) and a DeepL API key at `~/.config/deepl.env`. The tool
regenerates the `.pot` from source, translates new/changed strings for each
locale, and compiles the `.mo` files.
<!-- wp-translate:end -->
