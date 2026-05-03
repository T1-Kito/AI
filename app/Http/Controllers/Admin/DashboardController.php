<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TozPieAccountClient;
use Carbon\Carbon;
use Illuminate\View\View;
use RuntimeException;

class DashboardController extends Controller
{
    public function __invoke(TozPieAccountClient $tozPie): View
    {
        $today = Carbon::today();
        $ordersToday = Order::with('product')
            ->whereDate('created_at', $today)
            ->where('status', 'paid')
            ->get();

        $tozPieBalance = null;
        $tozPieError = null;

        try {
            $tozPieBalance = $tozPie->balance();
        } catch (RuntimeException $exception) {
            $tozPieError = $exception->getMessage();
        }

        return view('admin.dashboard', [
            'tozPieBalance' => $tozPieBalance,
            'tozPieError' => $tozPieError,
            'revenueToday' => $ordersToday->sum('total'),
            'ordersToday' => $ordersToday->count(),
            'profitToday' => $ordersToday->sum(fn (Order $order) => max(0, $order->total - (int) ($order->product?->import_price ?? 0))),
            'totalDeposits' => Deposit::where('status', 'completed')->sum('amount'),
            'pendingTopups' => Transaction::where('type', 'topup')->where('status', 'pending')->count(),
            'lowStockProducts' => Product::where('is_active', true)->where(function ($query) {
                $query->where('in_stock', false)->orWhere('stock', '<=', 2);
            })->latest()->take(6)->get(),
            'recentOrders' => Order::with(['product', 'user'])->latest()->take(8)->get(),
            'recentTransactions' => Transaction::with('user')->latest()->take(8)->get(),
            'userCount' => User::where('is_admin', false)->count(),
        ]);
    }
}
