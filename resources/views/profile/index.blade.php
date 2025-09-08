@extends('layouts.app')

@section('title', 'Личный кабинет')

@section('content')
    <section class="min-h-screen flex items-center justify-center py-12 px-4 bg-red-200">
        <div class="w-full max-w-7xl bg-white rounded-3xl shadow-xl p-6 sm:p-8 md:p-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold mb-8 text-gray-800 text-center">Мои подписки</h1>

            @if ($subscriptions && $subscriptions->count())
                <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 auto-rows-fr">
                    @foreach ($subscriptions as $sub)
                        <div
                            class="bg-white rounded-2xl border border-gray-200 shadow-lg hover:shadow-2xl transition-shadow duration-300 p-5 sm:p-6 flex flex-col justify-between">
                            <div class="mb-4 space-y-1 overflow-hidden">
                                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 truncate">{{ $sub->plan->name }}
                                </h2>
                                <p class="text-gray-600 text-sm sm:text-lg">Стоимость: <span
                                        class="font-semibold">{{ $sub->totalAmount() }}₽</span></p>
                                <p class="text-gray-600 text-sm sm:text-lg">
                                    Опции:
                                    <span class="font-semibold">
                                        @foreach ($sub->plan->options as $option)
                                            {{ $option->name }}@if (!$loop->last)
                                                ,
                                            @endif
                                        @endforeach
                                    </span>
                                </p>
                                <p class="text-gray-600 text-sm sm:text-lg">
                                    Частота доставки:
                                    @php $freq = \App\Enums\Frequency::getFrequencyElem($sub->frequency); @endphp
                                    <span class="font-semibold">{{ $freq['translate'] }} (≈
                                        {{ $freq['count'] }} доставки/мес.)</span>
                                </p>
                                <p class="text-gray-600 text-sm sm:text-lg">Адрес: <span
                                        class="font-semibold">{{ $sub->address }}</span></p>
                                <p class="text-gray-600 text-sm sm:text-lg">Получатель: <span
                                        class="font-semibold">{{ $sub->receiving_name }}</span></p>
                                <p class="text-gray-600 text-sm sm:text-lg">Телефон: <span
                                        class="font-semibold">{{ $sub->receiving_phone }}</span></p>
                            </div>

                            <div class="mt-4 border-t border-gray-200 pt-4 space-y-1">
                                <p class="text-gray-700 text-sm sm:text-base font-medium">Активна: <span
                                        class="font-semibold">{{ $sub->is_active ? 'Да' : 'Нет' }}</span></p>
                                <p class="text-gray-700 text-sm sm:text-base font-medium">Оформлена: <span
                                        class="font-semibold">{{ $sub->created_at->format('d.m.Y') }}</span></p>
                                <p class="text-gray-700 text-sm sm:text-base font-medium">Следующий платеж: <span
                                        class="font-semibold">{{ \Carbon\Carbon::parse($sub->next_date_payment)->format('d.m.Y') }}</span>
                                </p>
                            </div>

                            <div class="mt-6 flex flex-col gap-3">
                                @if ($sub->is_active)
                                    <form action="{{ route('subscription.disable', $sub->id) }}" method="POST" class="relative group">
                                        @csrf 
                                        <button type="submit"
                                            class="w-full bg-rose-500 text-white font-semibold py-3 rounded-xl hover:bg-rose-600 transition-colors duration-300 cursor-pointer">
                                            Остановить подписку
                                        </button>
 
                                        <span
                                            class="absolute left-1/2 -translate-x-1/2 -top-18 w-64 bg-gray-800 text-white text-sm rounded-lg p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 text-center z-10">
                                            Если остановить подписку, то деньги перестанут списываться с вашего привязанного
                                            счета.
                                        </span>
                                    </form>
                                @else
                                    <form action="{{ route('subscription.active', $sub->id) }}" method="POST">
                                        @csrf 
                                        <button type="submit"
                                            class="w-full bg-green-500 text-white font-semibold py-3 rounded-xl hover:bg-green-600 transition-colors duration-300 cursor-pointer">
                                            Возобновить подписку
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-500 text-lg mt-8">Подписок нет.</p>
            @endif
        </div>
    </section>
@endsection
