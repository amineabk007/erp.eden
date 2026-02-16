<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MyOrderController extends Controller
{
    private function clientIdOrFail(): int
    {
        $user = Auth::user();
        $clientId = (int) ($user->client_id ?? 0);
        if ($clientId <= 0) {
            abort(403, 'Aucun client associé à votre compte.');
        }
        return $clientId;
    }

    public function index()
    {
        $clientId = $this->clientIdOrFail();

        $orders = Order::with(['client'])
            ->where('client_id', $clientId)
            ->orderByDesc('id')
            ->paginate(20);

        return view('my.orders.index', compact('orders'));
    }

    public function create()
    {
        $this->clientIdOrFail();
        $products = Product::orderBy('name')->get();

        return view('my.orders.create', compact('products'));
    }

    public function store(Request $request)
    {
        $clientId = $this->clientIdOrFail();

        $data = $request->validate([
            'order_date' => ['nullable', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($data, $clientId) {

            $order = Order::create([
                'client_id' => $clientId,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'order_total' => 0,
                'status_id' => 1,
            ]);

            $total = 0;
            foreach ($data['lines'] as $line) {
                $qty = (float) $line['quantity'];
                $price = (float) $line['unit_price'];

                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => (int) $line['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                ]);

                $total += $qty * $price;
            }

            $order->order_total = $total;
            $order->save();

            return redirect()->route('my.orders.show', $order->id)->with('success', 'Commande envoyée ✅');
        });
    }

    public function show(Order $order)
    {
        $clientId = $this->clientIdOrFail();

        if ((int) $order->client_id !== (int) $clientId) {
            abort(404);
        }

        $order->load(['client', 'details.product']);
        return view('my.orders.show', compact('order'));
    }
}
