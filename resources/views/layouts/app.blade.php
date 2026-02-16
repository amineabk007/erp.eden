<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'EDEN ERP' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if (function_exists('vite'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="font-sans bg-slate-900 text-slate-200">
<div class="app-shell" x-data="sidebar">
    <div class="flex min-h-screen">
        <x-sidebar />

        <main class="flex-1 lg:ml-72">
            <header class="sticky top-0 z-20 border-b border-slate-800 bg-slate-950/90 px-6 py-4 backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400">ERP SaaS Modern</p>
                        <h1 class="text-xl">{{ $header ?? 'Dashboard' }}</h1>
                    </div>
                    <button class="btn-secondary" @click="toggle">Sidebar</button>
                </div>
            </header>

            <section class="p-6">
                @yield('content')
            </section>
        </main>
    </div>
</div>
</body>
</html>
