<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Zone;
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

        // Obtener zonas activas
        $zones = Zone::where('status', 'active')->get();

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
        ];

        foreach($zones as $z) {
            $currentDay = now()->dayOfWeek;
            $sched = $z->schedules ?? [];
            $daySched = $sched[$currentDay] ?? null;

            $hStart = $daySched['open'] ?? $z->opening_hour;
            $hEnd = $daySched['close'] ?? $z->closing_hour;
            
            $startHour = (int)explode(':', $hStart)[0];
            $endHour = (int)explode(':', $hEnd)[0];
            $totalHours = max(0, $endHour - $startHour);
            
            $dailyCapacity = $z->capacity * $totalHours;

            $stats[$z->slug] = [
                'count' => Reservation::where('zone', $z->slug)->whereDate('reservation_date', $today)->sum('guests'),
                'limit' => $dailyCapacity,
                'schedule' => substr($hStart, 0, 5) . ' - ' . substr($hEnd, 0, 5)
            ];
        }

        // Disponibilidad por día, zona y HORA (Próximos 7 días)
        $availability = Reservation::selectRaw('DATE(reservation_date) as date, HOUR(reservation_date) as hour, zone, SUM(guests) as total_guests')
            ->where('reservation_date', '>=', now()->toDateString())
            ->where('reservation_date', '<=', now()->addDays(7)->toDateString())
            ->groupBy('date', 'hour', 'zone')
            ->get()
            ->groupBy('date')
            ->map(function ($dateItems) {
                return $dateItems->groupBy('zone')->map(function ($zoneItems) {
                    return $zoneItems->pluck('total_guests', 'hour');
                });
            });

        return view('reservations-panel', compact('reservations', 'stats', 'availability', 'zones'));
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
            'duration' => 'required|integer|min:1|max:24',
        ]);

        // VALIDACIÓN DE CAPACIDAD REAL
        $zone = Zone::where('slug', $data['zone'])->firstOrFail();
        $date = date('Y-m-d', strtotime($data['datetime']));
        $hour = (int)date('H', strtotime($data['datetime']));
        $dayOfWeek = date('w', strtotime($data['datetime']));
        
        // Obtener capacidad para este día/hora desde la matriz
        $sched = $zone->schedules ?? [];
        $daySched = $sched[$dayOfWeek] ?? null;
        $limit = $daySched['capacity'] ?? $zone->capacity;
        
        // Sumar ocupación actual (incluyendo reservas que traslapan por duración)
        $currentOccupancy = \App\Models\Reservation::where('zone', $data['zone'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('reservation_date', '<=', $data['datetime'])
            ->whereRaw('DATE_ADD(reservation_date, INTERVAL duration HOUR) > ?', [$data['datetime']])
            ->sum('guests');

        if (($currentOccupancy + $data['guests']) > $limit) {
            return back()->withErrors(['guests' => "Lo sentimos, solo quedan " . ($limit - $currentOccupancy) . " lugares disponibles para este horario."]);
        }

        $reservation = Reservation::create([
            'name'             => $data['name'],
            'email'            => $data['email'] ?? null,
            'phone'            => $data['phone'] ?? null,
            'zone'             => $data['zone'],
            'reservation_date' => $data['datetime'],
            'duration'         => $data['duration'],
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
        try {
            $today = now()->toDateString();
            $currentDay = now()->dayOfWeek; // 0 (Sun) - 6 (Sat)

            // Reservas que están ACTIVAS en este momento exacto
            $now = now();
            $activeReservations = Reservation::whereIn('status', ['confirmed', 'pending'])
                ->where('reservation_date', '<=', $now)
                ->whereRaw('DATE_ADD(reservation_date, INTERVAL duration HOUR) >= ?', [$now])
                ->get();

            $totals = [
                'gym'     => (int) $activeReservations->where('zone', 'gym')->sum('guests'),
                'pool'    => (int) $activeReservations->where('zone', 'pool')->sum('guests'),
                'canchas' => (int) $activeReservations->where('zone', 'canchas')->sum('guests'),
            ];

            // Lista completa para el Feed (opcionalmente mostrar todas las de hoy o solo activas)
            $reservations = Reservation::whereIn('status', ['confirmed', 'pending'])
                ->whereDate('reservation_date', $today)
                ->orderBy('reservation_date', 'asc')
                ->get(['zone', 'guests', 'reservation_date', 'name', 'status', 'duration']);

            // Obtener estado real de apertura/cierre hoy
            $zones = Zone::all();
            $zoneStatus = [];
            foreach ($zones as $z) {
                $sched = $z->schedules ?? [];
                $isClosed = $sched[$currentDay]['is_closed'] ?? false;
                $zoneStatus[$z->slug] = $isClosed ? 'closed' : 'open';
            }

            return response()->json([
                'date'         => $today,
                'reservations' => $reservations,
                'totals'       => $totals,
                'zone_status'  => $zoneStatus,
                'grand_total'  => array_sum($totals),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Historial para el viaje en tiempo del motor 3D.
     * ?offset=N  donde N = minutos desde ahora (-1440 a +1440)
     */
    public function history(Request $request)
    {
        try {
            $offsetMin  = (int) $request->get('offset', 0);
            $targetTime = now()->addMinutes($offsetMin);
            $windowFrom = $targetTime->copy()->subMinutes(90);
            $windowTo   = $targetTime->copy()->addMinutes(90);

            $activeReservations = Reservation::whereIn('status', ['confirmed', 'pending'])
                ->where('reservation_date', '<=', $targetTime)
                ->whereRaw('DATE_ADD(reservation_date, INTERVAL duration HOUR) >= ?', [$targetTime])
                ->get();

            $totals = [
                'gym'     => (int) $activeReservations->where('zone', 'gym')->sum('guests'),
                'pool'    => (int) $activeReservations->where('zone', 'pool')->sum('guests'),
                'canchas' => (int) $activeReservations->where('zone', 'canchas')->sum('guests'),
            ];

            return response()->json([
                'offset'      => $offsetMin,
                'target_time' => $targetTime->format('Y-m-d H:i'),
                'window'      => ['from' => $windowFrom->format('H:i'), 'to' => $windowTo->format('H:i')],
                'totals'      => $totals,
                'grand_total' => array_sum($totals),
                'people'      => $reservations->values(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
