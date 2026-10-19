@extends('layouts.site')

@section('title', $errTitle . ' — Цветофор')
@section('robots', 'noindex')

@section('body')
    @include('partials.header')
    <main class="result errpage">
        <div class="code" aria-hidden="true">{{ $errCode }}</div>
        <h1 class="serif">{{ $errTitle }}</h1>
        <p>{{ $errText }}</p>
        <div class="row-actions">
            <a class="btn" href="{{ route('home') }}">На главную</a>
            <a class="btn ghost" style="margin-top:0" href="tel:+73012502220">Позвонить нам</a>
        </div>
    </main>
    @include('partials.footer')
@endsection
