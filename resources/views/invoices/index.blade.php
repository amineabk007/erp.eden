@extends('layouts.app', ['title' => 'Factures', 'header' => 'Factures'])

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-1 gap-2">
            <input type="text" class="form-input-dark" placeholder="Rechercher une facture...">
            <select class="form-input-dark max-w-48">
                <option>Tous statuts</option>
                <option>Payée</option>
                <option>Partielle</option>
                <option>Non payée</option>
            </select>
        </div>
        <button class="btn-primary">Nouvelle facture</button>
    </div>

    <div class="app-card p-4">
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 1; $i <= 6; $i++)
                        <tr>
                            <td>FA-2026-00{{ $i }}</td>
                            <td>Client {{ $i }}</td>
                            <td>16/02/2026</td>
                            <td class="tabular-nums">{{ number_format(4500 + ($i * 380), 0, ',', ' ') }} MAD</td>
                            <td><span class="{{ $i % 3 === 0 ? 'text-rose-400' : ($i % 2 === 0 ? 'text-amber-400' : 'text-emerald-400') }}">{{ $i % 3 === 0 ? 'Non payée' : ($i % 2 === 0 ? 'Partielle' : 'Payée') }}</span></td>
                            <td>
                                <div class="flex justify-end gap-2 text-slate-300">
                                    <button title="Modifier" class="rounded p-1 hover:bg-slate-800">✏️</button>
                                    <button title="Voir" class="rounded p-1 hover:bg-slate-800">👁</button>
                                    <button title="Supprimer" class="rounded p-1 hover:bg-slate-800">🗑</button>
                                </div>
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2 text-sm text-slate-400">
            <button class="btn-secondary px-3 py-1.5">Précédent</button>
            <span>Page 1 / 3</span>
            <button class="btn-secondary px-3 py-1.5">Suivant</button>
        </div>
    </div>
</div>
@endsection
