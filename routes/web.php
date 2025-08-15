<?php

use App\Http\Controllers\MainController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Главная страница
Route::get('/', [MainController::class, 'index'])->name('home');

Route::get('/account', [UserController::class, 'index'])
    ->name('account');

require __DIR__ . '/auth.php';
