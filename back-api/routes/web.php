<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/panel', function () {
    $reservations = \App\Models\Reservation::orderBy('created_at', 'desc')->get();
    return view('reservations-panel', compact('reservations'));
})->name('reservations.panel');
