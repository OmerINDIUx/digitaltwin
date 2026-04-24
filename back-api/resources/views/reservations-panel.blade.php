<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISTEMA DE RESERVACIONES | Digital Twin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

        <!-- ZONE EXPLORER (DINÁMICO) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            @foreach($zones as $zone)
                @php 
                    $zoneStat = $stats[$zone->slug] ?? ['count' => 0, 'limit' => 0, 'schedule' => 'N/A'];
                    
                    // Calcular horas que ya pasaron para descontar esos lugares "vencidos"
                    $nowH = (int)now()->format('H');
                    $startH = (int)explode(':', explode(' - ', $zoneStat['schedule'])[0])[0];
                    $hoursPassed = max(0, $nowH - $startH);
                    $capacityPerHour = $zoneStat['limit_per_hour'] ?? $zone->capacity;
                    $expiredSpots = $hoursPassed * $capacityPerHour;
                    
                    $totalTaken = $zoneStat['count'] + $expiredSpots;
                    $dailyLimit = $zoneStat['limit'];
                    
                    $pct = ($dailyLimit > 0) ? ($totalTaken / $dailyLimit) * 100 : 0; 
                    $free = max(0, $dailyLimit - $totalTaken);
                @endphp
                <div class="zone-card bg-white rounded-[2.5rem] overflow-hidden card-shadow border border-slate-100 ring-1 ring-slate-900/5 transition-all hover:shadow-2xl">
                    <div class="h-48 overflow-hidden relative">
                        <img src="{{ (str_contains($zone->image, 'http') || str_contains($zone->image, '/')) ? $zone->image : asset($zone->image) }}" class="zone-img w-full h-full object-cover transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
                        <div class="absolute bottom-4 left-6">
                            <span class="text-xs font-bold text-white bg-indigo-600 px-3 py-1 rounded-full uppercase tracking-tighter">{{ $zone->name }}</span>
                        </div>
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-extrabold text-slate-900 mb-1">{{ $zone->name }}</h3>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-6 italic">Horario: {{ $zoneStat['schedule'] }}</p>
                        
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-xs font-extrabold text-slate-500 uppercase">Disponibilidad</span>
                            <span class="text-lg font-extrabold {{ $pct > 80 ? 'text-red-500' : 'text-indigo-600' }}">
                                {{ $free }} lugares libres
                            </span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden mb-8">
                            <div class="h-full bg-indigo-600 transition-all duration-1000" style="width: {{ $pct }}%"></div>
                        </div>

                        <button onclick="openReserveModal('{{ $zone->slug }}')" class="w-full py-4 bg-slate-900 text-white font-bold rounded-2xl hover:bg-indigo-600 transition-all shadow-lg hover:shadow-indigo-500/20 active:scale-95">
                            RESERVAR AHORA 
                            @if($zone->slug == 'gym') 🏃 @elseif($zone->slug == 'pool') 🏊 @elseif($zone->slug == 'canchas') ⚽ @else ✨ @endif
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- EVENTS & CLASSES SECTION -->
        <div class="mb-16">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Próximas <span class="text-rose-500">Clases y Eventos</span></h2>
                    <p class="text-slate-500 font-bold uppercase text-[10px] tracking-widest mt-1">Participa en las actividades de la comunidad</p>
                </div>
                <div class="hidden md:block">
                    <span class="text-xs font-bold text-slate-400">Actualizado hace un momento</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($events as $ev)
                <div class="bg-white rounded-[2.5rem] overflow-hidden card-shadow border border-slate-100 group">
                    <div class="h-40 relative overflow-hidden">
                        <img src="{{ $ev->image ?: 'https://images.unsplash.com/photo-1571902251103-d71b46244bc0?auto=format&fit=crop&q=80&w=400' }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                        <div class="absolute top-4 left-6">
                            <span class="px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white text-[10px] font-black uppercase tracking-widest border border-white/20">
                                {{ $ev->type }}
                            </span>
                        </div>
                        <div class="absolute bottom-4 left-6">
                            <div class="flex items-center gap-2 text-white text-[10px] font-bold uppercase">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                {{ $ev->zone }}
                            </div>
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xl font-black text-slate-900">{{ $ev->name }}</h3>
                            <span class="text-indigo-600 font-black text-sm">
                                {{ $ev->price > 0 ? '$' . number_format($ev->price, 2) : 'GRATIS' }}
                            </span>
                        </div>
                        <p class="text-slate-400 text-xs line-clamp-2 mb-6">{{ $ev->description ?: 'Sin descripción disponible.' }}</p>
                        
                        <div class="flex items-center justify-between mb-6">
                            <div class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                📅 {{ $ev->event_date->format('d M') }} | ⏰ {{ $ev->event_date->format('H:i') }}
                            </div>
                            <div class="text-[10px] font-black {{ ($ev->capacity - $ev->registrations_count) < 5 ? 'text-rose-500' : 'text-emerald-500' }} uppercase tracking-widest">
                                {{ $ev->capacity - $ev->registrations_count }} cupos
                            </div>
                        </div>
                        <button onclick="openEventEnrollModal({{ $ev->id }}, '{{ $ev->name }}')" 
                            class="w-full py-4 bg-rose-500 text-white font-black rounded-2xl hover:bg-rose-600 transition-all shadow-lg shadow-rose-500/20 active:scale-95 text-xs uppercase tracking-widest">
                            Inscribirme Ahora
                        </button>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-12 text-center bg-slate-50 rounded-[2.5rem] border-2 border-dashed border-slate-200">
                    <p class="text-slate-400 font-bold uppercase text-xs tracking-widest">No hay eventos programados para esta semana</p>
                </div>
                @endforelse
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
    </div>

    <!-- MODAL DE ÉXITO Y QR -->
    @if(session('new_reservation_id'))
    <div id="qr-modal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/90 backdrop-blur-2xl animate-in fade-in zoom-in duration-300">
        <div class="w-full max-w-md relative">
            <!-- TARJETA PARA DESCARGAR -->
            <div id="access-card" class="bg-white p-10 rounded-[3.5rem] shadow-2xl relative border border-white/20 overflow-hidden mb-6">
                <!-- Decoración -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl"></div>
                
                <div class="flex justify-between items-start mb-8 relative z-10">
                    <div class="w-16 h-16 bg-emerald-500 text-white rounded-2xl flex items-center justify-center text-2xl shadow-xl shadow-emerald-500/30">✓</div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Digital Twin</p>
                        <p class="text-[10px] text-indigo-600 font-black uppercase tracking-widest">Access Pass</p>
                    </div>
                </div>
                
                <div class="mb-8 relative z-10">
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight mb-4">Pase de <span class="text-emerald-500">Acceso</span></h2>
                    <div class="space-y-4">
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                            <p class="text-[9px] text-slate-400 font-bold uppercase mb-1">Titular de Reserva</p>
                            <p class="text-base font-black text-slate-800">{{ session('new_reservation_name') }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                                <p class="text-[9px] text-slate-400 font-bold uppercase mb-1">Fecha</p>
                                <p class="text-sm font-black text-slate-800">{{ date('d M, Y', strtotime(session('new_reservation_date'))) }}</p>
                            </div>
                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                                <p class="text-[9px] text-slate-400 font-bold uppercase mb-1">Horario</p>
                                <p class="text-sm font-black text-slate-800">{{ date('H:i', strtotime(session('new_reservation_date'))) }} hrs</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col items-center justify-center py-6 bg-slate-900 rounded-[2.5rem] shadow-2xl">
                    <div id="qrcode" class="p-3 bg-white rounded-2xl shadow-inner border-4 border-slate-800"></div>
                    <p class="text-[8px] text-slate-500 font-bold mt-4 tracking-[0.4em] uppercase">Validación Biométrica Requerida</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <button onclick="downloadFullCard()" id="dl-btn" class="py-5 bg-white text-slate-900 font-black rounded-3xl hover:bg-slate-50 transition-all flex items-center justify-center gap-2 text-sm shadow-xl border border-slate-200">
                    💾 DESCARGAR
                </button>
                <button onclick="shareViaWhatsApp()" id="share-btn" class="py-5 bg-[#25D366] text-white font-black rounded-3xl hover:bg-[#128C7E] transition-all flex items-center justify-center gap-2 text-sm shadow-xl">
                    📱 ENVIAR WA
                </button>
            </div>

            <button onclick="document.getElementById('qr-modal').remove()" class="w-full py-5 bg-slate-900/50 text-white/50 font-bold rounded-3xl hover:bg-slate-900 hover:text-white transition-all text-sm tracking-widest backdrop-blur-md">
                CERRAR VENTANA
            </button>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        const qr = new QRCode(document.getElementById("qrcode"), {
            text: "{{ route('reservations.checkin', session('new_reservation_id')) }}",
            width: 160,
            height: 160,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        async function getCardCanvas() {
            const card = document.getElementById("access-card");
            
            // Ocultar elementos problemáticos temporalmente
            const blurs = card.querySelectorAll('.blur-3xl, .blur-2xl');
            blurs.forEach(b => b.style.opacity = '0');
            
            try {
                // Pequeña espera para asegurar renderizado de fuentes y QR
                await new Promise(r => setTimeout(r, 150));
                
                const canvas = await html2canvas(card, {
                    scale: 2,
                    backgroundColor: "#ffffff",
                    logging: false,
                    useCORS: true,
                    allowTaint: true,
                    foreignObjectRendering: false, // Más estable en algunos navegadores
                    onclone: (clonedDoc) => {
                        const el = clonedDoc.getElementById("access-card");
                        el.style.boxShadow = 'none';
                        el.style.transform = 'none';
                    }
                });
                return canvas;
            } catch (err) {
                console.error("Fallo en html2canvas:", err);
                throw err;
            } finally {
                blurs.forEach(b => b.style.opacity = '1');
            }
        }

        async function downloadFullCard() {
            const btn = document.getElementById('dl-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⌛...';
            
            try {
                const canvas = await getCardCanvas();
                const link = document.createElement("a");
                link.href = canvas.toDataURL("image/png");
                link.download = "Pase_Acceso_{{ session('new_reservation_name') }}.png";
                link.click();
            } catch (err) {
                console.error("Error al descargar:", err);
                alert("No se pudo generar la imagen completa. Puedes intentar tomar una captura de pantalla.");
            } finally {
                btn.innerHTML = originalText;
            }
        }

        function shareViaWhatsApp() {
            const text = encodeURIComponent("¡Hola! Ya tengo mi pase para el Digital Twin. Aquí puedes ver mi pase digital: {{ route('reservations.show', session('new_reservation_id')) }}");
            const waUrl = `https://wa.me/?text=${text}`;
            window.open(waUrl, "_blank");
        }
    </script>
    @endif

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
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Duración (Horas)</label>
                            <select name="duration" id="res-duration" required class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-bold appearance-none">
                                <!-- Opciones generadas por JS -->
                            </select>
                        </div>
                    </div>
                    <div class="pt-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">Pax (Invitados)</label>
                        <input type="number" name="guests" min="1" max="50" value="1" required class="w-full bg-white border border-slate-200 rounded-2xl text-slate-800 py-3.5 px-5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-bold text-center">
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
            @foreach($zones as $zone)
                "{{ $zone->slug }}": { 
                    label: "{{ $zone->name }}", 
                    start: {{ (int)substr($zone->opening_hour, 0, 2) }}, 
                    end: {{ (int)substr($zone->closing_hour, 0, 2) }}, 
                    limit: {{ $zone->capacity }},
                    maxHours: {{ $zone->max_reservation_hours ?: 1 }},
                    schedules: @json($zone->schedules ?? [])
                },
            @endforeach
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
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const dateStr = `${year}-${month}-${day}`;
                
                const dayName = days[date.getDay()];
                const dayNum = date.getDate();
                const dayOfWeek = date.getDay(); // 0-6
                
                const config = zoneConfig[zone];
                const daySched = config.schedules[dayOfWeek] || null;
                const isRestDay = daySched ? parseInt(daySched.is_closed) : false;
                
                // Ocupación diaria TOTAL (suma de horas)
                let zoneDayTotal = 0;
                if(availabilityData[dateStr] && availabilityData[dateStr][zone]) {
                    Object.values(availabilityData[dateStr][zone]).forEach(count => zoneDayTotal += parseInt(count));
                }
                
                let dayIntensity = isRestDay ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed' : 'bg-emerald-50 text-emerald-600 border-emerald-100';
                let dayLabel = isRestDay ? 'CERRADO' : 'LIBRE';
                
                const currentLimit = daySched ? parseInt(daySched.capacity) : config.limit;
                if(!isRestDay && zoneDayTotal > (currentLimit * 3)) { dayIntensity = 'bg-amber-50 text-amber-600 border-amber-100'; dayLabel = 'ALGO LLENO'; }
                if(!isRestDay && zoneDayTotal > (currentLimit * 8)) { dayIntensity = 'bg-rose-50 text-rose-600 border-rose-100'; dayLabel = 'MUY LLENO'; }

                const div = document.createElement('div');
                div.className = `flex-1 min-w-[55px] p-2 rounded-2xl border text-center transition-all day-card ${dayIntensity} ${!isRestDay ? 'cursor-pointer hover:scale-105' : ''}`;
                div.id = `day-${dateStr}`;
                div.innerHTML = `
                    <p class="text-[9px] font-bold uppercase mb-0.5">${dayName}</p>
                    <p class="text-base font-black tracking-tighter leading-none">${dayNum}</p>
                    <p class="text-[7px] font-black mt-1 uppercase opacity-60">${dayLabel}</p>
                `;
                
                if(!isRestDay) {
                    div.onclick = () => selectDay(dateStr, date);
                }
                grid.appendChild(div);
            }

            document.getElementById('hour-selection-container').classList.add('hidden');
            document.getElementById('res-modal').classList.remove('hidden');

            // Poblar selector de duración basado en maxHours de la zona
            const durationSelect = document.getElementById('res-duration');
            durationSelect.innerHTML = '';
            const max = zoneConfig[zone].maxHours || 1;
            for (let i = 1; i <= max; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = i + (i === 1 ? ' hora' : ' horas');
                durationSelect.appendChild(opt);
            }
        }

        function selectDay(dateStr, dateObj) {
            selectedDate = dateStr;
            selectedHour = null;
            const dayOfWeek = dateObj.getDay();
            
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const todayStr = `${year}-${month}-${day}`;
            
            const isToday = (dateStr === todayStr);
            const currentH = now.getHours();
            const currentM = now.getMinutes();

            // Highlight card
            document.querySelectorAll('.day-card').forEach(c => c.classList.remove('ring-2', 'ring-indigo-600', 'ring-offset-2', 'bg-indigo-600', 'text-white'));
            const card = document.getElementById(`day-${dateStr}`);
            card.classList.add('ring-2', 'ring-indigo-600', 'ring-offset-2');
            
            document.getElementById('selected-date-label').textContent = dateStr;
            
            // Generate Hours
            const hourGrid = document.getElementById('hour-grid');
            hourGrid.innerHTML = '';
            
            const config = zoneConfig[selectedZone];
            const daySched = config.schedules[dayOfWeek] || {};
            
            const hStart = daySched.open ? parseInt(daySched.open.split(':')[0]) : config.start;
            const hEnd = daySched.close ? parseInt(daySched.close.split(':')[0]) : config.end;
            const hCap = daySched.capacity ? parseInt(daySched.capacity) : config.limit;
            const bStart = daySched.break_start ? parseInt(daySched.break_start.split(':')[0]) : null;
            const bEnd = daySched.break_end ? parseInt(daySched.break_end.split(':')[0]) : null;

            for(let h = hStart; h < hEnd; h++) {
                // Filtro para el día de hoy: no mostrar horas pasadas
                if (isToday) {
                    if (h < currentH) continue;
                    if (h === currentH && currentM > 15) continue; // Margen de 15 minutos
                }

                // Verificar si es hora de descanso
                if(bStart !== null && bEnd !== null && h >= bStart && h < bEnd) continue;

                const hourStr = h < 10 ? `0${h}:00` : `${h}:00`;
                const occupancy = (availabilityData[dateStr] && availabilityData[dateStr][selectedZone] && availabilityData[dateStr][selectedZone][h]) ? parseInt(availabilityData[dateStr][selectedZone][h]) : 0;
                
                let hColor = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                let hDot = 'bg-emerald-500';
                let hStatus = 'LIBRE';

                if(occupancy >= hCap) {
                    hColor = 'bg-rose-50 text-rose-400 border-rose-100 cursor-not-allowed opacity-60';
                    hDot = 'bg-rose-400';
                    hStatus = 'LLENO';
                } else if(occupancy >= hCap * 0.5) {
                    hColor = 'bg-amber-50 text-amber-700 border-amber-200';
                    hDot = 'bg-amber-500';
                    hStatus = 'CONCURRIDO';
                }

                const hDiv = document.createElement('div');
                hDiv.className = `group relative p-3 rounded-2xl border text-center transition-all duration-300 hour-pill ${hColor} ${occupancy < hCap ? 'cursor-pointer hover:scale-105 hover:shadow-md' : ''}`;
                hDiv.id = `hour-${h}`;
                
                hDiv.innerHTML = `
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-[10px] font-black tracking-tighter">${hourStr}</span>
                        <div class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full ${hDot} animate-pulse"></span>
                            <span class="text-[7px] font-bold uppercase opacity-70">${hStatus}</span>
                        </div>
                    </div>
                `;
                
                if(occupancy < hCap) {
                    hDiv.onclick = () => selectHour(h, hourStr);
                }
                
                hourGrid.appendChild(hDiv);
            }
            
            document.getElementById('hour-selection-container').classList.remove('hidden');
            updateUI();
        }

        function selectHour(h, hourStr) {
            selectedHour = h;
            const duration = parseInt(document.getElementById('res-duration').value);
            const config = zoneConfig[selectedZone];
            const dayOfWeek = new Date(selectedDate + 'T00:00:00').getDay();
            const daySched = config.schedules[dayOfWeek] || {};
            const hCap = daySched.capacity ? parseInt(daySched.capacity) : config.limit;

            // Calcular disponibilidad real en el bloque completo
            let minFree = hCap;
            for(let i=0; i < duration; i++) {
                const targetH = h + i;
                const occupancy = (availabilityData[selectedDate] && availabilityData[selectedDate][selectedZone] && availabilityData[selectedDate][selectedZone][targetH]) ? parseInt(availabilityData[selectedDate][selectedZone][targetH]) : 0;
                const free = hCap - occupancy;
                if(free < minFree) minFree = free;
            }
            
            document.querySelectorAll('.hour-pill').forEach(p => p.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-indigo-500/50'));
            const pill = document.getElementById(`hour-${h}`);
            pill.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-lg', 'shadow-indigo-500/50');
            
            const dtFinal = `${selectedDate}T${h < 10 ? '0'+h : h}:00`;
            document.getElementById('res-datetime').value = dtFinal;
            document.getElementById('final-datetime-display').textContent = `${selectedDate} a las ${hourStr} (${minFree} libres en bloque)`;
            document.getElementById('final-datetime-display').classList.remove('text-slate-400', 'bg-slate-100');
            document.getElementById('final-datetime-display').classList.add('text-indigo-600', 'bg-indigo-50');
            
            const guestsInput = document.querySelector('input[name="guests"]');
            guestsInput.max = minFree;
            if(parseInt(guestsInput.value) > minFree) {
                guestsInput.value = minFree;
            }

            updateUI();
        }

        document.getElementById('res-duration').onchange = () => {
            if(selectedHour !== null) {
                const hourStr = selectedHour < 10 ? `0${selectedHour}:00` : `${selectedHour}:00`;
                selectHour(selectedHour, hourStr);
            }
        };

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
