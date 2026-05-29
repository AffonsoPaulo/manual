---
title: Navigation
description: How the sidebar is built, how to control page order, and how hidden pages work.
order: 3
key: guides.navigation
---

# Navigation

The sidebar is built automatically from the directory structure. No configuration or manifest files are needed — the package derives all navigation from the files it finds in `source_path`.

---

## Structure

The sidebar mirrors the directory layout:

```
docs/manual
├── index.md                  ← home (root entry)
├── getting-started/
│   ├── index.md              ← section landing page
│   ├── installation.md       ← leaf page
│   └── configuration.md      ← leaf page
└── guides/
    ├── index.md              ← section landing page
    └── front-matter.md       ← leaf page
```

- **Directories** become collapsible sections in the sidebar.
- **`index.md`** inside a directory becomes the section landing page — clicking the section header in the sidebar navigates to it.
- **All other files** become leaf entries listed under their section.
- A directory **without** an `index.md` still groups its children visually, but has no landing page link of its own.

---

## Ordering pages

Add an `order` value to any page's front matter to control its position within its section. Lower numbers appear first:

```yaml
---
order: 1
---
```

**Sort rules:**
1. Pages with an `order` value are sorted first, ascending.
2. Pages without `order` follow, sorted alphabetically by title.

To order a whole section relative to other sections, add `order` to the section's `index.md`.

---

## Hiding a page

Set `hidden: true` in front matter to remove a page from the sidebar and from the search index:

```yaml
---
hidden: true
---
```

Hidden pages are still fully accessible via their URL. They are simply not surfaced in navigation or search results. This is useful for:

- Draft pages not yet ready to publish.
- Internal reference pages accessible by direct link.
- Archive or changelog pages that exist but should not clutter the main sidebar.

---

## Previous and next links

The package automatically generates previous and next links for every page based on the visible reading order — the same order the sidebar entries appear in. Hidden pages are excluded from this sequence.

These links are available in the Blade view as `$previousPage` and `$nextPage` (both are `DocumentDescriptor` instances or `null`).

---

## Breadcrumbs

Breadcrumbs are generated automatically from the directory path of the current page. Each ancestor directory with an `index.md` becomes a breadcrumb entry. The root `index.md` is always the first breadcrumb.

Breadcrumbs are available in the view as the `$breadcrumbs` array.
