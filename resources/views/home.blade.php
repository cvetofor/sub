@extends('layouts.site')

@section('title', 'Цветофор — подписка на цветы в Улан-Удэ')

@section('body')
    @include('partials.header')

    <main id="top" data-hero-variant="{{ $variant }}">
        @include('home.hero-' . $variant)

        <section class="section" id="how">
            <div class="wrap">
                <div class="section-head"><h2 class="serif">Как это работает</h2></div>
                <div class="cards">
                    <div class="card"><div class="num">01</div><h3>Выбираете</h3><p>Готовый план или свой: четыре вопроса, две минуты.</p></div>
                    <div class="card"><div class="num">02</div><h3>Привозим по расписанию</h3><p>В ваш день и окно. Каждый раз другой состав.</p></div>
                    <div class="card"><div class="num">03</div><h3>Пауза, когда нужно</h3><p>Уехали: один тап в кабинете, списаний нет. Вернулись: включили.</p></div>
                </div>
            </div>
        </section>

        <section class="section" id="plans">
            <div class="wrap">
                <div class="section-head">
                    <div>
                        <h2 class="serif" id="plansTitle">Планы</h2>
                        <p class="sub">Цена за один букет, доставка включена. Второй букет в подписке в подарок.</p>
                    </div>
                    <div class="freqWrap">
                        <div class="seg two compact" role="group" aria-label="Для кого цветы" id="modeSeg">
                            <div class="pill" aria-hidden="true" style="opacity:0"></div>
                            <button type="button" data-mode="home" aria-pressed="false">Себе домой</button>
                            <button type="button" data-mode="gift" aria-pressed="false">В подарок</button>
                        </div>
                        <div class="seg three" role="group" aria-label="Частота доставки" id="freqSeg">
                            <div class="pill" aria-hidden="true" style="transform:translateX(100%)"></div>
                            @foreach ($pricing as $key => $f)
                                <button type="button" data-freq="{{ $key }}" aria-pressed="{{ $key === 'biweekly' ? 'true' : 'false' }}">{{ $f['label'] }}</button>
                            @endforeach
                        </div>
                        <div class="toast" id="freqNote" aria-live="polite">{{ $pricing['biweekly']['note'] }}</div>
                    </div>
                </div>

                <div class="plans" id="planList">
                    @foreach ($plans as $plan)
                        <article class="plan" data-id="{{ $plan['id'] }}" data-scenario="{{ $plan['scenario'] }}">
                            <img src="{{ $plan['image'] }}" alt="" loading="lazy" width="900" height="900">
                            <div class="in">
                                <div><div class="name">{{ $plan['name'] }}</div><div class="tag">{{ $plan['tag'] }}</div></div>
                                <div class="price"><b>{{ number_format($plan['price'], 0, ',', ' ') }} ₽</b><span>за букет</span></div>
                                <p class="desc">{{ $plan['description'] }}</p>
                                <hr>
                                <div class="ledger">
                                    <div class="row"><span>Первый месяц</span><span class="first" data-first></span></div>
                                    <div class="promo" data-promo></div>
                                    <div class="row"><span>Со второго месяца</span><span data-monthly></span></div>
                                </div>
                                <a class="btn md secondary" data-cta href="{{ route('wizard', ['plan' => $plan['id'], 'freq' => 'biweekly']) }}">Оформить «{{ $plan['name'] }}»</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p class="sub" style="margin-top:20px">Хочется по-своему? <a href="{{ route('wizard') }}">Соберите подписку за четыре вопроса</a></p>
            </div>
        </section>

        <section class="section" id="gallery">
            <div class="wrap">
                <div class="section-head"><h2 class="serif">Из последних доставок</h2><p class="sub">Каждый раз другой состав</p></div>
                <div class="gallery">
                    @foreach ($gallery as $photo)
                        <figure>
                            <img src="{{ $photo['src'] }}" alt="{{ $photo['alt'] }}" loading="lazy" width="900" height="900">
                            <figcaption>«{{ $photo['plan'] }}»</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section">
            <div class="wrap trust">
                <div><svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2F6420" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span><b>48 часов свежести.</b> Завял раньше, заменим.</span></div>
                <div><svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2F6420" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg><span><b>Не понравился при получении?</b> Привезём другой.</span></div>
                <div><svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2F6420" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg><span><b>Пауза без штрафов.</b> Один тап в кабинете.</span></div>
                <div><svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2F6420" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="14" rx="2"/><path d="M8 10h8M8 14h5"/></svg><span><b>Открытка с вашими словами.</b> В любом плане, бесплатно.</span></div>
            </div>
        </section>

        <section class="section" id="faq">
            <div class="wrap" style="max-width:860px">
                <div class="section-head"><h2 class="serif">Вопросы</h2></div>
                <div class="faq">
                    <details><summary>Как поставить на паузу или отменить?</summary><p>В личном кабинете, одной кнопкой, в любой день. Списаний на паузе нет, штрафов нет. Возобновить можно так же.</p></details>
                    <details><summary>Когда приедет первый букет?</summary><p>В ближайшую субботу после оплаты, в выбранное окно. День и окно меняются в кабинете.</p></details>
                    <details><summary>Что, если букет не понравился?</summary><p>Скажите курьеру при получении, привезём другой. Если букет завял раньше 48 часов, заменим.</p></details>
                    <details><summary>Можно подарить подписку?</summary><p>Да. Выберите «В подарок», укажите получателя и текст открытки. Первый букет приедет с открыткой от вас.</p></details>
                </div>
            </div>
        </section>
    </main>

    @include('partials.footer')

    <script type="application/json" id="site-data">{!! $siteJson !!}</script>
@endsection
