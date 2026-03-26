<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────
// RUTAS PÚBLICAS (sin autenticación)
// ──────────────────────────────────────────────
Route::get('/', function () {
    return view('welcome');
});

// Panel público: explorar zonas y hacer reservas
Route::get('/panel',  [ReservationController::class, 'index'])->name('reservations.index');
Route::post('/panel', [ReservationController::class, 'store'])->name('reservations.store');

// ──────────────────────────────────────────────
// RUTAS DE ADMINISTRACIÓN (con login)
// ──────────────────────────────────────────────
Route::get('/admin/login',  [AdminController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout',[AdminController::class, 'logout'])->name('admin.logout');

// Dashboard protegido
Route::get('/admin',  [AdminController::class, 'dashboard'])->name('admin.dashboard');

// Gestión de reservas (desde el admin)
Route::patch('/admin/reservations/{reservation}/status', [AdminController::class, 'updateStatus'])->name('admin.status');
Route::delete('/admin/reservations/{reservation}',        [AdminController::class, 'destroy'])->name('admin.destroy');
