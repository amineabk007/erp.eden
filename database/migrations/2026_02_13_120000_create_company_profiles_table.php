<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Moroccan identifiers (optional)
            $table->string('ice')->nullable();
            $table->string('if')->nullable();
            $table->string('rc')->nullable();
            $table->string('cnss')->nullable();
            $table->string('patente')->nullable();

            $table->string('logo_path')->nullable();
            $table->text('footer_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
