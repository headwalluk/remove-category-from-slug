# How it works

The plugin has no settings. Activate it and category archives move from `/category/<slug>/`
to `/<slug>/`:

```
/category/news/            →  /news/
/category/parent/child/    →  /parent/child/
/category/news/page/2/     →  /news/page/2/
/category/news/feed/       →  /news/feed/
```

## Generated links

On `init` the plugin changes the category permastruct from `category/%category%` to
`%category%`. Everything that builds a category URL through WordPress — `get_category_link()`,
menus, widgets, sitemaps — produces the bare-slug form.

## Rewrite rules

WordPress's own category rules all start with the category base, so the plugin replaces them.
For every category, including empty ones, it emits three rules: the archive, its paged form, and
its feeds. A child category's rules include its parents' slugs, so `child` under `parent` is
served at `/parent/child/`.

Because the rules list each category by name, they are rebuilt whenever a category is created,
edited or deleted, and on activation and deactivation.

## Redirecting old URLs

One more rule catches anything still under the old base (`/category/...`, or your custom
category base if you set one under **Settings → Permalinks**) and 301-redirects it to the same
path without the base. Bookmarks, inbound links and search-engine rankings carry over.

## Yoast SEO

Yoast SEO works out its own canonical URL and caches term permalinks in its indexable tables,
so it would keep printing `/category/<slug>/` as the canonical on category archives even though
every other link has changed. On category archives the plugin replaces Yoast's canonical with
the link WordPress now generates. Off category archives, and on sites without Yoast, this does
nothing.

## Slug collisions

A category and a page can share a slug — `news` the category and `news` the page. Without a
base in the URL, both want `/news/`, and whichever rule WordPress matches first wins. The
plugin does not try to resolve this: rename one of them.

## Deactivation and removal

Deactivating removes the custom rules and restores WordPress's defaults, so category URLs go
back to `/category/<slug>/`. The plugin stores no settings; deleting it removes only the
updater's cached release data.
