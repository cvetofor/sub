@extends('layouts.site')

@section('title', 'Собрать подписку — Цветофор')

@section('body')
    <header class="wz-top">
        <a href="{{ route('home') }}" id="backLink"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>Назад</a>
        <img src="{{ asset('images/logo.svg') }}" alt="Цветофор" width="140" height="50">
        <span class="toast" id="stepLabel" aria-live="polite">Шаг 1 из 4</span>
    </header>

    <main class="wz" id="wizard" data-for="{{ $for }}" data-plan-id="{{ $plan?->id }}">
        <div style="display:flex;flex-direction:column;gap:24px;min-width:0">
            <div>
                <div class="progress" role="progressbar" aria-label="Шаги оформления" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1" id="progress"><i class="on"></i><i></i><i></i><i></i></div>
                <div class="progress-labels" aria-hidden="true"><span>Бюджет</span><span>Стиль</span><span>Доставка</span><span>Оплата</span></div>
            </div>

            @if ($plan)
                <div class="planNote"><span>План:</span><b>{{ $plan->name }}</b></div>
            @endif

            <section id="step1">
                <h1 class="serif" tabindex="-1">Сколько тратить на один букет?</h1>
                <p class="lead">Любая сумма превращается в букет. Чем больше, тем пышнее и реже сорта.</p>
                <div class="panel" style="margin-top:20px">
                    <div class="top"><label for="budget">Бюджет на букет</label><div class="big" id="budgetText"></div></div>
                    <input id="budget" class="budget" type="range" min="1000" max="15000" step="500" value="{{ $budget }}" aria-valuetext="">
                    <div class="ticks"><span style="left:0">1 000</span><span style="left:28.6%">5 000</span><span style="left:64.3%">10 000</span><span style="left:100%">15 000 ₽</span></div>
                    <div class="hint" id="budgetHint"></div>
                </div>
                <fieldset style="margin-top:24px">
                    <legend>Как часто привозить</legend>
                    <div class="seg three" role="group" aria-label="Частота доставки" id="freqSeg">
                        <div class="pill" aria-hidden="true"></div>
                        @foreach ($pricing as $key => $f)
                            <button type="button" data-freq="{{ $key }}" aria-pressed="{{ $key === $frequency ? 'true' : 'false' }}">{{ $f['label'] }}</button>
                        @endforeach
                    </div>
                    <div class="toast" id="freqNote" aria-live="polite" style="margin-top:12px"></div>
                </fieldset>
                <div class="actions">
                    <a class="back" href="{{ route('home') }}">Назад</a>
                    <button class="btn" type="button" id="next1">Дальше: стиль</button>
                </div>
            </section>

            <section id="step2" hidden>
                <h1 class="serif" tabindex="-1">Какие цветы вам ближе?</h1>
                <p class="lead">Можно выбрать несколько. Флорист будет чередовать.</p>
                <fieldset style="margin-top:20px">
                    <legend class="sr">Стиль букета</legend>
                    <div class="styles" id="styles">
                        @foreach ([
                            ['Минимализм', 'Один вид цветов, много воздуха', 's-minimal.jpg'],
                            ['Яркая классика', 'Розы, насыщенный цвет', 's-classic.jpg'],
                            ['Эко и полевые', 'Травы, ромашки, зелень', 'g-home-table.jpg'],
                            ['Авторский', 'Доверьтесь флористу', 'g-author-window.jpg'],
                        ] as [$name, $desc, $img])
                            <button type="button" class="style" aria-pressed="false" data-name="{{ $name }}">
                                <img data-src="{{ asset('images/site/' . $img) }}" alt="" width="900" height="900">
                                <div class="t"><b>{{ $name }}</b><span>{{ $desc }}</span></div>
                                <span class="check" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span>
                            </button>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset style="margin-top:24px">
                    <legend>Что учесть</legend>
                    <div class="tags" id="tags">
                        @foreach (['Люблю розы', 'Пастель', 'Без лилий', 'Без сильного запаха', 'Гипоаллергенно'] as $tag)
                            <button type="button" aria-pressed="false">{{ $tag }}</button>
                        @endforeach
                    </div>
                </fieldset>
                <div class="actions">
                    <a class="back" href="#" id="back2">Назад</a>
                    <button class="btn" type="button" id="next2">Дальше: доставка</button>
                </div>
            </section>
        </div>

        <aside style="display:flex;flex-direction:column;gap:20px;min-width:0">
            <div class="preview" aria-hidden="true">
                <img src="{{ asset('images/site/s-minimal.jpg') }}" data-tier="0" alt="" width="800" height="800">
                <img src="{{ asset('images/site/g-home-table.jpg') }}" data-tier="1" alt="" width="900" height="900">
                <img src="{{ asset('images/site/g-author-window.jpg') }}" data-tier="2" alt="" width="900" height="900">
                <img src="{{ asset('images/site/plan-author.jpg') }}" data-tier="3" alt="" width="900" height="900">
                <span class="cap" id="previewCap"></span>
            </div>
            <div class="summary">
                <div class="k">Ваша подписка</div>
                @if ($for)
                    <div class="row"><span>Для кого</span><span>{{ $for === 'gift' ? 'В подарок' : 'Себе домой' }}</span></div>
                @endif
                @if ($plan)
                    <div class="row"><span>План</span><span>{{ $plan->name }}</span></div>
                @endif
                <div class="row"><span>Букет</span><span id="sBudget"></span></div>
                <div class="row"><span>Частота</span><span id="sFreq"></span></div>
                <div class="row" id="sStyleRow" hidden><span>Стиль</span><span id="sStyle"></span></div>
                <hr>
                <div class="row"><span>Первый месяц</span><span class="total" id="sFirst"></span></div>
                <div class="promo" id="sPromo"></div>
                <div class="row"><span>Со второго месяца</span><span id="sMonthly"></span></div>
                <div class="row"><span>Первый букет</span><span>сб, {{ $firstBouquetDate->translatedFormat('j F') }}</span></div>
                <p class="toast" style="margin:0">Списание раз в месяц картой. Пауза и отмена в кабинете, без штрафов.</p>
            </div>
        </aside>
    </main>

    <div class="bar">
        <div class="l"><b><span id="bFirst"></span> <span style="font-size:12px;font-weight:400;color:var(--color-text-2)">первый месяц</span></b><small id="bSub"></small></div>
        <button class="btn" type="button" id="barNext">Дальше</button>
    </div>

    <script type="application/json" id="wizard-data">{!! $wizardJson !!}</script>
@endsection
