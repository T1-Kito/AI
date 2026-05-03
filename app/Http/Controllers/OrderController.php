<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        abort_if(auth()->user()->is_admin, 403);

        return view('orders.index', [
            'orders' => Order::with('product')
                ->whereBelongsTo(auth()->user())
                ->latest()
                ->paginate(10),
        ]);
    }
}
