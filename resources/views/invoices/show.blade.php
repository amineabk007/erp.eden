@extends('layouts.app', ['title' => 'Facture', 'header' => 'Facture'])

@section('content')
<div class="space-y-4">
    <div class="app-card p-5">
        <div class="flex flex-col gap-4 border-b border-slate-800 pb-4 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-400">Facture</p>
                <h2 class="text-lg">FA-2026-0098</h2>
                <p class="text-sm text-slate-400">Client: Atlas SARL • Date: 16/02/2026</p>
            </div>
            <div class="text-right text-sm text-slate-400">
                <p>EDEN COMPANY</p>
                <p>Casablanca, Maroc</p>
                <p>contact@eden.ma</p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2 overflow-x-auto">
                <table class="app-table">
                    <thead>
                    <tr><th>Produit</th><th>Qté</th><th>P.U</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                    <tr><td>Produit A</td><td>10</td><td>250 MAD</td><td>2 500 MAD</td></tr>
                    <tr><td>Produit B</td><td>8</td><td>180 MAD</td><td>1 440 MAD</td></tr>
                    <tr><td>Produit C</td><td>12</td><td>120 MAD</td><td>1 440 MAD</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="app-card p-4">
                <h3 class="mb-3 text-sm uppercase tracking-wide text-slate-400">Totaux</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span>Sous-total</span><span>5 380 MAD</span></div>
                    <div class="flex justify-between"><span>TVA 20%</span><span>1 076 MAD</span></div>
                    <div class="flex justify-between border-t border-slate-700 pt-2 text-base font-semibold"><span>Total TTC</span><span>6 456 MAD</span></div>
                </div>
                <p class="mt-4 text-xs text-slate-400">Montant en lettres: six mille quatre cent cinquante-six dirhams.</p>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-500">Footer: Merci pour votre confiance.</p>
    </div>
</div>
@endsection
