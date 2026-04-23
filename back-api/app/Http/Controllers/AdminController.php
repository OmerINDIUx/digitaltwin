<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Zone;
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
        $zones = Zone::all();

        $today = now()->toDateString();
        $stats = [
            'total'     => Reservation::count(),
            'today'     => Reservation::whereDate('reservation_date', $today)->count(),
            'confirmed' => Reservation::where('status', 'confirmed')->count(),
            'pending'   => Reservation::where('status', 'pending')->count(),
            'guests'    => Reservation::sum('guests'),
        ];

        // PERSONAS EN VIVO AHORA — reservas confirmadas activas ±90 min del momento actual
        $now        = now('America/Mexico_City');
        $windowFrom = $now->copy()->subMinutes(90);
        $windowTo   = $now->copy()->addMinutes(90);

        $liveNow = Reservation::where('status', 'confirmed')
            ->whereBetween('reservation_date', [$windowFrom, $windowTo])
            ->orderBy('reservation_date', 'asc')
            ->get();

        $zoneStats = [];
        $liveCounts = [];
        foreach($zones as $z) {
            $zoneStats[$z->slug] = Reservation::where('zone', $z->slug)->whereDate('reservation_date', $today)->sum('guests');
            $liveCounts[$z->slug] = $liveNow->where('zone', $z->slug)->sum('guests');
        }
        $liveTotal = array_sum($liveCounts);

        // DATOS DE SENSORES IOT (Simulados para Digital Twin)
        $sensors = [
            ['name' => 'Módulo IoT 01 - Bosque', 'type' => 'Humedad Suelo', 'value' => '48.2%', 'status' => 'good', 'battery' => '92%'],
            ['name' => 'Módulo IoT 02 - Canchas', 'type' => 'Ruido (dB)', 'value' => '72.4 dB', 'status' => 'good', 'battery' => '85%'],
            ['name' => 'Módulo IoT 03 - Alberca', 'type' => 'Índice UV', 'value' => '9.2 UV', 'status' => 'warning', 'battery' => '100%'],
        ];

        $assets = [
            ['name' => 'Bomba Filtrado 01', 'zone' => 'Alberca', 'health' => 98, 'status' => 'Operativo'],
            ['name' => 'Rack de Cardio A', 'zone' => 'Gimnasio', 'health' => 65, 'status' => 'Mantenimiento'],
            ['name' => 'Climatización N.', 'zone' => 'Canchas', 'health' => 100, 'status' => 'Operativo'],
        ];

        // ESTADÍSTICAS SEMANALES PARA GRÁFICA
        $weeklyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $label = now()->subDays($i)->locale('es')->format('D d');
            $weeklyStats[] = [
                'label' => $label,
                'count' => Reservation::whereDate('reservation_date', $date)->where('status', 'confirmed')->count(),
                'guests' => (int) Reservation::whereDate('reservation_date', $date)->where('status', 'confirmed')->sum('guests'),
            ];
        }

        return view('admin.dashboard', compact(
            'reservations', 
            'stats', 
            'zoneStats', 
            'today', 
            'liveNow', 
            'liveCounts', 
            'liveTotal',
            'weeklyStats',
            'sensors',
            'assets',
            'zones'
        ));
    }

    // Cambiar estado de una reserva
    public function updateStatus(Request $request, Reservation $reservation)
    {
        if (!session('admin_logged_in')) abort(403);

        $request->validate(['status' => 'required|in:pending,confirmed,cancelled']);
        $reservation->update(['status' => $request->status]);

        return back()->with('success', "Reserva actualizada a: {$request->status}");
    }

    // Registro manual desde Admin
    public function adminStore(Request $request)
    {
        if (!session('admin_logged_in')) abort(403);

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email',
            'zone'             => 'required|exists:zones,slug',
            'reservation_date' => 'required|date',
            'guests'           => 'required|integer|min:1',
            'duration'         => 'required|integer|min:1',
        ]);

        $reservation = Reservation::create(array_merge($validated, [
            'status' => 'confirmed' // Por defecto confirmada si la hace el admin
        ]));

        return back()->with('success', "Reserva para {$reservation->name} creada con éxito.");
    }

    // Check-in manual (Asistió)
    public function adminCheckIn(Reservation $reservation)
    {
        if (!session('admin_logged_in')) abort(403);

        $reservation->update([
            'checked_in_at' => now(),
            'status' => 'confirmed'
        ]);

        return back()->with('success', "Asistencia de {$reservation->name} registrada manualmente.");
    }

    // Eliminar reserva
    public function destroy(Reservation $reservation)
    {
        if (!session('admin_logged_in')) abort(403);
        $reservation->delete();
        return back()->with('success', 'Reserva eliminada correctamente.');
    }
    // Acción en bloque (masiva)
    public function bulkAction(Request $request)
    {
        if (!session('admin_logged_in')) abort(403);

        $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'exists:reservations,id',
            'action' => 'required|in:delete,confirmed,pending,cancelled'
        ]);

        $ids = $request->ids;
        $action = $request->action;

        if ($action === 'delete') {
            Reservation::whereIn('id', $ids)->delete();
            $msg = count($ids) . " reservas eliminadas.";
        } else {
            Reservation::whereIn('id', $ids)->update(['status' => $action]);
            $msg = count($ids) . " reservas actualizadas a " . strtoupper($action);
        }

        return back()->with('success', $msg);
    }
}
