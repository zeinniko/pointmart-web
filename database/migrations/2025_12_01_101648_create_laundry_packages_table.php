<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laundry_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['per_kg', 'per_item', 'express']);
            $table->string('category', 100)->nullable();
            $table->decimal('price_per_kg', 10, 2)->nullable();
            $table->decimal('price_per_item', 10, 2)->nullable();
            $table->decimal('min_kg', 5, 2)->nullable();
            $table->string('estimation_time', 100)->nullable();
            $table->json('included_addons')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(1);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laundry_packages');
    }
};
