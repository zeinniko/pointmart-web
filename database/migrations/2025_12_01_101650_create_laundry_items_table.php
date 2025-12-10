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
        Schema::create('laundry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('laundry_packages')->cascadeOnDelete();
            $table->string('item_name', 100)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('image', 255)->nullable();
            $table->boolean('is_active')->default(1);
        });       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laundry_items');
    }
};
