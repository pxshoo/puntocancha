<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return view('inventory.index', [
            'products' => Product::with('category')
                ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')->orWhere('barcode', 'like', '%'.$search.'%');
                }))->orderBy('name')->paginate(20)->withQueryString(),
            'categories' => ProductCategory::orderBy('name')->get(),
            'movements' => StockMovement::with('product')->latest()->limit(20)->get(),
            'lowStock' => Product::where('is_active', true)->whereColumn('stock', '<=', 'alert_min_stock')->count(),
        ]);
    }

    public function create(): View
    {
        return view('inventory.form', ['product' => new Product, 'categories' => ProductCategory::orderBy('name')->get()]);
    }

    public function edit(Product $product): View
    {
        return view('inventory.form', ['product' => $product, 'categories' => ProductCategory::orderBy('name')->get()]);
    }

    public function store(StoreProductRequest $request, InventoryService $service): RedirectResponse
    {
        $service->save($request->validated());

        return redirect()->route('inventory.index')->with('success', 'Producto creado.');
    }

    public function update(StoreProductRequest $request, Product $product, InventoryService $service): RedirectResponse
    {
        $service->save($request->validated(), $product);

        return redirect()->route('inventory.index')->with('success', 'Producto actualizado.');
    }

    public function adjust(AdjustStockRequest $request, Product $product, InventoryService $service): RedirectResponse
    {
        $service->adjust($product, (int) $request->validated('quantity'), $request->validated('reason'));

        return redirect()->route('inventory.index')->with('success', 'Stock ajustado.');
    }

    public function category(StoreCategoryRequest $request): RedirectResponse
    {
        ProductCategory::create($request->validated());

        return redirect()->route('inventory.index')->with('success', 'Categoría creada.');
    }
}
