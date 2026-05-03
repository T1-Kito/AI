<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\TozPieProductSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'products' => Product::with('images')->latest()->paginate(10),
            'orders' => Order::with('product')->latest()->take(8)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->validated($request));
        $this->storeImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Da them san pham.');
    }

    public function edit(Product $product): View
    {
        $product->load('images');

        return view('admin.products.form', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request));
        $this->storeImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Da cap nhat san pham.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $product->delete();

        return back()->with('success', 'Da xoa san pham.');
    }

    public function destroyImage(ProductImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Da xoa anh.');
    }

    public function sync(TozPieProductSync $sync): RedirectResponse
    {
        try {
            $count = $sync->sync();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['sync' => $exception->getMessage()]);
        }

        return back()->with('success', "Da dong bo {$count} san pham tu TozPie.");
    }

    public function applyMarkup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'markup' => ['required', 'integer', 'min:0', 'max:500'],
        ]);

        Product::query()
            ->where('import_price', '>', 0)
            ->each(function (Product $product) use ($data): void {
                $sellingPrice = (int) ceil($product->import_price * (100 + $data['markup']) / 100);

                $product->update([
                    'selling_price' => $sellingPrice,
                    'price' => $sellingPrice,
                ]);
            });

        return back()->with('success', 'Da ap dung markup cho tat ca san pham co gia nhap.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tozpie_product_id' => ['nullable', 'integer', 'min:1'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'import_price' => ['nullable', 'integer', 'min:0'],
            'selling_price' => ['required', 'integer', 'min:0'],
            'old_price' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['import_price'] = (int) ($data['import_price'] ?? 0);
        $data['price'] = (int) $data['selling_price'];
        $data['api_product_id'] = $data['tozpie_product_id'] ?? null;
        $data['in_stock'] = (int) $data['stock'] > 0;

        return $data;
    }

    private function storeImages(Request $request, Product $product): void
    {
        $request->validate([
            'images.*' => ['nullable', 'image', 'max:4096'],
        ]);

        if (! $request->hasFile('images')) {
            return;
        }

        foreach ($request->file('images') as $index => $image) {
            $product->images()->create([
                'path' => $image->store('products', 'public'),
                'is_primary' => ! $product->images()->exists() && $index === 0,
            ]);
        }
    }
}
