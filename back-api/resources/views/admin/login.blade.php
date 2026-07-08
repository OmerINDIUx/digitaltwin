<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $today = \Carbon\Carbon::now()->locale('es_MX');
    @endphp
    <title>Panel de Control | UTOPÍA Japón</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        * { font-family: 'Outfit', sans-serif; }
        body { background: #f4f4f6; }
        .login-stage {
            background-color: #151327;
            background-image:
                radial-gradient(circle at 18% 18%, rgba(162, 125, 255, 0.22), transparent 26%),
                radial-gradient(circle at 82% 22%, rgba(94, 234, 212, 0.12), transparent 20%),
                linear-gradient(135deg, #19162d 0%, #1d1a38 42%, #132e35 100%);
        }
        .city-ribbon {
            background: linear-gradient(180deg, #fbfbfc 0%, #ffffff 100%);
            border-top: 1px solid rgba(203, 213, 225, 0.6);
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }
        .city-chip {
            background: #fff;
            border: 1px solid rgba(196, 181, 216, 0.55);
            box-shadow: 0 10px 20px -18px rgba(15, 23, 42, 0.3), 0 2px 6px -4px rgba(15, 23, 42, 0.14);
        }
        .city-copy { color: #85739e; }
        .city-strong { color: #5f4d7f; }
        .utopias-mark { width: 220px; max-width: 38vw; height: auto; }
        .cdmx-mark { width: 320px; max-width: 44vw; height: auto; }
        .glass-card {
            background: rgba(255,255,255,0.09);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 30px 70px rgba(5, 10, 20, 0.28);
        }
        .input-field {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: white;
            transition: all 0.2s;
        }
        .input-field:focus {
            background: rgba(255,255,255,0.12);
            border-color: rgba(99,102,241,0.6);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
            outline: none;
        }
        .input-field::placeholder { color: rgba(255,255,255,0.25); }
        .btn-login {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            box-shadow: 0 8px 32px rgba(99,102,241,0.35);
            transition: all 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 40px rgba(99,102,241,0.5);
        }
        .btn-login:active { transform: translateY(0); }
        .dot-grid {
            background-image: radial-gradient(circle, rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .axolotl-card {
            background:
                linear-gradient(180deg, rgba(15, 23, 42, 0) 0%, rgba(15, 23, 42, 0.74) 100%),
                linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
            border: 1px solid rgba(255,255,255,0.12);
        }
        .axolotl-stack img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            animation: axolotlReel 15s infinite;
        }
        .axolotl-stack img:nth-child(2) { animation-delay: 5s; }
        .axolotl-stack img:nth-child(3) { animation-delay: 10s; }
        @keyframes axolotlReel {
            0%, 10% { opacity: 0; transform: scale(1.03); }
            14%, 30% { opacity: 1; transform: scale(1); }
            38%, 100% { opacity: 0; transform: scale(1.02); }
        }
        @media (max-width: 1024px) {
            .utopias-mark { width: 180px; max-width: 42vw; }
            .cdmx-mark { width: 250px; max-width: 48vw; }
        }
        @media (max-width: 768px) {
            .utopias-mark { width: 150px; max-width: 44vw; }
            .cdmx-mark { width: 200px; max-width: 46vw; }
        }
    </style>
</head>
<body class="min-h-screen">
    <section class="city-ribbon px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1180px]">
            <div class="flex items-center justify-between gap-4 sm:gap-6">
                <img src="{{ asset('logo-topialogo.png') }}" alt="Logo UTOPÍAS" class="utopias-mark object-contain">
                <img src="{{ asset('logo-cdmx.svg') }}" alt="Logo Ciudad de México" class="cdmx-mark object-contain">
            </div>
        </div>

        <div class="mx-auto mt-5 grid max-w-[1180px] gap-3 pb-3 md:grid-cols-2 xl:grid-cols-[180px_minmax(0,1fr)_minmax(0,1fr)_260px]">
            <article class="flex min-h-[72px] flex-col justify-center rounded-[0.85rem] bg-white/70 px-1 md:pr-4">
                <p class="city-copy text-[1.1rem] font-black leading-none tracking-tight">La Ciudad hoy</p>
                <p class="city-strong mt-1 text-[0.9rem] font-black leading-tight">{{ \Illuminate\Support\Str::ucfirst($today->translatedFormat('d \\d\\e F \\d\\e Y')) }}</p>
            </article>

            <a href="https://www.clima.cdmx.gob.mx/index.xhtml" target="_blank" rel="noopener" class="city-chip flex min-h-[72px] items-center gap-3 rounded-[0.85rem] px-4 py-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[0.72rem] bg-[linear-gradient(180deg,#f7edff_0%,#ece5ff_100%)] text-[#856fb0]">
                    <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 15a6 6 0 1 1 11.2 3"></path>
                        <path d="M17 16a4 4 0 1 0-1.2 7.8H7a4 4 0 1 1 .5-7.9"></path>
                        <path d="M9 18l.5-1"></path>
                        <path d="M12 18l.5-1"></path>
                    </svg>
                </div>
                <div class="flex flex-1 items-center justify-between gap-3">
                    <p class="city-copy whitespace-nowrap text-[0.95rem] font-medium">
                        {{ $civicData['weather']['summary'] ?? 'Clima' }} {{ $civicData['weather']['temperature'] ?? '--' }}°C
                    </p>
                    <span class="h-8 w-px bg-slate-200"></span>
                    <p class="city-copy whitespace-nowrap text-[0.95rem] font-medium">
                        {{ $civicData['weather']['air_quality'] ?? 'Calidad del aire' }}
                        <span class="ml-2">Indice UV {{ $civicData['weather']['uv_index'] ?? '--' }}</span>
                    </p>
                </div>
            </a>

            <a href="https://hoynocircula.cdmx.gob.mx/" target="_blank" rel="noopener" class="city-chip flex min-h-[72px] items-center gap-3 rounded-[0.85rem] px-4 py-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[0.72rem] bg-[linear-gradient(180deg,#fff1f2_0%,#ffe4e6_100%)] text-rose-300">
                    <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="currentColor" aria-hidden="true">
                        <rect x="5" y="5" width="14" height="14" rx="3"></rect>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="city-strong text-[0.95rem] font-black leading-tight">Hoy no circula</p>
                    <p class="city-copy whitespace-nowrap text-[0.95rem] font-medium leading-tight">
                        Placas {{ $civicData['hoy_no_circula']['plates'] ?? 'Sin dato' }} - {{ $civicData['hoy_no_circula']['restriction'] ?? 'Consulta la información oficial' }}
                    </p>
                </div>
            </a>

            <a href="https://internetparatodas.cdmx.gob.mx/puntos-wifi" target="_blank" rel="noopener" class="city-chip flex min-h-[72px] items-center gap-3 rounded-[0.85rem] px-4 py-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[0.72rem] bg-[linear-gradient(180deg,#f2ebff_0%,#e8dffd_100%)] text-[#856fb0]">
                    <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 19h.01"></path>
                        <path d="M4.9 9a11 11 0 0 1 14.2 0"></path>
                        <path d="M7.8 12a6.8 6.8 0 0 1 8.4 0"></path>
                    </svg>
                </div>
                <div class="city-copy text-[0.95rem] font-medium leading-tight">
                    {{ $civicData['wifi']['points'] ?? 'Puntos WiFi' }}<br>{{ $civicData['wifi']['subtitle'] ?? 'Consulta la red disponible en tu zona' }}
                </div>
            </a>
        </div>
    </section>

    <section class="login-stage relative overflow-hidden">
        <div class="dot-grid pointer-events-none absolute inset-0 opacity-35"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-white/8 to-transparent"></div>
        <div class="relative mx-auto grid min-h-[calc(100vh-145px)] max-w-7xl items-stretch gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[1fr_.95fr] lg:gap-8 lg:px-8 lg:py-10">
            <div class="axolotl-card relative order-2 overflow-hidden rounded-[2rem] shadow-2xl min-h-[260px] sm:min-h-[340px] lg:order-1 lg:min-h-[670px]">
                <div class="axolotl-stack absolute inset-0">
                    <img src="{{ asset('Axolote_natacion.gif') }}" alt="Axolote natación">
                    <img src="{{ asset('Axolote_gym.gif') }}" alt="Axolote gimnasio">
                    <img src="{{ asset('Axolote_Futbol.gif') }}" alt="Axolote fútbol">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/55 via-slate-950/15 to-slate-950/10"></div>
            </div>

            <div class="order-1 flex items-center justify-center lg:order-2">
                <div class="glass-card w-full max-w-xl rounded-[2rem] p-6 sm:p-8 lg:min-h-[670px] lg:rounded-[2.5rem] lg:p-10 lg:flex lg:flex-col lg:justify-center">
                    <div class="mb-8">
                        <p class="text-[10px] font-black uppercase tracking-[0.28em] text-violet-200">Acceso administrativo</p>
                        <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">Iniciar sesión</h2>
                        <p class="mt-3 max-w-md text-sm font-medium leading-7 text-white/60">Panel de Control — UTOPÍA Japón para seguimiento operativo, validación y gestión interna.</p>
                    </div>

                    @if($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-500/20 bg-red-500/10 p-4">
                        <p class="text-sm font-semibold text-red-300">{{ $errors->first() }}</p>
                    </div>
                    @endif

                    @if(session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4">
                        <p class="text-sm font-medium text-emerald-300">{{ session('success') }}</p>
                    </div>
                    @endif

                    <form action="{{ route('admin.login.post') }}" method="POST" class="space-y-5">
                        @csrf
                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-extrabold uppercase tracking-[0.15em] text-slate-300">Correo electrónico</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                placeholder="admin@digitaltwin.mx"
                                class="input-field w-full rounded-2xl py-4 px-5 text-sm font-medium">
                        </div>
                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-extrabold uppercase tracking-[0.15em] text-slate-300">Contraseña</label>
                            <div class="relative">
                                <input type="password" name="password" id="pwd" required
                                    placeholder="••••••••••••"
                                    class="input-field w-full rounded-2xl py-4 px-5 pr-12 text-sm font-medium">
                                <button type="button" onclick="togglePwd()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition-colors">
                                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="btn-login w-full rounded-2xl py-4 text-base font-extrabold tracking-wide text-white">
                                ENTRAR AL PANEL
                            </button>
                        </div>
                    </form>

                    <div class="mt-6 text-center">
                        <a href="{{ url('/panel') }}" class="text-xs font-bold uppercase tracking-widest text-slate-300 transition-colors hover:text-white/90">
                            ← Volver a reservas públicas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.indi-footer', ['overlay' => true, 'theme' => 'dark'])

    <script>
        function togglePwd() {
            const p = document.getElementById('pwd');
            p.type = p.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
