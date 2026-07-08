<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de administracion | UTOPIAS Japon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        :root { --primary: #6366f1; --primary-dark: #4f46e5; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .sidebar { background: #0f172a; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .nav-link { color: #94a3b8; padding: 1rem 1.5rem; border-radius: 1rem; display: flex; align-items: center; gap: 0.75rem; font-weight: 600; transition: all 0.3s; margin-bottom: 0.5rem; }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-link.active { background: #6366f1; color: white; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3); }
        .nav-link i { width: 1.25rem; height: 1.25rem; }
        .mobile-menu-button,
        .mobile-close-button { display: none; }
        .admin-shell { margin-left: 18rem; }

        @media (max-width: 767px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .mobile-overlay { display: none; }
            .mobile-overlay.active { display: block; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 40; backdrop-filter: blur(4px); }
            .mobile-menu-button { display: inline-flex; position: fixed; top: 1rem; left: 1rem; z-index: 60; }
            .mobile-close-button { display: inline-flex; }
            .admin-shell { margin-left: 0; padding-top: 5rem; }
        }
    </style>
</head>
<body class="min-h-screen">
    <div id="mobile-overlay" class="mobile-overlay"></div>

    <button
        id="open-sidebar"
        type="button"
        class="mobile-menu-button items-center justify-center w-12 h-12 rounded-2xl bg-slate-900 text-white shadow-xl shadow-slate-900/20"
        aria-label="Abrir menu de navegacion"
    >
        <i data-lucide="menu" class="w-6 h-6"></i>
    </button>

    @include('admin.partials.sidebar')

    <div class="admin-shell flex min-h-screen flex-col">
        <main class="flex-1">
            @yield('content')
        </main>
        @include('partials.indi-footer')
    </div>

    <script>
        lucide.createIcons();

        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');
        const openSidebarButton = document.getElementById('open-sidebar');
        const closeSidebarButton = document.getElementById('close-sidebar');

        function setSidebarState(isOpen) {
            if (!sidebar || !mobileOverlay) return;

            sidebar.classList.toggle('active', isOpen);
            mobileOverlay.classList.toggle('active', isOpen);
            document.body.classList.toggle('overflow-hidden', isOpen);
        }

        openSidebarButton?.addEventListener('click', () => setSidebarState(true));
        closeSidebarButton?.addEventListener('click', () => setSidebarState(false));
        mobileOverlay?.addEventListener('click', () => setSidebarState(false));

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                setSidebarState(false);
            }
        });
    </script>
</body>
</html>
