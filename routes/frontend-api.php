<?php

use App\Http\Controllers\CityController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/city', 'as' => 'city.'], function () {
    Route::post('/set', [CityController::class, 'set'])->name('set');
});
