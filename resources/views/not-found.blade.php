<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página não encontrada · {{ $siteTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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
            width: min(480px, 100%);
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 2.75rem 2.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04);
            text-align: center;
        }

        .not-found-num {
            font-family: 'Outfit', -apple-system, sans-serif;
            font-size: clamp(3.5rem, 12vw, 5.5rem);
            font-weight: 800;
            letter-spacing: -0.05em;
            color: #e5e7eb;
            line-height: 1;
            margin-bottom: 0.5rem;
            display: block;
        }

        h1 {
            font-family: 'Outfit', -apple-system, sans-serif;
            font-size: clamp(1.25rem, 3vw, 1.5rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            color: #111827;
            margin-bottom: 0.75rem;
            line-height: 1.2;
        }

        p {
            font-size: 0.9375rem;
            line-height: 1.65;
            color: #6b7280;
            margin-bottom: 2rem;
        }

        a {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.625rem 1.25rem;
            background: #2563eb;
            color: #ffffff;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.12s;
        }
        a:hover { background: #1d4ed8; }
    </style>
</head>

<body>
    <main>
        <span class="not-found-num" aria-hidden="true">404</span>
        <h1>Página não encontrada</h1>
        <p>O documento que você está procurando não existe ou foi movido para outro endereço.</p>
        <a href="{{ $homeUrl }}">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none" aria-hidden="true">
                <path d="M8.5 2.5L5 6.5L8.5 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Voltar ao início
        </a>
    </main>
</body>

</html>
