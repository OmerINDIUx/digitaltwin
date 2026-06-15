<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Digital Twin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        :root { --primary: #6366f1; --primary-dark: #4f46e5; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .sidebar { background: #0f172a; }
        .nav-link { color: #94a3b8; padding: 1rem 1.5rem; border-radius: 1rem; display: flex; align-items: center; gap: 0.75rem; font-weight: 600; transition: all 0.3s; margin-bottom: 0.5rem; }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-link.active { background: #6366f1; color: white; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3); }
        .nav-link i { width: 1.25rem; height: 1.25rem; }
    </style>
</head>
<body class="flex min-h-screen">
    <div id="mobile-overlay" class="mobile-overlay"></div>

    @include('admin.partials.sidebar')

    <main class="flex-1 ml-72">
        @yield('content')
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
