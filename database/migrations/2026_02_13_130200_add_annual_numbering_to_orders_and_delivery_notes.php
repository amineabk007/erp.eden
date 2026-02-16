<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure delivery_notes has delivery_code (already exists in your schema) - no change needed
        // Ensure orders has order_code (already exists) - no change needed
        // This migration is kept for future compatibility.
    }

    public function down(): void
    {
        // no-op
    }
};
