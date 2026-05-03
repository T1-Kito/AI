<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\PurchaseProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(
            filter_var(env('SHOP_MAINTENANCE_MODE', false), FILTER_VALIDATE_BOOLEAN)
                && ! $request->user()?->is_admin,
            503,
            'Cua hang dang bao tri. Vui long quay lai sau.'
        );

        $query = Product::query()
            ->with('images')
            ->where('is_active', true)
            ->where('in_stock', true)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        return view('shop.index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Product::query()->where('is_active', true)->select('category')->distinct()->pluck('category'),
            'activeCategory' => $request->input('category', 'all'),
            'search' => $request->input('search', ''),
        ]);
    }

    public function buy(Request $request, Product $product, PurchaseProduct $purchase): RedirectResponse
    {
        if ($request->user()->is_admin) {
            abort(403, 'Admin khong can mua san pham.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'quantity' => ['required', 'integer', 'in:1'],
        ]);

        try {
            $result = $purchase->buy($request->user(), $product, $data);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['provider' => $exception->getMessage()]);
        }

        if ($result['status'] === 'stock') {
            return back()->withErrors(['quantity' => 'San pham khong du so luong trong kho.']);
        }

        if ($result['status'] === 'balance') {
            return redirect()->route('wallet.topup')->withErrors([
                'amount' => 'So du khong du de mua san pham nay. Ban nap them tien nha.',
            ]);
        }

        return redirect()->route('orders.index')->with('success', 'Mua hang thanh cong. Key da duoc cap trong don hang.');
    }
}
