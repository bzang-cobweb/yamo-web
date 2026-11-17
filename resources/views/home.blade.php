{{-- Page d'accueil : logo (ou nom) centré et description, réglés dans le back-office (page Application). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $name }}</title>
        @if ($description !== null)
            <meta name="description" content="{{ $description }}">
        @endif
        <style>
            :root { color-scheme: light dark; --text: #1b1b18; --muted: #5f5f5a; --bg: #fdfdfc; }
            @media (prefers-color-scheme: dark) { :root { --text: #ededec; --muted: #a1a09a; --bg: #0a0a0a; } }
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, sans-serif; }
            main { width: 100%; max-width: 36rem; padding: 2rem 1rem; text-align: center; }
            .home__logo { display: block; max-width: 12rem; max-height: 12rem; margin: 0 auto; object-fit: contain; }
            .home__name { margin: 0; font-size: 2.5rem; line-height: 1.2; }
            .home__description { margin: 1.5rem 0 0; color: var(--muted); font-size: 1.125rem; line-height: 1.6; white-space: pre-line; }
        </style>
    </head>
    <body>
        <main class="home">
            <h1 class="home__name">
                @if ($logoUrl !== null)
                    <img class="home__logo" src="{{ $logoUrl }}" alt="{{ $name }}">
                @else
                    {{ $name }}
                @endif
            </h1>
            @if ($description !== null)
                <p class="home__description">{{ $description }}</p>
            @endif
        </main>
    </body>
</html>
