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
        Schema::create('delivery_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_request_id')->constrained('delivery_requests')->cascadeOnDelete();
            $table->string('status', 50)->nullable();
            $table->dateTime('timestamp')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_status_history');
    }
};
