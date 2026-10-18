<?php

use App\Http\Controllers\AuthCodeController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Вход клиента по номеру телефона и коду из СМС
Route::get('/auth/login', [AuthCodeController::class, 'show'])->name('auth.login');
Route::post('/auth/code', [AuthCodeController::class, 'sendCode'])->middleware('throttle:10,1')->name('auth.code');
Route::post('/auth/verify', [AuthCodeController::class, 'verify'])->middleware('throttle:15,1')->name('auth.verify');
Route::match(['get', 'post'], '/logout', [AuthCodeController::class, 'logout'])->name('logout');

// Личный кабинет (новый вид на этапе 4)
Route::get('/profile', [AuthController::class, 'index'])->middleware('auth')->name('profile');
