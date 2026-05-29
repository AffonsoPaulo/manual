---
title: Search
description: Learn how the JSON search index is built and exposed.
---

# Search

When search is enabled, the package exposes a JSON endpoint for client-side querying.

## Behavior

- Hidden documents are excluded from the search payload.
- Each entry contains `title`, `description`, `headings`, `excerpt` (first 220 characters of description or plain text), `content`, and `url`.
- The endpoint path is reserved while search remains enabled.

## Configuration

```php
'search' => [
    'enabled' => true,
    'endpoint' => '_manual/search.json',
],
```

Change the endpoint only when you need to match an application-specific URL structure.
