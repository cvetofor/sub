<header class="hdr">
    <div class="wrap">
        <a class="logo" href="{{ route('home') }}" aria-label="Цветофор, на главную">
            <img src="{{ asset('images/logo.svg') }}" alt="" width="140" height="50">
        </a>
        <nav aria-label="Основное">
            <a href="{{ route('home') }}#how">Как это работает</a>
            <a href="{{ route('home') }}#plans">Планы</a>
            <a href="{{ route('home') }}#gallery">Из последних доставок</a>
            <a href="{{ route('home') }}#faq">Вопросы</a>
        </nav>
        <div class="right">
            <a class="plansLink" href="{{ route('home') }}#plans">Планы</a>
            <a class="login" href="{{ auth()->check() ? route('profile') : route('auth.login') }}">{{ auth()->check() ? 'Мои подписки' : 'Войти' }}</a>
        </div>
    </div>
</header>
