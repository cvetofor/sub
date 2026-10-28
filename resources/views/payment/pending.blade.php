@extends('layouts.site')

@section('title', 'Проверяем оплату — Цветофор')

@push('head')
    <meta http-equiv="refresh" content="2;url={{ request()->fullUrlWithQuery(['attempt' => $attempt]) }}">
@endpush

@section('robots', 'noindex, nofollow')

@section('body')
    @include('partials.header')
    <main class="result">
        <span class="mark" aria-hidden="true" style="background:var(--color-surface-2);color:var(--color-text-2)"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
        <h1 class="serif">Проверяем оплату</h1>
        <p>Банк подтверждает платёж, обычно это занимает несколько секунд. Страница обновится сама.</p>
    </main>
    @include('partials.footer')
@endsection
