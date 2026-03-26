<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Reservas - Utopía Japón</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap');
        body { font-family: 'Outfit', sans-serif; background: #0f172a; color: white; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="p-4 sm:p-8">
    <div class="max-w-6xl mx-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-white mb-2">Panel de <span class="text-indigo-400">Reservas</span></h1>
                <p class="text-slate-400">Gestión administrativa de Utopía Japón Digital Twin</p>
                <div class="mt-4">
                    <a href="http://localhost:5173" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-4 rounded-xl transition-all shadow-lg shadow-indigo-500/30">
                        <span>🚀</span> IR AL DASHBOARD 3D (INTERACTIVO)
                    </a>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4">
                <button onclick="document.getElementById('res-modal').classList.remove('hidden')" class="bg-indigo-500 hover:bg-indigo-400 text-white font-bold py-2 px-6 rounded-2xl transition-all shadow-lg shadow-indigo-500/20">
                    + NUEVA RESERVA
                </button>
                <div class="bg-indigo-500/10 border border-indigo-500/20 px-4 py-2 rounded-2xl flex items-center gap-2">
                    <span class="relative flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span></span>
                    <span class="text-sm font-semibold text-indigo-300">SISTEMA ACTIVO</span>
                </div>
            </div>
        </header>

        <div class="glass rounded-3xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-indigo-500/5 border-b border-white/5">
                        <tr>
                            <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-widest">Zona</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-widest">Fecha</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-widest">Hora</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-widest">Pax</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-widest">Estado</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-widest text-right">Registro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($reservations as $res)
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-indigo-500/20 flex items-center justify-center text-lg">
                                        {{ $res->zone == 'gym' ? '🏃' : ($res->zone == 'pool' ? '🏊' : '⚽') }}
                                    </div>
                                    <span class="font-bold text-slate-200 uppercase">{{ $res->zone }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-300">{{ $res->reservation_date->format('d M, Y') }}</td>
                            <td class="px-6 py-4 font-mono text-indigo-300">{{ $res->reservation_date->format('h:i A') }}</td>
                            <td class="px-6 py-4">
                                <span class="bg-slate-800 text-slate-300 px-3 py-1 rounded-full text-sm font-bold">{{ $res->guests }} Pax</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-amber-500/10 text-amber-500 border border-amber-500/20 rounded-lg text-xs font-bold">
                                    {{ strtoupper($res->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-slate-500 text-xs">
                                {{ $res->created_at->diffForHumans() }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center text-slate-500 italic">No hay reservaciones registradas aún.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <footer class="mt-8 text-center text-slate-600 text-xs">
            Digital Twin v2.4 - Sistema de Reservas Laravel
        </footer>
    </div>
    <!-- Modal Reservación en Laravel -->
    <div id="res-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="glass w-full max-w-md p-8 rounded-3xl shadow-2xl relative border border-white/10">
            <button onclick="document.getElementById('res-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors">✕</button>
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-white">Agendar Reserva</h2>
                <p class="text-slate-400 text-sm italic">Registro directo en base de datos</p>
            </div>
            <form action="{{ url('/api/reservations') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1 ml-1">Zona del Complejo</label>
                    <select name="zone" class="w-full bg-slate-900 border border-white/5 rounded-xl text-white py-3 px-4 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <option value="gym">🏃 Gimnasio</option>
                        <option value="pool">🏊 Alberca / Natación</option>
                        <option value="canchas">⚽ Canchas Deportivas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1 ml-1">Fecha y Hora</label>
                    <input type="datetime-local" name="datetime" required class="w-full bg-slate-900 border border-white/5 rounded-xl text-white py-3 px-4 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1 ml-1">Pax (Invitados)</label>
                    <input type="number" name="guests" min="1" value="1" required class="w-full bg-slate-900 border border-white/5 rounded-xl text-white py-3 px-4 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-4 rounded-2xl transition-all shadow-xl shadow-indigo-500/30">
                        CONFIRMAR RESERVACIÓN
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
