<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function save(array $data, ?Product $product = null): Product
    {
        return DB::transaction(function () use ($data, $product) {
            Club::whereKey(auth()->user()->club_id)->lockForUpdate()->firstOrFail();
            $categoryName = trim($data['new_category'] ?? $data['default_category'] ?? '');
            if ($categoryName !== '') {
                $category = ProductCategory::whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])->first()
                    ?? ProductCategory::create(['name' => $categoryName]);
                $data['category_id'] = $category->id;
            }
            unset($data['new_category'], $data['default_category'], $data['category_choice']);
            if (! empty($data['category_id'])) {
                ProductCategory::findOrFail($data['category_id']);
            }
            if ($product) {
                $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            } else {
                $product = new Product;
            }
            $initialStock = (int) ($data['initial_stock'] ?? 0);
            unset($data['initial_stock']);
            $new = ! $product->exists;
            $product->fill($data)->save();
            if ($new && $initialStock > 0) {
                $this->adjust($product, $initialStock, 'Stock inicial');
            }

            return $product->refresh();
        }, 3);
    }

    public function adjust(Product $product, int $quantity, string $reason): Product
    {
        return DB::transaction(function () use ($product, $quantity, $reason) {
            Club::whereKey(auth()->user()->club_id)->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $nextStock = $product->stock + $quantity;
            if ($quantity === 0 || $nextStock < 0 || $nextStock > 100000000) {
                throw ValidationException::withMessages(['quantity' => 'El ajuste debe dejar el stock entre 0 y 100.000.000 unidades.']);
            }
            $product->stock = $nextStock;
            $product->save();
            StockMovement::create([
                'product_id' => $product->id, 'user_id' => auth()->id(),
                'quantity' => $quantity, 'stock_after' => $nextStock, 'reason' => $reason,
            ]);

            return $product;
        }, 3);
    }
}
