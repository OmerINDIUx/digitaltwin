<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * API: Listar las últimas 5 reservas (Capa de compatibilidad con 3D)
     */
    public function index()
    {
        return response()->json(Reservation::orderBy('created_at', 'desc')->limit(5)->get());
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
