<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TozPieProductSync
{
    public function sync(): int
    {
        $apiKey = config('services.upstream_api.key');
        $baseUrl = rtrim((string) config('services.upstream_api.base_url'), '/');

        if (! $apiKey || ! $baseUrl) {
            throw new RuntimeException('Chua cau hinh UPSTREAM_API_BASE_URL va UPSTREAM_API_KEY.');
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders(['X-API-Key' => $apiKey])
                ->get($baseUrl . '/api/products');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Khong ket noi duoc API san pham TozPie.');
        }

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException($response->json('error') ?: 'TozPie khong tra danh sach san pham hop le.');
        }

        $products = collect($response->json('products', []));
        $marginPercent = (int) env('TOZPIE_MARKUP_PERCENT', 15);

        $products->each(function (array $item) use ($marginPercent): void {
            $tozpieId = (int) ($item['id'] ?? $item['product_id'] ?? 0);

            if ($tozpieId < 1) {
                return;
            }

            $importPrice = (int) ($item['price'] ?? $item['import_price'] ?? 0);
            $minimumSellingPrice = (int) ceil($importPrice * (100 + $marginPercent) / 100);
            $inStock = (bool) ($item['in_stock'] ?? false);

            $product = Product::firstOrNew(['tozpie_product_id' => $tozpieId]);
            $currentSellingPrice = $product->exists ? $product->sellingPrice() : 0;
            $sellingPrice = max($currentSellingPrice, $minimumSellingPrice);

            $product->fill([
                'api_product_id' => $tozpieId,
                'name' => (string) ($item['name'] ?? $product->name ?? 'TozPie #' . $tozpieId),
                'category' => (string) ($item['category'] ?? $product->category ?? 'TozPie'),
                'description' => $product->description ?? 'Dong bo tu TozPie.',
                'import_price' => $importPrice,
                'selling_price' => $sellingPrice,
                'price' => $sellingPrice,
                'stock' => $inStock ? max((int) ($item['stock'] ?? $product->stock ?? 1), 1) : 0,
                'in_stock' => $inStock,
                'is_active' => $product->is_active ?? true,
            ]);

            $product->save();
        });

        return $products->count();
    }
}
