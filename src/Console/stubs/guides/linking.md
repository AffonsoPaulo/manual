---
title: Linking
description: Compare relative Markdown links with the supported dynamic helpers.
---

# Linking

The package supports standard relative Markdown links and a restricted set of safe helpers.

## Relative Markdown Links

```md
[Installation](../getting-started/installation.md)
[Caching](../advanced/caching.md)
```

These links are rewritten to public manual URLs during rendering.

## Dynamic Helpers

Write helpers in Markdown when you want URL generation tied to route names or Laravel routes:

```md
{{ route('login') }}
{{ doc('guides.installation') }}
{{ doc_public('guides/front-matter') }}
```

Helpers also work inside Markdown links:

```md
[Login]({{ route('login') }})
[Installation]({{ doc('guides.installation') }})
[Front matter]({{ doc_public('guides/front-matter') }})
```

Keep examples inside code blocks when you want the literal syntax to appear in the manual.
