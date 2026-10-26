<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title') · {{ config('app.name') }}</title>

    <link rel="icon" href="{{ Vite::asset('resources/favicon/favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ Vite::asset('resources/favicon/favicon.ico') }}" sizes="any">
    <meta name="theme-color" content="#0d1e45">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap">

    @vite('resources/scss/default/styles.scss')
</head>
<body class="theme-afi">
<main class="hf-error">
    <div class="hf-error-card">
        <a href="/" class="hf-error-logo">
            <img src="{{ Vite::asset('resources/js/img/brand/hr-fabrics-logo-horizontal.svg') }}" alt="{{ config('app.name') }}">
        </a>

        <div class="hf-error-code" aria-hidden="true">@yield('code')</div>

        <span class="hf-error-eyebrow">{{ __('Error') }} @yield('code')</span>
        <h1 class="hf-error-title">@yield('message')</h1>
        <p class="hf-error-desc">@yield('detail', __('Something went wrong while loading this page.'))</p>

        <div class="hf-error-actions">
            <button type="button" class="btn btn-outline-secondary" onclick="history.length > 1 ? history.back() : (location.href = '/')">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                {{ __('Go back') }}
            </button>
            <a href="/" class="btn btn-primary">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
                {{ __('Back to dashboard') }}
            </a>
        </div>
    </div>

    <footer class="hf-error-footer">&copy; {{ date('Y') }} {{ config('app.name') }}</footer>
</main>
</body>
</html>
