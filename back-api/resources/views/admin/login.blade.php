<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Digital Twin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        * { font-family: 'Outfit', sans-serif; }
        body { background: #0a0f1e; }
        .mesh-bg {
            background-color: #0a0f1e;
            background-image:
                radial-gradient(at 20% 20%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 80% 80%, rgba(79, 70, 229, 0.1) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(16, 24, 40, 0.8) 0px, transparent 100%);
        }
        .glass-card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .input-field {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            transition: all 0.2s;
        }
        .input-field:focus {
            background: rgba(255,255,255,0.08);
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
    </style>
</head>
<body class="mesh-bg min-h-screen flex items-center justify-center p-4">
    <div class="dot-grid fixed inset-0 opacity-40 pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- LOGO HEADER -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-indigo-600 shadow-2xl shadow-indigo-600/40 mb-5">
                <span class="text-2xl">🏙️</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Digital Twin Admin</h1>
            <p class="text-slate-400 text-sm mt-1 font-medium">Panel de Control — Utopía Japón</p>
        </div>

        <!-- CARD -->
        <div class="glass-card rounded-[2.5rem] p-10">
            <h2 class="text-xl font-bold text-white mb-1">Iniciar Sesión</h2>
            <p class="text-slate-500 text-sm mb-8">Acceso restringido al personal autorizado</p>

            @if($errors->any())
            <div class="mb-6 bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex items-center gap-3">
                <span class="text-red-400 text-lg">⚠</span>
                <p class="text-red-400 text-sm font-medium">{{ $errors->first() }}</p>
            </div>
            @endif

            @if(session('success'))
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4">
                <p class="text-emerald-400 text-sm font-medium">{{ session('success') }}</p>
            </div>
            @endif

            <form action="{{ route('admin.login.post') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-[0.15em] mb-2 ml-1">Correo Electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        placeholder="admin@digitaltwin.mx"
                        class="input-field w-full rounded-2xl py-4 px-5 text-sm font-medium">
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-[0.15em] mb-2 ml-1">Contraseña</label>
                    <div class="relative">
                        <input type="password" name="password" id="pwd" required
                            placeholder="••••••••••••"
                            class="input-field w-full rounded-2xl py-4 px-5 text-sm font-medium pr-12">
                        <button type="button" onclick="togglePwd()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit" class="btn-login w-full text-white font-extrabold py-4 rounded-2xl text-base tracking-wide">
                        ENTRAR AL SISTEMA
                    </button>
                </div>
            </form>
        </div>

        <!-- FOOTER HINT -->
        <div class="mt-6 text-center">
            <a href="{{ url('/panel') }}" class="text-slate-600 hover:text-slate-400 text-xs font-bold uppercase tracking-widest transition-colors">
                ← Volver a reservas públicas
            </a>
        </div>
        <p class="text-center text-slate-700 text-[10px] mt-4 uppercase tracking-[0.2em] font-bold">Digital Twin v2.5 · Secure Area</p>
    </div>

    <script>
        function togglePwd() {
            const p = document.getElementById('pwd');
            p.type = p.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
