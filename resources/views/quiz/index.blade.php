@extends('layouts.site')

@section('title', 'Цветофор — подписка на цветы в Улан-Удэ')

@section('description', 'Цветы по подписке за две минуты: второй букет бесплатно, платите раз в месяц, пауза или отмена в любой день. От 990 ₽ за букет с доставкой.')

@section('bodyClass', 'qz-body')

@section('body')
    <main class="qz" id="quiz" data-hero-variant="q" data-design="{{ $design }}">

        {{-- Шапка: на главной логотип и вход, на шагах «назад», логотип и счётчик --}}
        <header class="qz-top">
            <div class="qz-top-l">
                <a class="qz-back" href="#" id="qzBack" aria-label="Назад" hidden><svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg></a>
                <a class="qz-logo" href="{{ route('home') }}" aria-label="Цветофор, на главную"><img src="{{ asset('images/logo-mark.svg') }}" alt="" width="50" height="50"><span class="qz-word">Цветофор</span></a>
            </div>
            <div class="qz-top-r">
                <a class="qz-login" id="qzLogin" href="{{ auth()->check() ? route('profile') : route('auth.login') }}">{{ auth()->check() ? 'Мои подписки' : 'Уже подписаны? Войти' }}</a>
                <div class="qz-steps" id="qzSteps" hidden><span id="qzStepLabel">Шаг 1 из 5</span><div class="qz-bar" role="progressbar" aria-label="Шаги оформления" aria-valuemin="1" aria-valuemax="5" aria-valuenow="1"><i id="qzBarFill"></i></div></div>
            </div>
        </header>

        {{-- Главная --}}
        <section class="qz-screen qz-home" id="qz0">
            <div class="qz-home-text">
                <div class="qz-eyebrow">Подписка на цветы · {{ $city ?: 'Улан-Удэ' }}</div>
                <h1 class="qz-h1">Цветы по подписке. Оформите за две минуты</h1>
                <p class="qz-lead">Второй букет бесплатно. Платите раз в месяц, пауза или отмена в любой день.</p>
                <div class="qz-cta-row">
                    <button type="button" class="qz-btn qz-btn-lg" id="qzStart">Оформить подписку</button>
                    <span class="qz-note">Пять коротких вопросов. Регистрации нет: ссылку на кабинет пришлём в СМС</span>
                </div>
                <div class="qz-how">
                    <div class="qz-how-i rise d1"><span>1</span><b>Выбираете букет и как часто</b></div>
                    <div class="qz-how-i rise d2"><span>2</span><b>Привозим в ваш день и время</b></div>
                    <div class="qz-how-i rise d3"><span>3</span><b>Пауза и перенос в кабинете</b></div>
                </div>
            </div>
            <div class="qz-home-photo">
                <img src="{{ asset('images/site/quiz-hero.jpg') }}" alt="Пионы в вазе на столе в гостиной" width="1200" height="1500" fetchpriority="high">
                <div class="qz-chips">
                    <span>от {{ number_format($plans->min('price') ?: 990, 0, '', ' ') }} ₽ за букет</span>
                    <span>доставка включена</span>
                    <span>гарантия свежести 48 часов</span>
                </div>
            </div>
        </section>

        {{-- Шаг 1: кому и как часто --}}
        <section class="qz-screen qz-step" id="qz1" hidden>
            <div class="qz-main">
                <h1 class="qz-h1" tabindex="-1">Кому и как часто привозить?</h1>
                <fieldset class="qz-group">
                    <legend><span class="qz-eyebrow">Кому</span><span class="qz-legend-hint" id="whoHint">выберите один вариант</span></legend>
                    <div class="qz-who" role="radiogroup" aria-label="Кому привозить" id="whoList">
                        <button type="button" class="qz-who-i" role="radio" aria-checked="false" data-who="home">
                            <span class="qz-ico" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg></span>
                            <span class="qz-who-t"><b>Себе</b><small>Привезём вам домой или на работу</small></span>
                            <span class="qz-check" aria-hidden="true"></span>
                        </button>
                        <button type="button" class="qz-who-i" role="radio" aria-checked="false" data-who="gift">
                            <span class="qz-ico" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M3 12h18M12 8v13M12 8c-2-3-6-3-6 0M12 8c2-3 6-3 6 0"/></svg></span>
                            <span class="qz-who-t"><b>В подарок</b><small>Спросим, кому и куда, и подпишем открытку от вас</small></span>
                            <span class="qz-check" aria-hidden="true"></span>
                        </button>
                    </div>
                </fieldset>
                <fieldset class="qz-group">
                    <legend><span class="qz-eyebrow">Как часто</span><span class="qz-legend-hint" id="freqHint">выберите, как часто привозить</span></legend>
                    <div class="qz-freq" role="radiogroup" aria-label="Как часто привозить" id="freqList">
                        <button type="button" class="qz-freq-i" role="radio" aria-checked="false" data-freq="weekly">
                            <span class="qz-check" aria-hidden="true"></span>
                            <b>Каждую неделю</b>
                            <span class="qz-dots" aria-hidden="true"><i class="on"></i><i class="gift"></i><i class="on"></i><i class="on"></i><em>4 в месяц</em></span>
                            <small>4 букета в месяц, второй бесплатно</small>
                            <span class="qz-chip">наш выбор</span>
                        </button>
                        <button type="button" class="qz-freq-i" role="radio" aria-checked="false" data-freq="biweekly">
                            <span class="qz-check" aria-hidden="true"></span>
                            <b>Раз в две недели</b>
                            <span class="qz-dots" aria-hidden="true"><i class="on"></i><i></i><i class="gift"></i><i></i><em>2 в месяц</em></span>
                            <small>2 букета в месяц, второй бесплатно</small>
                        </button>
                        <button type="button" class="qz-freq-i" role="radio" aria-checked="false" data-freq="monthly">
                            <span class="qz-check" aria-hidden="true"></span>
                            <b>Раз в месяц</b>
                            <span class="qz-dots" aria-hidden="true"><i class="on"></i><i></i><i class="gift"></i><i></i><em>2 в первый месяц</em></span>
                            <small>В первый месяц 2 букета, второй бесплатно. Дальше 1 в месяц</small>
                        </button>
                    </div>
                </fieldset>
                <div class="qz-trust">
                    <span><svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><b>Гарантия свежести 48 часов</b></span>
                    <span><svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 0 1 14-5.3M20 12a8 8 0 0 1-14 5.3"/><path d="M18 3v4h-4M6 21v-4h4"/></svg><span><b>Не понравился при получении</b>, скажите курьеру, привезём другой</span></span>
                    <span><svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg><span><b>Доставка включена</b> в цену</span></span>
                </div>
            </div>
            <aside class="qz-side" aria-live="polite">
                <div class="qz-eyebrow">Ваш первый месяц</div>
                <div class="qz-side-title" id="s1Title">Выберите, как часто привозить</div>
                <div class="qz-side-sub" id="s1Sub">Календарь покажет дни доставок.</div>
                <div class="qz-cal" id="cal1"></div>
                <div class="qz-legend"><span><i class="on"></i> доставка</span><span><i class="gift"></i> второй букет бесплатно</span></div>
                <button type="button" class="qz-btn qz-next" id="next1" disabled aria-describedby="hint1">Дальше: букет</button>
                <span class="qz-hint" id="hint1">Выберите, кому и как часто</span>
            </aside>
        </section>

        {{-- Шаг 2: какой букет --}}
        <section class="qz-screen qz-step" id="qz2" hidden>
            <div class="qz-main">
                <div>
                    <h1 class="qz-h1" tabindex="-1">Какой букет привозить?</h1>
                    <p class="qz-lead sm">Цена за один букет с доставкой. Каждый раз новый состав, флорист учитывает ваши пожелания.</p>
                </div>
                <div class="qz-plans" role="radiogroup" aria-label="Букет" id="planList">
                    @foreach ($plans as $plan)
                        <button type="button" class="qz-plan" role="radio" aria-checked="false" data-id="{{ $plan['id'] }}" data-price="{{ $plan['price'] }}" data-name="{{ $plan['name'] }}" data-name-gift="{{ $plan['nameGift'] }}">
                            <span class="qz-check" aria-hidden="true"></span>
                            <span class="qz-plan-ph"><img src="{{ $plan['image'] }}" alt="" loading="lazy" width="900" height="900">@if ($loop->index === 2)<span class="qz-chip">наш выбор</span>@elseif ($loop->last && $plans->count() > 2)<span class="qz-chip">самый большой</span>@endif</span>
                            <span class="qz-plan-t"><b class="qz-plan-name">{{ $plan['name'] }}</b><span class="qz-price">{{ number_format($plan['price'], 0, '', ' ') }} ₽</span><small>{{ $plan['about'] }}</small></span>
                        </button>
                    @endforeach
                </div>
                <div class="qz-own" id="ownBox">
                    <span class="qz-check" aria-hidden="true"></span>
                    <div class="qz-own-t"><b>Свой бюджет</b><small>Назовите сумму за букет, соберём под неё. От 990 ₽.</small></div>
                    <label class="qz-field"><span>Сумма за букет, ₽</span><input type="text" inputmode="numeric" id="ownBudget" placeholder="например, 2 500" maxlength="6"></label>
                </div>
            </div>
            <aside class="qz-side" aria-live="polite">
                <div class="qz-side-photo"><img id="s2Photo" src="{{ $plans->first()['image'] ?? asset('images/site/plan-home.jpg') }}" alt="" width="900" height="900"></div>
                <div class="qz-side-pick"><span id="s2Who">Себе · каждую неделю</span><b id="s2Name"></b></div>
                <div class="qz-pink"><span>Первый платёж</span><b class="qz-hero" id="s2First"></b><span id="s2FirstNote"></span></div>
                <p class="qz-side-sub" id="s2Monthly"></p>
                <div class="qz-side-list">
                    <b>В каждую доставку</b>
                    <span>· Свежие цветы дня в выбранном букете</span>
                    <span>· Доставка в выбранное время, уже в цене</span>
                    <span>· Простая открытка с вашим текстом, бесплатно</span>
                    <span>· Гарантия свежести 48 часов</span>
                </div>
                <button type="button" class="qz-btn qz-next" id="next2" disabled aria-describedby="hint2">Дальше: дополнения</button>
                <span class="qz-hint" id="hint2">Выберите букет или назовите бюджет</span>
            </aside>
        </section>

        {{-- Шаг 3: открытка и дополнения --}}
        <section class="qz-screen qz-step" id="qz3" hidden>
            <div class="qz-main">
                <div>
                    <h1 class="qz-h1" tabindex="-1" id="h3">Добавить что-нибудь к букету?</h1>
                    <p class="qz-lead sm">Всё по желанию. «Один раз» добавим к первому платежу, «к каждому букету» к каждому месяцу.</p>
                </div>
                <div class="qz-two" id="textBoxes">
                    <div class="qz-box pink" id="cardBox" hidden>
                        <label for="cardText"><b>Текст открытки</b> <em>входит бесплатно</em></label>
                        <textarea id="cardText" rows="2" maxlength="300" placeholder="Напишите пару слов, мы перепишем от руки на простой открытке"></textarea>
                        <div class="qz-pills" id="cardPills"><button type="button">Просто так</button><button type="button">С любовью</button><button type="button">Пусть дома всегда будет весна</button></div>
                    </div>
                    <div class="qz-box">
                        <label for="wishes"><b>Пожелания флористу</b></label>
                        <textarea id="wishes" rows="2" maxlength="500" placeholder="Любимые цветы, чего избегать, аллергия" aria-describedby="wishesHint"></textarea>
                        <span class="qz-hint left" id="wishesHint">Флорист читает это перед каждым букетом</span>
                    </div>
                </div>
                @if ($additions->isNotEmpty())
                    <fieldset class="qz-group">
                        <legend><span class="qz-eyebrow">Дополнения</span></legend>
                        <div class="qz-adds" id="addList">
                            @foreach ($additions as $a)
                                <button type="button" class="qz-add" aria-pressed="false" data-id="{{ $a['id'] }}" data-price="{{ $a['price'] }}" data-every="{{ $a['every'] ? 1 : 0 }}" data-name="{{ $a['name'] }}">
                                    <span class="qz-check" aria-hidden="true"></span>
                                    <span class="qz-add-ph"><img src="{{ $a['image'] }}" alt="" loading="lazy" width="600" height="400"><span class="qz-chip light">{{ $a['every'] ? 'к каждому букету' : 'один раз' }}</span></span>
                                    <span class="qz-add-row"><b>{{ $a['name'] }}</b><span class="qz-price sm">{{ number_format($a['price'], 0, '', ' ') }} ₽</span></span>
                                    @if ($a['about'])<small>{{ $a['about'] }}</small>@endif
                                </button>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
            </div>
            <aside class="qz-side" aria-live="polite">
                <div class="qz-side-photo short"><img id="s3Photo" src="{{ $plans->first()['image'] ?? asset('images/site/plan-home.jpg') }}" alt="" width="900" height="900"><div class="qz-card-preview" id="cardPreview" hidden></div></div>
                <div class="qz-lines" id="s3Lines"></div>
                <div class="qz-pink"><span>Первый платёж</span><b class="qz-hero" id="s3First"></b><span id="s3Monthly"></span></div>
                <div class="qz-side-list small"><span>· Простая открытка с вашим текстом входит бесплатно</span><span>· Дополнения можно поменять в кабинете перед любой доставкой</span></div>
                <button type="button" class="qz-btn qz-next" id="next3">Дальше: куда и когда</button>
                <button type="button" class="qz-quiet" id="skip3">Пропустить, ничего не добавлять</button>
            </aside>
        </section>

        {{-- Шаг 4: кому, куда и когда --}}
        <section class="qz-screen qz-step" id="qz4" hidden>
            <div class="qz-main">
                <h1 class="qz-h1" tabindex="-1" id="h4">Куда и когда привозить?</h1>
                <fieldset class="qz-block" id="recipientBox" hidden>
                    <legend><b>Кому</b><span class="qz-legend-hint">получателю ничего не пишем, ваши контакты спросим на последнем шаге</span></legend>
                    <div class="qz-grid2">
                        <label class="qz-field"><span>Имя получателя</span><input type="text" id="recName" maxlength="120" placeholder="Анна" autocomplete="off"></label>
                        <label class="qz-field"><span>Телефон получателя</span><input type="tel" id="recPhone" inputmode="tel" maxlength="32" placeholder="+7 ___ ___-__-__" autocomplete="off" aria-describedby="recPhoneHint"><small id="recPhoneHint">Нужен только курьеру для звонка, рассылок не будет</small><em class="qz-err" id="errRecPhone" hidden></em></label>
                    </div>
                    <label class="qz-sw"><input type="checkbox" id="hideSender"><span>Не говорить, от кого букет: курьер скажет только, что это подарок</span></label>
                    <label class="qz-sw pink"><input type="checkbox" id="agreeRec"><span><b>Договориться о дне и времени с получателем:</b> мы позвоним сами, сюрприза в дате не будет</span></label>
                </fieldset>
                <div class="qz-two">
                    <fieldset class="qz-block">
                        <legend><b>Куда</b>@if ($city)<span class="qz-legend-hint">{{ $city }}</span>@endif</legend>
                        <label class="qz-field"><span>Улица и дом</span><input type="text" id="address" maxlength="255" placeholder="Начните вводить адрес" autocomplete="street-address" required><em class="qz-err" id="errAddress" hidden></em></label>
                        <div class="qz-grid3">
                            <label class="qz-field"><span>Квартира</span><input type="text" id="apartment" maxlength="20" placeholder="34"></label>
                            <label class="qz-field"><span>Подъезд</span><input type="text" id="entrance" maxlength="20" placeholder="2"></label>
                            <label class="qz-field"><span>Домофон</span><input type="text" id="intercom" maxlength="20" placeholder="34"></label>
                        </div>
                        <label class="qz-field"><span>Комментарий курьеру</span><input type="text" id="comment" maxlength="500" placeholder="Код калитки, как найти вход"></label>
                    </fieldset>
                    <fieldset class="qz-block">
                        <legend><b>Когда</b><span class="qz-legend-hint" id="whenNote">с {{ $windows->first()['from'] ?? '9:00' }} до {{ $windows->last()['to'] ?? '21:00' }}</span></legend>
                        <div class="qz-days" role="radiogroup" aria-label="День недели" id="dayList">
                            @foreach (['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'] as $i => $d)
                                <button type="button" role="radio" aria-checked="false" data-day="{{ $i + 1 }}">{{ $d }}</button>
                            @endforeach
                        </div>
                        @if ($windows->isNotEmpty())
                            <div class="qz-wins" role="radiogroup" aria-label="Время" id="winList">
                                @foreach ($windows as $w)
                                    <button type="button" role="radio" aria-checked="false" data-id="{{ $w['id'] }}" data-from="{{ $w['from'] }}" data-to="{{ $w['to'] }}">{{ $w['from'] }}–{{ $w['to'] }}</button>
                                @endforeach
                            </div>
                        @endif
                        <span class="qz-hint left" id="whenHint">День и время можно поменять в кабинете, любую доставку перенести или пропустить.</span>
                    </fieldset>
                </div>
            </div>
            <aside class="qz-side" aria-live="polite">
                <div class="qz-eyebrow">Первая доставка</div>
                <div class="qz-side-title" id="s4Title">Выберите день и время</div>
                <div class="qz-side-sub" id="s4Sub">Первую доставку привезём на этой или следующей неделе.</div>
                <div class="qz-cal" id="cal4"></div>
                <div class="qz-legend"><span><i class="on"></i> доставка</span><span><i class="gift"></i> второй букет бесплатно</span></div>
                <button type="button" class="qz-btn qz-next" id="next4" disabled aria-describedby="hint4">Дальше: проверка</button>
                <span class="qz-hint" id="hint4">Выберите день и время</span>
            </aside>
        </section>

        {{-- Шаг 5: проверка и оплата --}}
        <section class="qz-screen qz-step" id="qz5" hidden>
            <div class="qz-main">
                <h1 class="qz-h1" tabindex="-1">Всё верно?</h1>
                <div class="qz-rows">
                    <div class="qz-row"><span>Букет и как часто</span><b id="r1"></b><button type="button" class="qz-edit" data-go="2" aria-label="Изменить букет и как часто">Изменить</button></div>
                    <div class="qz-row"><span id="r2Label">Куда</span><b id="r2"></b><button type="button" class="qz-edit" data-go="4" aria-label="Изменить адрес и получателя">Изменить</button></div>
                    <div class="qz-row"><span>Первая доставка</span><b id="r3"></b><button type="button" class="qz-edit" data-go="4" aria-label="Изменить день и время">Изменить</button></div>
                    <div class="qz-row"><span>Открытка и дополнения</span><b id="r4"></b><button type="button" class="qz-edit" data-go="3" aria-label="Изменить открытку и дополнения">Изменить</button></div>
                </div>
                <fieldset class="qz-block">
                    <legend><b>Как с вами связаться</b></legend>
                    <div class="qz-grid2">
                        <label class="qz-field"><span>Ваше имя</span><input type="text" id="senderName" maxlength="120" autocomplete="name" placeholder="Максим" value="{{ $user?->name === 'Клиент' ? '' : $user?->name }}" required><em class="qz-err" id="errName" hidden></em></label>
                        <label class="qz-field"><span>Ваш телефон</span><input type="tel" id="senderPhone" inputmode="tel" maxlength="32" autocomplete="tel" placeholder="+7 924 000-00-00" value="{{ $user ? \App\Support\Phone::pretty($user->phone_e164 ?: $user->phone) : '' }}" required aria-describedby="errPhone phoneHint"><em class="qz-err" id="errPhone" hidden></em></label>
                    </div>
                    <p class="qz-hint left" id="phoneHint">Сюда пришлём ссылку на кабинет и напоминания о доставке. Код из СМС вводить не нужно.</p>
                </fieldset>
                <div class="qz-alert" id="payError" role="alert" hidden></div>
            </div>
            <aside class="qz-side pay">
                <div class="qz-side-photo short"><img id="s5Photo" src="{{ $plans->first()['image'] ?? asset('images/site/plan-home.jpg') }}" alt="" width="900" height="900"></div>
                <div class="qz-lines" id="s5Lines"></div>
                <div class="qz-total"><span>Первый платёж</span><b id="s5First"></b></div>
                <div class="qz-timeline" id="s5Timeline"></div>
                <p class="qz-personal" id="s5Personal"></p>
                <button type="button" class="qz-btn qz-btn-lg qz-next" id="payBtn">Оплатить</button>
                <p class="qz-legal" id="legal">Нажимая «Оплатить», вы принимаете <a href="{{ route('user_agreement') }}" target="_blank" rel="noopener">условия подписки</a>. Дальше будем списывать <b id="legalMonthly"></b> раз в месяц с этой карты, пока вы не поставите паузу или не отмените подписку в кабинете.</p>
                <span class="qz-secure"><svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Защищённая оплата ЮKassa</span>
            </aside>
        </section>

        {{-- Нижняя панель на телефоне: итог и кнопка текущего шага --}}
        <div class="qz-bar" id="qzBar" hidden>
            <div class="qz-bar-l"><b id="barMain"></b><small id="barSub"></small></div>
            <button type="button" class="qz-btn" id="barNext">Дальше</button>
        </div>
    </main>

    <script type="application/json" id="quiz-data">{!! $quizJson !!}</script>
@endsection
