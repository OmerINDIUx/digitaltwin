<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTOPÍA Topilejo | Próximamente</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&display=swap');

        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            color: #182033;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50">
    <main class="flex min-h-[calc(100vh-97px)] items-center justify-center px-5 py-12">
        <section class="w-full max-w-5xl">
            <nav class="mb-10 flex items-center justify-between gap-4">
                <a href="{{ route('utopias.home') }}">
                    <img src="{{ asset('logo-topialogo.png') }}" alt="UTOPÍAS" class="h-12 w-auto">
                </a>
                <a href="{{ route('utopias.home') }}" class="rounded-full bg-white px-5 py-3 text-sm font-black uppercase tracking-wide text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-100">
                    Volver
                </a>
            </nav>

            <div class="overflow-hidden rounded-lg bg-white shadow-xl ring-1 ring-slate-200">
                <div class="grid lg:grid-cols-[.9fr_1.1fr]">
                    <div class="flex min-h-80 items-center justify-center bg-gradient-to-br from-violet-900 via-slate-800 to-teal-700 p-10 text-center text-white">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[.26em] text-white/75">UTOPÍA</p>
                            <h1 class="mt-3 text-5xl font-black tracking-tight sm:text-6xl">Topilejo</h1>
                        </div>
                    </div>
                    <div class="p-8 sm:p-12">
                        <span class="rounded-full bg-violet-200 px-4 py-2 text-xs font-black uppercase tracking-widest text-violet-950">Próximamente</span>
                        <h2 class="mt-6 text-4xl font-black tracking-tight text-slate-950">Esta sede estará disponible pronto.</h2>
                        <p class="mt-5 text-lg font-medium leading-8 text-slate-600">
                            Topilejo se integrará al menú de UTOPÍAS para consultar su experiencia digital, actividades y servicios cuando esté lista para publicarse.
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('utopias.home') }}#utopias" class="inline-flex justify-center rounded-full bg-slate-950 px-6 py-4 text-sm font-black uppercase tracking-wide text-white transition hover:bg-slate-800">
                                Ver menú
                            </a>
                            <a href="{{ url('/panel') }}" class="inline-flex justify-center rounded-full bg-violet-700 px-6 py-4 text-sm font-black uppercase tracking-wide text-white transition hover:bg-violet-800">
                                Entrar a Japón
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('partials.indi-footer')
</body>
</html>
