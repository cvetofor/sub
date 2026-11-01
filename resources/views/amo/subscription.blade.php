@extends('layouts.site')

@section('title', 'Заявка на подписку №' . $subscription->id . ' — Цветофор')
@section('robots', 'noindex, nofollow, noarchive')

@section('body')
    <main class="result" style="max-width:900px;text-align:left">
        <p class="qz-eyebrow">Цветофор · заявка №{{ $subscription->id }}</p>
        <h1 class="serif">Карточка заявки</h1>
        <p>Только просмотр. Изменить данные по этой ссылке нельзя.</p>

        <section class="qz-side-list" style="margin-top:24px">
            <b>Статус</b>
            <span>· {{ $subscription->isPaid() ? 'Первый платёж получен' : 'Ожидает первый платёж' }}</span>
            <span>· {{ $subscription->is_active ? 'Подписка активна' : 'Подписка пока не активна' }}</span>
            @if ($subscription->next_date_payment)<span>· Следующее списание: {{ $subscription->next_date_payment->format('d.m.Y') }}</span>@endif
        </section>

        <section class="qz-side-list" style="margin-top:20px">
            <b>Подписка</b>
            <span>· {{ $subscription->planName() }}, {{ number_format((float) $subscription->plan->price, 0, '', ' ') }} ₽ за букет</span>
            <span>· Первый платёж: {{ number_format($subscription->firstPaymentAmount(), 0, '', ' ') }} ₽</span>
            <span>· Далее: {{ number_format($subscription->monthlyAmount(), 0, '', ' ') }} ₽ в месяц</span>
            <span>· Частота: {{ \App\Services\Pricing::LABELS[$subscription->frequency] ?? $subscription->frequency }}</span>
            @if ($subscription->plan->options->isNotEmpty())<span>· Дополнения: {{ $subscription->plan->options->pluck('name')->join(', ') }}</span>@endif
        </section>

        <section class="qz-side-list" style="margin-top:20px">
            <b>Контакты и доставка</b>
            <span>· Заказчик: {{ $subscription->sender_name }}, {{ $subscription->sender_phone }}</span>
            <span>· Получатель: {{ $subscription->receiving_name }}, {{ $subscription->receiving_phone }}</span>
            <span>· Адрес: {{ $subscription->address }}</span>
            <span>· День доставки: {{ \App\Support\Days::NOMINATIVE[$subscription->delivery_day] ?? '—' }}@if ($subscription->timeDelivery), {{ substr($subscription->timeDelivery->from, 0, 5) }}–{{ substr($subscription->timeDelivery->to, 0, 5) }}@endif</span>
            @if (!empty($subscription->preferences['card_text']))<span>· Открытка: {{ $subscription->preferences['card_text'] }}</span>@endif
            @if (!empty($subscription->preferences['wishes']))<span>· Пожелания флористу: {{ $subscription->preferences['wishes'] }}</span>@endif
            @if ($subscription->comment)<span>· Комментарий курьеру: {{ $subscription->comment }}</span>@endif
        </section>

        <section class="qz-side-list" style="margin-top:20px">
            <b>Доставки</b>
            @forelse ($subscription->deliveries->sortBy('planned_date') as $delivery)
                <span>· {{ $delivery->planned_date->format('d.m.Y') }}@if ($delivery->timeDelivery), {{ substr($delivery->timeDelivery->from, 0, 5) }}–{{ substr($delivery->timeDelivery->to, 0, 5) }}@endif — {{ \App\Models\Delivery::STATUSES[$delivery->status] ?? $delivery->status }}</span>
            @empty
                <span>· Даты появятся после первой оплаты.</span>
            @endforelse
        </section>
    </main>
@endsection
