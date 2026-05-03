<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@tozpie.test'],
            [
                'name' => 'Admin TozPie',
                'password' => 'password',
                'is_admin' => true,
                'wallet_balance' => 0,
                'api_key' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@tozpie.test'],
            [
                'name' => 'Khach hang',
                'password' => 'password',
                'is_admin' => false,
                'wallet_balance' => 970,
                'api_key' => '52db74b03921d445e27c2a001dd90513f2cb69c82fca05c1ff1a975191dae6f8',
            ]
        );

        $products = [
            [1, 'Cursor Pro 100 - 1 ngay', 'Cursor 100 request/ngay', 13580, 14000, '1 ngay'],
            [2, 'Cursor Pro 200 - 1 ngay', 'Cursor 200 yeu cau/ngay', 21340, 22900, '1 ngay'],
            [3, 'Cursor Pro 100 - 7 ngay', 'Cursor 100 request/ngay', 36860, null, '1 tuan'],
            [4, 'Cursor Pro 200 - 1 tuan', 'Cursor 200 yeu cau/ngay', 67900, 70000, '1 tuan'],
            [5, 'Cursor Pro VIP - 1 thang', 'Cursor VIP', 189000, 220000, '1 thang'],
            [6, 'Claude Pro - 1 thang', 'Claude', 249000, 290000, '1 thang'],
            [7, 'ChatGPT Plus - 1 thang', 'ChatGPT Plus', 259000, 320000, '1 thang'],
            [8, 'Grok Premium - 1 thang', 'Grok', 199000, 250000, '1 thang'],
        ];

        foreach ($products as [$apiProductId, $name, $category, $price, $oldPrice, $duration]) {
            Product::updateOrCreate(
                ['name' => $name],
                [
                    'api_product_id' => $apiProductId,
                    'tozpie_product_id' => $apiProductId,
                    'category' => $category,
                    'import_price' => (int) round($price * 0.85),
                    'selling_price' => $price,
                    'price' => $price,
                    'old_price' => $oldPrice,
                    'duration' => $duration,
                    'stock' => 99,
                    'in_stock' => true,
                    'description' => 'San pham mau co the sua trong trang admin.',
                    'is_active' => true,
                ]
            );
        }
    }
}
