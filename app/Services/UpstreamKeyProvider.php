<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class UpstreamKeyProvider
{
    public function buy(Product $product, ?string $idempotencyKey = null): array
    {
        $apiKey = config('services.upstream_api.key');
        $baseUrl = rtrim((string) config('services.upstream_api.base_url'), '/');

        if (! $apiKey || ! $baseUrl) {
            return [
                'key' => 'DEMO-' . strtoupper(Str::random(8)) . '-' . strtoupper(Str::random(8)),
                'order_id' => null,
            ];
        }

        try {
            $response = Http::timeout(45)
                ->acceptJson()
                ->withHeaders(['X-API-Key' => $apiKey])
                ->post($baseUrl . '/api/buy-key', [
                    'product_id' => $product->tozpieProductId(),
                    'idempotency_key' => $idempotencyKey,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Khong ket noi duoc API nha cung cap. Thu lai sau.');
        }

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException($response->json('error') ?: 'API nha cung cap khong tra key.');
        }

        $key = $response->json('key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('API nha cung cap tra ve key khong hop le.');
        }

        return [
            'key' => $key,
            'order_id' => $response->json('order_id'),
        ];
    }
}
