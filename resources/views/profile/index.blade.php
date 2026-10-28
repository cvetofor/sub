@extends('layouts.site')

@section('title', 'Мои подписки — Цветофор')

@section('robots', 'noindex, nofollow')

@section('body')
    @include('partials.header')
    <main class="cab">
        <div class="cab-head">
            <div>
                <h1 class="serif">Мои подписки</h1>
                <p class="lead">{{ $user->name }} · {{ \App\Support\Phone::pretty($user->phone_e164 ?: $user->phone) }}</p>
            </div>
            <a class="linkBtn" href="{{ route('logout') }}">Выйти</a>
        </div>

        @if (session('success'))
            <div class="notice ok" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert" role="alert" style="margin:0">{{ session('error') }}</div>
        @endif

        @if ($subscriptions->isEmpty())
            <div class="summary empty">
                <p>Подписок пока нет. Выберите план, и первый букет приедет уже в ближайшую субботу.</p>
                <a class="btn" href="{{ route('home') }}#plans">Выбрать план</a>
            </div>
        @else
            <div class="cab-list">
                @foreach ($subscriptions as $sub)
                    <article class="summary sub" aria-labelledby="sub-{{ $sub->id }}">
                        <div class="sub-top">
                            <div>
                                <div class="k">Подписка №{{ $sub->id }}</div>
                                <h2 id="sub-{{ $sub->id }}">{{ $sub->plan->name }}</h2>
                            </div>
                            @if (!$sub->is_paid)
                                <span class="badge warn">Не оплачена</span>
                            @elseif ($sub->is_active)
                                <span class="badge">Активна</span>
                            @else
                                <span class="badge muted">На паузе</span>
                            @endif
                        </div>
                        <div class="row"><span>Частота</span><span>{{ \App\Services\Pricing::LABELS[$sub->frequency] ?? $sub->frequency }}</span></div>
                        @if ($sub->delivery_day)
                            <div class="row"><span>День</span><span>{{ \App\Support\Days::NOMINATIVE[$sub->delivery_day] ?? '' }}@if ($sub->timeDelivery), {{ substr($sub->timeDelivery->from, 0, 5) }}–{{ substr($sub->timeDelivery->to, 0, 5) }}@endif</span></div>
                        @endif
                        <div class="row"><span>Адрес</span><span>{{ $sub->address }}</span></div>
                        @if ($sub->receiving_name && $sub->receiving_name !== $sub->sender_name)
                            <div class="row"><span>Получатель</span><span>{{ $sub->receiving_name }}</span></div>
                        @endif
                        @if ($sub->plan->options->isNotEmpty())
                            <div class="row"><span>Дополнения</span><span>{{ $sub->plan->options->pluck('name')->join(', ') }}</span></div>
                        @endif
                        <hr>
                        @if ($sub->is_paid && !$sub->is_active && $sub->pause_reason === \App\Models\Subscription::PAUSE_PAYMENT)
                            <div class="alert" style="margin:0">Последнее списание не прошло. Проверьте карту и нажмите «Возобновить», мы повторим списание в течение двух часов.</div>
                        @endif
                        @if ($sub->is_paid && $sub->deliveries->isNotEmpty())
                            <div class="k">Ближайшие доставки</div>
                            <ul class="dlist">
                                @foreach ($sub->deliveries->take(4) as $d)
                                    <li>
                                        <span class="d-date">{{ \App\Support\Days::short($d->planned_date->dayOfWeekIso) }}, {{ $d->planned_date->translatedFormat('j F') }}@if ($d->windowLabel()) · {{ $d->windowLabel() }}@endif</span>
                                        @if ($sub->is_active && $d->planned_date->gte(now()->addDays(\App\Services\DeliverySchedule::MIN_LEAD_DAYS)->startOfDay()))
                                            <form method="POST" action="{{ route('subscription.skip', [$sub, $d]) }}" onsubmit="return confirm('Пропустить доставку {{ $d->planned_date->translatedFormat('j F') }}? Букет перенесём в конец расписания.')">@csrf<button class="linkBtn" type="submit">Пропустить</button></form>
                                        @elseif (!$sub->is_active)
                                            <span class="toast">на паузе</span>
                                        @else
                                            <span class="toast">уже собираем</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($sub->is_paid && $sub->payments->where('payment_status_id', \App\Models\Payment::PAYED)->isNotEmpty())
                            <details class="edit">
                                <summary>История платежей</summary>
                                <ul class="dlist" style="margin-top:8px">
                                    @foreach ($sub->payments->where('payment_status_id', \App\Models\Payment::PAYED)->sortByDesc('created_at') as $p)
                                        <li><span>{{ $p->created_at->translatedFormat('j F Y') }}</span><b>{{ number_format((float) $p->amount, 0, '', ' ') }} ₽</b></li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                        @if ($sub->is_paid)
                            <div class="row"><span>Списание раз в месяц</span><span class="total" style="font-size:20px">{{ number_format($sub->monthlyAmount(), 0, '', ' ') }} ₽</span></div>
                            @if ($sub->is_active && $sub->next_date_payment)
                                <div class="row"><span>Следующее</span><span>{{ $sub->next_date_payment->translatedFormat('j F Y') }}</span></div>
                            @endif
                            <div class="sub-actions">
                                @if ($sub->is_active)
                                    <form method="POST" action="{{ route('subscription.disable', $sub) }}">@csrf<button class="btn ghost" type="submit">Поставить на паузу</button></form>
                                @else
                                    <form method="POST" action="{{ route('subscription.active', $sub) }}">@csrf<button class="btn" type="submit">Возобновить</button></form>
                                @endif
                            </div>
                            <p class="toast" style="margin:0">Пауза останавливает списания и доставки. Возобновить можно в любой момент.</p>

                        <details class="edit" @if ($errors->hasBag('sub' . $sub->id)) open @endif>
                            <summary>Изменить адрес, день или получателя</summary>
                            <form method="POST" action="{{ route('subscription.delivery', $sub) }}" class="form" style="margin-top:16px">
                                @csrf
                                <div class="field">
                                    <label for="address-{{ $sub->id }}">Адрес доставки</label>
                                    <input id="address-{{ $sub->id }}" name="address" type="text" maxlength="255" required value="{{ old('address', $sub->address) }}">
                                    @error('address', 'sub' . $sub->id)<span class="err">{{ $message }}</span>@enderror
                                </div>
                                <div class="grid2">
                                    <div class="field">
                                        <label for="day-{{ $sub->id }}">День недели</label>
                                        <select id="day-{{ $sub->id }}" name="delivery_day">
                                            @foreach (\App\Support\Days::NOMINATIVE as $n => $name)
                                                <option value="{{ $n }}" @selected((int) old('delivery_day', $sub->delivery_day ?: 6) === $n)>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if ($windows->isNotEmpty())
                                        <div class="field">
                                            <label for="window-{{ $sub->id }}">Время</label>
                                            <select id="window-{{ $sub->id }}" name="time_delivery_id">
                                                @foreach ($windows as $w)
                                                    <option value="{{ $w->id }}" @selected((int) old('time_delivery_id', $sub->time_delivery_id) === $w->id)>{{ substr($w->from, 0, 5) }}–{{ substr($w->to, 0, 5) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </div>
                                <div class="grid2">
                                    <div class="field">
                                        <label for="rname-{{ $sub->id }}">Получатель</label>
                                        <input id="rname-{{ $sub->id }}" name="receiving_name" type="text" maxlength="120" value="{{ old('receiving_name', $sub->receiving_name) }}">
                                    </div>
                                    <div class="field">
                                        <label for="rphone-{{ $sub->id }}">Телефон получателя</label>
                                        <input id="rphone-{{ $sub->id }}" name="receiving_phone" type="tel" inputmode="tel" maxlength="32" value="{{ old('receiving_phone', \App\Support\Phone::pretty($sub->receiving_phone)) }}">
                                        @error('receiving_phone', 'sub' . $sub->id)<span class="err">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="field">
                                    <label for="card-{{ $sub->id }}">Текст открытки</label>
                                    <textarea id="card-{{ $sub->id }}" name="card_text" rows="2" maxlength="300">{{ old('card_text', $sub->preferences['card_text'] ?? '') }}</textarea>
                                </div>
                                <div class="field">
                                    <label for="comment-{{ $sub->id }}">Комментарий курьеру</label>
                                    <textarea id="comment-{{ $sub->id }}" name="comment" rows="2" maxlength="500">{{ old('comment', $sub->comment) }}</textarea>
                                </div>
                                <div class="sub-actions"><button class="btn" type="submit">Сохранить</button></div>
                            </form>
                        </details>
                        @else
                            <div class="row"><span>К оплате за первый месяц</span><span class="total" style="font-size:20px">{{ number_format($sub->firstPaymentAmount(), 0, '', ' ') }} ₽</span></div>
                            <div class="sub-actions">
                                <a class="btn" href="{{ route('subscription.pay', $sub) }}">Оплатить {{ number_format($sub->firstPaymentAmount(), 0, '', ' ') }} ₽</a>
                                <form method="POST" action="{{ route('subscription.cancel', $sub) }}" onsubmit="return confirm('Отменить заявку №{{ $sub->id }}? Деньги не списывались, ничего платить не нужно.')">@csrf<button class="btn ghost" type="submit">Отменить заказ</button></form>
                            </div>
                            <p class="toast" style="margin:0">Заявка ждёт оплаты, доставки начнутся после неё. Если передумали, отмените заказ: ничего платить не нужно.</p>
                        @endif
                    </article>
                @endforeach
            </div>
            <p class="toast">Поменять частоту или бюджет: напишите нам, и флорист всё перенастроит.</p>
        @endif
    </main>
    @include('partials.footer')
@endsection
