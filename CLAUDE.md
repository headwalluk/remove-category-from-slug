# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Remove Category from Slug removes the `/category/` base from category archive URLs
(`/category/news/` → `/news/`) and 301-redirects the old URLs. It is intentionally minimal: a
few rewrite hooks, no settings, no admin UI, no telemetry, no SDKs. If a change pulls in vendor
libraries, settings pages or admin notices, the scope has drifted.

- **Function prefix:** `rcfs_` for the global hook callbacks
- **Namespace:** `Remove_Category_From_Slug` for constants and classes
- **Text Domain:** `remove-category-from-slug` (no user-facing strings today)
- **PHP:** 8.2+ (do NOT use `declare(strict_types=1)` — breaks WordPress interop)
- **WordPress:** 6.0+
- **No build system** — no npm, no Composer, no bundler

The reference implementation we are deliberately *not* copying is at
`/var/www/bench1.local/web/wp-content/plugins/remove-category-url/`. Read its
`remove-category-url.php` for the rewrite logic and ignore everything else (Themeisle SDK,
promo filters, review nags). WPML support and old WordPress version branches are omitted on
purpose.

House conventions come from the maintainer's reference plugin, `quick-2fa`. Where this file is
silent, its `CLAUDE.md` is the standard.

This plugin is published publicly on GitHub. Tracked files must contain no client names,
client URLs, fleet measurements or client data of any kind — in code, comments, docs,
fixtures or commit messages.

`dev-notes/` is **private and untracked** (`.gitignore`), backed up with the dev site, and
blocked from the web by `dev-notes/.htaccess`. Never copy content from `dev-notes/` into a
tracked file without scrubbing it, and never reference a `dev-notes/` path from a file that
ships in the release zip.

## Commands

```bash
phpcs              # Check WordPress Coding Standards (configured in phpcs.xml)
phpcbf             # Auto-fix coding standards violations
phpcs              # Re-check: no errors and no warnings
```

```bash
wp-translate . --check-instructions   # Is the block at the end of this file still current?
wp-translate . --sync-instructions    # Update it; review the diff afterwards
wp-translate . --dry-run              # Preview; no DeepL calls, no writes
```

Never hand-edit inside the `wp-translate:begin`/`end` markers — the block is hash-validated.

## Testing

There is no unit-test framework and none is wanted. Exercise behaviour against the live dev
site with WP-CLI and curl:

```bash
wp rewrite flush
wp rewrite list --match=/news/
curl -sk -o /dev/null -w '%{http_code} %{redirect_url}\n' https://devx.headwall.tech/category/uncategorized/
curl -sk -o /dev/null -w '%{http_code}\n' 'https://devx.headwall.tech/?category_redirect%5B%5D=x'   # must not be 500
wp eval 'var_dump( rcfs_filter_yoast_canonical( null ) );'                                         # defensive path
```

Test the **defensive** path as well as the happy one, and reset any state you touch
(`wp transient delete rcfs_github_release`).

## Architecture

### Entry Point

`remove-category-from-slug.php` defines `RCFS_VERSION`, `RCFS_FILE`, `RCFS_PATH` and
`RCFS_BASENAME`, loads `constants.php`, registers the hooks and holds the `rcfs_` callbacks. The
GitHub updater is loaded only in admin, cron and WP-CLI requests.

### Hook Strategy

1. **`init` → override the category permastruct.** `$wp_rewrite->extra_permastructs['category']['struct'] = '%category%'` makes `get_category_link()` return the bare slug.
2. **`category_rewrite_rules` → rebuild rules per category.** Three rules per category (root, paged, feed) on the bare slug, with parent slugs joined by `/` via `get_category_parents()`.
3. **301 redirect for old URLs.** A catch-all rule maps `/<old_base>/(.*)` to the `category_redirect` query var; the `request` filter redirects it with `wp_safe_redirect()`.
4. **`wpseo_canonical`** — on category archives, replaces Yoast SEO's cached `/category/` canonical with `get_category_link()`.

Rewrite rules are flushed on activation, deactivation, and `created_category` / `edited_category` / `delete_category`.

### Key Files

| File | Purpose |
|------|---------|
| `remove-category-from-slug.php` | Plugin header, version constant, hook registration, rewrite/redirect/canonical callbacks |
| `constants.php` | Namespaced constants: query var, default category base, updater settings |
| `includes/class-github-updater.php` | In-plugin updater: checks GitHub Releases and feeds the WordPress update transient. Holds the `log()` / `log_error()` split |
| `uninstall.php` | Deletes the updater's cached release transients. The plugin stores nothing else |

## Public Contracts

Treat these as contracts whether or not they are documented:

| Contract | Breaks when |
|----------|-------------|
| `rcfs_*` callback names | renamed. Sites may `remove_filter()` them (see `docs/developers/hooks-and-filters.md`) |
| `rcfs_updater_enabled` filter | renamed or removed |
| `category_redirect` query var | renamed. It is public, so it also arrives from query strings |
| Generated URLs (`/<parent>/<child>/`, paged, feed) | changed. Every inbound link and search ranking depends on them |
| Transient keys `rcfs_github_release`, `rcfs_github_failed` | a constant's **value** changes; old caches are orphaned |

Take the additive path, deprecate rather than rename (`apply_filters_deprecated()`), and record
any change here in `CHANGELOG.md`. If you can't tell whether outside code depends on something,
stop and ask.

### Where the Plugin Runs

- **Request context.** Callbacks run under WP-CLI, cron, AJAX and REST as well as browser requests. Check for what the code needs
- **Install layout.** Build URLs with `home_url()`, `get_category_link()` and friends, never by joining strings onto the domain
- **Multisite is untested.** Rules are per-site; nothing is network-wide

## Code Conventions

### PHP Style

- **No `declare(strict_types=1)`** — breaks WordPress interop
- **Single-Entry Single-Exit:** one `return` at the end, accumulating into a variable. Top-of-function guard clauses are acceptable; `return` mid-function, inside loops or nested deep is not
- **An `if` with `elseif` branches ends in a plain `else`**; a no-op branch is written out with a short comment. `phpcs.xml` allows the comment-only branches
- **No assignment inside a condition**
- **No unreachable `return`** after a call that always `exit()`s
- **Constants for magic strings/numbers** in `constants.php`. A key constant's **value** is stored data and never changes
- **Type hints and return types** on all functions and properties, within PHP 8.2. Check newer syntax against that floor by hand — nothing enforces it mechanically
- **Callbacks on hooks the plugin doesn't own take `mixed`** and return `mixed` for filters, passing through any value they can't use. A typed parameter turns a sloppy third-party callback into a `TypeError` on a page we don't control. `rcfs_filter_yoast_canonical()` is the pattern
- **Check what WordPress returns.** `get_category_parents()` and `get_category_link()` can return `WP_Error`; test with `is_string()` before concatenating

### Logging

See `Github_Updater::log()` / `log_error()`: `log_error()` for genuine failures, always logged;
`log()` for routine tracing, only under `WP_DEBUG`. Never hide an error behind a debug flag,
and never leave a `catch` that records nothing. No logging dependency — `error_log()` with a
`phpcs:ignore` is correct.

### Comments

- One-line docblock summary per function, saying what it does
- Inline comments only where the mechanism isn't obvious
- Reasoning and history go in `docs/`, not in comments; reference the doc from the code

### Commit Messages

Conventional-commit prefixes — `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`, `style:`,
`test:`. Title under 50 chars, body as bullet points explaining *why*.

### Pre-Commit Workflow

1. `phpcs` — check violations
2. `phpcbf` — auto-fix
3. `phpcs` — verify clean: no errors **and no warnings**
4. Stage and commit

Every `phpcs:ignore` names the exact sniff and ends with `-- reason`.

## Release Workflow

1. Update the version in `remove-category-from-slug.php` — **both** the `Version:` header and `RCFS_VERSION`
2. Update `CHANGELOG.md`: move `[Unreleased]` entries under the new version
3. Update `readme.txt`: stable tag, changelog and upgrade notice
4. Run `phpcs`
5. Tag `vX.Y.Z` and push the tag; `.github/workflows/release.yml` builds the zips and the GitHub Release

The workflow refuses to build unless the header, `RCFS_VERSION` and the `readme.txt` stable tag
all match the tag. **The `Version` must always correspond to a real GitHub Release tag**, or
the updater misbehaves; see `docs/updates.md`.

## Distribution

GitHub Releases only, not wordpress.org (whose plugins may not use a third-party updater).
`.distignore` controls the release zip: `docs/`, `dev-notes/`, `.github/`, `phpcs.xml`,
`CLAUDE.md`, `README.md`, `CHANGELOG.md` and `SECURITY.md` are excluded. Anything that must ship
lives outside those paths.

## Reference Files

`docs/` is tracked and public. **One audience per document**:

- `docs/how-it-works.md`, `docs/updates.md` — site owners and self-hosters
- `docs/developers/hooks-and-filters.md` — the public extension surface

Supporting material (private, untracked):

- `dev-notes/00-project-tracker.md` — milestones, open questions, deferred work
- `CHANGELOG.md` — per-version release notes (tracked)

<!-- wp-translate:begin v=1.2.0 hash=cf9a74bec8090b260eac9ed6c5b76f690565d014b28da2ccd29d235bc9b743f9 -->
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

### 3. Use `_n()` for anything that can be counted

Never build a count-dependent sentence by hand, and never settle for a single
form that reads correctly only for one number. Languages differ in how many
plural forms they have — English and German have two, French treats 0 as
singular, Polish and Russian have three, Japanese has one, Arabic has six — and
`_n()` is the only way to express that.

```php
// Wrong — "1 reviews", and untranslatable into languages with other forms
printf( esc_html__( '%d reviews', 'remove-category-from-slug' ), $count );

// Right — wp-translate fills every form the target locale needs
printf(
    esc_html( _n( '%d review', '%d reviews', $count, 'remove-category-from-slug' ) ),
    $count
);
```

Keep the placeholder in **both** forms, even when the singular reads fine
without it (`'%d review'`, not `'One review'`) — some locales use the singular
slot for other numbers too.

For a short or ambiguous countable noun, use `_nx()` — the plural equivalent of
`_x()` — so the context reaches DeepL:

```php
// "Review" alone is ambiguous: critique? opinion? inspection?
_nx( '%d review', '%d reviews', $count, 'customer feedback on a company', 'remove-category-from-slug' );
```

**Locales needing more than two forms will have their extra slots left empty for
a human translator.** DeepL supplies a singular and a plural; nobody can invent
Polish's third form from those, and wp-translate deliberately leaves it blank
rather than filling it with a plausible guess. Expect to see empty
`msgstr[2]` entries in `pl_PL` — that is correct behaviour, not a failure.

### 4. Acronyms and technical tokens

wp-translate keeps common acronyms (`TLS`, `API`, `SMTP`, `URL`, `ID`, `UTC`, …)
verbatim automatically. If you introduce an unusual acronym or product name that
must not be translated, keep it as its own standalone string so it is recognised,
or ask the maintainer to add it to the tool's acronym list.

### 5. Don't translate dates — let WordPress localise them

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

### 6. English source dialect

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
