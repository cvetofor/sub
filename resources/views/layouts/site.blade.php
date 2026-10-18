<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Цветофор — подписка на цветы')</title>
    <meta name="description" content="@yield('description', 'Свежие цветы по расписанию: себе в вазу или любимому человеку в руки. От 1 999 ₽ за букет, второй в подарок. Пауза без штрафов.')">
    <meta property="og:title" content="@yield('title', 'Цветофор — подписка на цветы')">
    <meta property="og:description" content="@yield('description', 'Свежие цветы по расписанию. От 1 999 ₽ за букет, второй в подарок.')">
    <meta property="og:image" content="{{ asset('images/site/hero-home.jpg') }}">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">
    <link rel="preload" href="{{ asset('fonts/lora/lora-500-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/golos/golos-400-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="@yield('bodyClass')">
    @yield('body')
    @stack('scripts')
</body>
</html>
