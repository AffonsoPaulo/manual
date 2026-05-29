---
title: Caching
description: How the manifest and page cache layers work, and how to configure and control them.
order: 1
key: advanced.caching
---

# Caching

Servera Manual uses two independent cache layers to keep responses fast without needing a dedicated cache server or queue worker.

---

## The manifest cache

The manifest cache stores the entire scanned document graph for the source directory: every document descriptor (title, route, front matter, headings, plain text), the navigation tree, route lookup tables, and the search index payload.

**Cache key:** derived from the `source_path` value and an **inventory signature** — a hash of every file path and its modification time (`mtime`) in the source directory.

**Automatic invalidation:** any file added, removed, or saved invalidates the manifest on the next request. The page is re-scanned transparently — no manual intervention is required in development.

---

## The page cache

Each rendered document is cached individually, keyed on:

- The manifest signature (see above).
- The document's relative path.
- The file's modification time (`mtime`).

Because every page cache key includes the manifest signature, **any file change rebuilds the manifest and effectively invalidates all page caches**. Each page is then re-rendered the next time it is requested, or all at once by running `manual:build`.

The search index is cached under a similar key and rebuilt alongside the manifest.

---

## Cache configuration

```php
// config/manual.php
'cache_store' => env('MANUAL_CACHE_STORE'),
'cache_ttl'   => 3600,
```

### `cache_store`

The Laravel cache store to use. `null` (the default) falls back to the application's default store. To use a dedicated store:

```env
MANUAL_CACHE_STORE=redis
```

Using a persistent store like Redis or Memcached in production means the cache survives deployments (until `manual:build` or `manual:clear` is run).

### `cache_ttl`

How long items remain in cache before expiring.

| Value | Behaviour |
|---|---|
| `3600` (default) | Items expire after one hour and are re-rendered on the next request. |
| `null` | Items never expire. Invalidated only by file changes or `manual:clear`. |
| `0` or negative | Cache is bypassed entirely. Every request re-renders. |

**For local development**, set a negative TTL so every page reload reflects your edits immediately:

```php
'cache_ttl' => -1,
```

Or point `MANUAL_CACHE_STORE` to the `array` driver so the cache lives only for the duration of the request:

```env
MANUAL_CACHE_STORE=array
```

---

## Artisan commands

### `manual:build`

Scans the source directory, validates all routes, renders every page, and warms both cache layers. Run this after each deployment:

```bash
php artisan manual:build
```

Output:

```
Manual build complete: 15 documents scanned, 14 visible, 14 cached pages, 14 search documents.
```

The command fails with a non-zero exit code if any document has invalid front matter or a duplicate route, making it safe to use in CI pipelines.

### `manual:clear`

Flushes every cache key managed by the package. The next request triggers a full re-scan and re-render:

```bash
php artisan manual:clear
```

Use this when you want to force a full rebuild without waiting for the TTL to expire, or after changing configuration values that affect rendering.

---

## Recommended production setup

1. Set `cache_store` to a persistent driver (`redis`, `memcached`, or `database`).
2. Set `cache_ttl` to `null` so pages are only re-rendered when files change or `manual:build` is run.
3. Add `php artisan manual:build` to your deployment script so the cache is always warm after each release.
