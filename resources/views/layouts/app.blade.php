<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon" />
    <link rel="stylesheet" href="{{ asset('css/app.css')}}">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body>
    @include('layouts.header')

    <main class="bg-linear-to-t from-rose-50 to-neutral-50">
        @yield('content')
    </main>

    @include('layouts.footer')
</body>

</html>
