<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('order_type', ['minimarket', 'laundry', 'marketplace'])->default('minimarket')->after('order_code');
            $table->foreignId('package_id')->nullable()->constrained('laundry_packages')->nullOnDelete()->after('user_id');
            $table->decimal('weight_input', 8, 2)->nullable()->after('package_id');
            $table->dateTime('pickup_time')->nullable()->after('address_id');
            $table->dateTime('delivery_time')->nullable()->after('pickup_time');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->foreignId('laundry_item_id')->nullable()->constrained('laundry_items')->nullOnDelete()->after('product_id');
            $table->foreignId('addon_id')->nullable()->constrained('laundry_addons')->nullOnDelete()->after('laundry_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn(['order_type', 'package_id', 'weight_input', 'pickup_time', 'delivery_time']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['laundry_item_id', 'addon_id']);
            $table->dropColumn(['laundry_item_id', 'addon_id']);
        });
    }
};