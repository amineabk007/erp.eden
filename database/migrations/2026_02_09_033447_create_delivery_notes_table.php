<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('delivery_notes')) {
            Schema::create('delivery_notes', function (Blueprint $table) {
                $table->id();

                // ✅ unified name with codebase
                $table->string('delivery_code', 50)->nullable();

                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->date('delivery_date')->nullable();

                // status_id (draft/validated/paid/cancelled or your own mapping)
                $table->foreignId('status_id')->nullable()->constrained('statuses');

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
