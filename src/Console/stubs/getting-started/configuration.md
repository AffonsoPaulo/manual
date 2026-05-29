---
title: Configuration
description: Complete reference for every key in config/manual.php.
order: 2
key: getting-started.configuration
---

# Configuration

Publish the configuration file with:

```bash
php artisan vendor:publish --tag=manual-config
```

This creates `config/manual.php`. The full file with all defaults:

```php
return [
    'source_path'  => 'docs/manual',
    'route_prefix' => 'manual',
    'site_title'   => env('APP_NAME', 'Documentation'),
    'cache_store'  => env('MANUAL_CACHE_STORE'),
    'cache_ttl'    => 3600,
    'view'         => 'manual::page',
    'middleware'   => ['web'],

    'assets' => [
        'enabled' => true,
    ],

    'search' => [
        'enabled'  => true,
        'endpoint' => '_manual/search.json',
    ],

    'images' => [
        'enabled'    => true,
        'path'       => '_images',
        'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'],
    ],
];
```

---

## `source_path`

**Default:** `'docs/manual'`

The directory the package scans for Markdown files. This is the only directory that matters — all other paths are derived from it.

Relative paths are resolved from `base_path()`. Absolute paths are used as-is:

```php
'source_path' => 'docs/manual',            // → {project_root}/docs/manual
'source_path' => storage_path('docs'),     // → absolute path
'source_path' => '/var/www/docs',          // → absolute path
```

> **Change this first.** If your project has docs in a different location, `source_path` is the only thing you need to update.

---

## `route_prefix`

**Default:** `'manual'`

The URL prefix for every route the package registers: documents, images, and the search endpoint.

```php
'route_prefix' => 'docs',    // → /docs, /docs/getting-started, ...
'route_prefix' => '',        // → /, /getting-started, ... (serves at the root)
```

> When set to an empty string, documentation routes are registered at the application root. Make sure they do not conflict with other routes in your application.

---

## `site_title`

**Default:** `env('APP_NAME', 'Documentation')`

The title shown in the browser tab and on 404/500 error pages. By default it reads from your application's `APP_NAME` environment variable.

---

## `cache_store`

**Default:** `env('MANUAL_CACHE_STORE')` (falls back to the application's default cache store)

The Laravel cache store to use for the manifest cache and all page caches. Set `MANUAL_CACHE_STORE` in `.env` to use a dedicated store:

```env
MANUAL_CACHE_STORE=redis
```

Leave unset to use the application's default store. See [Caching](../advanced/caching.md) for details on the two cache layers.

---

## `cache_ttl`

**Default:** `3600` (one hour)

Controls how long cached content is kept before being re-rendered on the next request.

| Value | Behaviour |
|---|---|
| `3600` | Cached for one hour. |
| `null` | Cached forever; only invalidated by a file change or `manual:clear`. |
| `0` or negative | Cache bypassed on every request. Recommended for local development. |

To disable caching during development:

```php
'cache_ttl' => -1,
```

---

## `view`

**Default:** `'manual::page'`

The Blade view used to render every documentation page. Override this after publishing views with `php artisan vendor:publish --tag=manual-views`:

```php
'view' => 'vendor.manual.page',  // your customized view
```

See [Customization](../advanced/customization.md) for the full list of variables passed to the view.

---

## `middleware`

**Default:** `['web']`

The middleware stack applied to **all** routes registered by the package — documents, images, and the search endpoint. To protect documentation behind authentication, add your auth middleware here:

```php
'middleware' => ['web', 'auth'],
```

Any middleware that Laravel supports can be used.

---

## `assets.enabled`

**Default:** `true`

When `true`, the package injects its bundled CSS and JavaScript into the page. Set to `false` if you have fully replaced the default styles and scripts with your own.

---

## `search.enabled`

**Default:** `true`

Enables or disables the JSON search endpoint. When disabled, the endpoint is not registered and the search UI is hidden.

## `search.endpoint`

**Default:** `'_manual/search.json'`

The URL path of the search JSON endpoint, relative to the route prefix. With the default prefix, the endpoint is at `/manual/_manual/search.json`.

> This path is **reserved** while search is enabled — do not create a document at the same path. See [Search](../advanced/search.md) for the full payload structure.

---

## `images.enabled`

**Default:** `true`

Enables or disables image serving. When disabled, requests to image URLs return 404 and `@image/` aliases in Markdown are not rewritten.

## `images.path`

**Default:** `'_images'`

The subdirectory inside `source_path` where image files are stored. Must be a relative path — absolute paths are ignored and the default is used.

## `images.extensions`

**Default:** `['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico']`

The list of file extensions the image controller will serve. Requests for any other extension return 404, regardless of whether the file exists on disk.

```php
'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'avif'],
```
