---
title: Customization
description: Replace views, swap assets, protect routes, and adapt the manual to your application.
order: 3
key: advanced.customization
---

# Customization

Servera Manual is designed to work out of the box, but every visual and behavioral aspect can be overridden.

---

## Publishing views

Publish the Blade views to `resources/views/vendor/manual` to modify the layout, typography, or HTML structure:

```bash
php artisan vendor:publish --tag=manual-views
```

After publishing, edit the views in `resources/views/vendor/manual/`. The package will use your published views instead of its bundled ones.

### Available views

| View | Purpose |
|---|---|
| `page.blade.php` | The main documentation page layout. |
| `not-found.blade.php` | Rendered when a requested page does not exist (404). |
| `error.blade.php` | Rendered when an unexpected error occurs during rendering (500). |

### View variables

The `page.blade.php` view receives the following variables:

| Variable | Type | Description |
|---|---|---|
| `$page` | `RenderedManualPage` | The full page data transfer object. |
| `$document` | `DocumentDescriptor` | Metadata for the current document (title, route, headings, front matter, etc.). |
| `$navigation` | `array` | The full navigation tree for rendering the sidebar. |
| `$breadcrumbs` | `array` | Ordered breadcrumb items for the current page. |
| `$previousPage` | `array{title: string, url: string}\|null` | The previous document in reading order, or `null` if this is the first page. |
| `$nextPage` | `array{title: string, url: string}\|null` | The next document in reading order, or `null` if this is the last page. |
| `$siteTitle` | `string` | The value of `manual.site_title`. |
| `$searchEndpoint` | `string\|null` | The absolute URL of the search JSON endpoint, or `null` if search is disabled. |
| `$assetsEnabled` | `bool` | Whether to inject the bundled CSS and JS. |

After publishing, point the `view` config key to your customized view name if you rename it:

```php
'view' => 'manual.page',  // resources/views/manual/page.blade.php
```

---

## Publishing assets

Publish the compiled CSS and JavaScript to `public/vendor/manual`:

```bash
php artisan vendor:publish --tag=manual-assets
```

To replace the default styles entirely, set `assets.enabled` to `false` in `config/manual.php` so the package stops injecting the bundled stylesheet:

```php
'assets' => [
    'enabled' => false,
],
```

Then enqueue your own CSS and JS inside your published Blade view.

---

## Protecting documentation with authentication

Set the `middleware` config key to add authentication or any other middleware to every documentation route, including images and the search endpoint:

```php
'middleware' => ['web', 'auth'],
```

Any middleware supported by Laravel can be used. For example, to restrict access to users with a specific role using Spatie Laravel Permission:

```php
'middleware' => ['web', 'auth', 'role:editor'],
```

> Because images share the same middleware as documents, authenticated documentation means authenticated images — no workaround needed.

---

## Using a custom source directory

Point `source_path` to any directory. Both absolute and relative paths (resolved from `base_path()`) are accepted:

```php
'source_path' => 'content/docs',           // → {project_root}/content/docs
'source_path' => storage_path('docs'),     // → {project_root}/storage/docs
'source_path' => '/var/www/shared-docs',   // → absolute path
```

---

## Changing the route prefix

```php
'route_prefix' => 'docs',   // serves at /docs
'route_prefix' => '',        // serves at the application root /
```

When the prefix is empty, documentation competes with the rest of your application's routes. The package registers its routes after your application routes boot, so named application routes take precedence.

---

## Using a dedicated cache store

Isolate documentation cache from the rest of your application by pointing `cache_store` to a separate store defined in `config/cache.php`:

```php
// config/manual.php
'cache_store' => 'redis-docs',
```

```php
// config/cache.php
'stores' => [
    'redis-docs' => [
        'driver'     => 'redis',
        'connection' => 'default',
        'prefix'     => 'manual_',
    ],
],
```

This lets you flush documentation caches (`manual:clear`) without affecting other parts of the application.
