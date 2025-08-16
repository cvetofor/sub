@extends('layouts.app')

@section('title', 'Цветофор — Подписка на цветы')

@section('content')
    <section class="flex mx-auto max-w-7xl px-4 pt-10 pb-8">
        <div class="grid md:grid-cols-2 gap-8 items-center">
            <div class="flex flex-col">
                <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight leading-tight">Подписка на цветы: свежий букет
                    в
                    ваш стиль — без хлопот</h1>
                <p class="mt-4 text-lg text-gray-700">Доставляем в Ангарске, Улан-Удэ и Кяхте (+20 км). Вы выбираете частоту
                    и
                    бюджет — мы собираем идеальные букеты под ваши предпочтения. Гарантия свежести 48 часов.</p>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-sm">Замена при получении, если не
                        понравилось</span>
                    <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-sm">Пауза по заявке</span>
                    <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-sm">Фото-подтверждение +199 ₽ (опция)
                    </span>
                </div>
                <div class="mt-6 flex gap-3">
                    <button class="px-5 py-3 rounded-xl bg-rose-600 text-white shadow cursor-pointer" id="choosePlanBtn">
                        Выбрать план
                    </button>
                    <button class="px-5 py-3 rounded-xl border border-rose-200 bg-white shadow-sm cursor-pointer">
                        Просто по бюджету
                    </button>
                </div>
                <p class="mt-4 text-sm text-gray-600">Спецпредложение: <b>в первый месяц — второй букет бесплатно</b> для
                    всех тарифов (для новых клиентов, при ≥ 2 доставках в первый месяц).</p>
            </div>
            <div class="relative">
                <div
                    class="aspect-[4/3] rounded-3xl bg-white shadow-xl border border-rose-100 flex items-center justify-center">
                    <div class="p-6 text-center">
                        <h1 class="text-xl font-semibold">Вот так это выглядит</h1>
                        <p class="text-gray-600 mt-2">Реальные примеры: Современный минимализм · Яркая классика ·
                            Эко/полевые · Авторский</p>
                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <img class="h-20 bg-rose-100/60 rounded-xl" src="" alt="example_photo_1">
                            <img class="h-20 bg-rose-100/60 rounded-xl" src="" alt="example_photo_2">
                            <img class="h-20 bg-rose-100/60 rounded-xl" src="" alt="example_photo_3">
                            <img class="h-20 bg-rose-100/60 rounded-xl" src="" alt="example_photo_4">
                            <img class="h-20 bg-rose-100/60 rounded-xl" src="" alt="example_photo_5">
                            <img class="h-20 bg-rose-100/60 rounded-xl" src="" alt="example_photo_6">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pt-2 pb-10" id="plansSection">
        <div class="flex flex-wrap items-center gap-2">
            <button id="readyBtn" class="plan-btn active flex-1 sm:flex-none">Готовые планы</button>
            <button id="customBtn" class="plan-btn flex-1 sm:flex-none">Собрать самому</button>
            <div class="ml-auto flex flex-wrap items-center gap-2 text-sm btn-group">
                <p class="text-gray-600 w-full sm:w-auto">Частота:</p>
                <button
                    class="toggle-btn px-3 py-1 rounded-xl border bg-rose-100 border-rose-400 text-rose-700 active">Еженедельно</button>
                <button class="toggle-btn px-3 py-1 rounded-xl border bg-white border-rose-200">Раз в 2 недели</button>
                <button class="toggle-btn px-3 py-1 rounded-xl border bg-white border-rose-200">Раз в месяц</button>
            </div>
        </div>


        <div class="plans-wrapper mt-6 swiper-container relative" id="plansSection">
            <div class="swiper-wrapper">
                @foreach ($plans as $plan)
                    <div class="swiper-slide rounded-3xl border p-5 shadow-sm border-rose-500 shadow-rose-100 shadow-lg">
                        <div class="flex items-baseline justify-between mb-2">
                            <h1 class="flex items-baseline justify-between mb-2 text-xl font-semibold">{{ $plan->name }}
                            </h1>
                            <p class="text-rose-600 text-xs font-semibold">выбрано</p>
                        </div>
                        <div class="text-3xl font-bold">{{ $plan->price }} ₽ <span
                                class="text-base font-medium text-gray-600">за
                                доставку</span></div>
                        <p class="mt-1 text-sm text-gray-600">≈ 4 доставк(и) в месяц</p>
                        <p class="mt-3 text-sm text-gray-700 min-h-[48px]">{{ $plan->description }}</p>
                        <p class="mt-3 text-sm text-gray-600">Опции:
                            @foreach ($plan->options as $option)
                                <span>{{ $option->name }} +{{ $option->price }}₽ @if (!$loop->last), @endif </span>
                            @endforeach
                        </p>
                        <div class="mt-4 p-3 rounded-2xl bg-rose-100 border border-rose-200">
                            <p class="text-sm text-gray-700">Итого в месяц (с выбранными опциями):</p>
                            <p class="text-2xl font-extrabold">123₽</p>
                        </div>
                        <button class="mt-4 w-full px-4 py-2 rounded-2xl bg-rose-600 text-white">Выбрать план</button>
                    </div>
                @endforeach
            </div>

            <div class="swiper-button swiper-button-next"></div>
            <div class="swiper-button swiper-button-prev"></div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-14 grid md:grid-cols-2 gap-8">
        <div class="p-6 rounded-3xl border border-rose-200 bg-white shadow-sm">
            <h2 class="text-xl font-semibold mb-2">Гарантии и условия</h2>
            <ul class="space-y-2 text-gray-700 text-sm">
                <li>48 часов гарантия свежести — заменим букет, если увял.</li>
                <li>Не понравилось при получении — сделаем замену сразу.</li>
                <li>Пауза по заявке — без штрафов.</li>
                <li>Доставка: город и до 20 км за пределами (+899 ₽).</li>
                <li>Фото-подтверждение по желанию (+199 ₽ за доставку).</li>
            </ul>
        </div>
        <div class="p-6 rounded-3xl border border-rose-200 bg-white shadow-sm">
            <h2 class="text-xl font-semibold mb-2">Вопросы и ответы</h2>
            <details class="mb-2">
                <summary class="cursor-pointer font-medium">
                    Подписка навсегда? Как отменить?
                </summary>
                <p class="text-sm text-gray-700 mt-2">Отмена и пауза по заявке в один клик, без штрафов. Оплата всегда
                    рекуррентная, списание по графику
                    доставок.</p>
            </details>
            <details class="mb-2">
                <summary class="cursor-pointer font-medium">
                    Не будет ли одно и то же?
                </summary>
                <p class="text-sm text-gray-700 mt-2">Профиль предпочтений + сезонные подборки. Можно исключить конкретные
                    цветы и сильные ароматы.</p>
            </details>
            <details class="mb-2">
                <summary class="cursor-pointer font-medium">
                    Что, если опоздаете?
                </summary>
                <p class="text-sm text-gray-700 mt-2">Работаем в 2‑часовых окнах 09:00–23:00. При сдвиге предупредим заранее
                    и компенсируем бонусом.</p>
            </details>
            <details class="mb-2">
                <summary class="cursor-pointer font-medium">
                    Можно в подарок?
                </summary>
                <p class="text-sm text-gray-700 mt-2">Да. Электронный сертификат на 3/6/12 месяцев + бесплатная открытка к
                    каждому букету.</p>
            </details>
        </div>
    </section>


    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const swiper = new Swiper('.swiper-container', {
                slidesPerView: 1,
                spaceBetween: 16,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    640: {
                        slidesPerView: 2,
                        spaceBetween: 20,
                    },
                    768: {
                        slidesPerView: 4,
                        spaceBetween: 24,
                    }
                }
            });
        });
    </script>

    <script src="{{ asset('js/main_page.js') }}"></script>
@endsection
