---
title: Front Matter
description: Reference for every YAML front matter field supported by Manual.
order: 1
key: guides.front-matter
---

# Front Matter

Front matter is an optional YAML block at the very top of a Markdown file, delimited by `---`. The package reads it before rendering and uses its values to control the page title, URL, navigation position, search visibility, and more.

```yaml
---
title: My Page Title
slug: my-slug
url: section/custom-path
order: 2
description: A short summary shown in search results.
key: section.my-page
hidden: false
---

# My Page Title

Page content starts here.
```

Front matter is entirely optional. When a field is absent, the package infers a reasonable default.

---

## `title`

**Type:** string  
**Default:** first `# h1` heading → formatted filename

The title shown in navigation, breadcrumbs, and the browser tab.

When `title` is not set, the package resolves it in this order:

1. The text of the first `# h1` heading found in the Markdown body.
2. The filename converted to a headline — `front-matter.md` becomes `Front Matter`.

> Setting `title` in front matter is recommended when the filename or the first heading would produce a poor label in the sidebar.

---

## `slug`

**Type:** string  
**Default:** derived from the filename

Replaces only the **last segment** of the derived URL while keeping the rest of the path unchanged.

```yaml
---
slug: setup
---
```

A file at `getting-started/installation.md` normally maps to `/manual/getting-started/installation`. With `slug: setup`, it becomes `/manual/getting-started/setup` — the directory stays the same, only the last segment changes.

---

## `url`

**Type:** string  
**Default:** derived from the file path

Replaces the **entire relative route path** for the document, ignoring both the directory and the filename.

```yaml
---
url: reference/install
---
```

`getting-started/installation.md` becomes `/manual/reference/install` regardless of where the file lives in the directory tree.

> Use `url` when you need to move a page to a completely different URL without physically moving the file. Use `slug` when you only need to rename the last segment.

---

## `order`

**Type:** integer  
**Default:** none (alphabetical fallback)

Controls the position of the page within its section in the sidebar. Lower values appear first.

```yaml
---
order: 1
---
```

Pages with an `order` value are sorted first (ascending), then all remaining pages are sorted alphabetically. To control the order of a whole section, add `order` to the section's `index.md`.

---

## `description`

**Type:** string  
**Default:** none

A short summary of the page. Used in two places:

- As the `excerpt` in the search index (the text shown under the page title in search results).
- Available to Blade views as `$document->description` for use in meta tags or page summaries.

---

## `key`

**Type:** string (dot-notation recommended, e.g. `section.page`)  
**Default:** none

A stable, human-readable identifier for the document. Once set, you can reference this page from anywhere in your documentation using the `{{ doc('...') }}` helper — even if the URL changes later:

```md
See the [installation guide]({{ doc('getting-started.installation') }}) for details.
```

Keys are unique across the entire manual. Assigning a key is optional but strongly recommended for pages you expect to link to from multiple places.

---

## `hidden`

**Type:** boolean  
**Default:** `false`

When `true`, the page is excluded from the sidebar and the search index. Its URL remains fully accessible — navigating to it directly still works.

```yaml
---
hidden: true
---
```

Common uses:

- **Draft pages** that are not ready to be promoted.
- **Internal reference pages** you want accessible by direct link but not surfaced in search or navigation.
- **Changelog or archive pages** that should exist but not clutter the main navigation.

---

## Validation rules

The package validates front matter when scanning documents. An invalid value throws an error that is logged and shown on the page if `app.debug` is `true`.

| Field | Validation |
|---|---|
| `title` | Must be a string if present. |
| `slug` | Must be a string if present. |
| `url` | Must be a string if present. |
| `order` | Must be an integer (or a string that parses as one). |
| `description` | Must be a string if present. |
| `key` | Must be a non-empty string if present. |
| `hidden` | Must be a boolean (`true` or `false`). |
