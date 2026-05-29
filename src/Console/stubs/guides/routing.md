---
title: Routing
description: Understand how files and directories map to manual URLs.
---

# Routing

The router is derived from the relative path inside `manual.source_path`.

## Conventions

- `index.md` maps to the route of its directory.
- Other files map to their filename without the `.md` extension.
- `slug` replaces only the last derived segment.
- `route` replaces the complete relative route path.

## Examples

| File | Resulting Route |
| --- | --- |
| `index.md` | `/manual` |
| `getting-started/index.md` | `/manual/getting-started` |
| `guides/front-matter.md` | `/manual/guides/front-matter` |

With a `slug` override (last segment only):

```yaml
---
slug: setup
key: guides.installation
---
```

`guides/installation.md` becomes `/manual/guides/setup` instead of `/manual/guides/installation`.

With a `url` override (full relative path):

```yaml
---
url: reference/install
---
```

`guides/installation.md` becomes `/manual/reference/install` regardless of its directory.
