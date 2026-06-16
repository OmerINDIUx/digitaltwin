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
        <button id="close-sidebar" class="lg:hidden text-slate-400 hover:text-white">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
    </div>

    <nav class="space-y-2 flex-1">
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ Route::is('admin.dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
        </a>
        <a href="{{ route('admin.zones.index') }}" class="nav-link {{ Route::is('admin.zones.*') ? 'active' : '' }}">
            <i data-lucide="map" class="w-5 h-5"></i> Gestión de Áreas
        </a>
        <a href="{{ route('admin.scanner') }}" class="nav-link {{ Route::is('admin.scanner') ? 'active' : '' }}">
            <i data-lucide="qr-code" class="w-5 h-5"></i> Scanner
        </a>
        <a href="{{ route('admin.events.index') }}" class="nav-link {{ Route::is('admin.events.*') ? 'active' : '' }}">
            <i data-lucide="calendar-heart" class="w-5 h-5"></i> Eventos
        </a>
        <div class="pt-6 pb-2">
            <span class="text-slate-600 text-[10px] font-bold uppercase tracking-widest px-4">Accesos Rápidos</span>
        </div>
        <a href="{{ url('/panel') }}" target="_blank" class="nav-link">
            <i data-lucide="globe" class="w-5 h-5"></i> Panel Público
        </a>
        <a href="{{ rtrim(config('app.frontend_url'), '/') }}/" target="_blank" class="nav-link">
            <i data-lucide="box" class="w-5 h-5"></i> Visualizador 3D
        </a>
    </nav>

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
                <i data-lucide="log-out" class="w-4 h-4"></i> Cerrar Sesión
            </button>
        </form>
    </div>
</aside>
