<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISTEMA DE RESERVACIONES | Digital Twin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .glass-dark { background: rgba(30, 41, 59, 0.03); border: 1px solid rgba(0,0,0,0.05); }
        .card-shadow { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05); }
    </style>
</head>
<body class="min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- HEADER -->
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
            <div>
                <nav class="flex items-center gap-2 text-slate-400 text-xs font-bold uppercase tracking-widest mb-2">
                    <span>Admin</span> <span>/</span> <span class="text-indigo-600">Dashboard</span>
                </nav>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Centro de <span class="text-indigo-600">Reservaciones</span></h1>
                <p class="text-slate-500 mt-1">Control maestro de ocupación para Utopía Japón Digital Twin</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="http://localhost:5173" target="_blank" class="px-6 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-2xl hover:bg-slate-50 transition-all card-shadow flex items-center gap-2">
                    🏙️ VOLVER AL MODELO 3D
                </a>
                <button onclick="document.getElementById('res-modal').classList.remove('hidden')" class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-2xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20">
                    + REGISTRAR RESERVA
                </button>
            </div>
        </header>

        <!-- STATS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-6 rounded-3xl card-shadow border border-slate-100 ring-1 ring-slate-900/5">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Reservas</p>
                <h3 class="text-3xl font-extrabold text-slate-900">{{ $stats['total'] }}</h3>
                <div class="mt-2 text-xs font-bold text-indigo-500 bg-indigo-50 px-2 py-1 rounded inline-block">Histórico Global</div>
                <div class="mt-4 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500" style="width: 70%"></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl card-shadow border border-slate-100 ring-1 ring-slate-900/5">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Reservas para Hoy</p>
                <h3 class="text-3xl font-extrabold text-slate-900">{{ $stats['today'] }}</h3>
                <div class="mt-2 text-xs font-bold text-emerald-500 bg-emerald-50 px-2 py-1 rounded inline-block">Sincronizado</div>
                <div class="mt-4 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: 45%"></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl card-shadow border border-slate-100 ring-1 ring-slate-900/5">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Aforo Activo (Pax)</p>
                <h3 class="text-3xl font-extrabold text-slate-900">{{ $stats['guests'] }}</h3>
                <div class="mt-2 text-xs font-bold text-amber-500 bg-amber-50 px-2 py-1 rounded inline-block">Capacidad Real</div>
                <div class="mt-4 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-500" style="width: 60%"></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl card-shadow border border-slate-100 ring-1 ring-slate-900/5">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Zonas Populares</p>
                <div class="flex items-center gap-4 mt-1">
                    <div class="flex-1">
                        <span class="text-[10px] font-bold text-slate-400 block uppercase">GYM</span>
                        <span class="text-lg font-bold text-slate-800">{{ $stats['gym'] }}</span>
                    </div>
                    <div class="flex-1">
                        <span class="text-[10px] font-bold text-slate-400 block uppercase">POOL</span>
                        <span class="text-lg font-bold text-slate-800">{{ $stats['pool'] }}</span>
                    </div>
                </div>
                <div class="mt-4 h-1 w-full bg-slate-100 rounded-full overflow-hidden flex">
                    <div class="h-full bg-indigo-500" style="width: 40%"></div>
                    <div class="h-full bg-amber-500" style="width: 60%"></div>
                </div>
            </div>
        </div>

        <!-- FILTERS & TABLE -->
        <div class="bg-white rounded-[2.5rem] card-shadow border border-slate-100 ring-1 ring-slate-900/5 overflow-hidden">
            
            <!-- TOOLBAR / FILTERS -->
            <div class="p-6 bg-slate-50/50 border-b border-slate-100">
                <form action="{{ url('/panel') }}" method="GET" class="flex flex-col md:flex-row items-end gap-6">
                    <div class="w-full md:w-64">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Zona del complejo</label>
                        <select name="zone" onchange="this.form.submit()" class="w-full bg-white border border-slate-200 rounded-2xl py-2 px-4 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="all">Todas las zonas</option>
                            <option value="gym" {{ request('zone') == 'gym' ? 'selected' : '' }}>GYM / Gimnasio</option>
                            <option value="pool" {{ request('zone') == 'pool' ? 'selected' : '' }}>POOL / Natación</option>
                            <option value="canchas" {{ request('zone') == 'canchas' ? 'selected' : '' }}>SPORTS / Canchas</option>
                        </select>
                    </div>
                    <div class="w-full md:w-64">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Estado de reserva</label>
                        <select name="status" onchange="this.form.submit()" class="w-full bg-white border border-slate-200 rounded-2xl py-2 px-4 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="all">Cualquier estado</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendientes</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmadas</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Canceladas</option>
                        </select>
                    </div>
                    <div class="ml-auto flex items-center gap-4 py-2">
                        <span class="text-xs font-bold text-slate-400 italic">Mostrando {{ $reservations->count() }} resultados</span>
                        <button type="submit" class="p-2 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-all">
                             🔄 Refrescar
                        </button>
                    </div>
                </form>
            </div>

            <!-- TABLE -->
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-400 text-[10px] font-bold uppercase tracking-widest bg-slate-50/30">
                            <th class="px-8 py-5">Zona</th>
                            <th class="px-8 py-5">Horario de Reserva</th>
                            <th class="px-8 py-5">Invitados</th>
                            <th class="px-8 py-5">Estado</th>
                            <th class="px-8 py-5 text-right">Registro de Sistema</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reservations as $res)
                        <tr class="hover:bg-indigo-50/20 transition-all group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-2xl bg-slate-100 font-bold text-xl group-hover:bg-white group-hover:card-shadow transition-all">
                                        {{ $res->zone == 'gym' ? '🏃' : ($res->zone == 'pool' ? '🏊' : '⚽') }}
                                    </div>
                                    <span class="font-bold text-slate-800 uppercase text-sm tracking-tight">{{ $res->zone }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700">{{ $res->reservation_date->format('d M, Y') }}</span>
                                    <span class="text-xs font-bold text-indigo-500">{{ $res->reservation_date->format('h:i A') }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="px-3 py-1 bg-slate-100 text-slate-700 text-xs font-bold rounded-full border border-slate-200">
                                    {{ $res->guests }} PAX
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                @if($res->status == 'confirmed')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-[10px] font-extrabold border border-emerald-100 uppercase">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Confirmado
                                    </span>
                                @elseif($res->status == 'pending')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-amber-50 text-amber-600 text-[10px] font-extrabold border border-amber-100 uppercase">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span> Pendiente
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-lg bg-red-50 text-red-600 text-[10px] font-extrabold border border-red-100">CANCELADA</span>
                                @endif
                            </td>
                            <td class="px-8 py-5 text-right">
                                <span class="text-[10px] font-bold text-slate-300">{{ $res->created_at->diffForHumans() }}</span>
                                <div class="transition-opacity opacity-0 group-hover:opacity-100 mt-1">
                                    <button class="text-indigo-600 text-[10px] font-bold hover:underline">Gestionar ticket</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center text-slate-400 italic">No se encontraron reservaciones con los filtros seleccionados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-6 bg-slate-50/30 border-t border-slate-100 text-center">
                 <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Digital Twin Master Interface v2.5</p>
            </div>
        </div>
    </div>

    <!-- MODAL RESERVA -->
    <div id="res-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md transition-all">
        <div class="bg-white w-full max-w-md p-10 rounded-[3rem] shadow-2xl relative border border-slate-200">
            <button onclick="document.getElementById('res-modal').classList.add('hidden')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-900 transition-colors bg-slate-100 p-2 rounded-full">✕</button>
            
            <div class="mb-8 text-center">
                <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Agendar <span class="text-indigo-600">Espacio</span></h2>
                <p class="text-slate-500 text-sm mt-1">Sincronización segura con Utopía Japón</p>
            </div>

            <form action="{{ url('/panel') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Zona a reservar</label>
                    <select name="zone" class="w-full bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 py-4 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all appearance-none">
                        <option value="gym">🏃 Gimnasio Fitness</option>
                        <option value="pool">🏊 Natación Olímpica</option>
                        <option value="canchas">⚽ Canchas de Fútbol</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Horario programado</label>
                    <input type="datetime-local" name="datetime" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 py-4 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Cantidad de Invitados</label>
                    <input type="number" name="guests" min="1" max="50" value="1" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 py-4 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div class="pt-4">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-5 rounded-[2rem] transition-all shadow-xl shadow-indigo-600/30 text-lg">
                        CONFIRMAR RESERVA
                    </button>
                    <p class="text-[9px] text-slate-400 text-center mt-5 uppercase tracking-[0.2em] font-bold">Datos encriptados bajo protocolos Digital Twin</p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
