<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->title }} · {{ $siteTitle }}</title>
    @if ($document->description)
    <meta name="description" content="{{ $document->description }}">
    @endif

    @if ($assetsEnabled)
    <link rel="stylesheet" href="{{ asset('vendor/manual/manual.css') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        /* ── Tokens ──────────────────────────────────────────────────────────────── */
        :root {
            color-scheme: light;

            --bg:               #ffffff;
            --sidebar-bg:       #f9fafb;
            --sidebar-hover:    #f3f4f6;
            --sidebar-active:   rgba(37, 99, 235, 0.07);
            --sidebar-border:   #e5e7eb;
            --text:             #111827;
            --muted:            #6b7280;
            --subtle:           #9ca3af;
            --border:           #e5e7eb;
            --accent:           #2563eb;
            --accent-dark:      #1d4ed8;
            --accent-subtle:    #eff6ff;
            --accent-text:      #1e40af;
            --code-bg:          #0d1117;
            --code-text:        #e6edf3;
            --inline-bg:        #f3f4f6;
            --inline-text:      #374151;
            --table-header:     #f9fafb;

            --sidebar-w:        260px;
            --toc-w:            204px;
            --content-max:      728px;

            --font:             'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-heading:     'Syne', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-mono:        'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace;

            --ease:             cubic-bezier(0.16, 1, 0.3, 1);
            --t-fast:           110ms;
            --t:                180ms;
        }

        /* ── Reset ───────────────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { font-size: 16px; -webkit-text-size-adjust: 100%; }

        body {
            font-family: var(--font);
            font-size: 1rem;
            line-height: 1.7;
            color: var(--text);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        :focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
            border-radius: 4px;
        }

        a { color: var(--accent); text-decoration: none; }
        a:hover { color: var(--accent-dark); text-decoration: underline; }

        /* ── Shell ───────────────────────────────────────────────────────────────── */
        .manual-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
        }

        /* ── Sidebar ─────────────────────────────────────────────────────────────── */
        .manual-sidebar {
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--border) transparent;
            z-index: 20;
        }
        .manual-sidebar::-webkit-scrollbar { width: 4px; }
        .manual-sidebar::-webkit-scrollbar-track { background: transparent; }
        .manual-sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }

        .manual-sidebar-inner {
            padding: 1.25rem 0.875rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Brand */
        .manual-brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.375rem 0.5rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background var(--t-fast);
        }
        .manual-brand:hover { background: var(--sidebar-hover); text-decoration: none; }

        .manual-brand-mark {
            width: 26px;
            height: 26px;
            background: var(--accent);
            border-radius: 7px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .manual-brand-name {
            font-family: var(--font-heading);
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        /* ── Search ──────────────────────────────────────────────────────────────── */
        .manual-search-wrap { position: relative; }

        .manual-search-field {
            position: relative;
            display: flex;
            align-items: center;
        }

        .manual-search-icon {
            position: absolute;
            left: 0.625rem;
            color: var(--subtle);
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .manual-search {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.5rem 2.5rem 0.5rem 2.125rem;
            font-size: 0.8125rem;
            font-family: var(--font);
            color: var(--text);
            transition: border-color var(--t-fast), box-shadow var(--t-fast);
        }
        .manual-search::placeholder { color: var(--subtle); }
        .manual-search:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .manual-search-kbd {
            position: absolute;
            right: 0.5rem;
            font-size: 0.6875rem;
            color: var(--subtle);
            background: var(--sidebar-bg);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 0.1em 0.4em;
            font-family: var(--font-mono);
            pointer-events: none;
            line-height: 1.6;
        }

        .manual-search-results {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 0.375rem);
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06), 0 10px 20px -4px rgba(0,0,0,0.08);
            max-height: 360px;
            overflow-y: auto;
        }
        .manual-search-results[data-open="true"] { display: block; }

        .manual-search-results a {
            display: block;
            padding: 0.7rem 0.875rem;
            color: var(--text);
            border-top: 1px solid var(--border);
            transition: background var(--t-fast);
            text-decoration: none;
        }
        .manual-search-results a:first-child { border-top: none; }
        .manual-search-results a:hover,
        .manual-search-results a[data-active="true"] {
            background: var(--accent-subtle);
            text-decoration: none;
        }
        .manual-search-results a strong {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 0.125rem;
        }
        .manual-search-results a small {
            display: block;
            font-size: 0.75rem;
            color: var(--muted);
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .manual-search-empty {
            padding: 1rem 0.875rem;
            text-align: center;
            color: var(--subtle);
            font-size: 0.8125rem;
        }

        /* ── Navigation ──────────────────────────────────────────────────────────── */
        .manual-nav {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .manual-nav-group { display: contents; }

        .manual-nav-row {
            display: flex;
            align-items: center;
        }

        .manual-nav-item {
            flex: 1;
            display: block;
            padding: 0.375rem 0.5rem;
            font-size: 0.8125rem;
            font-weight: 400;
            line-height: 1.45;
            color: var(--muted);
            border-radius: 7px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            transition: color var(--t-fast), background var(--t-fast);
            text-decoration: none;
        }
        .manual-nav-item:hover { color: var(--text); background: var(--sidebar-hover); text-decoration: none; }
        .manual-nav-item[data-active="true"] {
            color: var(--accent-dark);
            background: var(--sidebar-active);
            font-weight: 500;
        }
        span.manual-nav-item {
            cursor: default;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--subtle);
            padding-top: 1rem;
            padding-bottom: 0.25rem;
        }
        span.manual-nav-item:hover { background: none; color: var(--subtle); }

        .manual-nav-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.375rem;
            height: 1.375rem;
            flex-shrink: 0;
            border: none;
            background: none;
            color: var(--subtle);
            cursor: pointer;
            border-radius: 5px;
            transition: color var(--t-fast), background var(--t-fast), transform var(--t);
            padding: 0;
            margin-right: 2px;
        }
        .manual-nav-toggle:hover { color: var(--text); background: var(--sidebar-hover); }
        .manual-nav-toggle[data-expanded="true"] { transform: rotate(90deg); }

        .manual-nav-children {
            display: none;
            flex-direction: column;
            gap: 1px;
            margin-top: 2px;
            margin-bottom: 4px;
            margin-left: 0.875rem;
            padding-left: 0.75rem;
            border-left: 1.5px solid var(--border);
        }
        .manual-nav-children[data-expanded="true"] { display: flex; }

        /* ── Mobile topbar ───────────────────────────────────────────────────────── */
        .manual-topbar {
            display: none;
            align-items: center;
            gap: 0.75rem;
            position: sticky;
            top: 0;
            z-index: 40;
            padding: 0 1rem;
            height: 52px;
            background: var(--bg);
            border-bottom: 1px solid var(--border);
        }

        .manual-menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.875rem;
            height: 1.875rem;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            border-radius: 7px;
            cursor: pointer;
            flex-shrink: 0;
            transition: background var(--t-fast);
        }
        .manual-menu-toggle:hover { background: var(--sidebar-hover); }

        .manual-topbar-title {
            font-size: 0.875rem;
            font-weight: 700;
            font-family: var(--font-heading);
            letter-spacing: -0.01em;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text);
        }

        /* ── Sidebar overlay ─────────────────────────────────────────────────────── */
        .manual-sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.25);
            z-index: 39;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        .manual-sidebar-overlay[data-visible="true"] { display: block; }

        /* ── Main ────────────────────────────────────────────────────────────────── */
        .manual-main {
            background: var(--bg);
            min-height: 100vh;
        }

        .manual-content-wrap {
            display: flex;
            gap: 3.5rem;
            max-width: calc(var(--content-max) + var(--toc-w) + 3.5rem + 6rem);
            margin: 0 auto;
            padding: 2.75rem 3rem 4.5rem;
        }

        /* ── Page content ────────────────────────────────────────────────────────── */
        .manual-page {
            flex: 1;
            min-width: 0;
            max-width: var(--content-max);
        }

        /* ── Table of contents ───────────────────────────────────────────────────── */
        .manual-toc {
            width: var(--toc-w);
            flex-shrink: 0;
        }

        .manual-toc-inner {
            position: sticky;
            top: 2rem;
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--border) transparent;
        }

        .manual-toc-label {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--subtle);
            margin-bottom: 0.625rem;
            display: block;
        }

        .manual-toc-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .manual-toc-item a {
            display: block;
            font-size: 0.8rem;
            color: var(--muted);
            padding: 0.3rem 0.5rem;
            border-radius: 5px;
            border-left: 2px solid transparent;
            margin-left: -2px;
            transition: color var(--t-fast), background var(--t-fast), border-color var(--t-fast);
            text-decoration: none;
            line-height: 1.4;
        }
        .manual-toc-item a:hover {
            color: var(--text);
            background: var(--sidebar-hover);
            text-decoration: none;
        }
        .manual-toc-item[data-active="true"] a {
            color: var(--accent);
            border-left-color: var(--accent);
            background: var(--accent-subtle);
        }

        .manual-toc-item.toc-h3 a { padding-left: 1.125rem; }
        .manual-toc-item.toc-h4 a { padding-left: 1.875rem; }

        /* ── Breadcrumbs ─────────────────────────────────────────────────────────── */
        .manual-breadcrumbs {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.25rem;
            color: var(--subtle);
            font-size: 0.8rem;
            margin-bottom: 1.75rem;
        }
        .manual-breadcrumbs .sep {
            color: var(--border);
            display: flex;
            align-items: center;
            user-select: none;
        }
        .manual-breadcrumbs a { color: var(--subtle); font-weight: 400; }
        .manual-breadcrumbs a:hover { color: var(--text); text-decoration: none; }
        .manual-breadcrumbs [aria-current="page"] { color: var(--text); font-weight: 500; }

        /* ── Page header ─────────────────────────────────────────────────────────── */
        .manual-page-header {
            margin-bottom: 2rem;
            padding-bottom: 1.75rem;
            border-bottom: 1px solid var(--border);
        }

        .manual-page-header h1 {
            font-family: var(--font-heading);
            font-size: clamp(1.75rem, 4vw, 2.375rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
            color: var(--text);
            margin-bottom: 0.625rem;
        }

        .manual-meta {
            font-size: 1rem;
            color: var(--muted);
            line-height: 1.65;
            font-weight: 300;
        }

        /* ── Article ─────────────────────────────────────────────────────────────── */
        .manual-article h2,
        .manual-article h3,
        .manual-article h4,
        .manual-article h5,
        .manual-article h6 {
            font-family: var(--font-heading);
            color: var(--text);
            letter-spacing: -0.02em;
            line-height: 1.25;
        }

        .manual-article h2 {
            font-size: clamp(1.125rem, 2.5vw, 1.375rem);
            font-weight: 700;
            margin: 2.25rem 0 0.75rem;
            padding-top: 0.25rem;
        }

        .manual-article h3 {
            font-size: clamp(1rem, 2vw, 1.125rem);
            font-weight: 700;
            margin: 2rem 0 0.625rem;
        }

        .manual-article h4 {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 1.75rem 0 0.5rem;
        }

        .manual-article h5,
        .manual-article h6 {
            font-size: 0.875rem;
            font-weight: 600;
            margin: 1.5rem 0 0.4rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Anchor links */
        .manual-heading-anchor {
            opacity: 0;
            margin-left: 0.375em;
            font-size: 0.7em;
            font-family: var(--font-mono);
            color: var(--subtle);
            font-weight: 400;
            text-decoration: none;
            transition: opacity var(--t-fast);
        }
        .manual-article h2:hover .manual-heading-anchor,
        .manual-article h3:hover .manual-heading-anchor,
        .manual-article h4:hover .manual-heading-anchor { opacity: 1; }

        .manual-article p,
        .manual-article li {
            font-size: 0.9375rem;
            line-height: 1.75;
            color: var(--text);
        }

        .manual-article p { margin: 0 0 1rem; }
        .manual-article p:last-child { margin-bottom: 0; }

        .manual-article a { color: var(--accent); font-weight: 500; }
        .manual-article a:hover { color: var(--accent-dark); }

        .manual-article strong { font-weight: 600; color: var(--text); }
        .manual-article em { font-style: italic; }

        /* Images */
        .manual-article img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            border: 1px solid var(--border);
            display: block;
            margin: 1.5rem auto;
        }

        /* Lists */
        .manual-article ul,
        .manual-article ol {
            padding-left: 1.5rem;
            margin: 0 0 1rem;
        }
        .manual-article li { margin-bottom: 0.25rem; }
        .manual-article li > ul,
        .manual-article li > ol { margin-top: 0.25rem; margin-bottom: 0; }

        /* Tables */
        .manual-article .manual-table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 1.5rem 0;
            border: 1px solid var(--border);
            border-radius: 10px;
        }
        .manual-article table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            overflow: hidden;
        }
        .manual-article th {
            background: var(--table-header);
            font-family: var(--font);
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .manual-article th,
        .manual-article td {
            border-bottom: 1px solid var(--border);
            padding: 0.625rem 0.875rem;
            text-align: left;
        }
        .manual-article tr:last-child td { border-bottom: none; }
        .manual-article tbody tr:hover td { background: var(--sidebar-hover); }

        /* Code blocks */
        .manual-article pre {
            background: var(--code-bg);
            color: var(--code-text);
            border-radius: 10px;
            padding: 1.125rem 1.25rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            position: relative;
            margin: 1.25rem 0;
            border: 1px solid rgba(255,255,255,0.05);
            font-size: 0.8125rem;
            max-width: 100%;
            min-width: 0;
        }
        .manual-article pre code {
            background: none;
            padding: 0;
            border-radius: 0;
            color: inherit;
            font-size: inherit;
            border: none;
        }
        .manual-article code {
            font-family: var(--font-mono);
            font-size: 0.875em;
        }
        .manual-article :not(pre) > code {
            background: var(--inline-bg);
            color: var(--inline-text);
            padding: 0.175em 0.4em;
            border-radius: 5px;
            font-size: 0.8em;
            border: 1px solid var(--border);
        }

        /* Copy button */
        .manual-copy-btn {
            position: absolute;
            top: 0.625rem;
            right: 0.625rem;
            padding: 0.25rem 0.625rem;
            font-size: 0.6875rem;
            font-family: var(--font);
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.45);
            border-radius: 5px;
            cursor: pointer;
            transition: background var(--t-fast), color var(--t-fast), opacity var(--t);
            opacity: 0;
        }
        .manual-article pre:hover .manual-copy-btn { opacity: 1; }
        .manual-copy-btn:hover { background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.85); }
        .manual-copy-btn[data-copied="true"] { color: #4ade80; border-color: rgba(74,222,128,0.2); }

        /* Blockquote */
        .manual-article blockquote {
            margin: 1.25rem 0;
            padding: 0.875rem 1.125rem;
            border-left: 3px solid var(--accent);
            background: var(--accent-subtle);
            border-radius: 0 8px 8px 0;
        }
        .manual-article blockquote p { margin: 0; color: var(--accent-text); }
        .manual-article blockquote p + p { margin-top: 0.5rem; }

        /* HR */
        .manual-article hr {
            border: none;
            border-top: 1px solid var(--border);
            margin: 2rem 0;
        }

        /* ── Pagination ──────────────────────────────────────────────────────────── */
        .manual-pagination {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.875rem;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        .manual-pagination a,
        .manual-pagination span {
            display: block;
            padding: 1rem 1.125rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            text-decoration: none;
            transition: border-color var(--t-fast), box-shadow var(--t-fast);
        }
        .manual-pagination a:hover {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
            text-decoration: none;
        }
        .manual-pagination-next { text-align: right; }

        .manual-pg-label {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--subtle);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.375rem;
        }
        .manual-pagination-next .manual-pg-label { justify-content: flex-end; }

        .manual-pg-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text);
            line-height: 1.3;
        }
        .manual-pagination a:hover .manual-pg-title { color: var(--accent); }

        /* ── Responsive ──────────────────────────────────────────────────────────── */
        @media (max-width: 1100px) {
            .manual-toc { display: none; }
            .manual-content-wrap { padding: 2.5rem 2.5rem 3.5rem; gap: 0; }
        }

        @media (max-width: 960px) {
            .manual-topbar { display: flex; }
            .manual-shell { grid-template-columns: 1fr; }

            .manual-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 40;
                transform: translateX(-100%);
                transition: transform 0.25s var(--ease);
                width: var(--sidebar-w);
                max-width: calc(100vw - 3rem);
            }
            .manual-sidebar[data-open="true"] { transform: translateX(0); }

            .manual-main { padding-top: 0; }
            .manual-content-wrap { padding: 1.5rem 1.25rem 3rem; }

            /* Prevent any element from expanding the layout viewport */
            body { overflow-x: hidden; }

            /* Ensure the article and its children never exceed the column width */
            .manual-article { min-width: 0; max-width: 100%; }

            .manual-article pre {
                max-width: calc(100vw - 2.5rem);
            }

            .manual-article .manual-table-wrap {
                max-width: calc(100vw - 2.5rem);
            }
        }

        @media (max-width: 640px) {
            .manual-pagination { grid-template-columns: 1fr; }
            .manual-pagination-next { text-align: left; }
            .manual-pagination-next .manual-pg-label { justify-content: flex-start; }
        }

        @media print {
            .manual-topbar, .manual-sidebar, .manual-sidebar-overlay,
            .manual-pagination, .manual-copy-btn, .manual-toc { display: none !important; }
            .manual-shell { grid-template-columns: 1fr; }
            .manual-content-wrap { padding: 0; max-width: 100%; }
        }
    </style>
</head>

<body>
    {{-- Mobile topbar --}}
    <header class="manual-topbar" role="banner">
        <button
            class="manual-menu-toggle"
            aria-label="Abrir navegação"
            aria-expanded="false"
            aria-controls="manual-sidebar"
            data-sidebar-toggle>
            <svg width="15" height="11" viewBox="0 0 15 11" fill="none" aria-hidden="true">
                <rect width="15" height="1.75" rx="0.875" fill="currentColor"/>
                <rect y="4.625" width="15" height="1.75" rx="0.875" fill="currentColor"/>
                <rect y="9.25" width="15" height="1.75" rx="0.875" fill="currentColor"/>
            </svg>
        </button>
        <span class="manual-topbar-title">{{ $siteTitle }}</span>
    </header>

    <div class="manual-sidebar-overlay" data-sidebar-overlay aria-hidden="true"></div>

    <div class="manual-shell" data-search-endpoint="{{ $searchEndpoint }}">
        <aside class="manual-sidebar" id="manual-sidebar">
            <div class="manual-sidebar-inner">
                @php
                    $manualHomeUrl = config('manual.route_prefix')
                        ? url(config('manual.route_prefix'))
                        : url('/');
                @endphp
                <a href="{{ $manualHomeUrl }}" class="manual-brand">
                    <span class="manual-brand-mark" aria-hidden="true">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                            <path d="M2 3.5h9M2 6.5h5.5M2 9.5h7" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="manual-brand-name">{{ $siteTitle }}</span>
                </a>

                @if ($searchEndpoint)
                <div class="manual-search-wrap">
                    <div class="manual-search-field">
                        <span class="manual-search-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                                <circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.25"/>
                                <path d="M8.5 8.5L11 11" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            class="manual-search"
                            type="search"
                            placeholder="Buscar…"
                            aria-label="Buscar no manual"
                            aria-autocomplete="list"
                            aria-controls="manual-search-results"
                            autocomplete="off"
                            data-manual-search>
                        <span class="manual-search-kbd" aria-hidden="true">⌘K</span>
                    </div>
                    <div
                        id="manual-search-results"
                        class="manual-search-results"
                        role="listbox"
                        aria-label="Resultados da busca"
                        data-manual-search-results></div>
                </div>
                @endif

                <nav class="manual-nav" aria-label="Índice do manual">
                    @include('manual::partials.navigation', ['items' => $navigation])
                </nav>
            </div>
        </aside>

        <main class="manual-main">
            <div class="manual-content-wrap">
                <div class="manual-page">
                    <nav class="manual-breadcrumbs" aria-label="Trilha de navegação">
                        @foreach ($breadcrumbs as $breadcrumb)
                        @if (!$loop->first)
                        <span class="sep" aria-hidden="true">
                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                                <path d="M3.5 2L6.5 5L3.5 8" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        @endif
                        @if ($breadcrumb->url && !$loop->last)
                        <a href="{{ $breadcrumb->url }}">{{ $breadcrumb->title }}</a>
                        @else
                        <span @if ($loop->last) aria-current="page" @endif>{{ $breadcrumb->title }}</span>
                        @endif
                        @endforeach
                    </nav>

                    <header class="manual-page-header">
                        <h1>{{ $document->title }}</h1>
                        @if ($document->description)
                        <p class="manual-meta">{{ $document->description }}</p>
                        @endif
                    </header>

                    <article class="manual-article">
                        {!! $page->html !!}
                    </article>

                    <nav class="manual-pagination" aria-label="Páginas anterior e próxima">
                        @if ($previousPage)
                        <a href="{{ $previousPage['url'] }}" class="manual-pagination-prev">
                            <span class="manual-pg-label">
                                <svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true">
                                    <path d="M7 2L4 5.5L7 9" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Anterior
                            </span>
                            <span class="manual-pg-title">{{ $previousPage['title'] }}</span>
                        </a>
                        @else
                        <span></span>
                        @endif

                        @if ($nextPage)
                        <a href="{{ $nextPage['url'] }}" class="manual-pagination-next">
                            <span class="manual-pg-label">
                                Próxima
                                <svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true">
                                    <path d="M4 2L7 5.5L4 9" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="manual-pg-title">{{ $nextPage['title'] }}</span>
                        </a>
                        @else
                        <span></span>
                        @endif
                    </nav>
                </div>

                <aside class="manual-toc" id="manual-toc" aria-label="Nesta página">
                    {{-- Populated by JS below --}}
                </aside>
            </div>
        </main>
    </div>

    @if ($assetsEnabled)
    <script src="{{ asset('vendor/manual/manual.js') }}" defer></script>
    @endif

    <script>
        // TOC builder — runs before DOMContentLoaded since it's at end of body
        (function () {
            var article = document.querySelector('.manual-article');
            var tocEl   = document.getElementById('manual-toc');
            if (!article || !tocEl) return;

            var headings = Array.from(article.querySelectorAll('h2, h3, h4'));
            if (headings.length < 2) return;

            var inner = document.createElement('div');
            inner.className = 'manual-toc-inner';

            var label = document.createElement('span');
            label.className = 'manual-toc-label';
            label.textContent = 'Nesta página';
            inner.appendChild(label);

            var list = document.createElement('ul');
            list.className = 'manual-toc-list';

            headings.forEach(function (h) {
                if (!h.id) return;
                var li = document.createElement('li');
                li.className = 'manual-toc-item toc-' + h.tagName.toLowerCase();
                var a = document.createElement('a');
                a.href = '#' + h.id;
                a.textContent = h.textContent.replace(/\s+#\s*$/, '').trim();
                li.appendChild(a);
                list.appendChild(li);
            });

            if (list.children.length < 2) return;

            inner.appendChild(list);
            tocEl.appendChild(inner);

            // Highlight active section on scroll
            var items = Array.from(list.querySelectorAll('.manual-toc-item'));
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var id = entry.target.id;
                    items.forEach(function (item) { item.removeAttribute('data-active'); });
                    var active = list.querySelector('a[href="#' + id + '"]');
                    if (active) active.parentElement.setAttribute('data-active', 'true');
                });
            }, { rootMargin: '0px 0px -55% 0px', threshold: 0.1 });

            headings.forEach(function (h) { if (h.id) observer.observe(h); });
        }());
    </script>
</body>

</html>
