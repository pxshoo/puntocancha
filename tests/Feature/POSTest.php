<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class POSTest extends TestCase
{
    use RefreshDatabase;

    private function loginClub(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function saleData(Product $product, array $overrides = []): array
    {
        return array_replace([
            'request_key' => (string) Str::uuid(), 'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ], $overrides);
    }

    private function productData(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Agua mineral', 'cost_price' => '500.25', 'sale_price' => '1000.50',
            'initial_stock' => 12, 'alert_min_stock' => 3, 'is_active' => true,
        ], $overrides);
    }

    public function test_guests_cannot_access_pos_or_inventory(): void
    {
        $this->get('/pos')->assertRedirect('/login');
        $this->get('/inventario')->assertRedirect('/login');
        $this->post('/pos')->assertRedirect('/login');
    }

    public function test_empty_pages_and_product_form_render(): void
    {
        $this->loginClub();
        $this->get('/pos')->assertOk()->assertSee('No hay productos activos');
        $this->get('/inventario')->assertOk()->assertSee('No hay productos.');
        $this->get('/inventario/nuevo')->assertOk();
    }

    public function test_product_category_stock_adjustment_and_low_stock_alert(): void
    {
        $user = $this->loginClub();
        $this->post(route('inventory.categories.store'), ['name' => 'Bebidas'])->assertSessionHasNoErrors();
        $category = ProductCategory::firstOrFail();
        $this->post('/inventario', $this->productData(['category_id' => $category->id, 'club_id' => 999]))
            ->assertRedirect(route('inventory.index'))->assertSessionHasNoErrors();
        $product = Product::firstOrFail();
        $this->assertEquals($user->club_id, $product->club_id);
        $this->assertEquals(12, $product->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => 12, 'reason' => 'Stock inicial']);

        $this->post(route('inventory.adjust', $product), ['quantity' => -10, 'reason' => 'Merma'])
            ->assertSessionHasNoErrors();
        $this->assertEquals(2, $product->fresh()->stock);
        $this->get('/inventario')->assertOk()->assertSee('1 producto(s)')->assertSee('Merma');
        $this->get('/pos')->assertOk()->assertSee('Agua mineral');
        $this->get(route('inventory.edit', $product))->assertOk();

        $this->post(route('inventory.adjust', $product), ['quantity' => -3, 'reason' => 'Error'])
            ->assertSessionHasErrors('quantity');
        $this->post(route('inventory.adjust', $product), ['quantity' => 0, 'reason' => 'Nada'])
            ->assertSessionHasErrors('quantity');
        $this->assertEquals(2, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_sale_uses_server_prices_and_records_historical_costs(): void
    {
        $this->loginClub();
        $product = Product::factory()->create(['cost_price' => '500.25', 'sale_price' => '1000.50']);
        $data = $this->saleData($product, ['total_amount' => 1, 'cost_amount' => 1]);
        $data['items'][0]['unit_sale_price'] = 1;
        $this->post('/pos', $data)->assertRedirect(route('pos.index'))->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $this->assertEquals('2001.00', $sale->total_amount);
        $this->assertEquals('1000.50', $sale->cost_amount);
        $this->assertEquals('paid', $sale->status);
        $this->assertNotNull($sale->paid_at);
        $this->assertEquals(8, $product->fresh()->stock);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'unit_cost_price' => '500.25', 'subtotal' => '2001.00']);
        $this->assertDatabaseHas('stock_movements', ['sale_id' => $sale->id, 'quantity' => -2, 'stock_after' => 8]);

        $updated = $this->productData(['cost_price' => '800.00', 'sale_price' => '1500.00']);
        unset($updated['initial_stock']);
        $this->put(route('inventory.update', $product), $updated)->assertSessionHasNoErrors();
        $this->assertEquals('1000.50', $sale->fresh()->cost_amount);
        $this->assertEquals('500.25', $sale->items()->first()->unit_cost_price);
        $this->get('/pos')->assertOk()->assertSee('Agua mineral');
    }

    public function test_sale_rolls_back_all_lines_on_insufficient_stock(): void
    {
        $this->loginClub();
        $first = Product::factory()->create(['stock' => 5]);
        $second = Product::factory()->create(['stock' => 1]);
        $data = $this->saleData($first);
        $data['items'][] = ['product_id' => $second->id, 'quantity' => 2];
        $this->post('/pos', $data)->assertSessionHasErrors('items');
        $this->assertEquals(5, $first->fresh()->stock);
        $this->assertEquals(1, $second->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_retried_sale_decrements_stock_only_once(): void
    {
        $this->loginClub();
        $product = Product::factory()->create();
        $data = $this->saleData($product);
        $this->post('/pos', $data)->assertSessionHasNoErrors();
        $this->post('/pos', $data)->assertSessionHasNoErrors();
        $this->assertEquals(8, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_stock_cannot_be_oversold_by_subsequent_sales(): void
    {
        $this->loginClub();
        $product = Product::factory()->create(['stock' => 3]);
        $this->post('/pos', $this->saleData($product))->assertSessionHasNoErrors();
        $this->post('/pos', $this->saleData($product))->assertSessionHasErrors('items');
        $this->assertEquals(1, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_invalid_quantities_duplicate_items_and_inactive_products_are_rejected(): void
    {
        $this->loginClub();
        $product = Product::factory()->create();
        foreach ([0, -1, 1.5, 10001] as $quantity) {
            $data = $this->saleData($product, ['items' => [['product_id' => $product->id, 'quantity' => $quantity]]]);
            $this->post('/pos', $data)->assertSessionHasErrors('items.0.quantity');
        }
        $data = $this->saleData($product);
        $data['items'][] = $data['items'][0];
        $this->post('/pos', $data)->assertSessionHasErrors('items.0.product_id');
        $product->update(['is_active' => false]);
        $this->post('/pos', $this->saleData($product))->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_booking_charge_is_open_until_settled_without_a_second_stock_deduction(): void
    {
        $this->loginClub();
        $product = Product::factory()->create();
        $booking = Booking::factory()->create();
        $this->post('/pos', $this->saleData($product, ['payment_method' => 'booking', 'booking_id' => $booking->id]))
            ->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $this->assertEquals('open', $sale->status);
        $this->assertNull($sale->payment_method);
        $this->assertNull($sale->paid_at);
        $this->get('/pos')->assertOk()->assertSee($booking->customer_name);
        $this->post(route('pos.settle', $sale), ['payment_method' => 'pos'])->assertSessionHasNoErrors();
        $this->post(route('pos.settle', $sale), ['payment_method' => 'cash'])->assertSessionHasNoErrors();
        $sale->refresh();
        $this->assertEquals('paid', $sale->status);
        $this->assertEquals('pos', $sale->payment_method);
        $this->assertNotNull($sale->paid_at);
        $this->assertEquals(8, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_canceled_and_other_day_bookings_cannot_receive_charges(): void
    {
        $this->loginClub();
        $product = Product::factory()->create();
        $canceled = Booking::factory()->create(['status' => 'canceled']);
        $yesterday = Booking::factory()->create(['start_time' => now()->subDay(), 'end_time' => now()->subDay()->addHour()]);
        foreach ([$canceled, $yesterday] as $booking) {
            $this->post('/pos', $this->saleData($product, ['payment_method' => 'booking', 'booking_id' => $booking->id]))
                ->assertSessionHasErrors('booking_id');
        }
        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_clubs_cannot_read_or_modify_each_others_inventory_sales_or_bookings(): void
    {
        $owner = $this->loginClub();
        $category = ProductCategory::create(['name' => 'Categoría privada']);
        $product = Product::factory()->create(['name' => 'Producto privado']);
        $booking = Booking::factory()->create(['customer_name' => 'Cliente privado']);
        $this->post('/pos', $this->saleData($product, ['payment_method' => 'booking', 'booking_id' => $booking->id]))->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $other = User::factory()->create();
        $this->assertNotEquals($owner->club_id, $other->club_id);
        $this->actingAs($other);
        $local = Product::factory()->create(['name' => 'Producto local']);
        $this->get('/inventario?q=Producto')->assertOk()->assertDontSee('Producto privado');
        $this->get('/pos')->assertOk()->assertDontSee('Producto privado')->assertDontSee('Cliente privado');
        $this->get('/reservas')->assertOk()->assertDontSee('Cliente privado');
        $this->get(route('inventory.edit', $product))->assertNotFound();
        $this->put(route('inventory.update', $product), [])->assertNotFound();
        $this->post(route('inventory.adjust', $product), ['quantity' => 10, 'reason' => 'Intento'])->assertNotFound();
        $this->post(route('pos.settle', $sale), ['payment_method' => 'cash'])->assertNotFound();
        $this->post('/pos', $this->saleData($product))->assertSessionHasErrors('items.0.product_id');
        $this->post('/pos', $this->saleData($local, ['payment_method' => 'booking', 'booking_id' => $booking->id]))
            ->assertSessionHasErrors('booking_id');
        $this->post('/inventario', $this->productData(['category_id' => $category->id]))->assertSessionHasErrors('category_id');
        $this->post('/reservas', ['court_id' => $booking->court_id])->assertSessionHasErrors('court_id');
        $this->assertEquals(8, DB::table('products')->where('id', $product->id)->value('stock'));
        $this->assertEquals('open', DB::table('sales')->where('id', $sale->id)->value('status'));
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_barcode_uniqueness_is_per_club_and_product_updates_do_not_change_stock(): void
    {
        $this->loginClub();
        $this->post('/inventario', $this->productData(['barcode' => '123']))->assertSessionHasNoErrors();
        $product = Product::firstOrFail();
        $this->post('/inventario', $this->productData(['barcode' => '123']))->assertSessionHasErrors('barcode');
        $data = $this->productData(['barcode' => '123', 'stock' => 999]);
        unset($data['initial_stock']);
        $this->put(route('inventory.update', $product), $data)->assertSessionHasNoErrors();
        $this->assertEquals(12, $product->fresh()->stock);
        $this->loginClub();
        $this->post('/inventario', $this->productData(['barcode' => '123']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('products', 2);
    }
}
