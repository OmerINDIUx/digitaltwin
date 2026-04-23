<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Digital Twin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        :root { --primary: #6366f1; --primary-dark: #4f46e5; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .sidebar { background: #0f172a; }
        .nav-link { color: #94a3b8; padding: 1rem 1.5rem; border-radius: 1rem; display: flex; items-center: center; gap: 0.75rem; font-weight: 600; transition: all 0.3s; margin-bottom: 0.5rem; }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-link.active { background: #6366f1; color: white; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3); }
        .nav-link i { width: 1.25rem; height: 1.25rem; }
    </style>
</head>
<body class="flex min-h-screen">
    <div id="mobile-overlay" class="mobile-overlay"></div>

    <aside id="sidebar" class="sidebar w-72 flex-shrink-0 flex flex-col p-6 fixed h-screen z-50">
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
        </div>

        <nav class="space-y-2 flex-1">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ Route::is('admin.dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="{{ route('admin.zones.index') }}" class="nav-link {{ Route::is('admin.zones.*') ? 'active' : '' }}">
                <i data-lucide="map"></i> Gestión de Áreas
            </a>
            <a href="{{ route('admin.scanner') }}" class="nav-link {{ Route::is('admin.scanner') ? 'active' : '' }}">
                <i data-lucide="qr-code"></i> Scanner
            </a>
            <a href="{{ route('admin.events.index') }}" class="nav-link {{ Route::is('admin.events.*') ? 'active' : '' }}">
                <i data-lucide="calendar-heart"></i> Eventos
            </a>
            <div class="pt-6 pb-2">
                <span class="text-slate-600 text-[10px] font-bold uppercase tracking-widest px-4">Accesos Rápidos</span>
            </div>
            <a href="{{ url('/panel') }}" target="_blank" class="nav-link">
                <i data-lucide="globe"></i> Panel Público
            </a>
            <a href="{{ url('/') }}" target="_blank" class="nav-link">
                <i data-lucide="box"></i> Visualizador 3D
            </a>
        </nav>

        <div class="border-t border-white/5 pt-6">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 text-slate-400 hover:text-white px-4 font-bold transition-all">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 ml-72">
        @yield('content')
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
