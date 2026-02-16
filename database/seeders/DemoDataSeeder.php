<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\DeliveryNote;
use App\Services\NumberingService;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $catDivers = Category::firstOrCreate(['name' => 'Divers']);
            $catFruits = Category::firstOrCreate(['name' => 'Fruits']);
            $catHerbes = Category::firstOrCreate(['name' => 'Herbes']);
            $catLegumes = Category::firstOrCreate(['name' => 'Legumes']);

            $unitPiece = Unit::firstOrCreate(['name' => 'Piece']);
            $unitKg    = Unit::firstOrCreate(['name' => 'Kg']);
            $unitBotte = Unit::firstOrCreate(['name' => 'Botte']);

            $client = Client::firstOrCreate(
                ['name' => 'Client Demo Marrakech'],
                ['phone' => '0600000000', 'address' => 'Marrakech, Maroc']
            );

            $p1 = Product::firstOrCreate(
                ['sku' => 'PRD-TOM-001'],
                ['name' => 'Tomate', 'category_id' => $catLegumes->id, 'unit_id' => $unitKg->id, 'price' => 6.50]
            );
            $p2 = Product::firstOrCreate(
                ['sku' => 'PRD-POM-001'],
                ['name' => 'Pomme', 'category_id' => $catFruits->id, 'unit_id' => $unitKg->id, 'price' => 12.00]
            );
            $p3 = Product::firstOrCreate(
                ['sku' => 'PRD-MEN-001'],
                ['name' => 'Menthe', 'category_id' => $catHerbes->id, 'unit_id' => $unitBotte->id, 'price' => 3.00]
            );

            /** @var NumberingService $numbering */
            $numbering = app(NumberingService::class);

            $order = Order::create([
                'client_id'   => $client->id,
                'order_date'  => now(),
                'status_id'   => 1,
                'order_total' => 0,
            ]);
            $order->order_code = $numbering->next('order', 'CMD');
            $order->save();

            $items = [
                ['product' => $p1, 'qty' => 10],
                ['product' => $p2, 'qty' => 5],
                ['product' => $p3, 'qty' => 8],
            ];

            $total = 0;
            foreach ($items as $it) {
                $qty = (float)$it['qty'];
                $pu  = (float)$it['product']->price;
                $line = $qty * $pu;
                $total += $line;

                OrderDetail::create([
                    'order_id'    => $order->id,
                    'product_id'  => $it['product']->id,
                    'quantity'    => $qty,
                    'unit_price'  => $pu,
                    'total_price' => $line,
                ]);
            }
            $order->order_total = $total;
            $order->save();

            $bl = DeliveryNote::create([
                'order_id'      => $order->id,
                'delivery_date' => now()->toDateString(),
                'status_id'     => 1,
            ]);
            $bl->delivery_code = $numbering->next('delivery_note', 'BL');
            $bl->save();
        });
    }
}
