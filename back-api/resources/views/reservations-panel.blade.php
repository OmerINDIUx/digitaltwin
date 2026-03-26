<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISTEMA DE RESERVACIONES | Digital Twin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f1f5f9; color: #1e293b; }
        .glass-dark { background: rgba(30, 41, 59, 0.03); border: 1px solid rgba(0,0,0,0.05); }
        .card-shadow { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05); }
        .zone-card:hover .zone-img { transform: scale(1.05); filter: contrast(110%) brightness(90%); }
    </style>
</head>
<body class="min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- HEADER -->
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
            <div>
                <nav class="flex items-center gap-2 text-slate-400 text-xs font-bold uppercase tracking-widest mb-2">
                    <span>Admin</span> <span>/</span> <span class="text-indigo-600">Explore</span>
                </nav>
                <h1 class="text-5xl font-extrabold text-slate-900 tracking-tight italic">Bienvenido al <span class="text-indigo-600">Complejo</span></h1>
                <p class="text-slate-500 mt-2 text-lg">¿Qué área deseas utilizar hoy? Revisa disponibilidad en tiempo real.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="http://localhost:5173" target="_blank" class="px-6 py-4 bg-white border border-slate-200 text-slate-700 font-bold rounded-2xl hover:bg-slate-50 transition-all card-shadow flex items-center gap-2">
                    🏙️ VOLVER AL MODELO 3D
                </a>
            </div>
        </header>

        <!-- ZONE EXPLORER (LAS CARDS QUE PIDIÓ EL USUARIO) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            
            <!-- GYM CARD -->
            @php $gymPct = ($stats['gym']['count'] / $stats['gym']['limit']) * 100; @endphp
            <div class="zone-card bg-white rounded-[2.5rem] overflow-hidden card-shadow border border-slate-100 ring-1 ring-slate-900/5 transition-all hover:shadow-2xl">
                <div class="h-48 overflow-hidden relative">
                    <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&q=80&w=400" class="zone-img w-full h-full object-cover transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-6">
                         <span class="text-xs font-bold text-white bg-indigo-600 px-3 py-1 rounded-full uppercase tracking-tighter">Fitness Center</span>
                    </div>
                </div>
                <div class="p-8">
                    <h3 class="text-2xl font-extrabold text-slate-900 mb-1">Gimnasio</h3>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-6 italic">Horario: {{ $stats['gym']['schedule'] }}</p>
                    
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs font-extrabold text-slate-500 uppercase">Disponibilidad</span>
                        <span class="text-lg font-extrabold {{ $gymPct > 80 ? 'text-red-500' : 'text-indigo-600' }}">
                            {{ max(0, $stats['gym']['limit'] - $stats['gym']['count']) }} lugares libres
                        </span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden mb-8">
                        <div class="h-full bg-indigo-600 transition-all duration-1000" style="width: {{ $gymPct }}%"></div>
                    </div>

                    <button onclick="openReserveModal('gym')" class="w-full py-4 bg-slate-900 text-white font-bold rounded-2xl hover:bg-indigo-600 transition-all shadow-lg hover:shadow-indigo-500/20 active:scale-95">
                        RESERVAR AHORA 🏃
                    </button>
                </div>
            </div>

            <!-- POOL CARD -->
            @php $poolPct = ($stats['pool']['count'] / $stats['pool']['limit']) * 100; @endphp
            <div class="zone-card bg-white rounded-[2.5rem] overflow-hidden card-shadow border border-slate-100 ring-1 ring-slate-900/5 transition-all hover:shadow-2xl">
                <div class="h-48 overflow-hidden relative">
                    <img src="https://images.unsplash.com/photo-1519449556851-5720b33024e7?auto=format&fit=crop&q=80&w=400" class="zone-img w-full h-full object-cover transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-6">
                         <span class="text-xs font-bold text-white bg-blue-600 px-3 py-1 rounded-full uppercase tracking-tighter">Aquatic area</span>
                    </div>
                </div>
                <div class="p-8">
                    <h3 class="text-2xl font-extrabold text-slate-900 mb-1">Natación</h3>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-6 italic">Horario: {{ $stats['pool']['schedule'] }}</p>
                    
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs font-extrabold text-slate-500 uppercase">Capacidad Hoy</span>
                        <span class="text-lg font-extrabold text-blue-600">
                            {{ max(0, $stats['pool']['limit'] - $stats['pool']['count']) }} disponibles
                        </span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden mb-8">
                        <div class="h-full bg-blue-600 transition-all duration-1000" style="width: {{ $poolPct }}%"></div>
                    </div>

                    <button onclick="openReserveModal('pool')" class="w-full py-4 bg-slate-900 text-white font-bold rounded-2xl hover:bg-blue-600 transition-all shadow-lg hover:shadow-blue-500/20 active:scale-95">
                        RESERVAR AHORA 🏊
                    </button>
                </div>
            </div>

            <!-- SPORTS CARD -->
            @php $canchasPct = ($stats['canchas']['count'] / $stats['canchas']['limit']) * 100; @endphp
            <div class="zone-card bg-white rounded-[2.5rem] overflow-hidden card-shadow border border-slate-100 ring-1 ring-slate-900/5 transition-all hover:shadow-2xl">
                <div class="h-48 overflow-hidden relative">
                    <img src="https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&q=80&w=400" class="zone-img w-full h-full object-cover transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-6">
                         <span class="text-xs font-bold text-white bg-emerald-600 px-3 py-1 rounded-full uppercase tracking-tighter">Court Arena</span>
                    </div>
                </div>
                <div class="p-8">
                    <h3 class="text-2xl font-extrabold text-slate-900 mb-1">Canchas Deportivas</h3>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-6 italic">Horario: {{ $stats['canchas']['schedule'] }}</p>
                    
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs font-extrabold text-slate-500 uppercase">Cupos restantes</span>
                        <span class="text-lg font-extrabold text-emerald-600">
                            {{ max(0, $stats['canchas']['limit'] - $stats['canchas']['count']) }} libres
                        </span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden mb-8">
                        <div class="h-full bg-emerald-600 transition-all duration-1000" style="width: {{ $canchasPct }}%"></div>
                    </div>

                    <button onclick="openReserveModal('canchas')" class="w-full py-4 bg-slate-900 text-white font-bold rounded-2xl hover:bg-emerald-600 transition-all shadow-lg hover:shadow-emerald-500/20 active:scale-95">
                        RESERVAR AHORA ⚽
                    </button>
                </div>
            </div>
        </div>

        <!-- BANNER ADMIN (reemplaza al historial) -->
        <div class="mt-10 bg-slate-900 rounded-[2.5rem] p-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-3xl bg-indigo-600/20 flex items-center justify-center text-2xl flex-shrink-0">🔒</div>
                <div>
                    <h3 class="text-white font-extrabold text-lg leading-tight">¿Eres administrador?</h3>
                    <p class="text-slate-400 text-sm mt-0.5">Accede al panel de control para ver y gestionar todas las reservaciones del complejo.</p>
                </div>
            </div>
            <a href="{{ route('admin.login') }}" class="flex-shrink-0 bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold py-3 px-8 rounded-2xl transition-all shadow-lg shadow-indigo-600/20 text-sm tracking-wide whitespace-nowrap">
                INICIAR SESIÓN →
            </a>
        </div>

        <div class="text-center mt-8 pb-8">
            <a href="{{ route('admin.login') }}" class="text-slate-300 hover:text-slate-500 text-[10px] font-bold uppercase tracking-[0.25em] transition-colors">
                🔒 Acceso administrativo
            </a>
        </div>
    <div id="res-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
        <div class="bg-white w-full max-w-lg p-10 rounded-[3rem] shadow-2xl relative border border-slate-200 max-h-screen overflow-y-auto">
            <button onclick="document.getElementById('res-modal').classList.add('hidden')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-900 transition-colors bg-slate-100 p-2 rounded-full w-9 h-9 flex items-center justify-center font-bold">✕</button>
            
            <div class="mb-8">
                <p id="modal-zone-label" class="text-xs font-extrabold text-indigo-500 uppercase tracking-widest mb-1">Seleccionando zona...</p>
                <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Agendar <span class="text-indigo-600">Reserva</span></h2>
                <p class="text-slate-400 text-sm mt-1">Completa tus datos para confirmar el espacio</p>
            </div>

            <form action="{{ url('/panel') }}" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="zone" id="modal-zone">

                <!-- DATOS PERSONALES -->
                <div class="bg-slate-50 rounded-3xl p-6 space-y-4">
                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">👤 Datos del Titular</p>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Nombre completo <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="Ej: Carlos Ramírez" class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-bold placeholder:font-normal placeholder:text-slate-300">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Email</label>
                            <input type="email" name="email" placeholder="correo@mail.com" class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm placeholder:text-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Teléfono</label>
                            <input type="tel" name="phone" placeholder="+52 000 000 0000" class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm placeholder:text-slate-300">
                        </div>
                    </div>
                </div>

                <!-- DETALLES DE RESERVA -->
                <div class="bg-slate-50 rounded-3xl p-6 space-y-4">
                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">📅 Detalles del Espacio</p>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Zona del complejo</label>
                        <div id="zone-display" class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 font-bold text-sm">—</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Fecha y Hora <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="datetime" required class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-bold text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Pax (Invitados)</label>
                            <input type="number" name="guests" min="1" max="50" value="1" required class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-bold text-center">
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-extrabold py-5 rounded-3xl transition-all shadow-xl text-base tracking-wide">
                        CONFIRMAR RESERVACIÓN ✓
                    </button>
                    <p class="text-[9px] text-slate-300 text-center mt-4 uppercase tracking-[0.2em] font-bold">Datos protegidos · Digital Twin Certified</p>
                </div>
            </form>
        </div>
    </div>

    <script>
        const zoneLabels = { gym: '🏃 Gimnasio Fitness', pool: '🏊 Natación Olímpica', canchas: '⚽ Canchas Deportivas' };
        function openReserveModal(zone) {
            document.getElementById('modal-zone').value = zone;
            document.getElementById('modal-zone-label').textContent = zoneLabels[zone] || zone;
            document.getElementById('zone-display').textContent = zoneLabels[zone] || zone;
            document.getElementById('res-modal').classList.remove('hidden');
        }
    </script>
</body>
</html>
