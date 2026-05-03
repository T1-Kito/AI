<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\PurchaseProduct;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PurchaseApiController extends Controller
{
    public function balance(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'balance' => $request->user()->wallet_balance,
            'email' => $request->user()->email,
        ]);
    }

    public function products(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'products' => Product::query()
                ->where('is_active', true)
                ->where('in_stock', true)
                ->latest()
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'tozpie_product_id' => $product->tozpie_product_id,
                    'name' => $product->name,
                    'price' => $product->sellingPrice(),
                    'in_stock' => $product->in_stock && $product->stock > 0,
                ])
                ->values(),
        ]);
    }

    public function buyKey(Request $request, PurchaseProduct $purchase): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'idempotency_key' => ['nullable', 'string', 'min:8', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        $user = $request->user();

        if (! empty($data['idempotency_key'])) {
            $existing = Order::where('user_id', $user->id)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'key' => $existing->purchased_key,
                    'order_id' => $existing->id,
                ]);
            }
        }

        $product = Product::findOrFail($data['product_id']);

        try {
            $result = $purchase->buy($user, $product, [
                'customer_name' => $user->name,
                'customer_email' => $user->email,
            ], $data['idempotency_key'] ?? null);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 400);
        }

        if ($result['status'] === 'stock') {
            return response()->json(['success' => false, 'error' => 'Product out of stock'], 400);
        }

        if ($result['status'] === 'balance') {
            return response()->json(['success' => false, 'error' => 'Insufficient balance'], 400);
        }

        return response()->json([
            'success' => true,
            'key' => $result['key'],
            'order_id' => $result['order']->id,
        ]);
    }

    public function purchaseHistory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $start = Carbon::createFromFormat('Y-m-d', $data['start_date'])->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $data['end_date'])->endOfDay();

        if ($start->diffInDays($end) > 7) {
            return response()->json([
                'success' => false,
                'error' => 'Date range must be at most 7 days',
            ], 400);
        }

        $limit = $data['limit'] ?? 50;

        return response()->json([
            'success' => true,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'limit' => $limit,
            'orders' => Order::with('product')
                ->whereBelongsTo($request->user())
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn (Order $order) => [
                    'order_id' => $order->id,
                    'product_id' => $order->product_id,
                    'product_name' => $order->product?->name,
                    'key' => $order->purchased_key,
                    'price' => $order->total,
                    'purchased_at' => $order->created_at->format('Y-m-d H:i:s'),
                ]),
        ]);
    }
}
