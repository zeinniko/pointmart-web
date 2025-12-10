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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->integer('total_laundry_order')->default(0);
            $table->integer('total_minimarket_order')->default(0);
            $table->decimal('revenue_laundry', 10, 2)->default(0);
            $table->decimal('revenue_minimarket', 10, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
