<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/city', 'as' => 'city.'], function () {
    Route::post('/set', [CityController::class, 'set'])->name('set');
});

// События воронки для A/B-теста главной
Route::post('/track', [TrackController::class, 'store'])->middleware('throttle:120,1')->name('track');
