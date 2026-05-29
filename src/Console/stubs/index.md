---
title: Manual
description: A Laravel package that turns a directory of Markdown files into a fully-rendered, searchable documentation site.
order: 0
key: home
---

# Manual

[![Latest Version](https://img.shields.io/packagist/v/affonsopaulo/manual)](https://packagist.org/packages/affonsopaulo/manual)
[![PHP Version](https://img.shields.io/packagist/php-v/affonsopaulo/manual)](https://packagist.org/packages/affonsopaulo/manual)
[![License](https://img.shields.io/packagist/l/affonsopaulo/manual)](https://packagist.org/packages/affonsopaulo/manual)

Manual turns a directory of Markdown files into a fully-rendered, searchable documentation site — self-hosted inside your Laravel application. No build step, no Node.js, no database required.

## How It Works

1. Write `.md` files inside a directory of your choice (default: `docs/manual`).
2. The package scans the directory, derives navigation and URLs from the file structure, renders Markdown to HTML, and caches the result.
3. Every URL under the configured route prefix is handled automatically — no route registration needed.

## Features

- **Zero configuration to get started** — sensible defaults cover most use cases out of the box.
- **File-system driven routing** — the directory layout is the navigation. No config files, no route declarations.
- **YAML front matter** — control titles, URLs, ordering, visibility, and more per page.
- **Two-layer cache** — a manifest cache for the document graph and a per-page HTML cache, both automatically invalidated when files change.
- **Client-side search** — a JSON endpoint powers full-text search without a search server.
- **Image serving** — images are served through the same middleware stack as your documents.
- **Dynamic helpers** — embed Laravel route URLs and links to other doc pages directly in Markdown.
- **No build tooling** — compiled assets ship inside the package.

## Getting Started

If you have not set up the package yet, start here:

- [Installation](./getting-started/installation.md)
- [Configuration](./getting-started/configuration.md)

## Guides

Deeper explanations of every authoring feature:

- [Front Matter](./guides/front-matter.md) — control titles, order, URLs, and visibility per page.
- [Routing](./guides/routing.md) — understand how files map to URLs.
- [Navigation](./guides/navigation.md) — shape the sidebar and reading order.
- [Linking](./guides/linking.md) — relative links, hash fragments, and dynamic helpers.
- [Images](./guides/images.md) — add images to your pages.

## Advanced

Production and customization topics:

- [Caching](./advanced/caching.md) — two-layer cache, TTL settings, build and clear commands.
- [Search](./advanced/search.md) — the JSON search index and how to use it.
- [Customization](./advanced/customization.md) — replace views, change middleware, adjust assets.
