# Manual

[![Latest Version](https://img.shields.io/packagist/v/affonsopaulo/manual)](https://packagist.org/packages/affonsopaulo/manual)
[![PHP Version](https://img.shields.io/packagist/php-v/affonsopaulo/manual)](https://packagist.org/packages/affonsopaulo/manual)
[![License](https://img.shields.io/packagist/l/affonsopaulo/manual)](https://packagist.org/packages/affonsopaulo/manual)

A Laravel package that turns a directory of Markdown files into a fully-rendered, searchable documentation site — no build step, no Node.js, no database. Drop in your `.md` files, run one Artisan command, and your docs are live.

---

## Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
- [Your First Documentation Page](#your-first-documentation-page)
- [Directory Structure](#directory-structure)
- [Front Matter](#front-matter)
- [Routing](#routing)
- [Navigation](#navigation)
- [Images](#images)
- [Linking Between Pages](#linking-between-pages)
- [Dynamic Helpers](#dynamic-helpers)
- [Search](#search)
- [Caching](#caching)
- [Artisan Commands](#artisan-commands)
- [Configuration Reference](#configuration-reference)
- [Customization](#customization)

---

## Introduction

Manual scans a directory tree of Markdown files and serves them as a styled documentation site — similar to GitBook or Mintlify, but self-hosted inside your Laravel application. Every URL, navigation item, breadcrumb, previous/next link, and search entry is derived automatically from the file system and optional YAML front matter you add to each file.

---

## Installation

Install the package via Composer:

```bash
composer require affonsopaulo/manual
```

The package auto-discovers and registers its service provider. You do not need to add anything to `config/app.php`.

Publish the configuration file when you need to override any default, and publish the compiled assets so browsers can load them:

```bash
php artisan vendor:publish --tag=manual-config
php artisan vendor:publish --tag=manual-assets
```

---

## Your First Documentation Page

The fastest way to get started is to scaffold the default documentation structure:

```bash
php artisan manual:init
```

You will see output like:

```
created _images/
created index.md
created getting-started/index.md
created getting-started/installation.md
...
Manual scaffold complete: 14 created, 0 overwritten, 0 skipped. Run "php artisan manual:build" next.
```

Now warm the cache and build the search index:

```bash
php artisan manual:build
```

Visit `/manual` in your browser — your documentation site is live.

### Creating a single page

To add a page without the full scaffold, use `manual:make` with the path relative to `source_path`:

```bash
php artisan manual:make guides/authentication --title="Authentication Guide" --order=3
```

This creates `docs/manual/guides/authentication.md` with a front matter block and an `# Authentication Guide` heading. See [Artisan Commands](#artisan-commands) for all available options.

---

## Directory Structure

The package derives every URL, breadcrumb, and navigation entry directly from the file and directory layout inside `source_path` (default: `docs/manual`). No routing configuration is required.

```
docs/manual
├── _images/                  ← images live here
├── index.md                  → /manual
├── getting-started/
│   ├── index.md              → /manual/getting-started
│   ├── installation.md       → /manual/getting-started/installation
│   └── configuration.md     → /manual/getting-started/configuration
├── guides/
│   ├── index.md              → /manual/guides
│   ├── front-matter.md       → /manual/guides/front-matter
│   └── routing.md            → /manual/guides/routing
└── advanced/
    ├── index.md              → /manual/advanced
    └── caching.md            → /manual/advanced/caching
```

**The rules are simple:**

- **`index.md`** in any directory represents the URL of that directory. `getting-started/index.md` maps to `/manual/getting-started`.
- **All other `.md` files** map to their filename without the extension. `guides/routing.md` maps to `/manual/guides/routing`.
- **Nesting is unlimited.** Sub-subdirectories follow the same rules recursively.
- The root `index.md` is the home page (`/manual`).

---

## Front Matter

Each Markdown file may begin with a YAML front matter block enclosed in `---` delimiters. Front matter is optional — the package infers sensible defaults from the file name and content when it is absent.

```yaml
---
title: Authentication Guide
slug: auth
url: guides/auth
order: 2
description: Learn how to protect your documentation behind authentication.
key: guides.authentication
hidden: false
---
# Authentication Guide

Your content here.
```

### Available fields

| Field         | Type    | Description                                                                                                                                                          |
| ------------- | ------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `title`       | string  | The page title shown in navigation and the browser tab. Falls back to the first `# h1` heading, then the formatted filename.                                         |
| `slug`        | string  | Replaces only the **last URL segment** while keeping the rest of the path. `guides/installation.md` with `slug: setup` becomes `/manual/guides/setup`.               |
| `url`         | string  | Replaces the **entire relative route path**. `guides/installation.md` with `url: reference/install` becomes `/manual/reference/install` regardless of its directory. |
| `order`       | integer | Controls navigation sort order (ascending). Pages without an `order` value sort alphabetically after ordered pages.                                                  |
| `description` | string  | Short summary shown in search results and used as the excerpt in the search index.                                                                                   |
| `key`         | string  | A stable dot-notation identifier (e.g. `guides.authentication`) for use with the `{{ doc('...') }}` helper.                                                          |
| `hidden`      | boolean | When `true`, the page is excluded from navigation and the search index but remains accessible by its URL. Useful for draft or unlisted pages.                        |

### Title resolution order

When no `title` is set, the package resolves the title in this order:

1. The first `# h1` heading in the Markdown body.
2. The filename formatted as a headline (e.g. `front-matter.md` → `Front Matter`).

---

## Routing

URLs are derived from the file path relative to `source_path`. The route prefix (default: `manual`) is prepended to every URL:

| File                       | URL                           |
| -------------------------- | ----------------------------- |
| `index.md`                 | `/manual`                     |
| `getting-started/index.md` | `/manual/getting-started`     |
| `guides/front-matter.md`   | `/manual/guides/front-matter` |
| `advanced/caching.md`      | `/manual/advanced/caching`    |

To customize how a specific document's URL is derived, use the `slug` or `url` front matter fields — see [Front Matter](#front-matter).

### Changing the route prefix

Update `route_prefix` in `config/manual.php` to serve documentation at a different base URL:

```php
'route_prefix' => 'docs',
```

To serve at the application root, set it to an empty string: `'route_prefix' => ''`.

---

## Navigation

The sidebar is built automatically from the directory structure. Directories become sections, `index.md` files become their landing pages, and all other documents become leaf entries.

Pages are sorted by `order` (ascending) first, then alphabetically. To order a whole section, add `order` to its `index.md`.

Pages with `hidden: true` are excluded from navigation and the search index but remain accessible by URL — ideal for drafts or internal content. Both fields are set in [front matter](#front-matter).

### Sections

Any directory with an `index.md` becomes a collapsible section in the sidebar with a dedicated landing page link. A directory without an `index.md` still groups its children visually but has no link of its own.

---

## Images

Place image files inside the `_images` directory at the root of your `source_path`. The package serves them automatically through the same URL prefix and middleware as your documents.

### The `@image/` alias

Use the `@image/` (or `@images/`) alias to reference any image from any page, regardless of how deeply nested the page is:

```md
![@image/screenshot.png](@image/screenshot.png)
```

The alias always resolves to the configured images directory (default `_images`), so a page five levels deep does not need `../../../../_images/screenshot.png`.

### Relative paths

If you prefer relative paths, reference images relative to the current document:

```md
![Screenshot](_images/screenshot.png)
```

From a page inside a subdirectory (e.g. `getting-started/installation.md`):

```md
![Screenshot](../_images/screenshot.png)
```

All relative image paths are rewritten to the correct served URL automatically.

### External and absolute URLs

URLs starting with a protocol (`https://`), an absolute path (`/`), or a data URI (`data:`) are left unchanged.

### Supported extensions

By default: `jpg`, `jpeg`, `png`, `gif`, `webp`, `svg`, `ico`. Extend or restrict the list in `config/manual.php` under `images.extensions`.

---

## Linking Between Pages

Link to other pages using standard relative Markdown links with `.md` extensions. The package rewrites them to the correct public URL at render time:

```md
[Installation](../getting-started/installation.md)
[Caching](../advanced/caching.md)
```

Hash fragments are preserved:

```md
[Front Matter — Order field](../guides/front-matter.md#order)
```

---

## Dynamic Helpers

You may embed dynamic URL helpers directly in your Markdown using `{{ }}` syntax. The package resolves these at render time before converting Markdown to HTML.

> **Helpers inside code blocks are never resolved.** You can safely document the helper syntax itself inside a fenced code block.

### `route()`

Generates a URL for any named Laravel route in your application:

```md
[Log in]({{ route('login') }})

Dashboard: {{ route('dashboard') }}
```

### `doc()`

Generates a URL for a documentation page using its `key` front matter value. This is stable across URL changes:

```md
[Authentication]({{ doc('guides.authentication') }})
```

The target document must have `key: guides.authentication` in its front matter.

### `doc_public()`

Generates a URL for a documentation page using its public route path relative to the prefix:

```md
[Caching]({{ doc_public('advanced/caching') }})
```

---

## Search

The package exposes a JSON endpoint at `/{prefix}/_manual/search.json` that powers client-side search. Hidden documents are excluded. Each entry contains:

| Field         | Description                                                |
| ------------- | ---------------------------------------------------------- |
| `title`       | The document title.                                        |
| `description` | The front matter description, if set.                      |
| `headings`    | A list of all heading texts in the document.               |
| `excerpt`     | The first 220 characters of the description or plain text. |
| `content`     | The full plain text content of the document.               |
| `url`         | The absolute URL of the document.                          |

Configure the endpoint path under `search.endpoint` in `config/manual.php`. The path is reserved while search is enabled, so ensure it does not conflict with any document URL.

---

## Caching

The package maintains two independent cache layers.

### Manifest cache

Holds the entire scanned document graph: every document descriptor, route lookup tables, and the navigation tree. Its key is derived from the source path and an **inventory signature** built from the path and modification time of every file. Any file added, removed, or modified automatically invalidates the manifest on the next request.

### Page and search cache

Each rendered page is cached individually, keyed on the document's relative path, its modification time, and a fingerprint of the active Laravel routes. The search index is cached under a similar key. Changing a single file invalidates only that file's page cache; the rest of the site remains cached.

### Cache configuration

```php
'cache_store' => env('MANUAL_CACHE_STORE'),   // null → default Laravel cache store
'cache_ttl'   => 3600,                        // seconds; null → store forever
```

| `cache_ttl` value | Effect                                                              |
| ----------------- | ------------------------------------------------------------------- |
| `3600` (default)  | Cached for one hour, then re-rendered on the next request.          |
| `null`            | Cached forever; invalidated only by file changes or `manual:clear`. |
| `0` or negative   | Cache bypassed entirely. Useful in local development.               |

To disable caching locally, set a negative TTL in `config/manual.php` or point `MANUAL_CACHE_STORE` to the `array` driver in `.env`.

---

## Artisan Commands

### `manual:init`

Scaffolds the default documentation structure in your `source_path`. Creates an `_images` directory, a root `index.md`, and example pages organized in three sections (`getting-started`, `guides`, `advanced`).

```bash
php artisan manual:init
php artisan manual:init --force   # overwrite existing files
```

### `manual:make`

Creates a single new Markdown document at the given path relative to `source_path`.

```bash
php artisan manual:make guides/authentication
```

Available options:

| Option           | Description                                                   |
| ---------------- | ------------------------------------------------------------- |
| `--title=`       | The page title written to front matter and the H1 heading.    |
| `--slug=`        | Sets the `slug` front matter value.                           |
| `--url=`         | Sets the `url` front matter value (full route path override). |
| `--order=`       | Sets the `order` front matter value (integer).                |
| `--description=` | Sets the `description` front matter value.                    |
| `--key=`         | Sets the `key` front matter value.                            |
| `--hidden`       | Marks the document as `hidden: true` in front matter.         |
| `--force`        | Overwrites the file if it already exists.                     |

Example:

```bash
php artisan manual:make guides/authentication \
    --title="Authentication Guide" \
    --description="Protect your documentation behind authentication." \
    --order=2 \
    --key=guides.authentication
```

### `manual:build`

Warms the manifest cache, renders and caches every page, and builds the search index. Run this after deploying or after structural changes (new files, renamed files, updated front matter):

```bash
php artisan manual:build
```

```
Manual build complete: 14 documents scanned, 13 visible, 13 cached pages, 13 search documents.
```

### `manual:clear`

Flushes every cache key managed by the package. The next request will re-scan, re-render, and re-cache everything.

```bash
php artisan manual:clear
```

---

## Configuration Reference

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Source Path
    |--------------------------------------------------------------------------
    | The directory the package scans for Markdown files.
    | Relative paths are resolved from base_path().
    */
    'source_path' => 'docs/manual',

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    | The URL prefix for all documentation routes.
    | Set to an empty string to serve at the application root.
    */
    'route_prefix' => 'manual',

    /*
    |--------------------------------------------------------------------------
    | Site Title
    |--------------------------------------------------------------------------
    | Shown in the browser tab and error pages.
    */
    'site_title' => env('APP_NAME', 'Documentation'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | null cache_store → the application's default cache store.
    | null cache_ttl   → cache forever (invalidated only by file changes).
    | 0 or negative    → cache bypassed (useful in development).
    */
    'cache_store' => env('MANUAL_CACHE_STORE'),
    'cache_ttl'   => 3600,

    /*
    |--------------------------------------------------------------------------
    | View
    |--------------------------------------------------------------------------
    | The Blade view used to render documentation pages.
    | Publish manual-views and point this to your customized view.
    */
    'view' => 'manual::page',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    | Applied to every documentation and image route.
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    | Disable if you are fully replacing the default styles and scripts.
    */
    'assets' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    | The JSON search index endpoint. The path is reserved while enabled.
    */
    'search' => [
        'enabled'  => true,
        'endpoint' => '_manual/search.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    | Images are served from source_path/{images.path}.
    */
    'images' => [
        'enabled'    => true,
        'path'       => '_images',
        'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'],
    ],

];
```

---

## Customization

### Replacing the Blade view

Publish the views, edit `resources/views/vendor/manual/page.blade.php`, then point the `view` config key to your customized template:

```bash
php artisan vendor:publish --tag=manual-views
```

The view receives these variables:

| Variable          | Type                       | Description                                                    |
| ----------------- | -------------------------- | -------------------------------------------------------------- |
| `$page`           | `RenderedManualPage`       | The full page DTO.                                             |
| `$document`       | `DocumentDescriptor`       | The current document's metadata.                               |
| `$navigation`     | `array`                    | The full navigation tree.                                      |
| `$breadcrumbs`    | `array`                    | Breadcrumb items for the current page.                         |
| `$previousPage`   | `DocumentDescriptor\|null` | The previous document in reading order.                        |
| `$nextPage`       | `DocumentDescriptor\|null` | The next document in reading order.                            |
| `$siteTitle`      | `string`                   | The configured site title.                                     |
| `$searchEndpoint` | `string\|null`             | The search JSON endpoint URL, or `null` if search is disabled. |
| `$assetsEnabled`  | `bool`                     | Whether the bundled assets should be injected.                 |

To replace the compiled CSS and JS entirely, publish the assets and set `assets.enabled` to `false`:

```bash
php artisan vendor:publish --tag=manual-assets
```

### Protecting documentation with authentication

Set the `middleware` config key to add your authentication middleware to every documentation and image route:

```php
'middleware' => ['web', 'auth'],
```

### Using a different source directory

Point `source_path` to any directory — both absolute and relative paths (resolved from `base_path()`) are supported:

```php
'source_path' => storage_path('docs'),
```

---

## Requirements

- PHP **8.5+**
- Laravel **12** or **13**

---

## License

Manual is open-source software licensed under the [MIT license](LICENSE).
