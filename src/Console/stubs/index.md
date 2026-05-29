---
title: Servera Manual
description: Ship a polished documentation area from Markdown files inside your Laravel application.
---

# Servera Manual

[![Latest Version](https://img.shields.io/packagist/v/affonsopaulo/manual)](https://packagist.org/packages/affonsopaulo/manual)
[![PHP Version](https://img.shields.io/packagist/php-v/affonsopaulo/manual)](https://packagist.org/packages/affonsopaulo/manual)
[![License](https://img.shields.io/packagist/l/affonsopaulo/manual)](https://packagist.org/packages/affonsopaulo/manual)

Welcome to the default manual scaffold for `affonsopaulo/manual`.

- Serve Markdown files as a documentation website.
- Organize pages by directories and `index.md` files.
- Control labels, navigation order, and routes through YAML front matter.
- Warm caches and build the search index with Artisan commands.

## Start Here

- [Installation](./getting-started/installation.md)
- [Configuration](./getting-started/configuration.md)
- [Front matter reference](./guides/front-matter.md)
- [Caching guide](./advanced/caching.md)

## Recommended Workflow

1. Write or scaffold your Markdown files.
2. Run `php artisan manual:build`.
3. Review the generated manual in the browser.
4. Re-run `manual:build` after structural changes.
