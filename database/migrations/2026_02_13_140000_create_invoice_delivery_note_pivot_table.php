<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('invoice_delivery_note')) {
            return;
        }

        Schema::create('invoice_delivery_note', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            // Optional scoping to company (safer for multi-tenant queries)
            if (Schema::hasColumn('invoices', 'company_id') || Schema::hasColumn('delivery_notes', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->index();
            }
            $table->timestamps();

            $table->unique(['invoice_id', 'delivery_note_id'], 'inv_bl_unique');
        });

        // Backfill company_id when possible
        if (Schema::hasColumn('invoice_delivery_note', 'company_id') && Schema::hasColumn('invoices', 'company_id')) {
            DB::statement("
                UPDATE invoice_delivery_note idn
                JOIN invoices i ON i.id = idn.invoice_id
                SET idn.company_id = i.company_id
                WHERE idn.company_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_delivery_note');
    }
};
