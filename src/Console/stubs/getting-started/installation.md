---
title: Installation
description: Install the package and publish the assets you want to customize.
---

# Installation

Install the package with Composer:

```bash
composer require affonsopaulo/manual
```

Publish the configuration file when you need to override defaults:

```bash
php artisan vendor:publish --tag=manual-config
```

Publish the Blade views or compiled assets only when you want to customize them:

```bash
php artisan vendor:publish --tag=manual-views
php artisan vendor:publish --tag=manual-assets
```

After creating your docs, build the manifest, page cache, and search index:

```bash
php artisan manual:build
```
