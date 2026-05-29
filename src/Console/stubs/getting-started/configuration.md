---
title: Configuration
description: Review every configuration key exposed by the package.
---

# Configuration

The package ships with the following defaults in `config/manual.php`:

| Key | Default |
| --- | --- |
| `source_path` | `docs/manual` |
| `route_prefix` | `manual` |
| `site_title` | `env('APP_NAME', 'Documentation')` |
| `cache_store` | `env('MANUAL_CACHE_STORE')` |
| `cache_ttl` | `3600` |
| `view` | `manual::page` |
| `middleware` | `['web']` |
| `assets.enabled` | `true` |
| `search.enabled` | `true` |
| `search.endpoint` | `_manual/search.json` |

## Notes

- `source_path` is the only directory scanned for Markdown files.
- `route_prefix` affects public URLs, not where files are stored.
- `cache_store` and `cache_ttl` control both page and search cache layers.
- `search.endpoint` is reserved while search is enabled.
