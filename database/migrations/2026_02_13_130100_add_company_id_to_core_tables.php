<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajoute company_id aux tables principales (multi-entreprise)
        $tables = [
            'clients',
            'products',
            'invoices',
            'invoice_details',
            'orders',
            'order_details',
            'delivery_notes',
            'payments',
            'categories',
            'units',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                    $t->index(['company_id']);
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'clients',
            'products',
            'invoices',
            'invoice_details',
            'orders',
            'order_details',
            'delivery_notes',
            'payments',
            'categories',
            'units',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $t) {
                    // Certains SGBD exigent de drop FK avant colonne
                    try { $t->dropForeign(['company_id']); } catch (\Throwable $e) {}
                    try { $t->dropIndex([$table.'_company_id_index']); } catch (\Throwable $e) {}
                    $t->dropColumn('company_id');
                });
            }
        }
    }
};
