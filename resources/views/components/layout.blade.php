<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{!empty($pageTitle) ? $pageTitle .' - ' : ''}}{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link
            href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:ital,wght@0,200;0,300;0,400;0,600;0,700;1,200;1,300;1,400;1,600;1,700&display=swap"
            rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&display=swap">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/fontawesome.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/solid.min.css" rel="stylesheet">
    <!-- Styles -->
    @vite('resources/scss/styles.scss')
</head>
<body class="">
<div class="app app-container app-without-sidebar app-header-fixed app-with-top-menu">
    <div class="app-header navbar-default">
        <div class="navbar-header">
            <a href="{{route('dashboard')}}" class="navbar-brand">
                <img src="{{asset('images/logo.png')}}"
                     alt="{{config('app.name')}}" class="logo">{{config('app.name')}}</a>
            <button type="button" class="navbar-mobile-toggler">
                <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
            </button>
        </div>
    </div>
    <div id="top-menu" class="app-top-menu">
        <div class="menu">
            <div class="menu-item">
                <a class="menu-link" href="{{route('dashboard')}}">
                    <span class="menu-text">Dashboard</span></a>
            </div>
            <div class="menu-item active">
                <a class="menu-link" href="{{route('exceptions.home')}}">
                    <span class="menu-text">Exceptions</span>
                </a>
            </div>
        </div>
    </div>
    <main class="app-content" id="content">
        {{ $slot }}
    </main>
</div>

</body>
</html>
