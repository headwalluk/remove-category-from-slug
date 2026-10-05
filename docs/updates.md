# Updates

The plugin is distributed through
[GitHub Releases](https://github.com/headwalluk/remove-category-from-slug/releases), not the
wordpress.org directory. From 1.1.0 it updates itself: new releases appear on the **Plugins**
screen and in **Dashboard → Updates** like any other plugin update.

Sites on 1.0.x need one manual install of 1.1.0 or later. After that, updates arrive
automatically.

## How it checks

The updater runs only in the admin area, under cron and under WP-CLI — never on front-end
requests. When WordPress checks for plugin updates, it asks the GitHub API for the latest
release of `headwalluk/remove-category-from-slug` and offers the `remove-category-from-slug.zip`
attached to it.

- A successful lookup is cached for 12 hours
- A failed lookup (network error, rate limit, a release with no zip) is cached for 1 hour, so
  an unreachable API doesn't hold up every admin page for the 10-second request timeout
- Both caches are cleared after the plugin updates

The installed version is read from the plugin file's `Version:` header — the value WordPress
itself shows — and not the `RCFS_VERSION` constant. If the two disagree, the updater logs an
error. The release workflow refuses to build a tag unless the header, the constant and the
`readme.txt` stable tag all match it, because a mismatch makes every site see an update that
never goes away.

## Logging

Failures (HTTP errors, malformed responses, a release with no matching zip) are always written
to the PHP error log, prefixed `Remove_Category_From_Slug Github_Updater [error]:`. Routine
tracing (cache hits, "up to date") is logged only when `WP_DEBUG` is on.

## Turning updates off

To stop update checks — on a staging site, or to pin the installed version — add this to a
must-use plugin or your theme's `functions.php`:

```php
add_filter( 'rcfs_updater_enabled', '__return_false' );
```
