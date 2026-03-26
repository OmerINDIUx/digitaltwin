<?php

use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

// Rutas de Reservas para el motor 3D
Route::get('/reservations', [ReservationController::class, 'index']);
Route::post('/reservations', [ReservationController::class, 'store']);
