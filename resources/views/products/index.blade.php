@extends('layouts.app', ['title' => 'Produits', 'header' => 'Produits'])

@section('content')
<div class="app-card p-4">
    <div class="mb-4 flex gap-2">
        <input class="form-input-dark" placeholder="Rechercher un produit..." />
        <select class="form-input-dark max-w-52"><option>Toutes catégories</option></select>
    </div>
    <table class="app-table">
        <thead><tr><th>SKU</th><th>Produit</th><th>Prix</th><th>Stock</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
            <tr><td>PRD-001</td><td>Produit A</td><td>250 MAD</td><td>33</td><td class="text-right">✏️ 👁 🗑</td></tr>
            <tr><td>PRD-002</td><td>Produit B</td><td>180 MAD</td><td>12</td><td class="text-right">✏️ 👁 🗑</td></tr>
        </tbody>
    </table>
</div>
@endsection
