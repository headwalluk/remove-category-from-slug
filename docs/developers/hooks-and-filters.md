# Hooks and filters

## `rcfs_updater_enabled`

Whether the GitHub updater checks for new releases. Since 1.1.0.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$enabled` | `bool` | Default `true` |

```php
add_filter( 'rcfs_updater_enabled', '__return_false' );
```

See [Updates](../updates.md).

## Removing the plugin's own callbacks

The plugin hooks named global functions, so any of them can be removed with
`remove_filter()` / `remove_action()`. For example, to keep Yoast SEO's own canonical on
category archives:

```php
add_action( 'plugins_loaded', function () {
	remove_filter( 'wpseo_canonical', 'rcfs_filter_yoast_canonical' );
} );
```

| Hook | Callback |
|------|----------|
| `init` | `rcfs_override_permastruct` |
| `category_rewrite_rules` | `rcfs_category_rewrite_rules` |
| `query_vars` | `rcfs_register_query_vars` |
| `request` | `rcfs_redirect_old_urls` |
| `wpseo_canonical` | `rcfs_filter_yoast_canonical` |
| `created_category`, `edited_category`, `delete_category` | `rcfs_flush_rules` |

Flush rewrite rules (`wp rewrite flush`) after removing a rewrite-related callback.

## Query var

`category_redirect` is registered as a public query var. Any request carrying it as a
non-empty string is 301-redirected to that path under the site's home URL.
