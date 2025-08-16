<?php

use App\Http\Controllers\MainController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Главная страница
Route::get('/', [MainController::class, 'index'])->name('home');

Route::get('/account', [UserController::class, 'index'])
    ->name('account');

Route::get('/policy', function () {
    return view('information.policy');
})->name('policy');

require __DIR__ . '/auth.php';
