<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&display=swap">

    @vite('resources/scss/default/styles.scss')

</head>
<body class="antialiased">
<div class="page-container">
    <div class="error">
        <div class="error-code m-b-10">@yield('code') <i class="fa fa-warning"></i></div>
        <div class="error-content">
            <div class="error-message">@yield('message')</div>
            <div class="error-desc m-b-20">
                @yield('detail')
            </div>
            <div>
                <a href="/" class="btn btn-success">Go Back to Home Page</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
