<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * API & Web: Listar todas o filtrar con estadísticas.
     */
    public function index(Request $request)
    {
        $query = Reservation::orderBy('reservation_date', 'desc');

        // Filtros opcionales
        if ($request->has('zone') && $request->zone != 'all') {
            $query->where('zone', $request->zone);
        }
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        // Historial total
        $reservations = $query->get();

        // Si es API para el motor 3D, devolvemos solo lo necesario (JSON)
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($reservations->take(10));
        }

        // Estadísticas para el Panel Web (Cards de Resumen)
        $today = now()->toDateString();
        
        $stats = [
            'total' => Reservation::count(),
            'today' => Reservation::whereDate('reservation_date', $today)->count(),
            'guests' => Reservation::sum('guests'),
            'gym' => [
                'count' => Reservation::where('zone', 'gym')->whereDate('reservation_date', $today)->sum('guests'),
                'limit' => 50,
                'schedule' => '07:00 - 22:00'
            ],
            'pool' => [
                'count' => Reservation::where('zone', 'pool')->whereDate('reservation_date', $today)->sum('guests'),
                'limit' => 30,
                'schedule' => '08:00 - 20:00'
            ],
            'canchas' => [
                'count' => Reservation::where('zone', 'canchas')->whereDate('reservation_date', $today)->sum('guests'),
                'limit' => 20,
                'schedule' => '09:00 - 21:00'
            ],
        ];

        return view('reservations-panel', compact('reservations', 'stats'));
    }

    /**
     * API: Guardar reserva desde el 3D
     */
    public function store(Request $request)
    {
        // Validar datos conforme a lo que envía el main.js
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'nullable|email|max:100',
            'phone'    => 'nullable|string|max:20',
            'zone'     => 'required|string',
            'datetime' => 'required|date',
            'guests'   => 'required|integer|min:1',
        ]);

        $reservation = Reservation::create([
            'name'             => $data['name'],
            'email'            => $data['email'] ?? null,
            'phone'            => $data['phone'] ?? null,
            'zone'             => $data['zone'],
            'reservation_date' => $data['datetime'],
            'guests'           => $data['guests'],
            'status'           => 'pending',
        ]);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'message' => 'Reserva guardada en Laravel',
                'reservation' => $reservation
            ]);
        }

        return redirect()->back()->with('success', 'Nueva reserva registrada correctamente.');
    }
    /**
     * API: Endpoint dedicado para el motor 3D.
     * Devuelve reservas activas en ventana ±90 min (igual que panel admin).
     */
    public function live()
    {
        $now        = now('America/Mexico_City');
        $windowFrom = $now->copy()->subMinutes(90);
        $windowTo   = $now->copy()->addMinutes(90);

        $reservations = Reservation::where('status', 'confirmed')
            ->whereBetween('reservation_date', [$windowFrom, $windowTo])
            ->orderBy('reservation_date', 'asc')
            ->get(['zone', 'guests', 'reservation_date', 'name', 'status']);

        // Totales por zona — MISMA VENTANA que el panel admin "AHORA EN VIVO"
        $totals = [
            'gym'     => (int) $reservations->where('zone', 'gym')->sum('guests'),
            'pool'    => (int) $reservations->where('zone', 'pool')->sum('guests'),
            'canchas' => (int) $reservations->where('zone', 'canchas')->sum('guests'),
        ];

        return response()->json([
            'window'       => ['from' => $windowFrom->format('H:i'), 'to' => $windowTo->format('H:i')],
            'reservations' => $reservations,
            'totals'       => $totals,
            'grand_total'  => array_sum($totals),
        ]);
    }

    /**
     * API: Historial para el viaje en tiempo del motor 3D.
     * ?offset=N  donde N = minutos desde ahora (-1440 a +1440)
     */
    public function history(Request $request)
    {
        $offsetMin  = (int) $request->get('offset', 0);
        $targetTime = now('America/Mexico_City')->addMinutes($offsetMin);
        $windowFrom = $targetTime->copy()->subMinutes(90);
        $windowTo   = $targetTime->copy()->addMinutes(90);

        $reservations = Reservation::where('status', 'confirmed')
            ->whereBetween('reservation_date', [$windowFrom, $windowTo])
            ->get(['zone', 'guests', 'reservation_date', 'name']);

        $totals = [
            'gym'     => (int) $reservations->where('zone', 'gym')->sum('guests'),
            'pool'    => (int) $reservations->where('zone', 'pool')->sum('guests'),
            'canchas' => (int) $reservations->where('zone', 'canchas')->sum('guests'),
        ];

        return response()->json([
            'offset'      => $offsetMin,
            'target_time' => $targetTime->format('Y-m-d H:i'),
            'window'      => ['from' => $windowFrom->format('H:i'), 'to' => $windowTo->format('H:i')],
            'totals'      => $totals,
            'grand_total' => array_sum($totals),
            'people'      => $reservations->values(),
        ]);
    }
}
