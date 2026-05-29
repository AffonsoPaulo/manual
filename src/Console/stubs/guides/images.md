---
title: Images
description: Add images to your documentation pages.
---

# Images

Place image files inside the `_images/` directory at the root of your documentation source path. The package serves them automatically under the same URL prefix and middleware as your documents.

## Adding an Image

Use standard Markdown image syntax and reference the image relative to the current document:

```md
![Screenshot](_images/screenshot.png)
```

From a page inside a subdirectory (e.g., `getting-started/installation.md`), use a relative path:

```md
![Screenshot](../_images/screenshot.png)
```

## The `@image/` Alias

Instead of writing paths relative to each document, use the `@image/` (or `@images/`) alias to reference images from any page regardless of depth:

```md
![@image/screenshot.png](@image/screenshot.png)

![@images/screenshot.png](@images/screenshot.png)
```

Both forms are equivalent. The alias always resolves to the configured `images.path` directory (default `_images`). This is especially useful in deeply nested pages where relative paths like `../../_images/...` become hard to maintain.

## Supported Extensions

By default, the following formats are accepted: `jpg`, `jpeg`, `png`, `gif`, `webp`, `svg`, `ico`.

## Configuration

```php
// config/manual.php
'images' => [
    'enabled'    => true,
    'path'       => '_images',  // folder name, relative to source_path
    'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'],
],
```

Change `images.path` to use a different folder name. Set `images.enabled` to `false` to disable image serving entirely.

## Notes

- External image URLs (`https://...`) are left unchanged.
- Absolute paths (`/public/...`) are left unchanged.
- Inline `data:` URIs are left unchanged.
- Images inherit the same middleware as your documents. If access to your manual requires authentication, images do too.
