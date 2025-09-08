<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

Route::get('/auth/login', [AuthController::class, 'authView'])->name('auth.login');
Route::get('/profile', [AuthController::class, 'index'])->middleware('auth')->name('profile');
