---
title: Installation
description: Step-by-step guide to installing Manual in a Laravel application.
order: 1
key: getting-started.installation
---

# Installation

## Requirements

- PHP **8.5** or higher
- Laravel **12** or **13**

## Step 1 — Install the package

```bash
composer require affonsopaulo/manual
```

The package registers itself automatically via Laravel's package auto-discovery. No changes to `config/app.php` are needed.

## Step 2 — Publish the configuration file

```bash
php artisan vendor:publish --tag=manual-config
```

This creates `config/manual.php` in your application. The defaults work out of the box, but you will likely want to review `source_path` and `route_prefix` before writing any content.

## Step 3 — Publish the compiled assets

The package ships with pre-compiled CSS and JavaScript. Publish them so browsers can load them:

```bash
php artisan vendor:publish --tag=manual-assets
```

This copies the files to `public/vendor/manual`. Repeat this command after upgrading the package to pick up asset changes.

## Step 4 — Scaffold your documentation directory

The `manual:init` command creates a starter structure under `source_path` (default: `docs/manual`):

```bash
php artisan manual:init
```

Output:

```
created _images/
created index.md
created getting-started/index.md
created getting-started/installation.md
created getting-started/configuration.md
created guides/index.md
created guides/front-matter.md
created guides/routing.md
created guides/navigation.md
created guides/linking.md
created guides/images.md
created advanced/index.md
created advanced/caching.md
created advanced/search.md
created advanced/customization.md
Manual scaffold complete: 14 created, 0 overwritten, 0 skipped. Run "php artisan manual:build" next.
```

If any files already exist, the command skips them. Pass `--force` to overwrite:

```bash
php artisan manual:init --force
```

## Step 5 — Build the cache

Warm the manifest cache, render every page, and build the search index:

```bash
php artisan manual:build
```

You will see a summary like:

```
Manual build complete: 15 documents scanned, 14 visible, 14 cached pages, 14 search documents.
```

## Step 6 — Open the manual

Visit `/manual` in your browser. Your documentation site is live.

---

## Creating individual pages

Once the scaffold is in place, use `manual:make` to add new pages at any time:

```bash
php artisan manual:make guides/authentication --title="Authentication" --order=5 --key=guides.authentication
```

The command creates the file with a populated front matter block and an H1 heading. Run `manual:build` afterward to update the cache.

## Keeping Blade views customizable

If you want to modify the layout, publish the Blade views:

```bash
php artisan vendor:publish --tag=manual-views
```

This copies the views to `resources/views/vendor/manual`. See [Customization](../advanced/customization.md) for details on the available template variables.
