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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('address_id')->nullable()->constrained('user_addresses');
            $table->decimal('total_price', 10, 2)->nullable();
            $table->enum('payment_status', ['pending','paid','cash'])->nullable();
            $table->string('order_status', 50)->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
