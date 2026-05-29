# Servera Manual

[![Latest Version](https://img.shields.io/packagist/v/serveracloud/manual)](https://packagist.org/packages/serveracloud/manual)
[![PHP Version](https://img.shields.io/packagist/php-v/serveracloud/manual)](https://packagist.org/packages/serveracloud/manual)
[![License](https://img.shields.io/packagist/l/serveracloud/manual)](https://packagist.org/packages/serveracloud/manual)

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

Servera Manual scans a directory tree of Markdown files and serves them as a styled documentation site — similar to GitBook or Mintlify, but self-hosted inside your Laravel application. Every URL, navigation item, breadcrumb, previous/next link, and search entry is derived automatically from the file system and optional YAML front matter you add to each file.

**How it works at a glance:**

1. You write plain `.md` files inside a directory of your choice (default: `docs/manual`).
2. On request, the package scans the directory once, builds a navigation manifest, renders Markdown to HTML, and caches the result.
3. A `DocumentController` handles every URL under the configured prefix and serves the rendered Blade view.
4. Images, search JSON, and assets each have their own dedicated controllers.

The compiled front-end assets ship inside the package — your application does not need Node.js or any build tooling.

---

## Installation

Install the package via Composer:

```bash
composer require serveracloud/manual
```

The package auto-discovers and registers its service provider. You do not need to add anything to `config/app.php`.

### Publish the configuration file

Publish the config file when you need to override any default:

```bash
php artisan vendor:publish --tag=manual-config
```

This creates `config/manual.php` in your application.

### Publish assets

The package ships with pre-compiled CSS and JavaScript. Publish them to `public/vendor/manual` so browsers can load them:

```bash
php artisan vendor:publish --tag=manual-assets
```

> If you want to replace the default Blade view entirely, publish the views too:
> ```bash
> php artisan vendor:publish --tag=manual-views
> ```

---

## Your First Documentation Page

The fastest way to get started is to scaffold the default documentation structure using the `manual:init` command. It creates a ready-to-use layout including an index page, a Getting Started section, guides, and an advanced section:

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

Now build the cache and search index:

```bash
php artisan manual:build
```

Visit `/manual` in your browser — your documentation site is live.

### Creating a single page

You can also create individual pages at any time with the `manual:make` command:

```bash
php artisan manual:make guides/authentication
```

This scaffolds `docs/manual/guides/authentication.md` with a front matter block and an `# Authentication` heading. Add `--title` to control the heading:

```bash
php artisan manual:make guides/authentication --title="Authentication Guide" --order=3
```

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

| Field | Type | Description |
|---|---|---|
| `title` | string | The page title shown in navigation and the browser tab. Falls back to the first `# h1` heading, then the formatted filename. |
| `slug` | string | Replaces only the **last URL segment** while keeping the rest of the path. `guides/installation.md` with `slug: setup` becomes `/manual/guides/setup`. |
| `url` | string | Replaces the **entire relative route path**. `guides/installation.md` with `url: reference/install` becomes `/manual/reference/install` regardless of its directory. |
| `order` | integer | Controls navigation sort order (ascending). Pages without an `order` value sort alphabetically after ordered pages. |
| `description` | string | Short summary shown in search results and used as the excerpt in the search index. |
| `key` | string | A stable dot-notation identifier (e.g. `guides.authentication`) for use with the `{{ doc('...') }}` helper. |
| `hidden` | boolean | When `true`, the page is excluded from navigation and the search index but remains accessible by its URL. Useful for draft or unlisted pages. |

### Title resolution order

When no `title` is set, the package resolves the title in this order:

1. The first `# h1` heading in the Markdown body.
2. The filename formatted as a headline (e.g. `front-matter.md` → `Front Matter`).

---

## Routing

URLs are derived from the file path relative to `source_path`. The route prefix (default: `manual`) is prepended to every URL. The following table shows examples with the default prefix:

| File | URL |
|---|---|
| `index.md` | `/manual` |
| `getting-started/index.md` | `/manual/getting-started` |
| `guides/front-matter.md` | `/manual/guides/front-matter` |
| `advanced/caching.md` | `/manual/advanced/caching` |

### Overriding the last segment with `slug`

Use `slug` when you want a different URL segment than the filename, without moving the file:

```yaml
---
slug: setup
---
```

`guides/installation.md` becomes `/manual/guides/setup`.

### Overriding the full path with `url`

Use `url` to place a page at a completely different path:

```yaml
---
url: reference/install
---
```

`guides/installation.md` becomes `/manual/reference/install`, independent of where it lives in the directory.

### Changing the route prefix

To serve documentation at a different base URL, update `route_prefix` in `config/manual.php`:

```php
'route_prefix' => 'docs',
```

All documentation URLs will now start with `/docs` instead of `/manual`.

> To serve documentation at the root of your application (e.g. `/`), set `route_prefix` to an empty string: `'route_prefix' => ''`.

---

## Navigation

The sidebar is built automatically from the directory structure. Directories become sections, `index.md` files become section landing pages, and all other documents become leaf entries.

### Controlling order

Add an `order` value to any page's front matter to pin its position in the navigation. Pages without `order` sort alphabetically after pages that have one.

```yaml
---
order: 1
---
```

To order an entire section, add `order` to its `index.md`.

### Hiding a page

Set `hidden: true` to remove a page from navigation and the search index. The page is still accessible via its URL, making this ideal for draft or internal pages you are not ready to promote:

```yaml
---
hidden: true
---
```

### Sections

Any directory with an `index.md` becomes a collapsible section in the sidebar. A directory without an `index.md` still groups its children, but it will not have a dedicated landing page link.

---

## Images

Place image files inside the `_images` directory at the root of your `source_path`:

```
docs/manual/
└── _images/
    ├── screenshot.png
    └── icons/
        └── arrow.png
```

The package serves images automatically through the same URL prefix and middleware as your documents. No separate controller setup is required.

### Referencing images with the `@image/` alias

Use the `@image/` (or `@images/`) alias to reference any image without worrying about relative paths. This alias always resolves to the configured images directory, regardless of how deeply nested the page is:

```md
![@image/screenshot.png](@image/screenshot.png)
```

Both forms are equivalent:

```md
![@image/screenshot.png](@image/screenshot.png)
![@images/screenshot.png](@images/screenshot.png)
```

This is the recommended way to reference images. A page five directories deep does not need `../../../../_images/screenshot.png` — `@image/screenshot.png` always works.

### Using relative paths

If you prefer relative paths, reference images relative to the current document:

```md
![Screenshot](_images/screenshot.png)
```

From a page inside a subdirectory (e.g. `getting-started/installation.md`):

```md
![Screenshot](../_images/screenshot.png)
```

The package rewrites all relative image paths to the correct served URL automatically.

### External and absolute image URLs

URLs that start with a protocol (`https://`), an absolute path (`/`), or a data URI (`data:`) are left unchanged and served as-is.

### Supported extensions

By default: `jpg`, `jpeg`, `png`, `gif`, `webp`, `svg`, `ico`.

You may extend or restrict this list in `config/manual.php`:

```php
'images' => [
    'enabled'    => true,
    'path'       => '_images',
    'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'],
],
```

---

## Linking Between Pages

### Relative Markdown links

You may link to other pages using standard relative Markdown links with `.md` extensions. The package rewrites them to the correct public URL at render time:

```md
[Installation](../getting-started/installation.md)
[Caching](../advanced/caching.md)
```

Links are resolved relative to the current document's location, so `../` navigates up one directory as expected.

### Linking to sections

You can append a hash fragment to any link and it will be preserved:

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

This requires the target document to have `key: guides.authentication` in its front matter.

### `doc_public()`

Generates a URL for a documentation page using its public route path (the URL path relative to the prefix):

```md
[Caching]({{ doc_public('advanced/caching') }})
```

---

## Search

The package exposes a JSON endpoint that powers client-side search. It is enabled by default and accessible at `/{prefix}/_manual/search.json`.

The search index includes every visible document. Hidden documents are excluded. Each entry contains:

| Field | Description |
|---|---|
| `title` | The document title. |
| `description` | The front matter description, if set. |
| `headings` | A list of all heading texts in the document. |
| `excerpt` | The first 220 characters of the description or plain text. |
| `content` | The full plain text content of the document. |
| `url` | The absolute URL of the document. |

### Configuration

```php
'search' => [
    'enabled'  => true,
    'endpoint' => '_manual/search.json',
],
```

The endpoint path is reserved while search is enabled. If you change it, ensure it does not conflict with any document URL.

---

## Caching

The package maintains two independent cache layers, both using the Laravel cache store of your choice.

### Manifest cache

The manifest cache holds the entire scanned document graph: every document descriptor, route lookup tables, and the navigation tree. Its cache key is derived from the source path and an **inventory signature** built from the path and modification time (`mtime`) of every file in the source directory.

**Any file added, removed, or modified automatically invalidates the manifest** on the next request — no manual intervention needed in development.

### Page and search cache

Each rendered page is cached individually, keyed on the document's relative path, its `mtime`, and a fingerprint of the active Laravel routes. The search index is cached separately under a similar key.

Changing a single file invalidates only that file's page cache; the rest of the site remains cached.

### Cache configuration

```php
'cache_store' => env('MANUAL_CACHE_STORE'),   // null → default Laravel cache store
'cache_ttl'   => 3600,                        // seconds; null → store forever
```

| `cache_ttl` value | Effect |
|---|---|
| `3600` (default) | Cached for one hour, then re-rendered on the next request. |
| `null` | Cached forever; invalidated only by file changes or `manual:clear`. |
| `0` or negative | Cache bypassed entirely — every request re-renders. Useful in development. |

To disable caching during local development, add to your `.env`:

```env
MANUAL_CACHE_STORE=array
```

Or set a negative TTL in `config/manual.php`:

```php
'cache_ttl' => -1,
```

---

## Artisan Commands

### `manual:init`

Scaffolds the default documentation structure in your `source_path`. Creates an `_images` directory, an `index.md`, and a set of example pages organized in three sections (`getting-started`, `guides`, `advanced`).

```bash
php artisan manual:init
```

Use `--force` to overwrite any files that already exist:

```bash
php artisan manual:init --force
```

### `manual:make`

Creates a single new Markdown document at the given path relative to `source_path`. The command writes a front matter block and an `# Heading` derived from the filename.

```bash
php artisan manual:make guides/authentication
```

Available options:

| Option | Description |
|---|---|
| `--title=` | The page title written to front matter and the H1 heading. |
| `--slug=` | Sets the `slug` front matter value. |
| `--url=` | Sets the `url` (full route path override) front matter value. |
| `--order=` | Sets the `order` front matter value (integer). |
| `--description=` | Sets the `description` front matter value. |
| `--key=` | Sets the `key` front matter value. |
| `--hidden` | Marks the document as hidden in front matter. |
| `--force` | Overwrites the file if it already exists. |

Example with multiple options:

```bash
php artisan manual:make guides/authentication \
    --title="Authentication Guide" \
    --description="Protect your documentation behind authentication." \
    --order=2 \
    --key=guides.authentication
```

### `manual:build`

Warms the manifest cache, renders and caches every page, and builds the search index. Run this after deploying or after making structural changes (new files, renamed files, updated front matter):

```bash
php artisan manual:build
```

Output example:

```
Manual build complete: 14 documents scanned, 13 visible, 13 cached pages, 13 search documents.
```

### `manual:clear`

Flushes every cache key managed by the package. The next request will re-scan, re-render, and re-cache everything:

```bash
php artisan manual:clear
```

---

## Configuration Reference

After publishing the config file with `php artisan vendor:publish --tag=manual-config`, you will find `config/manual.php`:

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

### Key reference

| Key | Default | Description |
|---|---|---|
| `source_path` | `docs/manual` | The directory scanned for `.md` files. |
| `route_prefix` | `manual` | URL prefix for all documentation routes. Empty string serves at the root. |
| `site_title` | `APP_NAME` | Shown in browser tab and error pages. |
| `cache_store` | `null` | Laravel cache store name. `null` uses the default. |
| `cache_ttl` | `3600` | Cache lifetime in seconds. `null` = forever, `0` or negative = disabled. |
| `view` | `manual::page` | Blade view for rendering pages. |
| `middleware` | `['web']` | Middleware applied to all routes including images. |
| `assets.enabled` | `true` | Whether to inject the bundled CSS and JS. |
| `search.enabled` | `true` | Whether to expose the JSON search endpoint. |
| `search.endpoint` | `_manual/search.json` | Path of the search JSON endpoint (relative to the route prefix). |
| `images.enabled` | `true` | Whether to serve images from `source_path`. |
| `images.path` | `_images` | Subdirectory inside `source_path` where images are stored. |
| `images.extensions` | `[...]` | Allowed image file extensions. |

---

## Customization

### Publishing resources

You may publish any combination of the package's resources:

```bash
# Configuration
php artisan vendor:publish --tag=manual-config

# Blade views
php artisan vendor:publish --tag=manual-views

# Compiled CSS and JS assets
php artisan vendor:publish --tag=manual-assets
```

### Replacing the Blade view

Publish the views and edit `resources/views/vendor/manual/page.blade.php`. Then point the package to your view:

```php
'view' => 'manual::page', // or 'your-view-name' after publishing
```

The view receives these variables:

| Variable | Type | Description |
|---|---|---|
| `$page` | `RenderedManualPage` | The full page DTO. |
| `$document` | `DocumentDescriptor` | The current document's metadata. |
| `$navigation` | `array` | The full navigation tree. |
| `$breadcrumbs` | `array` | Breadcrumb items for the current page. |
| `$previousPage` | `DocumentDescriptor\|null` | The previous document in reading order. |
| `$nextPage` | `DocumentDescriptor\|null` | The next document in reading order. |
| `$siteTitle` | `string` | The configured site title. |
| `$searchEndpoint` | `string\|null` | The search JSON endpoint URL, or `null` if search is disabled. |
| `$assetsEnabled` | `bool` | Whether the bundled assets should be injected. |

### Protecting documentation with authentication

Set the `middleware` config key to add your authentication middleware to every documentation and image route:

```php
'middleware' => ['web', 'auth'],
```

For more granular control, you may add any middleware stack that Laravel supports.

### Using a different source directory

Point `source_path` to any directory in your application:

```php
'source_path' => storage_path('docs'),
```

Both absolute and relative paths are supported. Relative paths are resolved from `base_path()`.

---

## Requirements

- PHP **8.5+**
- Laravel **12** or **13**

---

## License

Servera Manual is open-source software licensed under the [MIT license](LICENSE).
