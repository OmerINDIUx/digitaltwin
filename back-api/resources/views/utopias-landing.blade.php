<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTOPÍAS | Ciudad de México</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&display=swap');

        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            color: #182033;
        }

        .hero-media {
            background:
                radial-gradient(circle at 78% 16%, rgba(168, 85, 247, .32), transparent 32%),
                linear-gradient(125deg, #170f35 0%, #31215f 45%, #0f766e 100%);
        }

        .axolotl-reel {
            position: relative;
            overflow: hidden;
            background: #0f172a;
        }

        .hero-reel {
            position: absolute;
            inset: 0;
        }

        .axolotl-reel img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            animation: axolotlReel 12s infinite;
        }

        .axolotl-reel img:nth-child(2) {
            animation-delay: 4s;
        }

        .axolotl-reel img:nth-child(3) {
            animation-delay: 8s;
        }

        @keyframes axolotlReel {
            0%, 8% { opacity: 0; transform: scale(1.02); }
            12%, 30% { opacity: 1; transform: scale(1); }
            38%, 100% { opacity: 0; transform: scale(1.02); }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50">
    <header class="absolute inset-x-0 top-0 z-20">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8">
            <a href="{{ route('utopias.home') }}" class="flex items-center">
                <img src="{{ asset('logo-topialogo.png') }}" alt="UTOPÍAS" class="h-12 w-auto rounded bg-white/90 px-3 py-2 shadow-sm sm:h-14">
            </a>
            <nav class="flex items-center gap-2 rounded-full bg-white/90 p-1 text-sm font-extrabold text-slate-700 shadow-sm backdrop-blur">
                <a href="{{ url('/panel') }}" class="rounded-full bg-violet-700 px-4 py-2 text-white transition hover:bg-violet-800">Japón</a>
                <a href="{{ route('utopias.topilejo') }}" class="rounded-full px-4 py-2 transition hover:bg-slate-100">Topilejo</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-media relative flex min-h-[92vh] items-end overflow-hidden">
            <div class="axolotl-reel hero-reel">
                <img src="{{ asset('storage/Axolote_natacion.gif') }}" alt="Axolote natación">
                <img src="{{ asset('storage/Axolote_gym.gif') }}" alt="Axolote gimnasio">
                <img src="{{ asset('storage/Axolote_Futbol.gif') }}" alt="Axolote fútbol">
            </div>
            <div class="absolute inset-0 bg-gradient-to-r from-[#170f35]/95 via-[#28174f]/78 to-[#0f766e]/42"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/35 via-transparent to-slate-950/30"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-slate-50 to-transparent"></div>
            <div class="relative z-10 mx-auto grid w-full max-w-7xl gap-10 px-5 pb-16 pt-36 sm:px-8 lg:grid-cols-[1.05fr_.95fr] lg:items-end lg:pb-20">
                <div class="max-w-3xl text-white">
                    <p class="mb-4 inline-flex rounded-full bg-white/16 px-4 py-2 text-xs font-black uppercase tracking-[.22em] ring-1 ring-white/25 backdrop-blur">
                        Transformación social profunda
                    </p>
                    <h1 class="text-5xl font-black leading-[.95] tracking-tight sm:text-7xl lg:text-8xl">UTOPÍAS</h1>
                    <p class="mt-6 max-w-2xl text-lg font-medium leading-8 text-white/88 sm:text-xl">
                        Espacios integrales para reivindicar la dignidad humana, ejercer derechos y construir comunidad a través del deporte, la cultura, el cuidado y el encuentro vecinal.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ url('/panel') }}" class="inline-flex items-center justify-center rounded-full bg-violet-700 px-6 py-4 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-violet-950/25 transition hover:bg-violet-800">
                            Entrar a Utopía Japón
                        </a>
                        <a href="#utopias" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-4 text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-slate-100">
                            Ver Utopías
                        </a>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="overflow-hidden rounded-lg bg-slate-950 shadow-2xl ring-1 ring-white/20">
                    <div class="aspect-video">
                        <iframe
                            class="h-full w-full"
                            src="https://www.youtube.com/embed/lv-A5bsV-YU"
                            title="Video de UTOPÍAS"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="utopias" class="mx-auto max-w-7xl px-5 py-16 sm:px-8">
            <div class="grid gap-10 lg:grid-cols-[.85fr_1.15fr] lg:items-start">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.24em] text-violet-700">Proyecto de ciudad</p>
                    <h2 class="mt-3 text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">Escuelas vivas de ciudadanía</h2>
                </div>
                <div class="space-y-5 text-lg leading-8 text-slate-600">
                    <p>
                        Las Utopías son un proyecto de transformación social profunda que busca reivindicar la dignidad humana, garantizar el ejercicio pleno de los derechos y promover el desarrollo integral de todas las personas.
                    </p>
                    <p>
                        Estos espacios funcionan como equipamientos integrales que ofrecen actividades y servicios sociales, deportivos, culturales y recreativos. Son escuelas vivas de ciudadanía, donde el diálogo, el aprendizaje y el intercambio de saberes permiten la construcción de comunidad.
                    </p>
                    <p>
                        Las UTOPÍAS forman parte de los ejes estratégicos del Gobierno de la Ciudad de México, y cada una está diseñada con una visión arquitectónica que responde a su entorno social, aprovechando al máximo los espacios para generar experiencias inclusivas y accesibles.
                    </p>
                </div>
            </div>
        </section>

        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[.24em] text-teal-700">Menú de Utopías</p>
                        <h2 class="mt-3 text-4xl font-black tracking-tight text-slate-950">Elige una sede</h2>
                    </div>
                    <p class="max-w-xl text-base font-semibold leading-7 text-slate-500">
                        Por ahora Japón está disponible para reservas y experiencia digital. Topilejo quedará anunciado mientras se prepara su apertura en el sistema.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <a href="{{ url('/panel') }}" class="group overflow-hidden rounded-lg border border-violet-200 bg-slate-950 text-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="axolotl-reel h-64">
                            <img src="{{ asset('storage/Axolote_natacion.gif') }}" alt="Axolote natación">
                            <img src="{{ asset('storage/Axolote_gym.gif') }}" alt="Axolote gimnasio">
                            <img src="{{ asset('storage/Axolote_Futbol.gif') }}" alt="Axolote fútbol">
                        </div>
                        <div class="p-8">
                            <div class="mb-4 flex items-center justify-between gap-4">
                                <h3 class="text-3xl font-black">Utopía Japón</h3>
                                <span class="rounded-full bg-emerald-400 px-3 py-1 text-xs font-black uppercase tracking-widest text-emerald-950">Disponible</span>
                            </div>
                            <p class="text-base font-medium leading-7 text-white/78">Accede al panel de reservaciones, actividades y espacios disponibles.</p>
                        </div>
                    </a>

                    <a href="{{ route('utopias.topilejo') }}" class="group overflow-hidden rounded-lg border border-slate-200 bg-stone-100 text-slate-950 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex h-64 items-center justify-center bg-gradient-to-br from-violet-900 via-slate-800 to-teal-700 px-8 text-center text-4xl font-black text-white">
                            Topilejo
                        </div>
                        <div class="p-8">
                            <div class="mb-4 flex items-center justify-between gap-4">
                                <h3 class="text-3xl font-black">Utopía Topilejo</h3>
                                <span class="rounded-full bg-violet-200 px-3 py-1 text-xs font-black uppercase tracking-widest text-violet-950">Próximamente</span>
                            </div>
                            <p class="text-base font-medium leading-7 text-slate-600">Una nueva sede se integrará pronto al recorrido digital y al sistema de actividades.</p>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-16 sm:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    'Sistema Público de Cuidados.',
                    'Alberca semiolímpica para la práctica deportiva y recreativa.',
                    'Casas Sociales y Casas Culturales para fomentar la participación comunitaria.',
                    'Trotapista y espacios al aire libre que promueven hábitos saludables.',
                    'Salas de usos múltiples para talleres, actividades educativas y encuentros vecinales.',
                    'Un proyecto ancla en cada Utopía, que servirá como detonador social y cultural.'
                ] as $action)
                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-base font-bold leading-7 text-slate-700">{{ $action }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 sm:px-8 md:flex-row md:items-center md:justify-between">
            <img src="{{ asset('logo-topialogo.png') }}" alt="UTOPÍAS" class="h-10 w-fit">
            <p class="text-sm font-semibold text-slate-500">Gobierno de la Ciudad de México</p>
        </div>
    </footer>
</body>
</html>
