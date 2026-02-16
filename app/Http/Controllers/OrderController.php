<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $orders = Order::with(['client'])
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;

        $clients = Client::where('company_id', $companyId)->orderBy('name')->get();
        $products = Product::where('company_id', $companyId)->orderBy('name')->get();

        return view('orders.create', compact('clients', 'products'));
    }

    public function store(Request $request)
    {
        // ✅ Support "lines[0][...]" (current UI)
        // ✅ Also support legacy arrays: product_id[], quantity[], unit_price[]
        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'order_date' => ['nullable', 'date'],

            'lines' => ['nullable', 'array'],
            'lines.*.product_id' => ['required_with:lines', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],

            // legacy fallback
            'product_id' => ['nullable', 'array'],
            'product_id.*' => ['integer', 'exists:products,id'],
            'quantity' => ['nullable', 'array'],
            'quantity.*' => ['numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'array'],
            'unit_price.*' => ['numeric', 'min:0'],
        ]);

        $companyId = auth()->user()->company_id;

        // Build lines from either format
        $lines = $data['lines'] ?? null;

        if (empty($lines)) {
            $p = $request->input('product_id');
            $q = $request->input('quantity');
            $u = $request->input('unit_price');

            if (!is_array($p) || !is_array($q) || !is_array($u) || count($p) === 0) {
                return back()->withErrors(['error' => '❌ خاصك تزيد على الأقل منتوج واحد'])->withInput();
            }

            $lines = [];
            $max = max(count($p), count($q), count($u));
            for ($i=0; $i<$max; $i++) {
                if (empty($p[$i])) continue;
                $lines[] = [
                    'product_id' => (int) $p[$i],
                    'quantity'   => (float) ($q[$i] ?? 0),
                    'unit_price' => (float) ($u[$i] ?? 0),
                ];
            }
        }

        if (empty($lines)) {
            return back()->withErrors(['error' => '❌ خاصك تزيد على الأقل منتوج واحد'])->withInput();
        }

        return DB::transaction(function () use ($data, $lines, $companyId) {

            $order = Order::create([
                'company_id'  => $companyId,
                'client_id'   => (int) $data['client_id'],
                'order_date'  => $data['order_date'] ?? now()->toDateString(),
                'order_total' => 0,
                'status_id'   => 1,
            ]);

            $total = 0.0;
            foreach ($lines as $line) {
                $qty = (float) ($line['quantity'] ?? 0);
                $price = (float) ($line['unit_price'] ?? 0);

                OrderDetail::create([
                    'company_id' => $companyId,
                    'order_id'   => $order->id,
                    'product_id' => (int) $line['product_id'],
                    'quantity'   => $qty,
                    'unit_price' => $price,
                ]);

                $total += $qty * $price;
            }

            $order->order_total = $total;
            $order->save();

            return redirect()->route('orders.show', $order->id)->with('success', 'Commande créée ✅');
        });
    }

    public function show(Order $order)
    {
        $this->authorizeCompany($order);

        $order->load(['client', 'details.product']);
        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $this->authorizeCompany($order);

        $companyId = auth()->user()->company_id;

        $order->load(['client', 'details.product']);
        $clients = Client::where('company_id', $companyId)->orderBy('name')->get();
        $products = Product::where('company_id', $companyId)->orderBy('name')->get();

        return view('orders.edit', compact('order', 'clients', 'products'));
    }

    public function update(Request $request, Order $order)
    {
        $this->authorizeCompany($order);

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'order_date' => ['nullable', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $companyId = auth()->user()->company_id;

        return DB::transaction(function () use ($data, $order, $companyId) {

            $order->client_id = (int) $data['client_id'];
            $order->company_id = $companyId;

            if (isset($data['order_date'])) {
                $order->order_date = $data['order_date'];
            }
            $order->save();

            $order->details()->delete();

            $total = 0.0;
            foreach ($data['lines'] as $line) {
                $qty = (float) $line['quantity'];
                $price = (float) $line['unit_price'];

                OrderDetail::create([
                    'company_id' => $companyId,
                    'order_id' => $order->id,
                    'product_id' => (int) $line['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                ]);

                $total += $qty * $price;
            }

            $order->order_total = $total;
            $order->save();

            return redirect()->route('orders.show', $order->id)->with('success', 'Commande modifiée ✅');
        });
    }

    public function destroy(Order $order)
    {
        $this->authorizeCompany($order);

        if ($order->deliveryNotes()->exists()) {
            return back()->withErrors([
                'error' => '❌ مايمكنش تحذف commande عندها Bon de Livraison.'
            ]);
        }

        if ($order->invoices()->exists()) {
            return back()->withErrors([
                'error' => '❌ مايمكنش تحذف commande عندها Facture.'
            ]);
        }

        $order->delete();

        return redirect()->route('orders.index')
            ->with('success', 'Commande supprimée.');
    }

    private function authorizeCompany(Order $order): void
    {
        $companyId = auth()->user()->company_id;
        if ((int)$order->company_id !== (int)$companyId) {
            abort(403);
        }
    }
}
