<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->tinyInteger('rating')->comment('1-5');
            $table->text('message');

            // Admin response
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            // Index untuk performa admin monitoring
            $table->index('responded_at');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
