<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderDetail;

class ImportLegacyOrders extends Command
{
    protected $signature = 'legacy:import-orders {company_id} {--skip-existing=1}';
    protected $description = 'Import orders + order_details from legacy v1_db to current ERP DB';

    public function handle()
    {
        $companyId = (int) $this->argument('company_id');
        $skipExisting = (int) $this->option('skip-existing') === 1;

        $this->info("Importing legacy orders into company_id={$companyId} …");

        // ✅ Map legacy client_id -> new client_id (by code OR name)
        // ✅ Map legacy client_id -> new client_id (by name only)
$legacyClients = DB::connection('legacy')->table('clients')->get();

$clientMap = [];
foreach ($legacyClients as $lc) {
    $legacyName = $lc->client_name ?? $lc->name ?? null;
    if (!$legacyName) continue;

    $newId = DB::table('clients')
        ->where('company_id', $companyId)
        ->where('name', $legacyName)
        ->value('id');

    if ($newId) {
        $clientMap[$lc->id] = $newId;
    }
}


        $this->info("Client mappings found: " . count($clientMap));

        // ✅ Map legacy product_id -> new product_id (by name)
        $legacyProducts = DB::connection('legacy')->table('products')->get();

        $productMap = [];
        foreach ($legacyProducts as $lp) {
            $newId = DB::table('products')
                ->where('company_id', $companyId)
                ->where('name', $lp->product_name)
                ->value('id');

            if ($newId) {
                $productMap[$lp->id] = $newId;
            }
        }

        $this->info("Product mappings found: " . count($productMap));

        $legacyOrders = DB::connection('legacy')->table('orders')->orderBy('id')->get();
        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($legacyOrders, $companyId, $skipExisting, &$imported, &$skipped, $clientMap, $productMap) {

            foreach ($legacyOrders as $o) {

                $newClientId = $clientMap[$o->client_id] ?? null;
                if (!$newClientId) {
                    // no client match -> skip
                    $skipped++;
                    continue;
                }

                // Avoid duplicates: same order_code + company
                if ($skipExisting && !empty($o->order_code)) {
                    $exists = Order::where('company_id', $companyId)->where('order_code', $o->order_code)->exists();
                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                }

                $order = Order::create([
                    'company_id'  => $companyId,
                    'client_id'   => $newClientId,
                    'order_code'  => $o->order_code ?? null,
                    'order_date'  => $o->order_date ?? now()->toDateString(),
                    'status_id'   => $o->status_id ?? 1,
                    'order_total' => 0, // will recalc from details
                    'created_at'  => $o->created_at ?? now(),
                    'updated_at'  => $o->updated_at ?? now(),
                ]);

                // Import order_details
                $legacyDetails = DB::connection('legacy')->table('order_details')->where('order_id', $o->id)->get();

                $total = 0;
                foreach ($legacyDetails as $d) {

                    $newProductId = $productMap[$d->product_id] ?? null;
                    if (!$newProductId) continue;

                    $qty = (float) ($d->quantity ?? 0);
                    $price = (float) ($d->unit_price ?? 0);
                    $lineTotal = $qty * $price;

                    OrderDetail::create([
                        'order_id'    => $order->id,
                        'product_id'  => $newProductId,
                        'quantity'    => $qty,
                        'unit_price'  => $price,
                        'total_price' => $lineTotal, // إذا العمود كاين فـ order_details
                        'created_at'  => $d->created_at ?? now(),
                        'updated_at'  => $d->updated_at ?? now(),
                    ]);

                    $total += $lineTotal;
                }

                $order->order_total = $total;
                $order->save();

                $imported++;
            }
        });

        $this->info("✅ Orders imported: {$imported}");
        $this->info("⏭️ Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
