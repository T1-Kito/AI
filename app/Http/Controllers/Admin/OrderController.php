<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\UpstreamKeyProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('admin.orders.index', [
            'orders' => Order::with(['product', 'user'])->latest()->paginate(20),
        ]);
    }

    public function refund(Order $order): RedirectResponse
    {
        if (! $order->user_id || $order->status === 'refunded') {
            return back()->withErrors(['order' => 'Don hang nay khong the hoan tien.']);
        }

        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === 'refunded') {
                return;
            }

            User::whereKey($lockedOrder->user_id)->lockForUpdate()->firstOrFail()
                ->increment('wallet_balance', $lockedOrder->total);

            $lockedOrder->update(['status' => 'refunded']);
        });

        return back()->with('success', 'Da hoan tien don hang.');
    }

    public function retry(Order $order, UpstreamKeyProvider $provider): RedirectResponse
    {
        if (! $order->product || ! $order->user || ! in_array($order->status, ['failed', 'pending'], true)) {
            return back()->withErrors(['order' => 'Chi retry duoc don pending hoac failed co du thong tin.']);
        }

        $prepared = DB::transaction(function () use ($order): array {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedUser = User::whereKey($lockedOrder->user_id)->lockForUpdate()->firstOrFail();
            $lockedProduct = Product::whereKey($lockedOrder->product_id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === 'failed') {
                if ($lockedUser->wallet_balance < $lockedOrder->total) {
                    return ['ready' => false, 'message' => 'So du khach khong du de retry.'];
                }

                $lockedUser->decrement('wallet_balance', $lockedOrder->total);
                $lockedProduct->decrement('stock');
            }

            $lockedOrder->update(['status' => 'pending']);

            return ['ready' => true, 'product' => $lockedProduct->fresh()];
        });

        if (! $prepared['ready']) {
            return back()->withErrors(['order' => $prepared['message']]);
        }

        try {
            $result = $provider->buy($prepared['product'], $order->idempotency_key ?: 'admin-retry-' . $order->id . '-' . time());
        } catch (RuntimeException $exception) {
            DB::transaction(function () use ($order): void {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($lockedOrder->status !== 'pending') {
                    return;
                }

                User::whereKey($lockedOrder->user_id)->lockForUpdate()->firstOrFail()
                    ->increment('wallet_balance', $lockedOrder->total);

                Product::whereKey($lockedOrder->product_id)->lockForUpdate()->firstOrFail()
                    ->increment('stock');

                $lockedOrder->update(['status' => 'failed']);
            });

            return back()->withErrors(['order' => $exception->getMessage()]);
        }

        $order->update([
            'status' => 'paid',
            'purchased_key' => $result['key'],
            'upstream_order_id' => $result['order_id'],
        ]);

        return back()->with('success', 'Retry thanh cong, da cap key cho don.');
    }
}
