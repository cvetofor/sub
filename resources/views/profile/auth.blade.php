@extends('layouts.app')

@section('title', 'Цветофор — Войти')

@section('content')
    <section class="flex h-screen items-center justify-center m-4">
        <div class="form_auth flex flex-col w-md p-6 bg-white rounded-3xl shadow-xl border border-rose-300 gap-4">
            <h1 class="text-2xl font-semibold mb-4">Вход в личный кабинет</h1>

            @if (session('success'))
                <div class="p-3 mb-4 text-green-700 bg-green-100 rounded-2xl">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-3 mb-4 text-red-700 bg-red-100 rounded-2xl">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                <div id="phone">
                    <label class="block text-sm text-gray-600 mb-1" for="phone_input">Номер телефона</label>
                    <input id="phone_input" name="phone" type="text" placeholder="+7 (123) 456-78-90"
                        class="w-full px-3 py-2 rounded-xl border border-rose-200" required autocomplete="username">
                </div>

                <div id="password">
                    <label class="block text-sm text-gray-600 mb-1" for="password_input">Пароль</label>
                    <input id="password_input" name="password" type="password" placeholder="Пароль"
                        class="w-full px-3 py-2 rounded-xl border border-rose-200" required autocomplete="current-password">
                </div>

                <button type="submit"
                    class="px-4 py-2 rounded-full text-white cursor-pointer transition-all duration-200 bg-rose-600 mt-2">
                    Войти
                </button>
            </form>

            <form id="reset_form" action="{{ route('reset-password') }}" method="POST" class="mt-2 flex flex-col gap-2">
                @csrf
                <input type="hidden" name="phone" id="reset_phone">
                <button type="submit"
                    class="w-full px-4 py-2 rounded-full border border-rose-400 text-rose-700 cursor-pointer transition-all duration-200 bg-rose-100">
                    Забыл пароль
                </button>
            </form>
        </div>
    </section>

    <script>
        $(function() {
            $("#phone_input").mask("+7 (999) 999-9999");

            $("#reset_form").on("submit", function(e) {
                const phone = $("#phone_input").val().trim();
                if (!phone) {
                    e.preventDefault();
                    alert("Пожалуйста, заполните номер телефона для сброса пароля!");
                    return false;
                }
                $("#reset_phone").val(phone);
            });
        });
    </script>

@endsection
