---
title: Guides
description: In-depth guides on every authoring feature — front matter, routing, navigation, linking, and images.
order: 2
key: guides
---

# Guides

This section covers every tool available when writing and organizing documentation.

## [Front Matter](./front-matter.md)

YAML metadata you add to the top of each file. Controls the page title, URL, navigation order, visibility, and the stable key used by dynamic helpers.

## [Routing](./routing.md)

How file paths are translated into public URLs. Covers the index.md convention, the route prefix, and the `slug` and `url` overrides for when you need a different URL than the one derived from the filename.

## [Navigation](./navigation.md)

How the sidebar is built from the directory structure. Covers ordering, section landing pages, and hidden pages that stay accessible by URL but are removed from the sidebar and search index.

## [Linking](./linking.md)

How to link between pages using relative Markdown links and the three dynamic helpers: `route()`, `doc()`, and `doc_public()`.

## [Images](./images.md)

How to add images to your pages using the `@image/` alias or relative paths, and how the package serves them through the same middleware as your documents.
