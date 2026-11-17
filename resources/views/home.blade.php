{{-- Page d'accueil : logo centré (fichiers livrés dans public/images/logo) et description, réglée dans le back-office (page Application). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $name }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        @if ($description !== null)
            <meta name="description" content="{{ $description }}">
        @endif
        <style>
            :root { color-scheme: light dark; --text: #1b1b18; --muted: #5f5f5a; --bg: #fdfdfc; }
            @media (prefers-color-scheme: dark) { :root { --text: #ededec; --muted: #a1a09a; --bg: #0a0a0a; } }
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, sans-serif; }
            main { width: 100%; max-width: 36rem; padding: 2rem 1rem; text-align: center; }
            .home__name { margin: 0; line-height: 0; }
            .home__logo { display: block; width: 12rem; height: auto; margin: 0 auto; }
            .home__description { margin: 1.5rem 0 0; color: var(--muted); font-size: 1.125rem; line-height: 1.6; white-space: pre-line; }
        </style>
    </head>
    <body>
        <main class="home">
            <h1 class="home__name">
                <picture>
                    <source srcset="{{ asset('images/logo/yamo-logo-vertical-inverse.svg') }}" media="(prefers-color-scheme: dark)">
                    <img class="home__logo" src="{{ asset('images/logo/yamo-logo-vertical-couleur.svg') }}" alt="{{ $name }}" width="168" height="132">
                </picture>
            </h1>
            @if ($description !== null)
                <p class="home__description">{{ $description }}</p>
            @endif
        </main>
    </body>
</html>
