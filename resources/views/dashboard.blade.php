@extends('layouts.app', ['title' => 'Dashboard', 'header' => 'Dashboard'])

@section('content')
<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="app-card p-5">
            <p class="text-sm text-slate-400">Total facturé</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">142 850 MAD</p>
        </div>
        <div class="app-card p-5">
            <p class="text-sm text-slate-400">Total payé</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums text-emerald-400">98 420 MAD</p>
        </div>
        <div class="app-card p-5">
            <p class="text-sm text-slate-400">Reste à payer</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums text-amber-400">44 430 MAD</p>
        </div>
        <div class="app-card p-5">
            <p class="text-sm text-slate-400">Nombre de factures</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">126</p>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="app-card p-5 xl:col-span-2">
            <h2 class="mb-4 text-base">Chiffre d'affaires / mois</h2>
            <canvas id="revenueBarChart" height="120"></canvas>
        </div>
        <div class="app-card p-5">
            <h2 class="mb-4 text-base">Statuts factures</h2>
            <canvas id="invoicePieChart" height="220"></canvas>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="app-card p-5">
            <h2 class="mb-4 text-base">Dernières factures</h2>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr><th>N°</th><th>Client</th><th>Montant</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>FA-2026-0012</td><td>Atlas SARL</td><td>12 000 MAD</td><td class="text-emerald-400">Payée</td></tr>
                        <tr><td>FA-2026-0013</td><td>NovaCom</td><td>8 450 MAD</td><td class="text-amber-400">Partielle</td></tr>
                        <tr><td>FA-2026-0014</td><td>Buildex</td><td>4 920 MAD</td><td class="text-rose-400">Non payée</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="app-card p-5">
            <h2 class="mb-4 text-base">Derniers BL</h2>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr><th>N°</th><th>Commande</th><th>Client</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>BL-2026-032</td><td>CMD-221</td><td>Atlas SARL</td><td>14/02/2026</td></tr>
                        <tr><td>BL-2026-033</td><td>CMD-222</td><td>NovaCom</td><td>15/02/2026</td></tr>
                        <tr><td>BL-2026-034</td><td>CMD-223</td><td>Buildex</td><td>16/02/2026</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
