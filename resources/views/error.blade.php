<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteTitle }} · Erro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f9fafb;
            color: #111827;
            -webkit-font-smoothing: antialiased;
        }

        main {
            width: min(640px, 100%);
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 2.25rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04);
        }

        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 0.25rem 0.625rem;
            margin-bottom: 1.125rem;
        }

        h1 {
            font-family: 'Syne', -apple-system, sans-serif;
            font-size: clamp(1.375rem, 3vw, 1.75rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #111827;
            margin-bottom: 0.625rem;
            line-height: 1.2;
        }

        p {
            font-size: 0.9375rem;
            line-height: 1.7;
            color: #6b7280;
            margin-bottom: 1.25rem;
        }

        .error-code {
            display: block;
            margin-top: 0;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            background: #0d1117;
            color: #e6edf3;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.8125rem;
            line-height: 1.6;
            border: 1px solid rgba(255,255,255,0.05);
        }
    </style>
</head>

<body>
    <main>
        <span class="error-badge">
            <svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true">
                <circle cx="5.5" cy="5.5" r="4.5" stroke="currentColor" stroke-width="1.25"/>
                <path d="M5.5 3.25v2.5M5.5 7.25v.25" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
            </svg>
            Erro de renderização
        </span>
        <h1>Erro ao carregar o manual</h1>
        <p>O pacote identificou um problema na documentação e interrompeu a renderização para evitar conteúdo inconsistente.</p>
        <code class="error-code">{{ $message }}</code>
    </main>
</body>

</html>
