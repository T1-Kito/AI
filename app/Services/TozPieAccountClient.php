<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TozPieAccountClient
{
    public function balance(): array
    {
        $apiKey = config('services.upstream_api.key');
        $baseUrl = rtrim((string) config('services.upstream_api.base_url'), '/');

        if (! $apiKey || ! $baseUrl) {
            throw new RuntimeException('Chua cau hinh API TozPie.');
        }

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->withHeaders(['X-API-Key' => $apiKey])
                ->get($baseUrl . '/api/balance');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Khong ket noi duoc TozPie.');
        }

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException($response->json('error') ?: 'TozPie khong tra so du hop le.');
        }

        return [
            'balance' => (int) $response->json('balance'),
            'email' => (string) $response->json('email'),
        ];
    }
}
