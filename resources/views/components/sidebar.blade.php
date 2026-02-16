<aside
    class="fixed inset-y-0 left-0 z-30 hidden border-r border-slate-800 bg-slate-950 px-3 py-4 transition-all duration-300 lg:block"
    :class="collapsed ? 'w-20' : 'w-72'"
>
    <div class="mb-8 flex items-center gap-3 px-2">
        <div class="h-10 w-10 rounded-lg bg-emerald-500/20 text-emerald-400 grid place-items-center font-bold">E</div>
        <div x-show="!collapsed" x-transition>
            <p class="text-xs uppercase tracking-wide text-slate-400">EDEN ERP</p>
            <p class="text-sm font-semibold text-slate-100">SaaS Modern</p>
        </div>
    </div>

    @php
        $items = [
            ['label' => 'Dashboard', 'icon' => 'chart-bar', 'route' => 'dashboard'],
            ['label' => 'Clients', 'icon' => 'users', 'route' => 'clients.index'],
            ['label' => 'Produits', 'icon' => 'cube', 'route' => 'products.index'],
            ['label' => 'Commandes', 'icon' => 'clipboard-document-list', 'route' => 'orders.index'],
            ['label' => 'Bons de livraison', 'icon' => 'truck', 'route' => 'delivery-notes.index'],
            ['label' => 'Factures', 'icon' => 'document-text', 'route' => 'invoices.index'],
        ];
    @endphp

    <nav class="space-y-1">
        @foreach($items as $item)
            @php
                $isActive = request()->routeIs($item['route']);
            @endphp
            <a href="#" class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $isActive ? 'bg-slate-900 text-slate-100' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                <span class="absolute inset-y-1 left-0 w-1 rounded-r bg-emerald-500 {{ $isActive ? '' : 'opacity-0 group-hover:opacity-60' }}"></span>
                <x-dynamic-component :component="'icons.' . $item['icon']" class="h-5 w-5" />
                <span x-show="!collapsed" x-transition>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
