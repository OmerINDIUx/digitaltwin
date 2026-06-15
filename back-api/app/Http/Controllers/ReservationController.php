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
            
            $limitPerHour = (int)($daySched['capacity'] ?? $z->capacity);
            $dailyCapacity = $limitPerHour * $totalHours;

            // Restar aforo ocupado por eventos hoy
            $activeEvents = \App\Models\Event::where('zone', $z->slug)
                ->whereDate('event_date', $today)
                ->where('is_active', true)
                ->get();

            $eventBlock = 0;
            foreach($activeEvents as $ev) {
                if ($ev->type === 'private_event' || $ev->type === 'maintenance') {
                    // Bloquea TODA la capacidad de la zona por su duración
                    $eventBlock += $limitPerHour * $ev->duration;
                } else {
                    // Solo bloquea su cupo específico
                    $eventBlock += $ev->capacity * $ev->duration;
                }
            }

            $stats[$z->slug] = [
                'count' => Reservation::where('zone', $z->slug)->whereDate('reservation_date', $today)->sum('guests'),
                'limit' => max(0, $dailyCapacity - $eventBlock),
                'limit_per_hour' => $limitPerHour,
                'schedule' => substr($hStart, 0, 5) . ' - ' . substr($hEnd, 0, 5)
            ];
        }

        // Disponibilidad por día, zona y HORA (Próximos 7 días)
        $rawReservations = Reservation::whereIn('status', ['confirmed', 'pending'])
            ->whereDate('reservation_date', '>=', now()->toDateString())
            ->get();

        $availability = [];
        foreach ($rawReservations as $res) {
            $date = $res->reservation_date->format('Y-m-d');
            $startHour = (int)$res->reservation_date->format('H');
            
            for ($i = 0; $i < $res->duration; $i++) {
                $currentH = $startHour + $i;
                if (!isset($availability[$date])) $availability[$date] = [];
                if (!isset($availability[$date][$res->zone])) $availability[$date][$res->zone] = [];
                if (!isset($availability[$date][$res->zone][$currentH])) $availability[$date][$res->zone][$currentH] = 0;
                
                $availability[$date][$res->zone][$currentH] += $res->guests;
            }
        }

        // Sumar también ocupación de eventos/clases al calendario
        $activeEventsForCal = \App\Models\Event::where('is_active', true)
            ->whereDate('event_date', '>=', now()->toDateString())
            ->get();

        foreach ($activeEventsForCal as $ev) {
            $date = $ev->event_date->format('Y-m-d');
            $startHour = (int)$ev->event_date->format('H');
            
            for ($i = 0; $i < $ev->duration; $i++) {
                $currentH = $startHour + $i;
                if (!isset($availability[$date])) $availability[$date] = [];
                if (!isset($availability[$date][$ev->zone])) $availability[$date][$ev->zone] = [];
                if (!isset($availability[$date][$ev->zone][$currentH])) $availability[$date][$ev->zone][$currentH] = 0;
                
                // Si es evento privado o mantenimiento, bloquea todo el cupo de esa hora
                if ($ev->type === 'private_event' || $ev->type === 'maintenance') {
                    $z = $zones->where('slug', $ev->zone)->first();
                    $availability[$date][$ev->zone][$currentH] = $z ? $z->capacity : 999;
                } else {
                    $availability[$date][$ev->zone][$currentH] += $ev->capacity;
                }
            }
        }

        // Obtener eventos activos creados desde /admin/events.
        // Se filtra por dia completo para que los eventos de hoy sigan visibles en el panel.
        $events = \App\Models\Event::where('is_active', true)
            ->where('type', '!=', 'maintenance')
            ->whereDate('event_date', '>=', now()->toDateString())
            ->withCount([
                'activeRegistrations as registrations_count',
            ])
            ->orderBy('event_date', 'asc')
            ->take(12)
            ->get();

        return view('reservations-panel', compact('reservations', 'stats', 'availability', 'zones', 'events'));
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
        
        $endDatetime = date('Y-m-d H:i:s', strtotime($data['datetime'] . " + {$data['duration']} hours"));

        // Sumar ocupación actual (Cualquier reserva que se cruce con nuestro rango)
        $currentOccupancy = \App\Models\Reservation::where('zone', $data['zone'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('reservation_date', '<', $endDatetime)
            ->whereRaw('DATE_ADD(reservation_date, INTERVAL duration HOUR) > ?', [$data['datetime']])
            ->sum('guests');

        // Sumar ocupación de eventos/clases (Cualquier evento que se cruce)
        $activeEvents = \App\Models\Event::where('zone', $data['zone'])
            ->where('is_active', true)
            ->where('event_date', '<', $endDatetime)
            ->whereRaw('DATE_ADD(event_date, INTERVAL duration HOUR) > ?', [$data['datetime']])
            ->get();

        $eventOccupancy = 0;
        foreach($activeEvents as $ev) {
            if ($ev->type === 'private_event' || $ev->type === 'maintenance') {
                $eventOccupancy = $limit; 
                break;
            } else {
                $eventOccupancy += $ev->capacity;
            }
        }

        if (($currentOccupancy + $eventOccupancy + $data['guests']) > $limit) {
            $avail = max(0, $limit - $currentOccupancy - $eventOccupancy);
            return back()->withErrors(['guests' => "Lo sentimos, el aforo está limitado. Quedan {$avail} lugares disponibles para este horario."]);
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

        return redirect()->back()->with([
            'success' => 'Nueva reserva registrada correctamente.',
            'new_reservation_id' => $reservation->id,
            'new_reservation_name' => $reservation->name,
            'new_reservation_date' => $reservation->reservation_date
        ]);
    }
    /**
     * Ver el pase digital (Vista tipo App).
     */
    public function show(Reservation $reservation)
    {
        return view('reservations.check-in', [
            'status' => 'pending', 
            'message' => 'Pase de Acceso Activo', 
            'reservation' => $reservation
        ]);
    }

    /**
     * Marcar asistencia mediante QR.
     */
    public function checkIn(Reservation $reservation)
    {
        $now = now();
        $startTime = \Carbon\Carbon::parse($reservation->reservation_date);
        $endTime = $startTime->copy()->addHours($reservation->duration);

        // 1. Validar ventana de tiempo (Margen de 10 min antes y 10 min después del inicio)
        if ($now->lt($startTime->copy()->subMinutes(10))) {
            $msg = "Demasiado pronto. Solo puedes entrar desde las " . $startTime->copy()->subMinutes(10)->format('H:i') . " hrs.";
            return request()->ajax() 
                ? response()->json(['status' => 'error_time', 'message' => $msg]) 
                : view('reservations.check-in', ['status' => 'error', 'message' => $msg, 'reservation' => $reservation]);
        }

        if ($now->gt($startTime->copy()->addMinutes(10))) {
            $msg = "Pase expirado. Tu tolerancia de 10 minutos terminó a las " . $startTime->copy()->addMinutes(10)->format('H:i') . " hrs.";
            return request()->ajax() 
                ? response()->json(['status' => 'error_expired', 'message' => $msg]) 
                : view('reservations.check-in', ['status' => 'error', 'message' => $msg, 'reservation' => $reservation]);
        }

        // 2. Validar si ya asistió
        if ($reservation->checked_in_at) {
            $msg = "Este pase ya fue validado el " . $reservation->checked_in_at->format('d/m H:i') . " hrs.";
            return request()->ajax() 
                ? response()->json(['status' => 'error_duplicate', 'message' => $msg]) 
                : view('reservations.check-in', ['status' => 'error', 'message' => $msg, 'reservation' => $reservation]);
        }

        // 3. Confirmar asistencia
        $reservation->update([
            'checked_in_at' => now(),
            'status' => 'confirmed'
        ]);

        $msg = "¡Bienvenido, {$reservation->name}! Acceso autorizado.";
        return request()->ajax() 
            ? response()->json(['status' => 'success', 'message' => $msg, 'name' => $reservation->name]) 
            : view('reservations.check-in', ['status' => 'success', 'message' => $msg, 'reservation' => $reservation]);
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
                ->get(['id', 'zone', 'guests', 'reservation_date', 'name', 'status', 'duration', 'checked_in_at']);

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
                'server_time'  => $now->format('Y-m-d H:i:s'),
                'reservations' => $reservations,
                'active_reservations' => $activeReservations->values(),
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
                'people'      => $activeReservations->values(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Consultar disponibilidad para un slot específico
     */
    public function checkAvailability(Request $request)
    {
        $zoneSlug = $request->get('zone');
        $datetime = $request->get('datetime');
        $duration = (int)$request->get('duration', 1);

        if (!$zoneSlug || !$datetime) {
            return response()->json(['error' => 'Parámetros incompletos'], 400);
        }

        $zone = Zone::where('slug', $zoneSlug)->first();
        if (!$zone) return response()->json(['error' => 'Zona no encontrada'], 404);

        $endDatetime = date('Y-m-d H:i:s', strtotime($datetime . " + {$duration} hours"));

        $dayOfWeek = date('w', strtotime($datetime));
        $sched = $zone->schedules ?? [];
        $daySched = $sched[$dayOfWeek] ?? null;
        
        $limit = (int)($daySched['capacity'] ?? $zone->capacity);
        $open = $daySched['open'] ?? $zone->opening_hour;
        $close = $daySched['close'] ?? $zone->closing_hour;

        $currentOccupancy = Reservation::where('zone', $zoneSlug)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('reservation_date', '<', $endDatetime)
            ->whereRaw('DATE_ADD(reservation_date, INTERVAL duration HOUR) > ?', [$datetime])
            ->sum('guests');

        // DESCONTAR AFORO DE EVENTOS/CLASES
        $activeEvents = \App\Models\Event::where('zone', $zoneSlug)
            ->where('is_active', true)
            ->where('event_date', '<', $endDatetime)
            ->whereRaw('DATE_ADD(event_date, INTERVAL duration HOUR) > ?', [$datetime])
            ->get();

        $eventOccupancy = 0;
        foreach($activeEvents as $ev) {
            if ($ev->type === 'private_event' || $ev->type === 'maintenance') {
                $eventOccupancy = $limit; // Bloqueo total
                break;
            } else {
                $eventOccupancy += $ev->capacity;
            }
        }

        return response()->json([
            'capacity' => $limit,
            'occupied' => (int)$currentOccupancy + (int)$eventOccupancy,
            'available' => max(0, $limit - $currentOccupancy - $eventOccupancy),
            'schedule' => substr($open, 0, 5) . ' - ' . substr($close, 0, 5)
        ]);
    }

    public function registerEvent(Request $request, \App\Models\Event $event)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'required|string|max:30',
        ]);

        if ($event->activeRegistrations()->count() >= $event->capacity) {
            return response()->json(['error' => 'El evento ya está lleno.'], 422);
        }

        $event->registrations()->create(array_merge($data, [
            'status' => 'por_pagar',
        ]));
        
        $event->syncActiveAttendeeCount();

        return response()->json(['success' => true]);
    }
}
