<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ZoneController;
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
Route::get('/admin/scanner', [AdminController::class, 'scanner'])->name('admin.scanner');

// Eventos y Clases
Route::get('/admin/events', [AdminEventController::class, 'index'])->name('admin.events.index');
Route::post('/admin/events', [AdminEventController::class, 'store'])->name('admin.events.store');
Route::patch('/admin/events/{id}', [AdminEventController::class, 'update'])->name('admin.events.update');
Route::patch('/admin/events/{id}/toggle', [AdminEventController::class, 'toggleActive'])->name('admin.events.toggle');
Route::delete('/admin/events/{id}', [AdminEventController::class, 'destroy'])->name('admin.events.destroy');

// Gestión de reservas (desde el admin)
Route::patch('/admin/reservations/{reservation}/status', [AdminController::class, 'updateStatus'])->name('admin.status');
Route::delete('/admin/reservations/{reservation}',        [AdminController::class, 'destroy'])->name('admin.destroy');
Route::post('/admin/reservations/bulk',                   [AdminController::class, 'bulkAction'])->name('admin.bulk');

// Gestión de zonas
Route::get('/admin/zones',             [ZoneController::class, 'index'])->name('admin.zones.index');
Route::post('/admin/zones',            [ZoneController::class, 'store'])->name('admin.zones.store');
Route::patch('/admin/zones/{zone}',    [ZoneController::class, 'update'])->name('admin.zones.update');

Route::post('/admin/reservations', [AdminController::class, 'adminStore'])->name('admin.reservations.store');
Route::post('/admin/reservations/{reservation}/check-in', [AdminController::class, 'adminCheckIn'])->name('admin.reservations.checkin');

// Ver pase digital (Vista para el usuario)
Route::get('/reservations/show/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');

// Check-in de asistencia via QR (Endpoint de validación para el admin)
Route::get('/reservations/check-in/{reservation}', [ReservationController::class, 'checkIn'])->name('reservations.checkin');

// API de disponibilidad
Route::get('/api/availability', [ReservationController::class, 'checkAvailability'])->name('api.availability');

// Inscripción a eventos
Route::post('/events/{event}/register', [ReservationController::class, 'registerEvent'])->name('events.register');
