@extends('layouts.site')

@section('title', 'Вход — Цветофор')

@section('robots', 'noindex, nofollow')

@section('body')
    @include('partials.header')
    <main class="authPage" id="loginPage">
        <div>
            <h1 class="serif">Вход в кабинет</h1>
            <p class="lead">Введите телефон, пришлём код в СМС. Пароль не нужен.</p>
        </div>
        <div class="panel auth">
            <div class="field">
                <label for="senderPhone">Телефон</label>
                <input id="senderPhone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+7 912 345-67-89" maxlength="32" required autofocus>
                <span class="err" id="errPhone" hidden></span>
            </div>
            <div class="codeRow" id="codeRow" hidden>
                <div class="field">
                    <label for="smsCode">Код из СМС</label>
                    <input id="smsCode" class="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="4" placeholder="••••">
                    <span class="err" id="errCode" hidden></span>
                </div>
                <button type="button" class="btn" id="verifyBtn">Войти</button>
                <button type="button" class="linkBtn" id="resendBtn" disabled>Отправить ещё раз</button>
            </div>
            <button type="button" class="btn" id="sendCodeBtn" style="margin-top:0">Получить код</button>
            <p class="toast" id="debugCode" hidden></p>
        </div>
        <p class="toast">Ещё нет подписки? <a href="{{ route('home') }}#plans">Выбрать план</a></p>
    </main>
    @include('partials.footer')
    <script type="application/json" id="login-data">{!! json_encode(['urls' => ['code' => route('auth.code'), 'verify' => route('auth.verify')], 'redirect' => route('profile')], JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endsection
