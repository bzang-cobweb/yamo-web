{{-- Page d'accueil : choix de la langue, logo centré (fichiers livrés dans public/images/logo) et description, réglée dans le back-office (page Application). --}}
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
            .home__locale { position: relative; display: inline-flex; align-items: center; margin: 0 0 2rem; color: var(--muted); }
            .home__locale svg { position: absolute; pointer-events: none; }
            .home__locale-globe { left: .75rem; }
            .home__locale-chevron { right: .75rem; }
            .home__locale select { appearance: none; -webkit-appearance: none; margin: 0; padding: .375rem 2.25rem; border: 1px solid currentColor; border-radius: 999px; background: transparent; color: var(--text); font: inherit; font-size: .875rem; cursor: pointer; }
            .home__locale button { margin-left: .5rem; font: inherit; font-size: .875rem; }
            .visually-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
            .home__description { margin: 1.5rem 0 0; color: var(--muted); font-size: 1.125rem; line-height: 1.6; white-space: pre-line; }
        </style>
    </head>
    <body>
        <main class="home">
            {{-- Choix de la langue, au-dessus du logo ; masqué s'il n'y a qu'une langue. --}}
            @if (count($locales) > 1)
                <form class="home__locale" method="get" action="{{ route('locale.switch') }}">
                    <label class="visually-hidden" for="locale">{{ __('home.locales') }}</label>
                    <svg class="home__locale-globe" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>
                    <select id="locale" name="locale" onchange="this.form.submit()">
                        @foreach ($locales as $code => $label)
                            <option value="{{ $code }}" lang="{{ $code }}" @selected($code === app()->getLocale())>{{ $label }}</option>
                        @endforeach
                    </select>
                    <svg class="home__locale-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    <noscript><button type="submit">OK</button></noscript>
                </form>
            @endif
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
