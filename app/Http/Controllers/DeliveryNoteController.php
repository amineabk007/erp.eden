<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\Order;
use App\Models\CompanyProfile;
use App\Services\NumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeliveryNoteController extends Controller
{
    public function index()
    {
        // ✅ Load order + client باش Total يبان مزيان فـ table بلا N+1
        $deliveryNotes = DeliveryNote::with(['order.client'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('delivery_notes.index', compact('deliveryNotes'));
    }

    public function create()
    {
        $orders = Order::with('client')
            ->orderByDesc('id')
            ->get();

        return view('delivery_notes.create', compact('orders'));
    }

    public function store(Request $request, NumberingService $numbering)
    {
        $data = $request->validate([
            'order_id'      => 'required|exists:orders,id',
            'delivery_date' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($data, $numbering) {

            $order = Order::with('details')->findOrFail($data['order_id']);

            // ✅ منع BL فارغ
            if ($order->details->isEmpty()) {
                return back()->withErrors(['order_id' => 'هذه commande ما فيها حتى produit.'])->withInput();
            }

            $bl = DeliveryNote::create([
                'order_id'      => $order->id,
                'delivery_date' => $data['delivery_date'] ?? now()->toDateString(),
                'status_id'     => 1,
            ]);

            // Annual numbering: BL-YYYY-0001
            $bl->delivery_code = $numbering->next('delivery_note', 'BL');

            // ✅ احسب total ديال BL من order_details
            $total = $this->computeTotalFromOrder($order);

            // ✅ إذا كان العمود total_amount كاين فـ delivery_notes خليه يتعمر
            if (Schema::hasColumn('delivery_notes', 'total_amount')) {
                $bl->total_amount = $total;
            }

            $bl->save();

            return redirect()->route('delivery-notes.index')->with('success', 'BL créé ✅');
        });
    }

    public function show(DeliveryNote $delivery_note)
    {
        $bl = $delivery_note->load(['order.client', 'order.details.product']);

        // ✅ total جاهز للـview (حتى إذا total_amount ماكاينش)
        $total = $this->computeTotal($bl);

        return view('delivery_notes.show', compact('bl', 'total'));
    }

    public function print(DeliveryNote $delivery_note)
    {
        $bl = $delivery_note->load(['order.client', 'order.details.product']);

        // ✅ multi-company: خذ profile ديال نفس الشركة
        $company = CompanyProfile::where('company_id', auth()->user()->company_id)->first();

        $total = $this->computeTotal($bl);

        return view('delivery_notes.print', compact('bl', 'company', 'total'));
    }

    public function pdf(DeliveryNote $delivery_note)
    {
        $bl = $delivery_note->load(['order.client', 'order.details.product']);
        $company = CompanyProfile::where('company_id', auth()->user()->company_id)->first();

        $total = $this->computeTotal($bl);

        $pdf = \PDF::loadView('delivery_notes.print', compact('bl', 'company', 'total'));
        return $pdf->download(($bl->delivery_code ?: ('BL_'.$bl->id)) . '.pdf');
    }

    public function storeFromOrder(Order $order, NumberingService $numbering)
    {
        return DB::transaction(function () use ($order, $numbering) {

            $order->loadMissing(['details']);

            if ($order->details->isEmpty()) {
                return back()->withErrors(['order_id' => 'هذه commande ما فيها حتى produit.']);
            }

            $bl = DeliveryNote::create([
                'order_id'      => $order->id,
                'delivery_date' => now()->toDateString(),
                'status_id'     => 1,
            ]);

            $bl->delivery_code = $numbering->next('delivery_note', 'BL');

            $total = $this->computeTotalFromOrder($order);

            if (Schema::hasColumn('delivery_notes', 'total_amount')) {
                $bl->total_amount = $total;
            }

            $bl->save();

            return redirect()->route('delivery-notes.show', $bl->id)
                ->with('success', 'BL créé depuis la commande ✅');
        });
    }

    /**
     * ✅ Total ديال BL:
     * - إذا total_amount كاين ومعبّي > 0 خذو
     * - إلا لا: احسبو من order_details
     */
    private function computeTotal(DeliveryNote $bl): float
    {
        $total = (float) ($bl->total_amount ?? 0);

        if ($total > 0) {
            return $total;
        }

        if ($bl->relationLoaded('order') && $bl->order) {
            $bl->order->loadMissing('details');
            return $this->computeTotalFromOrder($bl->order);
        }

        return 0.0;
    }

    private function computeTotalFromOrder(Order $order): float
    {
        // ✅ حساب مباشر من quantity * unit_price (أضمن)
        return (float) $order->details->sum(function ($d) {
            return ((float)($d->quantity ?? 0)) * ((float)($d->unit_price ?? 0));
        });
    }
}
