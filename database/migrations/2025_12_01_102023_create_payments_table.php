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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable(); // bisa laundry atau minimarket
            $table->enum('order_type', ['laundry', 'minimarket'])->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('method', ['cash'])->nullable();
            $table->enum('status', ['paid', 'failed'])->default('failed');
            $table->dateTime('transaction_time')->nullable();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
