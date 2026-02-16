@extends('layouts.app', ['title' => 'Clients', 'header' => 'Clients'])

@section('content')
<div class="app-card p-4">
    <div class="mb-4 flex gap-2">
        <input class="form-input-dark" placeholder="Rechercher un client..." />
        <select class="form-input-dark max-w-52"><option>Tous</option></select>
    </div>
    <table class="app-table">
        <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
            <tr><td>Atlas SARL</td><td>contact@atlas.ma</td><td>+2126000000</td><td class="text-right">✏️ 👁 🗑</td></tr>
            <tr><td>NovaCom</td><td>hello@nova.ma</td><td>+2126111111</td><td class="text-right">✏️ 👁 🗑</td></tr>
        </tbody>
    </table>
</div>
@endsection
