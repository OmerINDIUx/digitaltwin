<?php

use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

// Endpoint JSON para el motor 3D
Route::get('/reservations/history', [ReservationController::class, 'history']); // ?offset=minutes
Route::get('/reservations/live',    [ReservationController::class, 'live']);
Route::get('/reservations',         [ReservationController::class, 'index']);
Route::post('/reservations',        [ReservationController::class, 'store']);
