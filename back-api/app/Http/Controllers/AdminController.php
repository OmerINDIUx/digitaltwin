<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // -----------------------------------------------
    // LOGIN
    // -----------------------------------------------
    public function showLogin()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $adminEmail    = config('app.admin_email', env('ADMIN_EMAIL', 'admin@digitaltwin.mx'));
        $adminPassword = config('app.admin_password', env('ADMIN_PASSWORD', 'utopiajapan2025'));

        if ($request->email === $adminEmail && $request->password === $adminPassword) {
            $request->session()->put('admin_logged_in', true);
            $request->session()->put('admin_email', $request->email);
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas. Verifica tus datos.'])->withInput();
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_logged_in', 'admin_email']);
        return redirect()->route('admin.login');
    }

    // -----------------------------------------------
    // DASHBOARD (PROTEGIDO)
    // -----------------------------------------------
    public function dashboard(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Debes iniciar sesión.']);
        }

        $query = Reservation::orderBy('reservation_date', 'desc');

        if ($request->filled('zone') && $request->zone !== 'all') {
            $query->where('zone', $request->zone);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $reservations = $query->paginate(12)->withQueryString();

        $today = now()->toDateString();
        $stats = [
            'total'     => Reservation::count(),
            'today'     => Reservation::whereDate('reservation_date', $today)->count(),
            'confirmed' => Reservation::where('status', 'confirmed')->count(),
            'pending'   => Reservation::where('status', 'pending')->count(),
            'guests'    => Reservation::sum('guests'),
        ];

        $zoneStats = [
            'gym'     => Reservation::where('zone', 'gym')->whereDate('reservation_date', $today)->sum('guests'),
            'pool'    => Reservation::where('zone', 'pool')->whereDate('reservation_date', $today)->sum('guests'),
            'canchas' => Reservation::where('zone', 'canchas')->whereDate('reservation_date', $today)->sum('guests'),
        ];

        // PERSONAS EN VIVO AHORA — reservas confirmadas activas ±90 min del momento actual
        $now        = now('America/Mexico_City');
        $windowFrom = $now->copy()->subMinutes(90);
        $windowTo   = $now->copy()->addMinutes(90);

        $liveNow = Reservation::where('status', 'confirmed')
            ->whereBetween('reservation_date', [$windowFrom, $windowTo])
            ->orderBy('reservation_date', 'asc')
            ->get();

        $liveCounts = [
            'gym'     => $liveNow->where('zone', 'gym')->sum('guests'),
            'pool'    => $liveNow->where('zone', 'pool')->sum('guests'),
            'canchas' => $liveNow->where('zone', 'canchas')->sum('guests'),
        ];
        $liveTotal = array_sum($liveCounts);

        return view('admin.dashboard', compact('reservations', 'stats', 'zoneStats', 'today', 'liveNow', 'liveCounts', 'liveTotal'));
    }

    // Cambiar estado de una reserva
    public function updateStatus(Request $request, Reservation $reservation)
    {
        if (!session('admin_logged_in')) abort(403);

        $request->validate(['status' => 'required|in:pending,confirmed,cancelled']);
        $reservation->update(['status' => $request->status]);

        return back()->with('success', "Reserva actualizada a: {$request->status}");
    }

    // Eliminar reserva
    public function destroy(Reservation $reservation)
    {
        if (!session('admin_logged_in')) abort(403);
        $reservation->delete();
        return back()->with('success', 'Reserva eliminada correctamente.');
    }
}
