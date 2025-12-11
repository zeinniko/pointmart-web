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
        $tables = ['laundry_addons', 'laundry_items', 'laundry_packages', 'products', 'product_categories','product_stock'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Cek apakah kolom created_at & updated_at belum ada
                    if (!Schema::hasColumn($tableName, 'created_at') && !Schema::hasColumn($tableName, 'updated_at')) {
                        $table->timestamps(); // otomatis dibuat & otomatis diisi Eloquent
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['users', 'roles', 'laundry_items', 'laundry_packages', 'user_addresses', 'user_devices'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'created_at') && Schema::hasColumn($tableName, 'updated_at')) {
                        $table->dropTimestamps();
                    }
                });
            }
        }
    }
};
