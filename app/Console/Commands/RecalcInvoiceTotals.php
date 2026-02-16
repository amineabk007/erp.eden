<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;

class RecalcInvoiceTotals extends Command
{
    protected $signature = 'invoice:recalc';
    protected $description = 'Recalculate invoices totals from invoice_details';

    public function handle()
    {
        $count = 0;

        Invoice::chunk(200, function ($invoices) use (&$count) {
            foreach ($invoices as $inv) {
                $inv->recalcTotal();
                $count++;
            }
        });

        $this->info("Totals recalculated for {$count} invoices ✅");
        return Command::SUCCESS;
    }
}
