<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 50)->nullable();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->dateTime('order_date')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->decimal('order_total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
