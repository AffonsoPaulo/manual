---
title: Images
description: Add images to your documentation pages using the @image/ alias or relative paths.
order: 5
key: guides.images
---

# Images

Place image files inside the `_images` directory at the root of your `source_path`:

```
docs/manual/
└── _images/
    ├── screenshot.png
    ├── banner.webp
    └── icons/
        └── arrow.svg
```

The package serves images automatically through the same URL prefix and middleware as your documents. No separate routes or controllers need to be configured.

---

## The `@image/` alias

The recommended way to reference images is the `@image/` alias. It always resolves to the configured images directory, regardless of how deeply nested the current page is:

```md
![@image/screenshot.png](@image/screenshot.png)
![@image/icons/arrow.svg](@image/icons/arrow.svg)
```

`@images/` (plural) works identically:

```md
![@images/screenshot.png](@images/screenshot.png)
```

**Why use the alias?**

Without it, a page at `advanced/caching.md` would need `../_images/screenshot.png`, while a page at `guides/linking.md` would need the same `../_images/screenshot.png`. These relative paths are easy to get wrong and break when you move files. The `@image/` alias makes every image reference the same regardless of where the page lives.

---

## Relative paths

If you prefer relative paths, reference images relative to the current document's location:

```md
![Screenshot](_images/screenshot.png)
```

From a page inside a subdirectory (e.g. `getting-started/installation.md`):

```md
![Screenshot](../_images/screenshot.png)
```

The package resolves all relative image paths and rewrites the `src` attribute to the correct served URL automatically.

---

## External and absolute URLs

The following are passed through **unchanged** and served as-is:

- URLs with a protocol: `https://example.com/image.png`
- Absolute paths: `/public/logo.png`
- Data URIs: `data:image/png;base64,...`

---

## Supported extensions

By default the package serves: `jpg`, `jpeg`, `png`, `gif`, `webp`, `svg`, `ico`.

Requests for any other extension return a 404, even if the file exists on disk. Extend the list in `config/manual.php`:

```php
'images' => [
    'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'avif'],
],
```

---

## Configuration

```php
// config/manual.php
'images' => [
    'enabled'    => true,
    'path'       => '_images',
    'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'],
],
```

| Key | Description |
|---|---|
| `images.enabled` | Set to `false` to disable image serving entirely. `@image/` aliases will not be rewritten and image routes will return 404. |
| `images.path` | The subdirectory inside `source_path` where images are stored. Must be a relative path. |
| `images.extensions` | The allowlist of file extensions the image controller will serve. |

---

## Security

The image controller validates every request before serving a file:

- **Null bytes** in the path are rejected immediately.
- **Path traversal** segments (`.` and `..`) are rejected — it is not possible to escape the images directory.
- The **resolved file path** is verified to be inside `images.path` using `realpath()`.
- The **MIME type** of the file is verified to start with `image/` using PHP's `finfo` extension.

Images inherit the same middleware as your documents, including any authentication middleware you have configured.
