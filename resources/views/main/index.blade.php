@extends('layouts.app')

@section('title', 'Цветофор — Подписка на цветы')

@section('content')
    @php
        use App\Models\City;
        use App\Models\TimeDelivery;

        $cities = City::active()->get();
        $timeDeliveries = TimeDelivery::all();

        $totalOptDel = $options->where('type', 'delivery')->merge($options->where('type', 'addition'))->toArray();
    @endphp

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
                                <span>{{ $option->name }} +{{ $option->price }}₽ @if (!$loop->last)
                                        ,
                                    @endif </span>
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

        <div class="mt-6 grid lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7 xl:col-span-8 p-5 rounded-3xl border border-rose-200 bg-white shadow-sm">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Город</label>
                        <select id="citySelect" class="w-full px-3 py-2 rounded-xl border border-rose-200 cursor-pointer">
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Адрес доставки</label>
                        <input class="w-full px-3 py-2 rounded-xl border border-rose-200" type="text"
                            placeholder="Улица, дом, подъезд, комментарий">
                    </div>

                    <div class="ml-auto flex flex-wrap items-center gap-2 text-sm btn-group">
                        <p class="text-gray-600 w-full sm:w-auto">Частота:</p>
                        <button
                            class="toggle-btn px-3 py-2 rounded-xl border bg-rose-100 border-rose-400 text-rose-700 active">Еженедельно</button>
                        <button class="toggle-btn px-3 py-2 rounded-xl border bg-white border-rose-200">
                            Раз в 2 недели</button>
                        <button class="toggle-btn px-3 py-2 rounded-xl border bg-white border-rose-200">Раз в месяц</button>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Окно доставки</label>
                        <select class="w-full px-3 py-2 rounded-xl border border-rose-200" name="" id="">
                            @foreach ($timeDeliveries as $time)
                                <option value="{{ $time->id }}">{{ $time->from }}—{{ $time->to }}</option>
                            @endforeach
                        </select>
                        @foreach ($options->where('type', 'delivery') as $option)
                            <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="delivery_option_{{ $option->id }}"
                                    value="{{ $option->id }}" data-option-name="{{ $option->name }}"
                                    data-option-price="{{ $option->price }}">{{ $option->name }}
                                (+{{ $option->price }}₽/доставка)
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm text-gray-600 mb-2">
                        Бюджет за доставку: <span id="budgetValueMain" class="font-medium text-gray-700">2 990 ₽</span>
                    </label>
                    <input id="budgetRange" type="range" min="2990" max="50000" step="10" value="2990"
                        class="w-full h-2 bg-rose-200 rounded-lg appearance-none">
                    <p class="text-sm text-gray-600 mt-1">
                        Диапазон: 2 990 – 50 000 ₽. Флористы подберут состав под ваш бюджет, без привязки к «количеству
                        стеблей».
                    </p>
                </div>

                <div class="mt-6 grid md:grid-cols-2 gap-4">
                    <div>
                        @if ($options->where('type', 'style')->isNotEmpty())
                            <div class="text-sm text-gray-600 mb-1">Стиль букетов</div>
                            @foreach ($options->where('type', 'style') as $option)
                                <label class="flex items-center gap-2 text-sm text-gray-800 mb-1">
                                    <input type="checkbox" name="style_option_{{ $option->id }}"
                                        value="{{ $option->id }}" data-option-name="{{ $option->name }}"
                                        data-option-price="{{ $option->price }}">{{ $option->name }}
                                </label>
                            @endforeach
                        @endif
                    </div>
                    <div>
                        @if ($options->where('type', 'preference')->isNotEmpty())
                            <div class="text-sm text-gray-600 mb-1">Предпочтения</div>
                            @foreach ($options->where('type', 'preference') as $option)
                                <label class="flex items-center gap-2 text-sm text-gray-800 mb-1">
                                    <input type="checkbox" name="preference_option_{{ $option->id }}"
                                        value="{{ $option->id }}" data-option-name="{{ $option->name }}"
                                        data-option-price="{{ $option->price }}">{{ $option->name }}
                                </label>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="mt-6 grid md:grid-cols-2 gap-4">
                    @if ($options->where('type', 'occasion')->isNotEmpty())
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Повод</label>
                            <select class="w-full px-3 py-2 rounded-xl border border-rose-200">
                                @foreach ($options->where('type', 'occasion') as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if ($options->where('type', 'recipient')->isNotEmpty())
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Кому дарится букет</label>
                            <select class="w-full px-3 py-2 rounded-xl border border-rose-200">
                                @foreach ($options->where('type', 'recipient') as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                @if ($options->where('type', 'addition')->isNotEmpty())
                    <div class="mt-6">
                        <p class="text-sm text-gray-600 mb-2">Дополнения</p>
                        <div class="grid sm:grid-cols-2 gap-3">
                            @foreach ($options->where('type', 'addition') as $option)
                                <label class="flex items-center gap-2 text-sm text-gray-800">
                                    <input type="checkbox" name="addition_option_{{ $option->id }}"
                                        value="{{ $option->id }}" data-option-name="{{ $option->name }}"
                                        data-option-price="{{ $option->price }}">{{ $option->name }} (
                                    @if ($option->price > 0)
                                        +{{ $option->price }}₽
                                    @else
                                        бесплатно
                                    @endif
                                    )
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-sm text-gray-700">
                    Оплата — только рекуррентные платежи с карты. Отмена/пауза по заявке без штрафов. Для адресов за городом
                    — доплата 899 ₽ за каждую доставку (до 20 км).
                </div>
            </div>
            <aside class="lg:col-span-5 xl:col-span-4">
                <div class="sticky top-24 p-5 rounded-3xl border border-rose-300 bg-white shadow-md">
                    <h2 class="text-lg font-semibold">Ваш выбор</h2>
                    <p class="mt-2 text-sm text-gray-700">Город: <b id="cityOutput"></b></p>
                    <p class="mt-2 text-sm text-gray-700">Частота: <b id="frequencyOutput"></b></p>
                    <div class="mt-3">
                        <label class="block text-sm text-gray-600 mb-2">
                            Бюджет за доставку: <span id="budgetValueAside" class="font-medium text-gray-700">2 990
                                ₽</span>
                        </label>
                    </div>
                    <div class="mt-4 border-t border-dashed pt-3">
                        <p class="text-sm text-gray-600">Опции за доставку</p>
                        <ul class="text-sm mt-1 space-y-1">
                            @foreach ($totalOptDel as $option)
                                <li class="opacity-50" data-option-name="{{ $option['name'] }}" data-option-price="{{ $option['price'] }}">{{ $option['name'] }} +{{ $option['price'] }}₽</li>
                            @endforeach
                        </ul>
                    </div>
                    <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="" id="">
                        Я новый клиент (применить промо 1+1)
                    </label>
                    <div class="mt-4 p-3 rounded-2xl bg-rose-50 border border-rose-200">
                        <p class="text-sm text-gray-700">1-й месяц (до промо)</p>
                        <h1 class="text-3xl font-extrabold">₽</h1>
                    </div>
                </div>
            </aside>
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
                <p class="text-sm text-gray-700 mt-2">Работаем в 2‑часовых окнах 09:00–23:00. При сдвиге предупредим
                    заранее
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
