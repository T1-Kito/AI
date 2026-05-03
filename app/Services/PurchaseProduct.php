<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseProduct
{
    public function __construct(private readonly UpstreamKeyProvider $provider)
    {
    }

    public function buy(User $buyer, Product $product, array $customer, ?string $idempotencyKey = null): array
    {
        if ($idempotencyKey) {
            $existing = Order::where('user_id', $buyer->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing && $existing->status === 'paid') {
                return ['status' => 'ok', 'order' => $existing, 'key' => $existing->purchased_key];
            }

            if ($existing) {
                throw new \RuntimeException('Yeu cau mua voi idempotency_key nay da ton tai nhung chua thanh cong.');
            }
        }

        $order = DB::transaction(function () use ($buyer, $product, $customer, $idempotencyKey) {
            $lockedUser = User::whereKey($buyer->id)->lockForUpdate()->firstOrFail();
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $sellingPrice = $lockedProduct->sellingPrice();

            if (! $lockedProduct->is_active || ! $lockedProduct->in_stock || $lockedProduct->stock < 1) {
                return ['status' => 'stock'];
            }

            if ($lockedUser->wallet_balance < $sellingPrice) {
                return ['status' => 'balance'];
            }

            $lockedUser->decrement('wallet_balance', $sellingPrice);
            $lockedProduct->decrement('stock');

            if ($lockedProduct->stock <= 1) {
                $lockedProduct->update(['in_stock' => false]);
            }

            return Order::create([
                'product_id' => $lockedProduct->id,
                'user_id' => $lockedUser->id,
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'] ?? null,
                'customer_phone' => $customer['customer_phone'] ?? null,
                'quantity' => 1,
                'total' => $sellingPrice,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        if (is_array($order)) {
            return $order;
        }

        try {
            $providerResult = $this->provider->buy($product, $idempotencyKey);
        } catch (\RuntimeException $exception) {
            DB::transaction(function () use ($buyer, $product, $order): void {
                User::whereKey($buyer->id)->lockForUpdate()->firstOrFail()
                    ->increment('wallet_balance', $order->total);

                $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $lockedProduct->increment('stock');
                $lockedProduct->update(['in_stock' => true]);

                $order->update([
                    'status' => 'failed',
                    'purchased_key' => null,
                ]);
            });

            throw $exception;
        }

        $order->update([
            'status' => 'paid',
            'purchased_key' => $providerResult['key'],
            'upstream_order_id' => $providerResult['order_id'],
        ]);

        return ['status' => 'ok', 'order' => $order, 'key' => $providerResult['key']];
    }
}
