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
                    <img src="{{ asset('Natación.JPG') }}" class="zone-img w-full h-full object-cover transition-transform duration-500">
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

                    <!-- CALENDARIO DE DISPONIBILIDAD (DYNAMIC) -->
                    <div class="space-y-4 mt-2">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-extrabold text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span> 1. Elegir Día
                            </p>
                            <span id="selected-date-label" class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-lg">Hoy</span>
                        </div>
                        <div id="availability-grid" class="flex flex-wrap gap-2 pt-1">
                            <!-- Se llena con JS -->
                        </div>

                        <!-- SELECCIÓN DE HORA -->
                        <div id="hour-selection-container" class="space-y-3 pt-4 border-t border-slate-100 hidden">
                            <p class="text-[11px] font-extrabold text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> 2. Horarios disponibles
                            </p>
                            <div id="hour-grid" class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                                <!-- Se llena con JS -->
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Zona del complejo</label>
                        <div id="zone-display" class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 font-bold text-sm">—</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-1">
                             <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Fecha y Hora Seleccionada</label>
                             <div id="final-datetime-display" class="w-full bg-slate-100 border border-slate-200 rounded-2xl text-slate-400 py-3.5 px-5 font-bold text-xs">— Selecciona día y hora —</div>
                             <input type="hidden" name="datetime" id="res-datetime" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Pax (Invitados)</label>
                            <input type="number" name="guests" min="1" max="50" value="1" required class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-bold text-center">
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submit-btn" disabled class="w-full bg-slate-300 text-white font-extrabold py-5 rounded-3xl transition-all shadow-xl text-base tracking-wide cursor-not-allowed">
                        CONFIRMAR RESERVACIÓN ✓
                    </button>
                    <p class="text-[9px] text-slate-300 text-center mt-4 uppercase tracking-[0.2em] font-bold">Datos protegidos · Digital Twin Certified</p>
                </div>
            </form>
        </div>
    </div>

    <script>
        const zoneConfig = {
            gym: { label: 'Gimnasio', start: 7, end: 22, limit: 50 },
            pool: { label: 'Natación', start: 8, end: 20, limit: 30 },
            canchas: { label: 'Canchas', start: 9, end: 21, limit: 20 }
        };
        const availabilityData = @json($availability);
        
        let selectedZone = null;
        let selectedDate = null;
        let selectedHour = null;

        function openReserveModal(zone) {
            selectedZone = zone;
            selectedDate = null;
            selectedHour = null;
            updateUI();
            
            document.getElementById('modal-zone').value = zone;
            document.getElementById('modal-zone-label').textContent = '🏃 ' + (zoneConfig[zone]?.label || zone);
            document.getElementById('zone-display').textContent = zoneConfig[zone]?.label || zone;
            
            // Generar Calendario Mini de 7 días
            const grid = document.getElementById('availability-grid');
            grid.innerHTML = '';
            
            const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            const today = new Date();
            
            for(let i=0; i < 7; i++) {
                const date = new Date();
                date.setDate(today.getDate() + i);
                const dateStr = date.toISOString().split('T')[0];
                const dayName = days[date.getDay()];
                const dayNum = date.getDate();
                
                // Ocupación diaria TOTAL (suma de horas)
                let zoneDayTotal = 0;
                if(availabilityData[dateStr] && availabilityData[dateStr][zone]) {
                    Object.values(availabilityData[dateStr][zone]).forEach(count => zoneDayTotal += parseInt(count));
                }
                
                let dayIntensity = 'bg-emerald-50 text-emerald-600 border-emerald-100';
                let dayLabel = 'LIBRE';
                if(zoneDayTotal > (zoneConfig[zone].limit * 3)) { dayIntensity = 'bg-amber-50 text-amber-600 border-amber-100'; dayLabel = 'ALGO LLENO'; }
                if(zoneDayTotal > (zoneConfig[zone].limit * 8)) { dayIntensity = 'bg-rose-50 text-rose-600 border-rose-100'; dayLabel = 'MUY LLENO'; }

                const div = document.createElement('div');
                div.className = `flex-1 min-w-[55px] p-2 rounded-2xl border text-center transition-all cursor-pointer hover:scale-105 day-card ${dayIntensity}`;
                div.id = `day-${dateStr}`;
                div.innerHTML = `
                    <p class="text-[9px] font-bold uppercase mb-0.5">${dayName}</p>
                    <p class="text-base font-black tracking-tighter leading-none">${dayNum}</p>
                    <p class="text-[7px] font-black mt-1 uppercase opacity-60">${dayLabel}</p>
                `;
                
                div.onclick = () => selectDay(dateStr, date);
                grid.appendChild(div);
            }

            document.getElementById('hour-selection-container').classList.add('hidden');
            document.getElementById('res-modal').classList.remove('hidden');
        }

        function selectDay(dateStr, dateObj) {
            selectedDate = dateStr;
            selectedHour = null;
            
            // Highlight card
            document.querySelectorAll('.day-card').forEach(c => c.classList.remove('ring-2', 'ring-indigo-600', 'ring-offset-2', 'bg-indigo-600', 'text-white'));
            const card = document.getElementById(`day-${dateStr}`);
            card.classList.add('ring-2', 'ring-indigo-600', 'ring-offset-2');
            
            document.getElementById('selected-date-label').textContent = dateStr;
            
            // Generate Hours
            const hourGrid = document.getElementById('hour-grid');
            hourGrid.innerHTML = '';
            
            const config = zoneConfig[selectedZone];
            for(let h = config.start; h < config.end; h++) {
                const hourStr = h < 10 ? `0${h}:00` : `${h}:00`;
                const occupancy = (availabilityData[dateStr] && availabilityData[dateStr][selectedZone] && availabilityData[dateStr][selectedZone][h]) ? parseInt(availabilityData[dateStr][selectedZone][h]) : 0;
                
                let hColor = 'bg-slate-50 text-slate-700 border-slate-200';
                if(occupancy >= config.limit) hColor = 'bg-red-50 text-red-300 border-red-100 cursor-not-allowed opacity-50';
                else if(occupancy > config.limit * 0.7) hColor = 'bg-amber-50 text-amber-600 border-amber-200';

                const hDiv = document.createElement('div');
                hDiv.className = `p-2.5 rounded-xl border text-center text-xs font-bold cursor-pointer hover:bg-indigo-50 transition-colors hour-pill ${hColor}`;
                hDiv.textContent = hourStr;
                hDiv.id = `hour-${h}`;
                
                if(occupancy < config.limit) {
                    hDiv.onclick = () => selectHour(h, hourStr);
                }
                
                hourGrid.appendChild(hDiv);
            }
            
            document.getElementById('hour-selection-container').classList.remove('hidden');
            updateUI();
        }

        function selectHour(h, hourStr) {
            selectedHour = h;
            document.querySelectorAll('.hour-pill').forEach(p => p.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600'));
            const pill = document.getElementById(`hour-${h}`);
            pill.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
            
            const dtFinal = `${selectedDate}T${h < 10 ? '0'+h : h}:00`;
            document.getElementById('res-datetime').value = dtFinal;
            document.getElementById('final-datetime-display').textContent = `${selectedDate} a las ${hourStr}`;
            document.getElementById('final-datetime-display').classList.remove('text-slate-400', 'bg-slate-100');
            document.getElementById('final-datetime-display').classList.add('text-indigo-600', 'bg-indigo-50');
            
            updateUI();
        }

        function updateUI() {
            const btn = document.getElementById('submit-btn');
            if(selectedDate && selectedHour !== null) {
                btn.disabled = false;
                btn.classList.remove('bg-slate-300', 'cursor-not-allowed');
                btn.classList.add('bg-slate-900', 'hover:bg-indigo-600');
            } else {
                btn.disabled = true;
                btn.classList.add('bg-slate-300', 'cursor-not-allowed');
                btn.classList.remove('bg-slate-900', 'hover:bg-indigo-600');
            }
        }
    </script>
</body>
</html>
