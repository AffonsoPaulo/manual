---
title: Customization
description: Publish package resources and adapt the manual to your application.
---

# Customization

You can keep the default experience or publish specific resources for customization.

## Publishable Resources

```bash
php artisan vendor:publish --tag=manual-config
php artisan vendor:publish --tag=manual-views
php artisan vendor:publish --tag=manual-assets
```

## Common Customizations

- Override the Blade view with `manual.view`.
- Change the manual middleware stack with `manual.middleware`.
- Disable automatic asset loading with `manual.assets.enabled`.
- Point `manual.source_path` to a different docs directory when your project needs another structure.
