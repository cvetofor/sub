<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Цветофор — подписка на цветы')</title>
    <meta name="description" content="@yield('description', 'Свежие цветы по расписанию: себе в вазу или любимому человеку в руки. От 1 999 ₽ за букет, второй в подарок. Пауза без штрафов.')">
    <meta property="og:title" content="@yield('title', 'Цветофор — подписка на цветы')">
    <meta property="og:description" content="@yield('description', 'Свежие цветы по расписанию. От 1 999 ₽ за букет, второй в подарок.')">
    <meta property="og:image" content="{{ asset('images/site/hero-home.jpg') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @hasSection('robots')
        <meta name="robots" content="@yield('robots')">
    @endif
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">
    <link rel="preload" href="{{ asset('fonts/lora/lora-500-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/golos/golos-400-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
    <script>
        window.dataLayer = window.dataLayer || [];
        window.TRACK_URL = @json(route('track'));
        @if (config('services.dadata.token'))
            window.DADATA_TOKEN = @json(config('services.dadata.token'));
            window.DADATA_CITY = @json(\App\Services\AddressCheck::city());
        @endif
        @if (config('analytics.ym_id'))
            window.YM_ID = {{ (int) config('analytics.ym_id') }};
        @endif
    </script>
    @if (config('analytics.ym_id'))
        {{-- Яндекс.Метрика: цели и электронная коммерция через dataLayer --}}
        <script>
            (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
            (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
            ym({{ (int) config('analytics.ym_id') }}, "init", { clickmap: true, trackLinks: true, accurateTrackBounce: true, webvisor: false, ecommerce: "dataLayer" });
        </script>
        <noscript><div><img src="https://mc.yandex.ru/watch/{{ (int) config('analytics.ym_id') }}" style="position:absolute; left:-9999px;" alt=""></div></noscript>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="@yield('bodyClass')">
    @yield('body')
    @stack('scripts')
</body>
</html>
