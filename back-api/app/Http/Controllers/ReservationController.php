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
        $stats = [
            'total' => Reservation::count(),
            'today' => Reservation::whereDate('reservation_date', now()->toDateString())->count(),
            'guests' => Reservation::sum('guests'),
            'gym' => Reservation::where('zone', 'gym')->count(),
            'pool' => Reservation::where('zone', 'pool')->count(),
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
            'zone' => 'required|string',
            'datetime' => 'required|date',
            'guests' => 'required|integer'
        ]);

        $reservation = Reservation::create([
            'zone' => $data['zone'],
            'reservation_date' => $data['datetime'],
            'guests' => $data['guests'],
            'status' => 'pending'
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
}
