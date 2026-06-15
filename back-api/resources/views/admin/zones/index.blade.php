@extends('admin.dashboard_layout')

@section('content')
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.05);
        }
    </style>

    <div class="p-8 min-h-screen" x-data="{ showToast: {{ session('success') ? 'true' : 'false' }}, toastMsg: '{{ session('success') }}' }" x-init="if(showToast) setTimeout(() => showToast = false, 3000)">
        
        <!-- TOAST NOTIFICATION -->
        <div x-show="showToast" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="fixed bottom-10 right-10 z-[200] bg-slate-900 text-white px-6 py-4 rounded-[2rem] shadow-2xl flex items-center gap-3 border border-white/10">
            <div class="w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center">
                <i data-lucide="check" class="w-4 h-4 text-white"></i>
            </div>
            <span class="text-sm font-black tracking-tight" x-text="toastMsg"></span>
        </div>

        <header class="flex items-center justify-between mb-10">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Gestión de <span class="text-indigo-600">Áreas</span></h2>
                <p class="text-slate-400 text-sm mt-1">Configura las zonas interactivas del complejo y sus límites.</p>
            </div>
            <button onclick="document.getElementById('add-zone-modal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                <i data-lucide="plus" class="w-5 h-5"></i> Nueva Área
            </button>
        </header>

        @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-600 rounded-2xl font-bold text-sm flex items-center gap-3">
            <i data-lucide="check-circle"></i> {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
            @foreach($zones as $zone)
            <div class="glass-card rounded-[2.5rem] overflow-hidden group">
                <div class="h-40 bg-slate-200 relative">
                    <img src="{{ (str_contains($zone->image, 'http') || str_contains($zone->image, '/')) ? $zone->image : asset($zone->image) }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                    <div class="absolute bottom-4 left-6 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white">
                            <i data-lucide="{{ $zone->icon ?: 'map-pin' }}"></i>
                        </div>
                        <h3 class="text-white font-black text-xl">{{ $zone->name }}</h3>
                    </div>
                    <div class="absolute top-4 right-4">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $zone->status == 'active' ? 'bg-emerald-500 text-white' : 'bg-slate-500 text-white' }}">
                            {{ $zone->status }}
                        </span>
                    </div>
                </div>

                <div class="p-8">
                    <form action="{{ route('admin.zones.update', $zone->id) }}" method="POST" class="space-y-6">
                        @csrf @method('PATCH')
                        
                        @if ($errors->any())
                        <div class="p-4 bg-red-50 text-red-600 rounded-2xl border border-red-100">
                            <p class="text-[10px] font-black uppercase tracking-widest mb-2">Errores de validación:</p>
                            <ul class="text-[10px] font-bold list-disc ml-4 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Nombre de la Zona</label>
                                <input type="text" name="name" value="{{ $zone->name }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Estado Operativo</label>
                                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                    <option value="active" {{ $zone->status == 'active' ? 'selected' : '' }}>🟢 Activo (Visible)</option>
                                    <option value="inactive" {{ $zone->status == 'inactive' ? 'selected' : '' }}>🔴 Inactivo (Oculto)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Max Horas / Reserva</label>
                                <input type="number" name="max_reservation_hours" value="{{ $zone->max_reservation_hours ?: 2 }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                            
                            <!-- Campos ocultos para evitar errores de validación sin duplicar UI -->
                            <input type="hidden" name="capacity" value="{{ $zone->capacity }}">
                            <input type="hidden" name="opening_hour" value="{{ $zone->opening_hour }}">
                            <input type="hidden" name="closing_hour" value="{{ $zone->closing_hour }}">
                        </div>

                        <!-- INTERFAZ DE CONFIGURACIÓN POR DÍA (UX MEJORADA) -->
                        <div class="mt-10 border-t border-slate-100 pt-8" x-data="{ activeDay: 1 }">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h4 class="text-slate-900 font-black text-sm tracking-tight">MATRIZ HORARIA SEMANAL</h4>
                                    <p class="text-slate-400 text-[9px] font-bold uppercase tracking-widest">Configuración específica por día</p>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="copySchedule(1, 'all', {{ $zone->id }})" class="text-[9px] font-black uppercase bg-slate-100 hover:bg-indigo-600 hover:text-white px-3 py-2 rounded-xl transition-all border border-slate-200 shadow-sm">
                                        Replicar Lunes
                                    </button>
                                </div>
                            </div>

                            <!-- Selector de Días (Tabs) -->
                            <div class="flex p-1 bg-slate-100 rounded-2xl mb-6 overflow-x-auto gap-1">
                                @php
                                    $dayLabels = [1 => 'L', 2 => 'M', 3 => 'M', 4 => 'J', 5 => 'V', 6 => 'S', 0 => 'D'];
                                    $dayFullNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo'];
                                @endphp
                                @foreach($dayLabels as $val => $label)
                                <button type="button" 
                                    @click="activeDay = {{ $val }}" 
                                    :class="activeDay === {{ $val }} ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                                    class="flex-1 py-3 px-2 rounded-xl text-xs font-black transition-all">
                                    {{ $label }}
                                </button>
                                @endforeach
                            </div>

                            <!-- Contenido de los Días -->
                            @php $sched = $zone->schedules ?? []; @endphp
                            @foreach($dayFullNames as $val => $fullName)
                            @php 
                                $d = $sched[$val] ?? []; 
                                $isClosed = ($d['is_closed'] ?? false);
                            @endphp
                            <div x-show="activeDay === {{ $val }}" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                                <div class="flex items-center justify-between p-5 {{ $isClosed ? 'bg-red-50/50 border-red-100' : 'bg-emerald-50/30 border-emerald-100' }} rounded-[2.2rem] border transition-all duration-300">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center {{ $isClosed ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' }} transition-all">
                                            <i data-lucide="{{ $isClosed ? 'lock' : 'unlock' }}" class="w-6 h-6"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Estado para {{ $fullName }}</p>
                                            <p class="text-sm font-black {{ $isClosed ? 'text-red-600' : 'text-emerald-600' }}">{{ $isClosed ? 'ÁREA CERRADA' : 'ÁREA ABIERTA' }}</p>
                                        </div>
                                    </div>
                                    <select name="schedules[{{ $val }}][is_closed]" class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-xs font-black outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm">
                                        <option value="0" {{ !$isClosed ? 'selected' : '' }}>Abierto</option>
                                        <option value="1" {{ $isClosed ? 'selected' : '' }}>Cerrado</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 gap-4">
                                    <div class="bg-white p-6 rounded-[2.2rem] border border-slate-100 shadow-sm flex items-center justify-between">
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 ml-1">Capacidad Máxima</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="schedules[{{ $val }}][capacity]" value="{{ $d['capacity'] ?? $zone->capacity }}" class="w-24 text-2xl font-black text-slate-800 bg-transparent border-none p-0 focus:ring-0 leading-none">
                                                <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">Personas / hora</span>
                                            </div>
                                        </div>
                                        <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300">
                                            <i data-lucide="users" class="w-6 h-6"></i>
                                        </div>
                                    </div>

                                    <div class="bg-white p-6 rounded-[2.2rem] border border-slate-100 shadow-sm">
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 ml-1">Configuración de Horario</label>
                                        <div class="grid grid-cols-2 gap-6">
                                            <div>
                                                <p class="text-[8px] font-black text-slate-400 uppercase mb-2 ml-1">Apertura</p>
                                                <input type="time" name="schedules[{{ $val }}][open]" value="{{ $d['open'] ?? substr($zone->opening_hour, 0, 5) }}" class="w-full text-sm font-black text-slate-700 bg-slate-50 rounded-xl px-4 py-3 border border-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                                            </div>
                                            <div>
                                                <p class="text-[8px] font-black text-slate-400 uppercase mb-2 ml-1">Cierre</p>
                                                <input type="time" name="schedules[{{ $val }}][close]" value="{{ $d['close'] ?? substr($zone->closing_hour, 0, 5) }}" class="w-full text-sm font-black text-slate-700 bg-slate-50 rounded-xl px-4 py-3 border border-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-amber-50/40 p-6 rounded-[2.5rem] border border-amber-100">
                                    <div class="flex items-center gap-3 mb-4">
                                        <i data-lucide="wrench" class="w-4 h-4 text-amber-500"></i>
                                        <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest">Ventana de Mantenimiento / Pausa</p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="flex-1">
                                            <label class="block text-[8px] font-bold text-amber-700/60 mb-1">INICIO</label>
                                            <input type="time" name="schedules[{{ $val }}][break_start]" value="{{ $d['break_start'] ?? '' }}" class="w-full bg-white border border-amber-200 rounded-xl py-2 px-3 text-xs font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-[8px] font-bold text-amber-700/60 mb-1">FIN</label>
                                            <input type="time" name="schedules[{{ $val }}][break_end]" value="{{ $d['break_end'] ?? '' }}" class="w-full bg-white border border-amber-200 rounded-xl py-2 px-3 text-xs font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="pt-10 flex gap-3">
                            <button type="submit" class="flex-1 bg-indigo-600 text-white py-6 rounded-[2.5rem] font-black text-base uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-2xl shadow-indigo-600/30 active:scale-95 group">
                                <span class="group-hover:mr-2 transition-all">GUARDAR CAMBIOS</span> 
                                <i data-lucide="save" class="inline-block w-5 h-5"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- MODAL NUEVA ÁREA -->
    <div id="add-zone-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
        <div class="bg-white w-full max-w-md p-10 rounded-[3rem] shadow-2xl relative">
            <button onclick="document.getElementById('add-zone-modal').classList.add('hidden')" class="absolute top-6 right-6 text-slate-400">✕</button>
            <h3 class="text-2xl font-black text-slate-900 mb-6">Nueva Área</h3>
            <form action="{{ route('admin.zones.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Slug (Identificador único)</label>
                    <input type="text" name="slug" placeholder="ej: spa-vip" required class="w-full border border-slate-200 rounded-xl py-3 px-4 font-bold outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Nombre</label>
                    <input type="text" name="name" placeholder="Nombre de la zona" required class="w-full border border-slate-200 rounded-xl py-3 px-4 font-bold outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Capacidad</label>
                        <input type="number" name="capacity" value="20" class="w-full border border-slate-200 rounded-xl py-3 px-4 font-bold outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Icono Lucide</label>
                        <input type="text" name="icon" value="map-pin" class="w-full border border-slate-200 rounded-xl py-3 px-4 font-bold outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Apertura</label>
                        <input type="time" name="opening_hour" value="08:00" class="w-full border border-slate-200 rounded-xl py-3 px-4 font-bold outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cierre</label>
                        <input type="time" name="closing_hour" value="20:00" class="w-full border border-slate-200 rounded-xl py-3 px-4 font-bold outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black uppercase tracking-widest mt-4 shadow-lg shadow-indigo-600/20 active:scale-95 transition-all">
                    CREAR ÁREA ✓
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function copySchedule(fromDay, targetType, zoneId) {
            // Buscamos los valores del día origen
            const isClosed = document.querySelector(`select[name="schedules[${fromDay}][is_closed]"]`).value;
            const capacity = document.querySelector(`input[name="schedules[${fromDay}][capacity]"]`).value;
            const open = document.querySelector(`input[name="schedules[${fromDay}][open]"]`).value;
            const close = document.querySelector(`input[name="schedules[${fromDay}][close]"]`).value;
            const bStart = document.querySelector(`input[name="schedules[${fromDay}][break_start]"]`).value;
            const bEnd = document.querySelector(`input[name="schedules[${fromDay}][break_end]"]`).value;

            // Aplicamos a los demás días (0-6)
            [0,1,2,3,4,5,6].forEach(day => {
                if (day === fromDay) return;
                
                document.querySelector(`select[name="schedules[${day}][is_closed]"]`).value = isClosed;
                document.querySelector(`input[name="schedules[${day}][capacity]"]`).value = capacity;
                document.querySelector(`input[name="schedules[${day}][open]"]`).value = open;
                document.querySelector(`input[name="schedules[${day}][close]"]`).value = close;
                document.querySelector(`input[name="schedules[${day}][break_start]"]`).value = bStart;
                document.querySelector(`input[name="schedules[${day}][break_end]"]`).value = bEnd;
            });

            // Feedback visual
            const btn = event.target;
            const oldText = btn.innerHTML;
            btn.innerHTML = '¡Copiado! ✓';
            btn.classList.add('bg-emerald-500', 'text-white');
            setTimeout(() => {
                btn.innerHTML = oldText;
                btn.classList.remove('bg-emerald-500', 'text-white');
            }, 1500);
        }
    </script>
@endsection
