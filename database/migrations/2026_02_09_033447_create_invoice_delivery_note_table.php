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

                // ✅ توحيد الاسم مع Model/View: delivery_code
                $table->string('delivery_code', 50)->nullable();

                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->date('delivery_date')->nullable();

                // status_id (draft/validated/paid/cancelled)
                $table->unsignedBigInteger('status_id')->nullable();

                $table->timestamps();
                $table->softDeletes(); // ✅ مهم حيت Model فيه SoftDeletes
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
