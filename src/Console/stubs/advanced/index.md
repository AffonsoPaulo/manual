---
title: Advanced
description: Caching, search, and customization topics for production use.
order: 3
key: advanced
---

# Advanced

This section covers topics you will likely care about when deploying to production or when you want to customize the look and behavior of the manual.

## [Caching](./caching.md)

How the two-layer cache works, how to configure the cache store and TTL, and how to use `manual:build` and `manual:clear` effectively. Start here if pages are stale or performance is a concern.

## [Search](./search.md)

The JSON search index — what it contains, how to consume it, and how to configure the endpoint. The search index is built automatically by `manual:build` and updated when files change.

## [Customization](./customization.md)

How to publish and replace the Blade views, swap in your own CSS and JavaScript, protect documentation behind authentication, and point the package at a different source directory.
