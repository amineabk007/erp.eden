<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Category;

class ImportLegacyProducts extends Command
{
    protected $signature = 'legacy:import-products 
                            {company_id : ID de la société cible}
                            {--keep-category=1 : importer category_id tel quel}
                            {--skip-existing=1 : ignorer produits existants}';

    protected $description = 'Import ONLY products from legacy v1_db into current ERP';

    public function handle()
    {
        $companyId = (int) $this->argument('company_id');
        $keepCategory = (int) $this->option('keep-category') === 1;
        $skipExisting = (int) $this->option('skip-existing') === 1;

        $legacyProducts = DB::connection('legacy')->table('products')->get();

        $count = 0;
        $skipped = 0;

        foreach ($legacyProducts as $p) {

            // Avoid duplicates by name + company
            if ($skipExisting) {
                $exists = Product::where('company_id', $companyId)
                    ->where('name', $p->product_name)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }
            }

            Product::create([
                'company_id'   => $companyId,
                'name'         => $p->product_name,
                'description'  => $p->description,
                'category_id'  => $keepCategory ? $p->category_id : null,
                'unit_id'      => $p->unit_id ?? 1,
                'buy_price'    => $p->buying_price ?? 0,
                'sell_price'   => $p->selling_price ?? 0,
                'image'        => $p->image,
                'created_at'   => $p->created_at ?? now(),
                'updated_at'   => $p->updated_at ?? now(),
            ]);

            $count++;
        }

        $this->info("✅ Produits importés : {$count}");
        if ($skipExisting) {
            $this->info("⏭️ Produits ignorés (déjà existants) : {$skipped}");
        }

        return self::SUCCESS;
    }
}
