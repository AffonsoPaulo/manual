---
title: Routing
description: How file paths are translated into public URLs, and how to override them with front matter.
order: 2
key: guides.routing
---

# Routing

Manual derives every public URL directly from the file and directory layout inside `source_path`. No route declarations are needed — the file system is the router.

---

## The default mapping

Each file's URL is its path relative to `source_path`, without the `.md` extension, prepended with the configured `route_prefix` (default: `manual`):

| File | URL |
|---|---|
| `index.md` | `/manual` |
| `getting-started/index.md` | `/manual/getting-started` |
| `getting-started/installation.md` | `/manual/getting-started/installation` |
| `guides/front-matter.md` | `/manual/guides/front-matter` |
| `advanced/caching.md` | `/manual/advanced/caching` |

### The `index.md` convention

A file named `index.md` represents the URL of its **parent directory**, not a URL named `index`. This makes it the landing page for that section:

- `getting-started/index.md` → `/manual/getting-started`
- `guides/index.md` → `/manual/guides`
- `index.md` (root) → `/manual`

Any other filename maps to itself: `getting-started/installation.md` → `/manual/getting-started/installation`.

---

## Overriding the last segment with `slug`

The `slug` field replaces only the last URL segment while keeping the rest of the path:

```yaml
---
slug: setup
---
```

`getting-started/installation.md` normally maps to `/manual/getting-started/installation`. With `slug: setup`, it becomes `/manual/getting-started/setup`.

Use `slug` when you want a different URL than the filename suggests, without moving the file or changing its section.

---

## Overriding the full path with `url`

The `url` field replaces the entire relative route path:

```yaml
---
url: reference/install
---
```

`getting-started/installation.md` becomes `/manual/reference/install`, ignoring both the directory and the filename.

> If two documents resolve to the same URL — either through `slug`, `url`, or identical filenames — the package throws an error at scan time and the build fails.

---

## Changing the route prefix

The `route_prefix` config key controls the base URL for all documentation routes:

```php
// config/manual.php
'route_prefix' => 'docs',   // → /docs, /docs/getting-started, ...
```

To serve documentation at the application root, set it to an empty string:

```php
'route_prefix' => '',   // → /, /getting-started, ...
```

> When `route_prefix` is empty, documentation routes compete directly with your application routes. Register them carefully to avoid conflicts.

---

## Reserved routes

While search and images are enabled, their endpoint paths are reserved and cannot be used as document URLs. By default:

- `_manual/search.json` — the search endpoint
- `_images/...` — the image serving path

Creating a document whose resolved URL matches a reserved route causes a scan-time error.
