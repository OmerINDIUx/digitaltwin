@extends('admin.dashboard_layout')

@section('content')
<style>
    .event-popup {
        position: fixed;
        inset: 0;
        z-index: 250;
        display: grid;
        place-items: center;
        padding: 2rem;
        background: rgba(15, 23, 42, 0.64);
        backdrop-filter: blur(14px);
    }

    .event-popup.hidden {
        display: none;
    }

    .event-popup__panel {
        width: min(880px, calc(100vw - 3rem));
        max-height: min(760px, calc(100vh - 3rem));
        overflow: hidden;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 28px;
        box-shadow: 0 30px 90px rgba(15, 23, 42, 0.28);
    }

    .event-popup__body {
        overflow-y: auto;
        padding: 1.5rem;
    }

    @media (max-width: 760px) {
        .event-popup {
            padding: 1rem;
            place-items: end center;
        }

        .event-popup__panel {
            width: 100%;
            max-height: calc(100vh - 2rem);
            border-radius: 24px;
        }
    }
</style>

<div class="p-8">
    <div class="flex items-center justify-between mb-10">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Eventos y Clases</h2>
            <p class="text-slate-500 font-bold uppercase text-[10px] tracking-[0.2em] mt-1">Programación de actividades grupales</p>
        </div>
        <button onclick="document.getElementById('event-modal').classList.remove('hidden')" 
            class="bg-rose-600 hover:bg-rose-700 text-white font-black py-4 px-8 rounded-2xl transition-all shadow-xl shadow-rose-600/20 flex items-center gap-3 group">
            <i data-lucide="plus-circle" class="w-5 h-5 group-hover:rotate-90 transition-transform"></i>
            Nuevo Evento / Clase
        </button>
    </div>

    @if(session('success'))
    <div class="mb-8 bg-emerald-50 border border-emerald-100 text-emerald-600 p-4 rounded-2xl font-bold flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($events as $event)
        <div class="glass-card rounded-[2.5rem] overflow-hidden border border-slate-100 hover:shadow-2xl transition-all group">
            <div class="h-48 relative overflow-hidden">
                <img src="{{ $event->image ?: 'https://images.unsplash.com\/photo-1571902251103-d71b46244bc0?auto=format&fit=crop&q=80&w=600' }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                <div class="absolute top-4 left-4">
                    <span class="px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white text-[10px] font-black uppercase tracking-widest border border-white/20">
                        {{ $event->type }}
                    </span>
                </div>
            </div>
            <div class="p-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600">
                        <i data-lucide="{{ $event->zone == 'gym' ? 'activity' : ($event->zone == 'pool' ? 'waves' : 'target') }}" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $event->zone }}</p>
                        <h3 class="font-black text-xl text-slate-900">{{ $event->name }}</h3>
                    </div>
                </div>
                
                <div class="space-y-3 mb-8">
                    <div class="flex items-center gap-3 text-slate-500 text-sm font-bold">
                        <i data-lucide="calendar" class="w-4 h-4 text-indigo-500"></i>
                        {{ $event->event_date->format('d M, Y - H:i') }} hrs
                    </div>
                    <div class="flex items-center gap-3 text-slate-500 text-sm font-bold">
                        <i data-lucide="users" class="w-4 h-4 text-emerald-500"></i>
                        {{ $event->registrations_count }} / {{ $event->capacity }} cupos ocupados
                    </div>
                    <div class="flex items-center gap-3 text-slate-500 text-sm font-bold">
                        <i data-lucide="badge-dollar-sign" class="w-4 h-4 text-amber-500"></i>
                        ${{ number_format((float) $event->price, 2) }}
                    </div>
                </div>

                <button type="button" onclick="document.getElementById('event-details-{{ $event->id }}').classList.remove('hidden')" class="w-full mb-3 py-3 bg-slate-900 text-white font-black rounded-xl text-center text-xs uppercase tracking-widest hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                    Ver inscritos y detalles
                </button>

                <div class="flex gap-3">
                    <button onclick='openEditModal({!! json_encode($event) !!})' class="flex-1 py-3 bg-indigo-50 text-indigo-600 font-black rounded-xl text-center text-xs uppercase tracking-widest hover:bg-indigo-100 transition-all">
                        Editar Clase
                    </button>
                    <form action="{{ route('admin.events.toggle', $event->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="p-3 {{ $event->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }} font-black rounded-xl hover:scale-105 transition-all" title="{{ $event->is_active ? 'Desactivar' : 'Activar' }}">
                            <i data-lucide="{{ $event->is_active ? 'eye' : 'eye-off' }}" class="w-5 h-5"></i>
                        </button>
                    </form>
                    <form action="{{ route('admin.events.destroy', $event->id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-3 bg-rose-50 text-rose-600 font-black rounded-xl hover:bg-rose-100 transition-all">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div id="event-details-{{ $event->id }}" class="event-popup hidden" onclick="if (event.target === this) this.classList.add('hidden')">
            <div class="event-popup__panel animate-in fade-in zoom-in duration-300">
                <div class="px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-widest">{{ $event->type }}</span>
                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-widest">{{ $event->zone }}</span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 leading-tight">{{ $event->name }}</h3>
                        <p class="text-sm font-bold text-slate-500 mt-2 flex items-center gap-2">
                            <i data-lucide="calendar-clock" class="w-4 h-4 text-indigo-500"></i>
                            {{ $event->event_date->format('d/m/Y H:i') }} hrs · {{ $event->duration }} h
                        </p>
                    </div>
                    <button type="button" onclick="document.getElementById('event-details-{{ $event->id }}').classList.add('hidden')" class="w-10 h-10 flex-shrink-0 rounded-full border border-slate-200 text-slate-400 hover:text-slate-700 hover:bg-slate-50 transition-all flex items-center justify-center">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <div class="event-popup__body">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cupos ocupados</p>
                            <p class="text-xl font-black text-slate-900">{{ $event->registrations_count }} / {{ $event->capacity }}</p>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Disponibles</p>
                            <p class="text-xl font-black text-slate-900">{{ max(0, $event->capacity - $event->registrations_count) }}</p>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Precio</p>
                            <p class="text-xl font-black text-slate-900">${{ number_format((float) $event->price, 2) }}</p>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Estatus</p>
                            <p class="text-sm font-black {{ $event->is_active ? 'text-emerald-600' : 'text-slate-400' }} uppercase tracking-widest mt-1">{{ $event->is_active ? 'Activo' : 'Inactivo' }}</p>
                        </div>
                    </div>

                    <div class="mb-6 bg-white border border-slate-200 rounded-2xl p-5">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Información general</p>
                        <p class="text-sm font-bold text-slate-600 leading-6">{{ $event->description ?: 'Sin descripción registrada.' }}</p>
                    </div>

                    @if(!empty($event->attachments))
                        <div class="mb-6 bg-white border border-slate-200 rounded-2xl p-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Archivos adjuntos</p>
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach($event->attachments as $attachment)
                                    <a href="{{ $attachment['url'] ?? '#' }}" target="_blank" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-slate-700 hover:border-indigo-200 hover:bg-indigo-50 transition-all">
                                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-indigo-600">
                                            <i data-lucide="{{ str_contains($attachment['mime'] ?? '', 'pdf') ? 'file-text' : 'paperclip' }}" class="w-5 h-5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black">{{ $attachment['name'] ?? 'Archivo adjunto' }}</p>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ isset($attachment['size']) ? number_format($attachment['size'] / 1024, 0) . ' KB' : 'Archivo' }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white">
                        <div class="px-5 py-4 bg-slate-50 flex items-center justify-between gap-4">
                            <h4 class="font-black text-slate-900">Personas inscritas</h4>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $event->registrations->count() }} registros</span>
                        </div>

                        @if($event->registrations->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            <th class="px-5 py-3">Nombre</th>
                                            <th class="px-5 py-3">Correo</th>
                                            <th class="px-5 py-3">WhatsApp</th>
                                            <th class="px-5 py-3">Fecha de inscripción</th>
                                            <th class="px-5 py-3">Estatus</th>
                                            <th class="px-5 py-3">Asistencia</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($event->registrations as $registration)
                                            <tr class="text-sm font-bold text-slate-600">
                                                <td class="px-5 py-4 text-slate-900">{{ $registration->name }}</td>
                                                <td class="px-5 py-4">{{ $registration->email ?: 'Sin email' }}</td>
                                                <td class="px-5 py-4">{{ $registration->phone ?: 'Sin WhatsApp' }}</td>
                                                <td class="px-5 py-4">{{ $registration->created_at->format('d/m/Y H:i') }}</td>
                                                <td class="px-5 py-4">
                                                    <form action="{{ route('admin.events.registrations.status', $registration) }}" method="POST" class="flex items-center gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="status" onchange="this.form.submit()" class="min-w-36 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black uppercase tracking-widest text-slate-700 outline-none transition-all focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">
                                                            <option value="aceptado" @selected($registration->status === 'aceptado')>Aceptado</option>
                                                            <option value="rechazado" @selected($registration->status === 'rechazado')>Rechazado</option>
                                                            <option value="por_pagar" @selected(($registration->status ?? 'por_pagar') === 'por_pagar')>Por pagar</option>
                                                            <option value="pagado" @selected($registration->status === 'pagado')>Pagado</option>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td class="px-5 py-4">
                                                    @if($registration->checked_in_at)
                                                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest">
                                                            <i data-lucide="check-circle" class="w-3 h-3"></i>
                                                            {{ $registration->checked_in_at->format('d/m H:i') }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                                            Pendiente
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-10 text-center">
                                <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="user-x" class="w-7 h-7 text-slate-300"></i>
                                </div>
                                <p class="font-black text-slate-400">Todavía no hay personas inscritas.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 text-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="calendar-off" class="w-10 h-10 text-slate-300"></i>
            </div>
            <h3 class="text-xl font-black text-slate-400">No hay eventos programados</h3>
            <p class="text-slate-400 text-sm mt-2">Crea el primer evento para comenzar.</p>
        </div>
        @endforelse
    </div>
</div>

<!-- Modal Evento -->
<div id="event-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-6">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md"></div>
    <div class="relative bg-white w-full max-w-2xl max-h-[90vh] rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300 flex flex-col">
        <div class="p-8 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-2xl font-black text-slate-900">Nuevo Evento / Clase</h3>
            <button onclick="document.getElementById('event-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <form action="{{ route('admin.events.store') }}" method="POST" class="p-8 overflow-y-auto" enctype="multipart/form-data">
            @csrf
            <div class="space-y-6">
                <!-- Zona de Carga Prioritaria -->
                <div class="p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-[2rem] text-center relative group">
                    <input type="file" name="image" id="create-image-input" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewAndCompress(this, 'create')">
                    <div id="create-preview-container" class="hidden absolute inset-0 rounded-[2rem] overflow-hidden">
                        <img id="create-preview-img" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-slate-900/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <p class="text-white font-black text-xs uppercase">Cambiar Foto</p>
                        </div>
                    </div>
                    <div id="create-upload-placeholder">
                        <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-3 text-indigo-600">
                            <i data-lucide="image-plus" class="w-6 h-6"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Subir Portada (Compresión Auto)</p>
                    </div>
                    <!-- Barra de Progreso -->
                    <div id="create-compress-bar" class="hidden absolute bottom-0 left-0 right-0 h-1 bg-indigo-100 overflow-hidden rounded-b-[2rem]">
                        <div id="create-compress-progress" class="h-full bg-indigo-600 transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nombre del Evento</label>
                    <input type="text" name="name" required placeholder="Ej: Yoga al amanecer" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Descripción</label>
                    <textarea name="description" rows="3" placeholder="Detalles de la clase, requisitos, etc..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all"></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Archivos adjuntos</label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-500 hover:border-indigo-200 hover:bg-indigo-50 transition-all">
                        <i data-lucide="paperclip" class="w-5 h-5 text-indigo-500"></i>
                        <span>Subir PDF, imagen o documento</span>
                        <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp" class="hidden">
                    </label>
                    <p class="mt-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Máximo 10 MB por archivo</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Área</label>
                        <select name="zone" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all appearance-none">
                            @foreach($zones as $z)
                                <option value="{{ $z->slug }}">{{ $z->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tipo</label>
                        <select name="type" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all appearance-none">
                            <option value="class">Clase</option>
                            <option value="maintenance">Mantenimiento</option>
                            <option value="private_event">Evento Privado</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Precio ($)</label>
                        <input type="number" name="price" step="0.01" value="0.00" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                    </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Fecha y Hora</label>
                        <input type="datetime-local" name="event_date" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Dur (Hrs)</label>
                            <input type="number" name="duration" required value="1" min="1" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Cap (Pax)</label>
                            <input type="number" name="capacity" required value="20" min="1" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked class="w-5 h-5 accent-indigo-600">
                    <label class="text-xs font-bold text-slate-700">Evento Activo (Visible para residentes)</label>
                </div>

                @if($errors->any())
                <div class="bg-rose-50 text-rose-600 p-4 rounded-2xl text-xs font-bold">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="pt-6">
                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 shadow-xl shadow-indigo-600/20 transition-all uppercase text-xs tracking-[0.2em]">
                        Publicar Evento
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Evento -->
<div id="edit-event-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-6">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md"></div>
    <div class="relative bg-white w-full max-w-2xl max-h-[90vh] rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300 flex flex-col">
        <div class="p-8 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-2xl font-black text-slate-900">Editar Evento</h3>
            <button onclick="document.getElementById('edit-event-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <form id="edit-event-form" method="POST" class="p-8 overflow-y-auto" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <div class="space-y-6">
                <!-- Zona de Carga Prioritaria (Edit) -->
                <div class="p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-[2rem] text-center relative group">
                    <input type="file" name="image" id="edit-image-input" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewAndCompress(this, 'edit')">
                    <div id="edit-preview-container" class="absolute inset-0 rounded-[2rem] overflow-hidden">
                        <img id="edit-preview-img" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-slate-900/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <p class="text-white font-black text-xs uppercase">Cambiar Foto</p>
                        </div>
                    </div>
                    <!-- Barra de Progreso -->
                    <div id="edit-compress-bar" class="hidden absolute bottom-0 left-0 right-0 h-1 bg-indigo-100 overflow-hidden rounded-b-[2rem]">
                        <div id="edit-compress-progress" class="h-full bg-indigo-600 transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nombre del Evento</label>
                    <input type="text" name="name" id="edit-name" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Descripción</label>
                    <textarea name="description" id="edit-description" rows="3" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all"></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Archivos actuales</label>
                    <div id="edit-existing-attachments" class="space-y-2 rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm font-bold text-slate-500">
                        Sin archivos adjuntos.
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Agregar archivos</label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-500 hover:border-indigo-200 hover:bg-indigo-50 transition-all">
                        <i data-lucide="paperclip" class="w-5 h-5 text-indigo-500"></i>
                        <span>Subir PDF, imagen o documento</span>
                        <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp" class="hidden">
                    </label>
                    <p class="mt-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Máximo 10 MB por archivo</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Área</label>
                        <select name="zone" id="edit-zone" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all appearance-none">
                            @foreach($zones as $z)
                                <option value="{{ $z->slug }}">{{ $z->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tipo</label>
                        <select name="type" id="edit-type" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all appearance-none">
                            <option value="class">Clase</option>
                            <option value="maintenance">Mantenimiento</option>
                            <option value="private_event">Evento Privado</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Precio ($)</label>
                        <input type="number" name="price" id="edit-price" step="0.01" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                    </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Fecha y Hora</label>
                        <input type="datetime-local" name="event_date" id="edit-date" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Dur (Hrs)</label>
                            <input type="number" name="duration" id="edit-duration" required min="1" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Cap (Pax)</label>
                            <input type="number" name="capacity" id="edit-capacity" required min="1" class="w-full bg-slate-50 border border-slate-100 rounded-2xl py-4 px-5 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all">
                        </div>
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 shadow-xl shadow-indigo-600/20 transition-all uppercase text-xs tracking-[0.2em]">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/compressorjs/1.2.1/compressor.min.js"></script>
<script>
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[char]));
    }

    function formatFileSize(bytes) {
        if (!bytes) return 'Archivo';
        return `${Math.round(bytes / 1024)} KB`;
    }

    function renderExistingAttachments(attachments = []) {
        const container = document.getElementById('edit-existing-attachments');

        if (!attachments.length) {
            container.innerHTML = 'Sin archivos adjuntos.';
            return;
        }

        container.innerHTML = attachments.map((attachment) => `
            <label class="flex items-center justify-between gap-4 rounded-xl bg-white px-4 py-3 border border-slate-100">
                <span class="min-w-0">
                    <a href="${escapeHtml(attachment.url)}" target="_blank" class="block truncate text-slate-800 hover:text-indigo-600">${escapeHtml(attachment.name || 'Archivo adjunto')}</a>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">${formatFileSize(attachment.size)}</span>
                </span>
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-rose-500">
                    <input type="checkbox" name="remove_attachments[]" value="${escapeHtml(attachment.path)}" class="w-4 h-4 accent-rose-600">
                    Quitar
                </span>
            </label>
        `).join('');
    }

    function openEditModal(event) {
        document.getElementById('edit-event-form').action = `/admin/events/${event.id}`;
        document.getElementById('edit-name').value = event.name;
        document.getElementById('edit-description').value = event.description || '';
        document.getElementById('edit-zone').value = event.zone;
        document.getElementById('edit-type').value = event.type;
        document.getElementById('edit-price').value = event.price;
        document.getElementById('edit-capacity').value = event.capacity;
        document.getElementById('edit-duration').value = event.duration;
        
        if(event.event_date) {
            const date = new Date(event.event_date);
            const formatted = date.toISOString().slice(0, 16);
            document.getElementById('edit-date').value = formatted;
        }

        // Preview de imagen actual
        if(event.image) {
            document.getElementById('edit-preview-img').src = event.image;
            document.getElementById('edit-preview-container').classList.remove('hidden');
        }

        renderExistingAttachments(event.attachments || []);

        document.getElementById('edit-event-modal').classList.remove('hidden');
    }

    function previewAndCompress(input, mode) {
        const file = input.files[0];
        if (!file) return;

        const previewContainer = document.getElementById(`${mode}-preview-container`);
        const previewImg = document.getElementById(`${mode}-preview-img`);
        const progressBar = document.getElementById(`${mode}-compress-bar`);
        const progressFill = document.getElementById(`${mode}-compress-progress`);
        const submitBtn = input.closest('form').querySelector('button[type="submit"]');

        // Mostrar preview preliminar
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            previewContainer.classList.remove('hidden');
            if(mode === 'create') document.getElementById('create-upload-placeholder').classList.add('hidden');
        };
        reader.readAsDataURL(file);

        // Iniciar compresión
        progressBar.classList.remove('hidden');
        progressFill.style.width = '20%';
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        submitBtn.innerText = 'Comprimiendo imagen...';

        new Compressor(file, {
            quality: 0.6,
            maxWidth: 1200,
            success(result) {
                progressFill.style.width = '100%';
                setTimeout(() => {
                    progressBar.classList.add('hidden');
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    submitBtn.innerText = mode === 'create' ? 'Publicar Evento' : 'Guardar Cambios';
                }, 500);

                // Reemplazar archivo en el input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(new File([result], file.name, { type: result.type }));
                input.files = dataTransfer.files;
            },
            error(err) {
                console.error(err.message);
                progressBar.classList.add('hidden');
                submitBtn.disabled = false;
            },
        });
    }
</script>

@if($errors->any())
<script>
    document.getElementById('event-modal').classList.remove('hidden');
</script>
@endif

@if(session('open_event_details'))
<script>
    document.getElementById('event-details-{{ session('open_event_details') }}')?.classList.remove('hidden');
</script>
@endif
@endsection
