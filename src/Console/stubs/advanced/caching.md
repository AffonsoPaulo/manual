---
title: Caching
description: Understand the manifest cache, page cache, and the build and clear commands.
---

# Caching

The package maintains two cache layers.

## Manifest Cache

The manifest cache stores the scanned document graph, route lookup tables, and navigation tree. Its key is derived from the source path and an inventory signature that includes every file path and modification time. Any file added, removed, or modified invalidates the manifest.

## Page and Search Cache

Rendered HTML pages and the search index payload are cached separately. Each key includes the manifest signature, the document's relative path, its modification time, and a fingerprint of the active Laravel routes and URL defaults.

## Useful Commands

```bash
php artisan manual:build   # warm manifest, all page caches, and search index
php artisan manual:clear   # flush every tracked cache key
```

## TTL and Store Configuration

```php
'cache_store' => env('MANUAL_CACHE_STORE'),  // null → default Laravel store
'cache_ttl'   => 3600,                       // seconds; null → store forever; 0 or negative → bypass cache
```

- Set `cache_ttl` to `null` when you want the cache to persist indefinitely and invalidate only through `manual:clear` or a file change.
- Set `cache_ttl` to `0` or a negative integer to disable caching entirely, which is useful during local development.
