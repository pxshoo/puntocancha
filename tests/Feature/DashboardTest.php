<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function sale(string $date, string $status = 'paid', string $amount = '2000.00', string $cost = '800.00'): Sale
    {
        $sale = Sale::create([
            'request_key' => (string) Str::uuid(), 'total_amount' => $amount, 'cost_amount' => $cost,
            'status' => $status, 'user_id' => auth()->id(),
            'payment_method' => $status === 'paid' ? 'cash' : null,
            'paid_at' => $status === 'paid' ? $date : null,
        ]);
        $sale->forceFill(['created_at' => $date])->save();

        return $sale;
    }

    public function test_dashboard_requires_authentication_and_handles_an_empty_club(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertSee('Todavía no hay ventas ni arriendos')->assertSee('No hay alertas de stock.')
            ->assertViewHas('metrics', fn ($metrics) => $metrics['total'] === 0.0 && $metrics['rentalCount'] === 0);
    }

    public function test_summary_includes_period_boundaries_and_excludes_canceled_bookings_and_other_clubs(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $court = Court::factory()->create(['name' => 'Cancha principal']);
        Booking::factory()->create(['court_id' => $court->id, 'start_time' => '2026-10-01 00:00:00', 'total_price' => 20000, 'deposit_paid' => 5000]);
        Booking::factory()->create(['court_id' => $court->id, 'start_time' => '2026-10-31 23:59:59', 'total_price' => 10000, 'deposit_paid' => 12000]);
        Booking::factory()->create(['start_time' => '2026-10-15 12:00:00', 'status' => 'canceled', 'total_price' => 99999]);
        Booking::factory()->create(['start_time' => '2026-11-01 00:00:00', 'total_price' => 99999]);
        $sale = $this->sale('2026-10-01 00:00:00');
        $this->sale('2026-10-31 23:59:59', 'open', '1000.00', '400.00');
        $this->sale('2026-11-01 00:00:00', 'paid', '9999.00');
        $product = Product::factory()->create(['name' => 'Agua local', 'stock' => 1]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_name' => 'Agua local',
            'quantity' => 2, 'unit_cost_price' => 400, 'unit_sale_price' => 1000, 'subtotal' => 2000]);

        $this->actingAs(User::factory()->create());
        Booking::factory()->create(['start_time' => '2026-10-10 12:00:00', 'customer_name' => 'Cliente ajeno', 'total_price' => 88888]);
        $this->sale('2026-10-10 12:00:00', 'paid', '88888.00');
        Product::factory()->create(['name' => 'Producto ajeno', 'stock' => 0]);

        $response = $this->actingAs($owner)->get('/dashboard?period=custom&from=2026-10-01&to=2026-10-31');
        $response->assertOk()->assertDontSee('Cliente ajeno')->assertDontSee('Producto ajeno')
            ->assertSee('Agua local')->assertSee('Cancha principal')
            ->assertViewHas('metrics', fn ($m) => $m['rentals'] === 30000.0 && $m['rentalCount'] === 2 &&
                $m['sales'] === 3000.0 && $m['saleCount'] === 2 && $m['total'] === 33000.0 &&
                $m['cost'] === 1200.0 && $m['posMargin'] === 1800.0 &&
                $m['deposits'] === 17000.0 && $m['rentalPending'] === 15000.0 &&
                $m['openSales'] === 1000.0 && $m['collectedPos'] === 2000.0)
            ->assertViewHas('daily', fn ($days) => $days->count() === 31 &&
                $days->first()['sales'] === 2000.0 && $days->last()['rentals'] === 10000.0)
            ->assertViewHas('topProducts', fn ($products) => $products->count() === 1 && (int) $products->first()->units === 2);
    }

    public function test_pos_receipts_use_payment_date_without_counting_previous_sales_as_new_sales(): void
    {
        $this->actingAs(User::factory()->create());
        $sale = $this->sale('2026-09-30 10:00:00', 'paid', '5000.00');
        $sale->update(['paid_at' => '2026-10-02 10:00:00']);
        $this->get('/dashboard?period=custom&from=2026-10-01&to=2026-10-03')
            ->assertOk()->assertViewHas('metrics', fn ($m) => $m['collectedPos'] === 5000.0 && $m['sales'] === 0.0);
    }

    public function test_presets_use_current_month_week_and_day(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15)->setTime(12, 0));
        $this->actingAs(User::factory()->create());
        $this->get('/dashboard')->assertOk()->assertViewHas('from', fn ($date) => $date->toDateString() === '2026-10-01')
            ->assertViewHas('to', fn ($date) => $date->toDateString() === '2026-10-31');
        $this->get('/dashboard?period=today')->assertOk()->assertViewHas('daily', fn ($days) => $days->count() === 1);
        $this->get('/dashboard?period=week')->assertOk()->assertViewHas('from', fn ($date) => $date->toDateString() === '2026-10-12')
            ->assertViewHas('to', fn ($date) => $date->toDateString() === '2026-10-18');
    }

    public function test_invalid_date_filters_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/dashboard?period=custom')->assertSessionHasErrors(['from', 'to']);
        $this->get('/dashboard?period=custom&from=2026-10-31&to=2026-10-01')->assertSessionHasErrors('to');
        $this->get('/dashboard?period=custom&from=2024-01-01&to=2026-10-01')->assertSessionHasErrors('to');
        $this->get('/dashboard?period=custom&from=invalid&to=invalid')->assertSessionHasErrors(['from', 'to']);
        $this->get('/dashboard?period=unknown')->assertSessionHasErrors('period');
    }
}
