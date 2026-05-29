---
title: Linking
description: How to link between pages using relative Markdown links and the route(), doc(), and doc_public() helpers.
order: 4
key: guides.linking
---

# Linking

There are two ways to link to other pages in Servera Manual: **relative Markdown links** and **dynamic helpers**.

---

## Relative Markdown links

Use standard Markdown links with `.md` extensions to reference other documents. The package rewrites them to the correct public URL at render time:

```md
[Installation](../getting-started/installation.md)
[Caching](../advanced/caching.md)
[Front Matter](./front-matter.md)
```

Links are resolved relative to the current document's location in the directory tree. The `../` prefix navigates up one directory level, exactly as you would expect from a file system path.

### Hash fragments

Append a `#` fragment to jump to a specific heading. The fragment is passed through as-is:

```md
[Order field](./front-matter.md#order)
[Cache TTL](../advanced/caching.md#cache-configuration)
```

Headings receive auto-generated IDs based on their text (slugified). For example, `## Cache Configuration` gets the ID `cache-configuration`.

### What happens to broken links

If a relative `.md` link does not resolve to any known document, the package leaves the original `href` unchanged rather than throwing an error. The link will be broken in the browser, but the page will still render.

---

## Dynamic helpers

You may embed dynamic URL helpers in Markdown using `{{ }}` syntax. The package resolves them before converting Markdown to HTML, so the rendered output contains real URLs — never the raw helper expression.

```md
Visit the [dashboard]({{ route('dashboard') }}) after logging in.

Read about [installation]({{ doc('getting-started.installation') }}) before continuing.
```

> **Helpers inside fenced code blocks are never resolved.** You can safely write helper syntax inside a ` ``` ` block to show it as literal text — as in this very page.

---

### `route('name')`

Generates a URL for any named Laravel route in your application:

```md
{{ route('login') }}
{{ route('dashboard') }}

[Log in]({{ route('login') }})
```

If the named route does not exist, the package throws an error at render time and the page will not be served.

---

### `doc('key')`

Generates a URL for a documentation page using its `key` front matter value. This is the most stable way to link between pages — the link keeps working even if you move the file or change its URL:

```md
{{ doc('guides.front-matter') }}
{{ doc('getting-started.installation') }}

[Front matter reference]({{ doc('guides.front-matter') }})
```

The target document must have a matching `key` in its front matter. If the key does not exist, an error is thrown at render time.

---

### `doc_public('path')`

Generates a URL for a documentation page using its **public route path** — the URL path relative to the route prefix:

```md
{{ doc_public('advanced/caching') }}
{{ doc_public('guides/front-matter') }}

[Caching]({{ doc_public('advanced/caching') }})
```

The path is the URL you would visit in a browser, without the prefix. If no document is found at that path, an error is thrown at render time.

> **`doc()` vs `doc_public()`** — Use `doc()` with a key when you want refactor-safe links that survive URL changes. Use `doc_public()` when you know the public path will stay stable and you prefer not to assign a key.
