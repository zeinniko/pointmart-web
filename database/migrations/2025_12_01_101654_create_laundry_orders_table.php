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
        Schema::create('laundry_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('package_id')->nullable()->constrained('laundry_packages');
            $table->foreignId('address_id')->nullable()->constrained('user_addresses');
            $table->dateTime('pickup_time')->nullable();
            $table->dateTime('delivery_time')->nullable();
            $table->decimal('weight_input', 5, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'cash'])->default('pending');
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
        Schema::dropIfExists('laundry_orders');
    }
};
