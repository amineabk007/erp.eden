<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\DeliveryNote;
use App\Models\Order;

class ImportLegacyDeliveryNotes extends Command
{
    protected $signature = 'legacy:import-delivery-notes {company_id} {--skip-existing=1}';
    protected $description = 'Import delivery notes (BL) from legacy v1_db into ERP';

    public function handle()
    {
        $companyId = (int) $this->argument('company_id');
        $skipExisting = (int) $this->option('skip-existing') === 1;

        $this->info("Importing legacy delivery notes into company_id={$companyId} …");

        // Map legacy order_id -> new order_id using order_code
        $legacyOrders = DB::connection('legacy')->table('orders')->get();

        $orderMap = [];
        foreach ($legacyOrders as $lo) {
            if (empty($lo->order_code)) continue;

            $newId = DB::table('orders')
                ->where('company_id', $companyId)
                ->where('order_code', $lo->order_code)
                ->value('id');

            if ($newId) {
                $orderMap[$lo->id] = $newId;
            }
        }

        $this->info("Order mappings found: " . count($orderMap));

        $legacyBLs = DB::connection('legacy')->table('delivery_notes')->orderBy('id')->get();

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($legacyBLs, $orderMap, $companyId, $skipExisting, &$imported, &$skipped) {

            foreach ($legacyBLs as $bl) {

                $newOrderId = $orderMap[$bl->order_id] ?? null;
                if (!$newOrderId) {
                    $skipped++;
                    continue;
                }

                // avoid duplicates by delivery_code
                if ($skipExisting && !empty($bl->delivery_note_code)) {
                    $exists = DeliveryNote::where('order_id', $newOrderId)
                        ->where('delivery_code', $bl->delivery_note_code)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                }

                $newBL = DeliveryNote::create([
                    'company_id'    => $companyId,
                    'order_id'      => $newOrderId,
                    'delivery_code' => $bl->delivery_note_code,
                    'delivery_date' => $bl->delivery_date ?? now()->toDateString(),
                    'status_id'     => 1,
                    'created_at'    => $bl->created_at ?? now(),
                    'updated_at'    => $bl->updated_at ?? now(),
                ]);

                // compute total from order_details
                if (Schema::hasColumn('delivery_notes', 'total_amount')) {
                    $order = Order::with('details')->find($newOrderId);
                    if ($order) {
                        $total = $order->details->sum(function ($d) {
                            return ((float)($d->quantity ?? 0)) * ((float)($d->unit_price ?? 0));
                        });
                        $newBL->total_amount = $total;
                        $newBL->save();
                    }
                }

                $imported++;
            }
        });

        $this->info("✅ Delivery Notes imported: {$imported}");
        $this->info("⏭️ Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
