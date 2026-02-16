<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::latest()->paginate(10);
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'email' => ['nullable','email','max:150'],
            'phone' => ['nullable','string','max:50'],
            'address' => ['nullable','string','max:1000'],
            'city' => ['nullable','string','max:100'],
            'ice' => ['nullable','string','max:50'],
            'rc' => ['nullable','string','max:50'],
            'IF_number' => ['nullable','string','max:50'],
        ]);

        Client::create($data);

        return redirect()->route('clients.index')->with('success', 'Client ajouté');
    }


    public function show(Client $client)
    {
        // Load history (orders / BL / invoices) if relations exist
        $client->load([
            'orders' => function ($q) { $q->latest()->limit(20); },
        ]);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'email' => ['nullable','email','max:150'],
            'phone' => ['nullable','string','max:50'],
            'address' => ['nullable','string','max:1000'],
            'city' => ['nullable','string','max:100'],
            'ice' => ['nullable','string','max:50'],
            'rc' => ['nullable','string','max:50'],
            'IF_number' => ['nullable','string','max:50'],
        ]);

        $client->update($data);

        return redirect()->route('clients.index')->with('success', 'Client modifié');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client supprimé');
    }
}
