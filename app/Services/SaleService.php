<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            Club::whereKey(auth()->user()->club_id)->lockForUpdate()->firstOrFail();
            $existing = Sale::where('request_key', $data['request_key'])->first();
            if ($existing) {
                return $existing;
            }

            $onAccount = $data['payment_method'] === 'booking';
            $booking = null;
            if ($onAccount) {
                $booking = Booking::whereKey($data['booking_id'])->lockForUpdate()->first();
                if (! $booking || $booking->status === 'canceled' || ! $booking->start_time->isToday()) {
                    throw ValidationException::withMessages(['booking_id' => 'Selecciona una reserva activa de hoy de tu club.']);
                }
            }

            $quantities = [];
            foreach ($data['items'] as $item) {
                $id = (int) $item['product_id'];
                $quantities[$id] = ($quantities[$id] ?? 0) + (int) $item['quantity'];
            }
            $products = Product::whereIn('id', array_keys($quantities))->orderBy('id')->lockForUpdate()->get();
            if ($products->count() !== count($quantities) || $products->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Hay productos que no pertenecen a tu club.']);
            }

            $total = 0;
            $cost = 0;
            foreach ($products as $product) {
                $quantity = $quantities[$product->id];
                if (! $product->is_active || $quantity < 1 || $quantity > 10000 || $product->stock < $quantity) {
                    throw ValidationException::withMessages(['items' => "Stock insuficiente o producto inactivo: {$product->name}."]);
                }
                $total += $this->cents($product->sale_price) * $quantity;
                $cost += $this->cents($product->cost_price) * $quantity;
            }

            $sale = Sale::create([
                'customer_email' => $data['customer_email'] ?? $booking?->customer_email,
                'booking_id' => $booking?->id, 'user_id' => auth()->id(),
                'request_key' => $data['request_key'],
                'total_amount' => $this->decimal($total), 'cost_amount' => $this->decimal($cost),
                'status' => $onAccount ? 'open' : 'paid',
                'payment_method' => $onAccount ? null : $data['payment_method'],
                'paid_at' => $onAccount ? null : now(),
            ]);
            foreach ($products as $product) {
                $quantity = $quantities[$product->id];
                SaleItem::create([
                    'sale_id' => $sale->id, 'product_id' => $product->id, 'product_name' => $product->name,
                    'quantity' => $quantity, 'unit_cost_price' => $product->cost_price,
                    'unit_sale_price' => $product->sale_price,
                    'subtotal' => $this->decimal($this->cents($product->sale_price) * $quantity),
                ]);
                $product->decrement('stock', $quantity);
                StockMovement::create([
                    'product_id' => $product->id, 'user_id' => auth()->id(), 'sale_id' => $sale->id,
                    'quantity' => -$quantity, 'stock_after' => $product->stock, 'reason' => 'Venta POS',
                ]);
            }

            app(ConfirmationService::class)->sale($sale);

            return $sale;
        }, 3);
    }

    public function settle(Sale $sale, string $method): Sale
    {
        return DB::transaction(function () use ($sale, $method) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($sale->status === 'open') {
                $sale->update(['status' => 'paid', 'payment_method' => $method, 'paid_at' => now()]);
            }

            app(ConfirmationService::class)->sale($sale);

            return $sale;
        }, 3);
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
