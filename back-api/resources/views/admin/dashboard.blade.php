<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Digital Twin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        * { font-family: 'Outfit', sans-serif; }
        body { background: #f1f5f9; }
        .sidebar { background: #0f172a; }
        .sidebar-link { color: rgba(148,163,184,1); transition: all 0.2s; border-radius: 14px; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(255,255,255,0.07); color: white; }
        .stat-card { box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04); }
        .table-row:hover { background: rgba(99,102,241,0.03); }
        .status-confirmed { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
        .status-pending   { background: #fffbeb; color: #d97706; border-color: #fde68a; }
        .status-cancelled { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .badge-base { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; border-width: 1px; border-style: solid; }
        .scroll-table::-webkit-scrollbar { height: 4px; }
        .scroll-table::-webkit-scrollbar-track { background: #f1f5f9; }
        .scroll-table::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="sidebar w-64 min-h-screen flex-shrink-0 flex flex-col p-6 fixed top-0 left-0 h-full">
        <!-- Logo -->
        <div class="flex items-center gap-3 mb-10 px-2">
            <div class="w-10 h-10 bg-indigo-600 rounded-2xl flex items-center justify-center text-lg shadow-lg shadow-indigo-600/30">🏙️</div>
            <div>
                <div class="text-white font-extrabold text-sm tracking-tight leading-none">Digital Twin</div>
                <div class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Admin Panel</div>
            </div>
        </div>

        <!-- NAV -->
        <nav class="space-y-1 flex-1">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link active flex items-center gap-3 px-4 py-3 text-sm font-bold">
                <span>📊</span> Dashboard
            </a>
            <a href="{{ url('/panel') }}" target="_blank" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm font-bold">
                <span>🌐</span> Panel Público
            </a>
            <a href="http://localhost:5173" target="_blank" class="sidebar-link flex items-center gap-3 px-4 py-3 text-sm font-bold">
                <span>🏗️</span> Modelo 3D
            </a>
        </nav>

        <!-- USER FOOTER -->
        <div class="border-t border-white/5 pt-6 mt-6">
            <div class="flex items-center gap-3 mb-4 px-2">
                <div class="w-9 h-9 rounded-full bg-indigo-600/30 flex items-center justify-center font-extrabold text-indigo-400 text-sm">A</div>
                <div>
                    <div class="text-white text-xs font-bold leading-none">Administrador</div>
                    <div class="text-slate-500 text-[10px] mt-0.5">{{ session('admin_email') }}</div>
                </div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="sidebar-link w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-red-400 hover:text-red-300 hover:bg-red-500/10">
                    <span>🚪</span> Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="ml-64 flex-1 p-8">

        <!-- TOP BAR -->
        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Centro de <span class="text-indigo-600">Reservaciones</span></h1>
                <p class="text-slate-400 text-sm mt-1">Vista completa del sistema — {{ now()->format('d \d\e F, Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-100 px-4 py-2 rounded-2xl">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-emerald-700 text-xs font-extrabold uppercase tracking-widest">Sistema activo</span>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 rounded-3xl px-6 py-4 flex items-center gap-3">
            <span class="text-emerald-500 text-xl">✓</span>
            <p class="text-emerald-700 font-bold text-sm">{{ session('success') }}</p>
        </div>
        @endif

        <!-- STATS CARDS -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-5 mb-10">
            <div class="bg-white rounded-3xl p-6 stat-card col-span-1">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Total</p>
                <p class="text-4xl font-extrabold text-slate-900">{{ $stats['total'] }}</p>
                <p class="text-xs text-indigo-500 font-bold mt-2 bg-indigo-50 inline-block px-2 py-0.5 rounded">Historial</p>
            </div>
            <div class="bg-white rounded-3xl p-6 stat-card col-span-1">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Hoy</p>
                <p class="text-4xl font-extrabold text-slate-900">{{ $stats['today'] }}</p>
                <p class="text-xs text-blue-500 font-bold mt-2 bg-blue-50 inline-block px-2 py-0.5 rounded">Programadas</p>
            </div>
            <div class="bg-white rounded-3xl p-6 stat-card col-span-1">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Confirmadas</p>
                <p class="text-4xl font-extrabold text-emerald-600">{{ $stats['confirmed'] }}</p>
                <p class="text-xs text-emerald-500 font-bold mt-2 bg-emerald-50 inline-block px-2 py-0.5 rounded">Activas</p>
            </div>
            <div class="bg-white rounded-3xl p-6 stat-card col-span-1">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Pendientes</p>
                <p class="text-4xl font-extrabold text-amber-500">{{ $stats['pending'] }}</p>
                <p class="text-xs text-amber-500 font-bold mt-2 bg-amber-50 inline-block px-2 py-0.5 rounded">En cola</p>
            </div>
            <div class="bg-white rounded-3xl p-6 stat-card col-span-1">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Total Pax</p>
                <p class="text-4xl font-extrabold text-slate-900">{{ $stats['guests'] }}</p>
                <p class="text-xs text-slate-400 font-bold mt-2 bg-slate-50 inline-block px-2 py-0.5 rounded">Invitados</p>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             AHORA EN VIVO — Ocupación en este momento
             ════════════════════════════════════════════ -->
        <div class="bg-slate-900 rounded-[2.5rem] p-8 mb-10">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-400 block animate-ping absolute"></span>
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-400 block relative"></span>
                    </div>
                    <div>
                        <h2 class="text-white font-extrabold text-xl leading-none">AHORA EN VIVO</h2>
                        <p class="text-slate-400 text-xs mt-0.5">Reservas activas · ventana ±90 minutos · {{ now('America/Mexico_City')->format('h:i A') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-5xl font-extrabold text-white">{{ $liveTotal }}</div>
                    <div class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">personas en el complejo</div>
                </div>
            </div>

            @if($liveTotal > 0)
            <!-- RESUMEN POR ZONA -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                @foreach(['gym' => ['icon'=>'🏃','label'=>'Gimnasio','color'=>'indigo'], 'pool' => ['icon'=>'🏊','label'=>'Natación','color'=>'blue'], 'canchas' => ['icon'=>'⚽','label'=>'Canchas','color'=>'emerald']] as $zKey => $zInfo)
                <div class="bg-white/5 rounded-2xl p-4 border border-white/10">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-bold text-slate-300">{{ $zInfo['icon'] }} {{ $zInfo['label'] }}</span>
                        <span class="text-2xl font-extrabold text-white">{{ $liveCounts[$zKey] }}</span>
                    </div>
                    <p class="text-[10px] text-slate-500 font-bold uppercase">personas ahora</p>
                </div>
                @endforeach
            </div>

            <!-- LISTA DE PERSONAS EN EL COMPLEJO AHORA -->
            <div class="bg-white/5 rounded-2xl border border-white/10 overflow-hidden">
                <div class="px-5 py-3 border-b border-white/10">
                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">👥 Personas identificadas en instalaciones</p>
                </div>
                <div class="divide-y divide-white/5 max-h-56 overflow-y-auto">
                    @foreach($liveNow as $person)
                    <div class="px-5 py-3 flex items-center justify-between hover:bg-white/5 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-indigo-600/40 flex items-center justify-center text-indigo-300 font-extrabold text-xs">
                                {{ strtoupper(substr($person->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-white font-bold text-sm leading-none">{{ $person->name ?? 'Invitado' }}</div>
                                @if($person->phone)
                                <div class="text-slate-500 text-[10px] mt-0.5">{{ $person->phone }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-right">
                            <div>
                                <div class="text-[10px] font-extrabold text-slate-400 uppercase">
                                    {{ $person->zone == 'gym' ? '🏃 GYM' : ($person->zone == 'pool' ? '🏊 POOL' : '⚽ CANCHAS') }}
                                </div>
                                <div class="text-[10px] text-slate-500">{{ $person->reservation_date->format('h:i A') }} · {{ $person->guests }} pax</div>
                            </div>
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse flex-shrink-0"></span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            @else
            <div class="text-center py-8">
                <div class="text-4xl mb-3">😴</div>
                <p class="text-slate-400 font-bold">No hay reservas activas en este momento</p>
                <p class="text-slate-600 text-xs mt-1">La próxima reserva aparecerá aquí cuando esté dentro de la ventana de ±90 min</p>
            </div>
            @endif

            <div class="mt-4 flex items-center justify-between">
                <p class="text-slate-600 text-[10px] font-bold uppercase tracking-widest">Auto-refresca en <span id="refresh-countdown">60</span>s</p>
                <a href="{{ route('admin.dashboard') }}" class="text-indigo-400 hover:text-indigo-300 text-xs font-bold transition-colors">↺ Actualizar ahora</a>
            </div>
        </div>

        <!-- OCUPACIÓN HOY (mini barras por zona) -->
        <div class="grid grid-cols-3 gap-5 mb-10">
            @php
            $gymTotal     = (int) \App\Models\Reservation::where('status','confirmed')->where('zone','gym')->whereDate('reservation_date',$today)->sum('guests');
            $poolTotal    = (int) \App\Models\Reservation::where('status','confirmed')->where('zone','pool')->whereDate('reservation_date',$today)->sum('guests');
            $canchasTotal = (int) \App\Models\Reservation::where('status','confirmed')->where('zone','canchas')->whereDate('reservation_date',$today)->sum('guests');
            $zones = [
              'gym'     => ['label' => '🏃 Gimnasio',          'limit' => 50, 'color' => 'indigo',  'count' => $gymTotal],
              'pool'    => ['label' => '🏊 Natación',          'limit' => 30, 'color' => 'blue',    'count' => $poolTotal],
              'canchas' => ['label' => '⚽ Canchas Deportivas','limit' => 20, 'color' => 'emerald', 'count' => $canchasTotal],
            ];
            @endphp
            @foreach($zones as $key => $zone)
            @php
            $pct = $zone['limit'] > 0 ? min(100, round(($zone['count'] / $zone['limit']) * 100)) : 0;
            $remaining = max(0, $zone['limit'] - $zone['count']);
            @endphp
            <div class="bg-white rounded-3xl p-6 stat-card">
                <div class="flex justify-between items-start mb-4">
                    <p class="font-extrabold text-slate-800 text-sm">{{ $zone['label'] }}</p>
                    <span class="text-[10px] font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg">HOY CONFIRMADOS</span>
                </div>
                <div class="flex items-end justify-between mb-3">
                    <div>
                        <span class="text-3xl font-extrabold text-slate-900">{{ $zone['count'] }}</span>
                        <span class="text-xs text-slate-400 ml-1 font-bold">pax reservados</span>
                    </div>
                    <span class="text-xs font-bold text-slate-400">{{ $remaining }} cupos libres</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-{{ $zone['color'] }}-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                </div>
                <p class="text-[10px] text-slate-400 font-bold mt-2 uppercase">{{ $pct }}% del aforo diario</p>
            </div>
            @endforeach
        </div>

        <!-- FILTROS + TABLA -->
        <div class="bg-white rounded-[2.5rem] overflow-hidden" style="box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
            <!-- TOOLBAR -->
            <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                <form action="{{ route('admin.dashboard') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-48">
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-1.5">Buscar por nombre</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ej: Carlos Ramírez…"
                            class="w-full bg-white border border-slate-200 rounded-2xl py-2.5 px-4 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="w-44">
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-1.5">Zona</label>
                        <select name="zone" class="w-full bg-white border border-slate-200 rounded-2xl py-2.5 px-4 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="all">Todas</option>
                            <option value="gym" {{ request('zone')=='gym' ? 'selected' : '' }}>GYM</option>
                            <option value="pool" {{ request('zone')=='pool' ? 'selected' : '' }}>POOL</option>
                            <option value="canchas" {{ request('zone')=='canchas' ? 'selected' : '' }}>CANCHAS</option>
                        </select>
                    </div>
                    <div class="w-44">
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-1.5">Estado</label>
                        <select name="status" class="w-full bg-white border border-slate-200 rounded-2xl py-2.5 px-4 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="all">Todos</option>
                            <option value="confirmed" {{ request('status')=='confirmed' ? 'selected' : '' }}>Confirmadas</option>
                            <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>Pendientes</option>
                            <option value="cancelled" {{ request('status')=='cancelled' ? 'selected' : '' }}>Canceladas</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-2xl transition-all text-sm">
                        Filtrar
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="text-slate-400 hover:text-slate-600 text-sm font-bold py-2.5 px-4 rounded-2xl hover:bg-slate-100 transition-all">
                        Limpiar
                    </a>
                    <div class="ml-auto flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-bold">{{ $reservations->total() }} registros</span>
                    </div>
                </form>
            </div>

            <!-- TABLE -->
            <div class="scroll-table overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="px-8 py-4 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Cliente</th>
                            <th class="px-8 py-4 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Zona</th>
                            <th class="px-8 py-4 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Horario</th>
                            <th class="px-8 py-4 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Pax</th>
                            <th class="px-8 py-4 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Estado</th>
                            <th class="px-8 py-4 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($reservations as $res)
                        <tr class="table-row transition-all">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-50 to-indigo-100 flex items-center justify-center text-indigo-600 font-extrabold text-sm flex-shrink-0">
                                        {{ strtoupper(substr($res->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm leading-tight">{{ $res->name ?? 'Sin nombre' }}</div>
                                        @if($res->email)
                                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $res->email }}</div>
                                        @endif
                                        @if($res->phone)
                                        <div class="text-[11px] text-slate-400">{{ $res->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">{{ $res->zone == 'gym' ? '🏃' : ($res->zone == 'pool' ? '🏊' : '⚽') }}</span>
                                    <span class="font-bold text-slate-700 text-sm uppercase">{{ $res->zone }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="font-bold text-slate-700 text-sm">{{ $res->reservation_date->format('d M Y') }}</div>
                                <div class="text-xs text-indigo-500 font-bold">{{ $res->reservation_date->format('h:i A') }}</div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="bg-slate-100 text-slate-600 text-xs font-extrabold px-3 py-1 rounded-xl">{{ $res->guests }} PAX</span>
                            </td>
                            <td class="px-8 py-5">
                                <form action="{{ route('admin.status', $res->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()"
                                        class="badge-base status-{{ $res->status }} cursor-pointer bg-transparent border pr-2 rounded-xl text-[10px] font-extrabold uppercase outline-none">
                                        <option value="pending"   {{ $res->status=='pending'   ? 'selected' : '' }}>⏳ Pendiente</option>
                                        <option value="confirmed" {{ $res->status=='confirmed' ? 'selected' : '' }}>✓ Confirmada</option>
                                        <option value="cancelled" {{ $res->status=='cancelled' ? 'selected' : '' }}>✕ Cancelada</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-[10px] font-bold text-slate-300">{{ $res->created_at->diffForHumans() }}</span>
                                    <form action="{{ route('admin.destroy', $res->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta reserva?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-slate-300 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50" title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-8 py-20 text-center">
                                <div class="text-4xl mb-3">🔍</div>
                                <p class="text-slate-400 font-bold">No se encontraron reservas con esos filtros</p>
                                <a href="{{ route('admin.dashboard') }}" class="text-indigo-500 text-sm font-bold mt-2 inline-block hover:underline">Limpiar filtros</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if($reservations->hasPages())
            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/30">
                {{ $reservations->links() }}
            </div>
            @endif
        </div>
    </main>
    <script>
        // Auto-refresco cada 60 segundos con countdown visible
        let secs = 60;
        const el = document.getElementById('refresh-countdown');
        setInterval(() => {
            secs--;
            if (el) el.textContent = secs;
            if (secs <= 0) window.location.reload();
        }, 1000);
    </script>
</body>
</html>
