---
title: Search
description: How the JSON search index is built, what it contains, and how to use it.
order: 2
key: advanced.search
---

# Search

Servera Manual exposes a JSON endpoint that you can use to power client-side full-text search. No search server or external service is required.

---

## The endpoint

By default, the search index is available at:

```
/manual/_manual/search.json
```

The path is relative to the route prefix. With `route_prefix` set to `docs`, the endpoint becomes `/docs/_manual/search.json`.

The endpoint path is **reserved** while search is enabled. Do not create a documentation page at the same path.

---

## What is indexed

The index includes every **visible** document in the manual. Pages with `hidden: true` in their front matter are excluded.

Each entry in the `documents` array contains:

| Field | Description |
|---|---|
| `title` | The document title (resolved from front matter, H1, or filename). |
| `description` | The `description` front matter value, or `null` if not set. |
| `headings` | An array of all heading texts in the document (H1–H6). |
| `excerpt` | The first 220 characters of the description, or the page's plain text if no description is set. |
| `content` | The full plain text content of the document (Markdown stripped). |
| `path` | The route path relative to the prefix (e.g. `guides/front-matter`). |
| `url` | The absolute URL of the document. |

The response envelope also includes a `generated_at` ISO 8601 timestamp.

### Example response

```json
{
  "generated_at": "2025-01-15T14:32:00+00:00",
  "documents": [
    {
      "title": "Front Matter",
      "description": "Reference for every YAML front matter field.",
      "headings": ["Front Matter", "title", "slug", "url", "order"],
      "excerpt": "Reference for every YAML front matter field.",
      "content": "Front matter is an optional YAML block ...",
      "path": "guides/front-matter",
      "url": "https://example.com/manual/guides/front-matter"
    }
  ]
}
```

---

## Building and caching the index

The search index is built as part of `manual:build` and cached alongside the page cache. When a document changes, the cached index is invalidated and rebuilt on the next request or the next `manual:build` run.

```bash
php artisan manual:build
```

---

## Configuration

```php
// config/manual.php
'search' => [
    'enabled'  => true,
    'endpoint' => '_manual/search.json',
],
```

Set `enabled` to `false` to disable the endpoint entirely. When disabled, the route is not registered and the search UI (if present in the view) is hidden.

Change `endpoint` only when you need to match a specific URL structure. The value is relative to the route prefix.

---

## Using the index

Fetch the endpoint with any HTTP client and filter or rank the results on the client side. A minimal example using the browser's `fetch` API:

```js
fetch('/manual/_manual/search.json')
  .then(res => res.json())
  .then(({ documents }) => {
    const query = 'caching';
    const results = documents.filter(doc =>
      doc.content.toLowerCase().includes(query) ||
      doc.title.toLowerCase().includes(query)
    );
    console.log(results);
  });
```

The default Blade view ships with a built-in search UI that consumes this endpoint automatically. If you replace the view, the `$searchEndpoint` variable contains the full URL of the endpoint (or `null` if search is disabled).
