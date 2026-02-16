<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'nullable|string|max:100',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'user_id'    => Auth::id(),
            'amount'     => $data['amount'],
            'method'     => $data['method'] ?? 'cash',
        ]);

        return back()->with('success', 'Paiement ajouté avec succès ✅');
    }


    public function destroy(Invoice $invoice, Payment $payment)
    {
        // Safety: ensure payment belongs to invoice
        if ((int) $payment->invoice_id !== (int) $invoice->id) {
            abort(404);
        }

        $payment->delete();

        return back()->with('success', 'Paiement supprimé ✅');
    }
}
