@extends('layouts.app')

@section('title', 'Цветофор — Оплата прошла успешно')

@section('content')
    <section class="flex flex-col items-center justify-center min-h-screen px-4">
        <div class="bg-white p-10 rounded-3xl shadow-xl border border-rose-200 text-center max-w-xl">
            <svg class="mx-auto w-16 h-16 text-rose-600 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-4">Оплата прошла успешно!</h1>
            <p class="text-gray-700 mb-6">Спасибо за вашу покупку. Ваша подписка оплачена до
                 {{ \Carbon\Carbon::parse($subscription->next_date_payment)->format('d.m.Y') }}. Идентификатор вашей подписки: <b>{{ $subscription->id }}.</b></p>
            <a href="{{ route('home') }}"
                class="inline-block px-6 py-3 bg-rose-600 text-white rounded-xl shadow hover:bg-rose-700 transition">
                Вернуться на главную
            </a>
        </div>
    </section>
@endsection
