<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LaundryOrder;
use App\Models\LaundryOrderItem;
use App\Models\LaundryOrderAddon;
use App\Models\LaundryPackage;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\LaundryItem;
use App\Models\UserAddress;
use App\Models\LaundryAddon;

class AllDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        UserAddress::updateOrCreate(
            ['id' => 9],
            [
                'user_id' => 3,
                'label' => 'Rumah',
                'address' => 'Jl. Melati No. 5, Bandung',
                'latitude' => '-6.9147440',
                'longitude' => '107.6098100',
                'is_default' => 1,
            ]
        );

        UserAddress::updateOrCreate(
            ['id' => 11],
            [
                'user_id' => 1,
                'label' => 'gal',
                'address' => 'gang',
                'latitude' => '-6.1940094',
                'longitude' => '106.8228640',
                'is_default' => 0,
            ]
        );

        UserAddress::updateOrCreate(
            ['id' => 12],
            [
                'user_id' => 1,
                'label' => 'hsh',
                'address' => 'sbgaga',
                'latitude' => '-6.2000000',
                'longitude' => '106.8166660',
                'is_default' => 0,
            ]
        );

        $categories = [
            ['id' => 2, 'name' => 'Minuman'],
            ['id' => 4, 'name' => 'Perawatan Pria'],
            ['id' => 6, 'name' => 'Bumbu Dapur'],
            ['id' => 7, 'name' => 'Rumah Tangga'],
            ['id' => 8, 'name' => 'Snack'],
            ['id' => 13, 'name' => 'Coklat & Permen'],
            ['id' => 14, 'name' => 'Obat-obatan'],
            ['id' => 15, 'name' => 'Premium Items'],
            ['id' => 17, 'name' => 'Skincare'],
        ];

        foreach ($categories as $category) {
            ProductCategory::updateOrCreate(
                ['id' => $category['id']],
                $category
            );
        }

        $products = [

            [
                'product_category_id' => 2,
                'name' => 'Bear Brand',
                'price' => 3,
                'unit' => 'pcs',
                'barcode' => '123456',
                'image' => null,
                'is_active' => 1,
            ],

            [
                'product_category_id' => 13,
                'name' => 'COKLAT ALFREDO TOPLES',
                'description' => 'Perasan jeruk segar dingin',
                'price' => 290,
                'unit' => 'pcs',
                'barcode' => '000003',
                'image' => null,
                'is_active' => 1,
            ],

            [
                'product_category_id' => 15,
                'name' => 'ZERO 8 – PREMIUM',
                'price' => 300,
                'unit' => 'pcs',
                'barcode' => '1234156565',
                'image' => null,
                'is_active' => 1,
            ],

            [
                'product_category_id' => 15,
                'name' => 'ZERO 8 – STANDAR',
                'price' => 180,
                'unit' => 'pcs',
                'barcode' => '000002',
                'image' => null,
                'is_active' => 1,
            ],

            [
                'product_category_id' => 13,
                'name' => 'Beng-Beng',
                'price' => 20,
                'unit' => 'pcs',
                'barcode' => '8996001431030',
                'image' => null,
                'is_active' => 1,
            ],

            [
                'product_category_id' => 13,
                'name' => 'M & M COKLAT',
                'price' => 50,
                'unit' => 'pcs',
                'barcode' => '93682961',
                'image' => null,
                'is_active' => 1,
            ],

            [
                'product_category_id' => 8,
                'name' => '2 KELINCI GARLIC HIJAU',
                'price' => 90,
                'unit' => 'pcs',
                'barcode' => '0000000000001',
                'image' => null,
                'is_active' => 1,
            ],

            [
                'product_category_id' => 2,
                'name' => '28+ COFFEE',
                'price' => 45,
                'unit' => 'pcs',
                'barcode' => '00000002',
                'image' => null,
                'is_active' => 1,
            ],

            [
                'product_category_id' => 2,
                'name' => '7 UP',
                'price' => 20,
                'unit' => 'pcs',
                'barcode' => '000005',
                'image' => null,
                'is_active' => 1,
            ],

            [
                'product_category_id' => 17,
                'name' => 'ACNES FACE WASH ALL',
                'price' => 110,
                'unit' => 'pcs',
                'barcode' => '0000007',
                'image' => null,
                'is_active' => 1,
            ],

        ];

        foreach ($products as $product) {
            Product::create($product);
        }


        $packages = [
            [
                'name' => 'CKL Express Super 8 Jam',
                'type' => 'per_kg',
                'category' => 'Paket Cuci Kering',
                'price_per_kg' => 65,
                'price_per_item' => null,
                'min_kg' => 3,
                'estimation_time' => 8,
                'included_addons' => json_encode([]),
                'description' => '65 Bath/Kg - Minimal 3 Kg',
                'is_active' => 1,
            ],

            [
                'name' => 'CKL Reguler',
                'type' => 'per_kg',
                'category' => 'Paket Cuci Kering',
                'price_per_kg' => 35,
                'price_per_item' => null,
                'min_kg' => 3,
                'estimation_time' => 72,
                'included_addons' => json_encode([]),
                'description' => '35 Baht/Kg - Minimal 3 Kg',
                'is_active' => 1,
            ],

            [
                'name' => 'CKS Reguler',
                'type' => 'per_kg',
                'category' => 'Cuci - Kering - Setrika',
                'price_per_kg' => 45,
                'price_per_item' => null,
                'min_kg' => 3,
                'estimation_time' => 48,
                'included_addons' => json_encode([2]),
                'description' => '45 Baht/Kg Minimal 3 Kg',
                'is_active' => 1,
            ],

            [
                'name' => 'CKS Premium 8 Jam',
                'type' => 'express',
                'category' => 'Express Service',
                'price_per_kg' => null,
                'price_per_item' => 80,
                'min_kg' => 3,
                'estimation_time' => 8,
                'included_addons' => json_encode([2]),
                'description' => "8 Jam doang udah kelar! Cucian mu bakal pulang dalam keadaan super wanggi!\n\nKami Pastikan kamu puas. Kalau enggak, tinggal bilang - cuci ulang FREE!",
                'is_active' => 1,
            ],

            [
                'name' => 'CKL Express 24 Jam',
                'type' => 'express',
                'category' => 'Express Service',
                'price_per_kg' => null,
                'price_per_item' => 55,
                'min_kg' => 3,
                'estimation_time' => 24,
                'included_addons' => json_encode([]),
                'description' => "Dalam 24 Jam cucianmu balik wanggi dan rapi banget... kayak habis spa!\n\nKami Pastikan kamu puas. Kalau enggak, tinggal bilang - cuci ulang FREE!",
                'is_active' => 1,
            ],

            [
                'name' => 'Bed Cover & Lainya',
                'type' => 'per_item',
                'category' => 'Laundry Satuan',
                'price_per_kg' => null,
                'price_per_item' => 0,
                'min_kg' => 0,
                'estimation_time' => 0,
                'included_addons' => json_encode([]),
                'description' => null,
                'is_active' => 1,
            ],

            [
                'name' => 'Laundry Boneka',
                'type' => 'per_item',
                'category' => 'Laundry Satuan',
                'price_per_kg' => null,
                'price_per_item' => 0,
                'min_kg' => 0,
                'estimation_time' => 0,
                'included_addons' => json_encode([]),
                'description' => null,
                'is_active' => 1,
            ],

            [
                'name' => 'Atasan',
                'type' => 'per_item',
                'category' => 'Laundry Satuan',
                'price_per_kg' => null,
                'price_per_item' => 0,
                'min_kg' => 0,
                'estimation_time' => 0,
                'included_addons' => json_encode([]),
                'description' => null,
                'is_active' => 1,
            ],

            [
                'name' => 'Bawahan',
                'type' => 'per_item',
                'category' => 'Laundry Satuan',
                'price_per_kg' => null,
                'price_per_item' => 0,
                'min_kg' => 0,
                'estimation_time' => 0,
                'included_addons' => json_encode([]),
                'description' => null,
                'is_active' => 1,
            ],

            [
                'name' => 'Aksesoris',
                'type' => 'per_item',
                'category' => 'Laundry Satuan',
                'price_per_kg' => null,
                'price_per_item' => 0,
                'min_kg' => 0,
                'estimation_time' => 0,
                'included_addons' => json_encode([]),
                'description' => null,
                'is_active' => 1,
            ],

            [
                'name' => 'CKS Premium 24 Jam',
                'type' => 'per_kg',
                'category' => 'Express Service',
                'price_per_kg' => 70,
                'price_per_item' => null,
                'min_kg' => 3,
                'estimation_time' => 24,
                'included_addons' => json_encode([]),
                'description' => 'Di cuci terpisah , setrika halus dan rapih wangi tahan lama',
                'is_active' => 1,
            ],

        ];

        foreach ($packages as $package) {
            LaundryPackage::create($package);
        }

        // ==============================
        // BUAT LAUNDRY ITEMS
        // ==============================

        $item1 = LaundryItem::create([
            'package_id' => 1, // pastikan package ini sudah dibuat
            'item_name'  => 'Kemeja',
            'price'      => 15,
            'category'   => 'Atasan',
            'image'      => null,
            'is_active'  => 1,
        ]);

        $item2 = LaundryItem::create([
            'package_id' => 1,
            'item_name'  => 'Celana',
            'price'      => 10,
            'category'   => 'Bawahan',
            'image'      => null,
            'is_active'  => 1,
        ]);

        // ================= DRAFT ORDER =================
        $order = LaundryOrder::create([
            'order_code'    => 'LD-TEST001',
            'user_id'       => 3,
            'package_id'    => 1,
            'address_id'    => 9,
            'weight_input'  => 3,
            'payment_status' => 'pending',
            'order_status'  => 'draft',
            'total_price'   => 0,
        ]);

        // ================= ITEMS =================
        LaundryOrderItem::create([
            'laundry_order_id' => $order->id,
            'laundry_item_id'  => 1,
            'qty'              => 2,
            'price'            => 15,
            'subtotal'         => 30,
        ]);

        LaundryOrderItem::create([
            'laundry_order_id' => $order->id,
            'laundry_item_id'  => 2,
            'qty'              => 1,
            'price'            => 10,
            'subtotal'         => 10,
        ]);

        // ==============================
        // BUAT LAUNDRY ADDON DULU
        // ==============================

        $addon = LaundryAddon::create([
            'name'        => 'Pewangi Premium',
            'code'        => 'PWG01',
            'type'        => 'per_item', // ✅ sesuai enum
            'price'       => 20,
            'description' => 'Tambahan pewangi premium tahan lama',
            'is_active'   => 1,
        ]);

        // ================= ADDON =================
        LaundryOrderAddon::create([
            'laundry_order_id' => $order->id,
            'addon_id'         => $addon->id,
            'price'            => 20,
            'quantity'         => 1,
            'subtotal'         => 20,
        ]);

        // ==============================
        // BUAT CART UNTUK USER ID 3
        // ==============================
        $cart = Cart::create([
            'user_id' => 3,
        ]);

        // ==============================
        // AMBIL BEBERAPA PRODUCT
        // (Pastikan sudah ada product di DB)
        // ==============================
        $products = Product::take(3)->get();

        foreach ($products as $product) {

            $qty = rand(1, 3);
            $price = $product->price ?? 10000;
            $subtotal = $price * $qty;

            CartItem::create([
                'cart_id'   => $cart->id,
                'product_id' => $product->id,
                'qty'       => $qty,
                'price'     => $price,
                'subtotal'  => $subtotal,
            ]);
        }
    }
}
