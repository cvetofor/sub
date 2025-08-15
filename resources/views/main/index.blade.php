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
                    <button class="px-5 py-3 rounded-xl bg-rose-600 text-white shadow cursor-pointer">
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

    <section class="mx-auto max-w-7xl px-4 pt-2 pb-10">
        <div class="flex items-center gap-2">
            <button id="readyBtn" class="plan-btn active">
                Готовые планы
            </button>
            <button id="customBtn" class="plan-btn">
                Собрать самому
            </button>
            <div class="ml-auto flex items-center gap-2 text-sm btn-group">
                <p class="text-gray-600">Частота:</p>
                <button class="toggle-btn px-3 py-1 rounded-xl border bg-rose-100 border-rose-400 text-rose-700 active">Еженедельно</button>
                <button class="toggle-btn px-3 py-1 rounded-xl border bg-white border-rose-200">Раз в 2 недели</button>
                <button class="toggle-btn px-3 py-1 rounded-xl border bg-white border-rose-200">Раз в месяц</button>
            </div>
        </div>

        <div class="mt-6 grid md:grid-cols-4 gap-4">
            @foreach ($plans as $plan)
                <div class="rounded-3xl border p-5 shadow-sm border-rose-500 shadow-rose-100 shadow-lg">
                    <div class="flex items-baseline justify-between mb-2">
                        <h1 class="flex items-baseline justify-between mb-2 text-xl font-semibold">{{ $plan->name }}</h1>
                        <p class="text-rose-600 text-xs font-semibold">выбрано</p>
                    </div>
                    <div class="text-3xl font-bold">{{ $plan->price }} ₽ <span
                            class="text-base font-medium text-gray-600">за
                            доставку</span></div>
                    <p class="mt-1 text-sm text-gray-600">≈ 4 доставк(и) в месяц</p>
                    <p class="mt-3 text-sm text-gray-700 min-h-[48px]">{{ $plan->description }}</p>
                    <p class="mt-3 text-sm text-gray-600">Опции:
                        @foreach ($plan->options as $option)
                            <span>{{ $option->name }} +{{ $option->price }} ₽, </span>
                        @endforeach
                    </p>
                    <div class="mt-4 p-3 rounded-2xl bg-rose-100 border border-rose-200">
                        <p class="text-sm text-gray-700">Итого в месяц (с выбранными опциями):</p>
                        <p class="text-2xl font-extrabold">123 ₽</p>
                    </div>
                    <button class="mt-4 w-full px-4 py-2 rounded-2xl bg-rose-600 text-white">Выбрать план</button>
                </div>
            @endforeach
        </div>
    </section>

    <section>
        
    </section>

    <script src="{{ asset('js/main_page.js') }}"></script>
@endsection
