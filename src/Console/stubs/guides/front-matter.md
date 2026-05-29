---
title: Front Matter
description: Reference for every supported YAML front matter attribute.
---

# Front Matter

Each Markdown file may start with a YAML front matter block:

```yaml
---
title: Installation
slug: setup
url: guides/setup
order: 2
description: Prepare the package for first use.
key: guides.installation
hidden: false
---
```

## Available Fields

| Field | Type | Purpose |
| --- | --- | --- |
| `title` | string | Overrides the page title shown in the manual. |
| `slug` | string | Replaces only the last URL segment for the current file. |
| `url` | string | Replaces the full public URL path for the current document. |
| `order` | integer | Controls navigation order before the alphabetical fallback. |
| `description` | string | Provides summary text for search results and page metadata. |
| `key` | string | Creates a stable identifier for `doc()` helper lookups. |
| `hidden` | boolean | Removes the page from navigation and search while keeping its URL accessible. |
