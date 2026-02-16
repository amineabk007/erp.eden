<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->index(['company_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                try { $table->dropForeign(['company_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['users_company_id_index']); } catch (\Throwable $e) {}
                $table->dropColumn('company_id');
            });
        }
    }
};
