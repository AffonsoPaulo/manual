---
title: Navigation
description: Control how pages appear in the sidebar and in previous or next links.
---

# Navigation

Navigation is built from directories, `index.md` files, and visible documents.

## Ordering

- Use `order` for explicit sorting.
- When `order` is missing, the package falls back to alphabetical sorting.
- Section landing pages usually live in `index.md`.

## Visibility

```yaml
---
hidden: true
---
```

Hidden documents still render by URL, but they are removed from the sidebar and search index.

## Folder Shape

The directory structure directly influences the sidebar:

```text
docs/manual
├── index.md
├── getting-started
│   ├── index.md
│   └── installation.md
└── guides
    ├── index.md
    └── routing.md
```
