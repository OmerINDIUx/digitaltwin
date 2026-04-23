<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Command Center | Digital Twin</title>
    
    <!-- Scripts & Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #10b981;
            --bg-slate: #0f172a;
        }

        * { font-family: 'Outfit', sans-serif; }
        
        body { 
            background: #f8fafc;
            color: #1e293b;
        }

        /* Glassmorphism Classes */
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
        }

        .dark-glass {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Sidebar Styling */
        .sidebar {
            background: var(--bg-slate);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #94a3b8;
            font-weight: 600;
            transition: all 0.2s;
        }

        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-link.active {
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Animations */
        @keyframes pulse-soft {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.95); }
        }
        .animate-pulse-soft { animation: pulse-soft 2s infinite ease-in-out; }

        /* Status Badges */
        .status-confirmed { background: #dcfce7; color: #166534; }
        .status-pending   { background: #fef9c3; color: #854d0e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .metric-glow-indigo { box-shadow: 0 0 20px rgba(99, 102, 241, 0.15); }
        .metric-glow-emerald { box-shadow: 0 0 20px rgba(16, 185, 129, 0.15); }

        /* Floating Bulk Toolbar */
        #bulk-toolbar {
            transform: translateY(100px);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        #bulk-toolbar.active {
            transform: translateY(0);
        }

        /* Checkbox Custom Styling */
        .custom-checkbox {
            width: 20px;
            height: 20px;
            border-radius: 6px;
            border: 2px solid #cbd5e1;
            appearance: none;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .custom-checkbox:checked {
            background: var(--primary);
            border-color: var(--primary);
        }
        .custom-checkbox:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 14px;
            font-weight: bold;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            main { margin-left: 0 !important; }
            .mobile-overlay { display: none; }
            .mobile-overlay.active { display: block; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 40; backdrop-filter: blur(4px); }
        }
    </style>
</head>
<body class="flex min-h-screen">
    <div id="mobile-overlay" class="mobile-overlay"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="sidebar w-72 flex-shrink-0 flex flex-col p-6 fixed h-screen z-50">
        <!-- Logo -->
        <div class="flex items-center justify-between mb-12 px-2">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-2xl shadow-lg shadow-indigo-600/30 text-white">
                    <i data-lucide="layers"></i>
                </div>
                <div>
                    <h1 class="text-white font-extrabold text-lg leading-none tracking-tight">Digital Twin</h1>
                    <span class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Command Center</span>
                </div>
            </div>
            <button id="close-sidebar" class="lg:hidden text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <!-- NAVIGATION -->
        <nav class="space-y-2 flex-1">
            <a href="{{ route('admin.dashboard') }}" class="nav-link active">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                Dashboard
            </a>
            <a href="{{ route('admin.zones.index') }}" class="nav-link">
                <i data-lucide="map" class="w-5 h-5"></i>
                Gestión de Áreas
            </a>
            <a href="{{ url('/panel') }}" target="_blank" class="nav-link">
                <i data-lucide="globe" class="w-5 h-5"></i>
                Panel Público
            </a>
            <a href="{{ url('/') }}" target="_blank" class="nav-link">
                <i data-lucide="box" class="w-5 h-5"></i>
                Visualizador 3D
            </a>
            <div class="pt-6 pb-2">
                <span class="text-slate-600 text-[10px] font-bold uppercase tracking-widest px-4">Gestión</span>
            </div>
            <a href="#" class="nav-link">
                <i data-lucide="users" class="w-5 h-5"></i>
                Usuarios
            </a>
            <a href="#" class="nav-link">
                <i data-lucide="settings" class="w-5 h-5"></i>
                Configuración
            </a>
        </nav>

        <!-- FOOTER -->
        <div class="border-t border-white/5 pt-6">
            <div class="bg-white/5 rounded-2xl p-4 mb-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-600/20 flex items-center justify-center text-indigo-400 font-bold">
                    {{ strtoupper(substr(session('admin_email', 'A'), 0, 1)) }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-white text-xs font-bold truncate">Administrador</p>
                    <p class="text-slate-500 text-[10px] truncate">{{ session('admin_email') }}</p>
                </div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-red-500/10 text-red-400 font-bold text-sm hover:bg-red-500/20 transition-all">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="ml-72 flex-1 p-8 min-h-screen">
        
        <!-- HEADER -->
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Monitor de <span class="text-indigo-600">Operaciones</span></h2>
                <div class="flex items-center gap-2 text-slate-400 text-sm mt-1">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <span>{{ now()->locale('es')->format('l, d \d\e F Y') }}</span>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="glass-card px-5 py-3 rounded-2xl flex items-center gap-3">
                    <div class="relative">
                        <span class="w-3 h-3 bg-emerald-500 rounded-full block animate-ping absolute"></span>
                        <span class="w-3 h-3 bg-emerald-500 rounded-full block relative"></span>
                    </div>
                    <span class="text-slate-700 font-bold text-sm tracking-wide">SISTEMA EN LÍNEA</span>
                </div>
                <button onclick="window.location.reload()" class="bg-indigo-600 hover:bg-indigo-700 text-white w-12 h-12 rounded-2xl flex items-center justify-center transition-all shadow-lg shadow-indigo-600/20">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </button>
            </div>
        </header>

        <!-- STATS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-10">
            <div class="glass-card p-6 rounded-[2rem] border-l-4 border-indigo-600 metric-glow-indigo">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Total Reservas</p>
                <div class="flex items-end justify-between">
                    <h3 class="text-4xl font-black text-slate-900">{{ $stats['total'] }}</h3>
                    <i data-lucide="bar-chart-3" class="text-indigo-500 w-8 h-8 opacity-20"></i>
                </div>
                <div class="mt-3 text-xs text-indigo-600 font-bold bg-indigo-50 px-2 py-1 rounded-lg inline-flex items-center gap-1">
                    <i data-lucide="trending-up" class="w-3 h-3"></i> Acumulado
                </div>
            </div>

            <div class="glass-card p-6 rounded-[2rem] border-l-4 border-blue-500">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Para Hoy</p>
                <div class="flex items-end justify-between">
                    <h3 class="text-4xl font-black text-slate-900">{{ $stats['today'] }}</h3>
                    <i data-lucide="clock" class="text-blue-500 w-8 h-8 opacity-20"></i>
                </div>
                <div class="mt-3 text-xs text-blue-600 font-bold bg-blue-50 px-2 py-1 rounded-lg inline-flex items-center gap-1">
                    Próximas 24h
                </div>
            </div>

            <div class="glass-card p-6 rounded-[2rem] border-l-4 border-emerald-500 metric-glow-emerald">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Confirmadas</p>
                <div class="flex items-end justify-between">
                    <h3 class="text-4xl font-black text-emerald-600">{{ $stats['confirmed'] }}</h3>
                    <i data-lucide="check-circle" class="text-emerald-500 w-8 h-8 opacity-20"></i>
                </div>
                <div class="mt-3 text-xs text-emerald-600 font-bold bg-emerald-50 px-2 py-1 rounded-lg inline-flex items-center gap-1">
                    Operativas
                </div>
            </div>

            <div class="glass-card p-6 rounded-[2rem] border-l-4 border-amber-500">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Pendientes</p>
                <div class="flex items-end justify-between">
                    <h3 class="text-4xl font-black text-amber-500">{{ $stats['pending'] }}</h3>
                    <i data-lucide="alert-circle" class="text-amber-500 w-8 h-8 opacity-20"></i>
                </div>
                <div class="mt-3 text-xs text-amber-600 font-bold bg-amber-50 px-2 py-1 rounded-lg inline-flex items-center gap-1">
                    En espera
                </div>
            </div>

            <div class="glass-card p-6 rounded-[2rem] border-l-4 border-slate-900">
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Total Visitantes</p>
                <div class="flex items-end justify-between">
                    <h3 class="text-4xl font-black text-slate-900">{{ $stats['guests'] }}</h3>
                    <i data-lucide="users" class="text-slate-500 w-8 h-8 opacity-20"></i>
                </div>
                <div class="mt-3 text-xs text-slate-500 font-bold bg-slate-100 px-2 py-1 rounded-lg inline-flex items-center gap-1">
                    Historial Pax
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            <!-- IOT SENSORS -->
            <div class="glass-card rounded-[2.5rem] p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-slate-900 font-black text-xl tracking-tight">SENSORES IOT</h3>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Infraestructura del Complejo</p>
                    </div>
                    <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="cpu"></i>
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($sensors as $sensor)
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-indigo-200 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full {{ $sensor['status'] == 'good' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }} flex items-center justify-center">
                                <i data-lucide="radio" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800">{{ $sensor['name'] }}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase">{{ $sensor['type'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-slate-900">{{ $sensor['value'] }}</p>
                            <div class="flex items-center justify-end gap-1 text-[10px] font-bold {{ $sensor['status'] == 'good' ? 'text-emerald-500' : 'text-amber-500' }}">
                                <i data-lucide="battery" class="w-3 h-3"></i> {{ $sensor['battery'] }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- ASSET MANAGEMENT -->
            <div class="glass-card rounded-[2.5rem] p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-slate-900 font-black text-xl tracking-tight">ESTADO DE ACTIVOS</h3>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Mantenimiento y Salud</p>
                    </div>
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="wrench"></i>
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($assets as $asset)
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="text-sm font-bold text-slate-800">{{ $asset['name'] }}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase">{{ $asset['zone'] }}</p>
                            </div>
                            <span class="text-[10px] font-black px-2 py-1 rounded-lg {{ $asset['status'] == 'Operativo' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">
                                {{ $asset['status'] }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full {{ $asset['health'] > 90 ? 'bg-emerald-500' : 'bg-amber-500' }} rounded-full" style="width: {{ $asset['health'] }}%"></div>
                            </div>
                            <span class="text-xs font-black text-slate-700">{{ $asset['health'] }}%</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
            <!-- LIVE MONITOR -->
            <div class="lg:col-span-2 dark-glass rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-600/10 blur-[80px] -mr-32 -mt-32"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10">
                                <i data-lucide="radio" class="text-emerald-400 w-6 h-6 animate-pulse"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-black text-xl tracking-tight">EN VIVO AHORA</h3>
                                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Monitoreo en tiempo real (±90 min)</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-5xl font-black text-white leading-none">{{ $liveTotal }}</div>
                            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mt-2">Pax en el complejo</p>
                        </div>
                    </div>

                    @if($liveTotal > 0)
                    <div class="grid grid-cols-3 gap-4 mb-8">
                        @php
                            $zones = [
                                'gym' => ['icon' => 'activity', 'label' => 'Gym', 'color' => 'indigo'],
                                'pool' => ['icon' => 'waves', 'label' => 'Pool', 'color' => 'blue'],
                                'canchas' => ['icon' => 'target', 'label' => 'Canchas', 'color' => 'emerald']
                            ];
                        @endphp
                        @foreach($zones as $key => $z)
                        <div class="bg-white/5 border border-white/10 rounded-2xl p-5 hover:bg-white/10 transition-all cursor-pointer group">
                            <div class="flex justify-between items-start mb-3">
                                <i data-lucide="{{ $z['icon'] }}" class="text-{{ $z['color'] }}-400 w-5 h-5 group-hover:scale-110 transition-transform"></i>
                                <span class="text-2xl font-black text-white">{{ $liveCounts[$key] }}</span>
                            </div>
                            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">{{ $z['label'] }}</p>
                        </div>
                        @endforeach
                    </div>

                    <div class="bg-black/20 rounded-3xl border border-white/5 overflow-hidden">
                        <div class="px-6 py-4 border-b border-white/5 bg-white/5">
                            <span class="text-slate-400 text-[10px] font-bold uppercase tracking-widest flex items-center gap-2">
                                <i data-lucide="list" class="w-3 h-3"></i> Ocupación Detallada
                            </span>
                        </div>
                        <div class="divide-y divide-white/5 max-h-64 overflow-y-auto">
                            @foreach($liveNow as $person)
                            <div class="px-6 py-4 flex items-center justify-between hover:bg-white/5 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-400 font-black text-sm">
                                        {{ strtoupper(substr($person->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <h4 class="text-white font-bold text-sm">{{ $person->name ?? 'Invitado' }}</h4>
                                        <p class="text-slate-500 text-[10px] uppercase font-bold">{{ $person->zone }} · {{ $person->guests }} pax</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-indigo-400 font-black text-sm">{{ $person->reservation_date->format('h:i A') }}</p>
                                    <span class="text-slate-500 text-[10px]">Confirmado</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="py-12 text-center">
                        <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4 border border-white/10">
                            <i data-lucide="moon" class="text-slate-600 w-8 h-8"></i>
                        </div>
                        <p class="text-slate-400 font-bold">No hay actividad en este momento</p>
                        <p class="text-slate-600 text-[10px] uppercase mt-2">Próxima ventana de monitoreo automática</p>
                    </div>
                    @endif
                    
                    <div class="mt-6 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Refrescando en <span id="refresh-counter">60</span>s
                        </div>
                        <button onclick="window.location.reload()" class="text-indigo-400 hover:text-indigo-300 text-[10px] font-bold uppercase tracking-widest flex items-center gap-2">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i> Actualizar Ahora
                        </button>
                    </div>
                </div>
            </div>

            <!-- TREND CHART -->
            <div class="glass-card rounded-[2.5rem] p-8 flex flex-col">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-slate-900 font-black text-xl tracking-tight">TENDENCIA</h3>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Reservas últimos 7 días</p>
                    </div>
                    <i data-lucide="trending-up" class="text-indigo-500 w-6 h-6"></i>
                </div>
                <div class="flex-1 min-h-[200px] relative">
                    <canvas id="weeklyChart"></canvas>
                </div>
                <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Pico Semanal</p>
                        <p class="text-xl font-black text-slate-900">{{ collect($weeklyStats)->max('count') }} <span class="text-[10px] text-slate-400">Res.</span></p>
                    </div>
                    <div class="text-center border-l border-slate-100">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Promedio Diario</p>
                        <p class="text-xl font-black text-slate-900">{{ round(collect($weeklyStats)->avg('count'), 1) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTERS & TABLE -->
        <div class="glass-card rounded-[2.5rem] overflow-hidden">
            <div class="p-8 border-b border-slate-100 bg-slate-50/50">
                <form action="{{ route('admin.dashboard') }}" method="GET" class="flex flex-wrap items-end gap-6">
                    <div class="flex-1 min-w-[280px]">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Buscar Reserva</label>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nombre o correo..." 
                                class="w-full bg-white border border-slate-200 rounded-2xl py-3 pl-11 pr-4 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                        </div>
                    </div>
                    
                    <div class="w-48">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Zona</label>
                        <select name="zone" class="w-full bg-white border border-slate-200 rounded-2xl py-3 px-4 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none appearance-none cursor-pointer transition-all">
                            <option value="all">Todas las áreas</option>
                            @foreach($zones as $z)
                                <option value="{{ $z->slug }}" {{ request('zone')==$z->slug ? 'selected' : '' }}>{{ $z->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-48">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Estado</label>
                        <select name="status" class="w-full bg-white border border-slate-200 rounded-2xl py-3 px-4 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none appearance-none cursor-pointer transition-all">
                            <option value="all">Cualquier estado</option>
                            <option value="confirmed" {{ request('status')=='confirmed' ? 'selected' : '' }}>Confirmadas</option>
                            <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>Pendientes</option>
                            <option value="cancelled" {{ request('status')=='cancelled' ? 'selected' : '' }}>Canceladas</option>
                        </select>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-2xl transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                            Filtrar
                        </button>
                        <a href="{{ route('admin.dashboard') }}" class="bg-white border border-slate-200 text-slate-600 font-bold py-3 px-4 rounded-2xl hover:bg-slate-50 transition-all flex items-center justify-center">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- BULK ACTIONS FORM -->
            <form id="bulk-form" action="{{ route('admin.bulk') }}" method="POST">
                @csrf
                <input type="hidden" name="action" id="bulk-action-input">
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 w-10">
                                    <input type="checkbox" id="select-all" class="custom-checkbox">
                                </th>
                                <th class="px-2 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente / ID</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Zona</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Horario</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Personas</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reservations as $res)
                        <tr class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-8 py-6">
                                <input type="checkbox" name="ids[]" value="{{ $res->id }}" class="res-checkbox custom-checkbox">
                            </td>
                            <td class="px-2 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-50 to-white border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-lg shadow-sm group-hover:scale-110 transition-transform">
                                        {{ strtoupper(substr($res->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 text-base leading-none">{{ $res->name ?? 'Sin nombre' }}</p>
                                        <p class="text-slate-400 text-xs mt-1.5 flex items-center gap-1">
                                            <i data-lucide="mail" class="w-3 h-3"></i> {{ $res->email ?: 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200">
                                    <i data-lucide="{{ $res->zone == 'gym' ? 'activity' : ($res->zone == 'pool' ? 'waves' : 'target') }}" class="w-4 h-4 text-slate-600"></i>
                                    <span class="text-xs font-black text-slate-700 uppercase">{{ $res->zone }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div>
                                    <p class="font-bold text-slate-800 text-sm">{{ $res->reservation_date->format('d M, Y') }}</p>
                                    <p class="text-indigo-600 font-black text-xs mt-1">{{ $res->reservation_date->format('h:i A') }}</p>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                                        <i data-lucide="users" class="w-4 h-4 text-slate-500"></i>
                                    </div>
                                    <span class="text-sm font-black text-slate-700">{{ $res->guests }} PAX</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <form action="{{ route('admin.status', $res->id) }}" method="POST" class="flex justify-center">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" 
                                        class="text-[10px] font-black uppercase tracking-wider px-4 py-2 rounded-xl cursor-pointer outline-none appearance-none text-center min-w-[120px] transition-all
                                        status-{{ $res->status }}">
                                        <option value="pending" {{ $res->status=='pending' ? 'selected' : '' }}>⏳ Pendiente</option>
                                        <option value="confirmed" {{ $res->status=='confirmed' ? 'selected' : '' }}>✓ Confirmada</option>
                                        <option value="cancelled" {{ $res->status=='cancelled' ? 'selected' : '' }}>✕ Cancelada</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if($res->phone)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $res->phone) }}" target="_blank" 
                                        class="w-10 h-10 rounded-xl flex items-center justify-center text-emerald-500 hover:bg-emerald-50 transition-all" title="Contactar por WhatsApp">
                                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                                    </a>
                                    @endif
                                    <span class="text-[10px] font-bold text-slate-300">{{ $res->created_at->diffForHumans() }}</span>
                                    <form action="{{ route('admin.destroy', $res->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta reserva?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 transition-all">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-8 py-24 text-center">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i data-lucide="search-x" class="text-slate-300 w-10 h-10"></i>
                                </div>
                                <h4 class="text-slate-900 font-bold text-lg">Sin resultados</h4>
                                <p class="text-slate-500 text-sm mt-1">No encontramos ninguna reserva que coincida con tus filtros.</p>
                                <a href="{{ route('admin.dashboard') }}" class="text-indigo-600 font-bold text-sm mt-4 inline-block hover:underline">Limpiar todos los filtros</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($reservations->hasPages())
            <div class="px-8 py-6 border-t border-slate-100 bg-slate-50/30">
                {{ $reservations->links() }}
            </div>
            @endif
            </form>
        </div>

        <!-- FLOATING BULK TOOLBAR -->
        <div id="bulk-toolbar" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[60] bg-slate-900 text-white px-8 py-4 rounded-[2.5rem] shadow-2xl flex items-center gap-8 border border-white/10 dark-glass">
            <div class="flex items-center gap-3 border-r border-white/10 pr-8">
                <span id="selected-count" class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-black text-sm">0</span>
                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Seleccionados</span>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" onclick="submitBulk('confirmed')" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white transition-all text-xs font-bold uppercase">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Confirmar
                </button>
                <button type="button" onclick="submitBulk('pending')" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/20 text-amber-400 hover:bg-amber-500 hover:text-white transition-all text-xs font-bold uppercase">
                    <i data-lucide="clock" class="w-4 h-4"></i> Pendiente
                </button>
                <button type="button" onclick="submitBulk('cancelled')" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-700 text-slate-300 hover:bg-slate-600 transition-all text-xs font-bold uppercase">
                    <i data-lucide="x-circle" class="w-4 h-4"></i> Cancelar
                </button>
                <div class="w-px h-6 bg-white/10 mx-2"></div>
                <button type="button" onclick="submitBulk('delete')" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white transition-all text-xs font-bold uppercase">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Eliminar
                </button>
            </div>

            <button type="button" onclick="deselectAll()" class="ml-4 text-slate-500 hover:text-white transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- FOOTER TEXT -->
        <footer class="mt-12 text-center text-slate-400 text-xs font-bold uppercase tracking-[0.2em] pb-8">
            Digital Twin &copy; {{ date('Y') }} &bull; Command Center v2.0
        </footer>
    </main>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Chart.js Configuration
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyData = @json($weeklyStats);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: weeklyData.map(d => d.label),
                datasets: [{
                    label: 'Reservas',
                    data: weeklyData.map(d => d.count),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' }
                    }
                }
            }
        });

        // Selection Logic
        const selectAll = document.getElementById('select-all');
        const resCheckboxes = document.querySelectorAll('.res-checkbox');
        const bulkToolbar = document.getElementById('bulk-toolbar');
        const selectedCount = document.getElementById('selected-count');
        const bulkActionInput = document.getElementById('bulk-action-input');
        const bulkForm = document.getElementById('bulk-form');

        function updateBulkToolbar() {
            const checked = document.querySelectorAll('.res-checkbox:checked');
            selectedCount.textContent = checked.length;
            if (checked.length > 0) {
                bulkToolbar.classList.add('active');
            } else {
                bulkToolbar.classList.remove('active');
            }
        }

        selectAll.addEventListener('change', (e) => {
            resCheckboxes.forEach(cb => cb.checked = e.target.checked);
            updateBulkToolbar();
        });

        resCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkToolbar);
        });

        function submitBulk(action) {
            if (action === 'delete' && !confirm('¿Estás seguro de eliminar las reservas seleccionadas?')) return;
            bulkActionInput.value = action;
            bulkForm.submit();
        }

        function deselectAll() {
            resCheckboxes.forEach(cb => cb.checked = false);
            selectAll.checked = false;
            updateBulkToolbar();
        }
    </script>
</body>
</html>
