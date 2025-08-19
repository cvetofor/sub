<?php

use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/subscription', 'as' => 'subscription.'], function () {
    Route::post('/create', [SubscriptionController::class, 'create'])->name('create');
});
