<?php

use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/panel', [ReservationController::class, 'index'])->name('reservations.index');
Route::post('/panel', [ReservationController::class, 'store'])->name('reservations.store');
