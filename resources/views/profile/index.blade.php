@extends('layouts.app')

@section('title', 'Личный кабинет')

@section('content')
    <section class="h-screen">
        <form action="{{ route('logout') }}" method="post">
            @csrf
            <button type="submit">Выйти</button>
        </form>
    </section>
@endsection
