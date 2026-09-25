<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Restore the sidebar's collapsed state before first paint (no flash on refresh). --}}
    <script>
        try {
            if (/(?:^|;\s*)sidebar_state=false(?:;|$)/.test(document.cookie)) {
                document.documentElement.classList.add('hf-sidebar-collapsed');
            }
        } catch (e) {}
    </script>
    <!-- Favicons (resources/favicon, bundled by Vite via app.tsx) -->
    <link rel="icon" href="{{ Vite::asset('resources/favicon/favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ Vite::asset('resources/favicon/favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/favicon/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0d1e45">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

    <!-- Styles -->
    @vite('resources/scss/default/styles.scss')

    @viteReactRefresh
    @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.jsx"])
    <x-inertia::head>
        <title>{{ config('app.name') }}</title>
    </x-inertia::head>
</head>

<body class="theme-afi">
<x-inertia::app />
</body>
</html>
