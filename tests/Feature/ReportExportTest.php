<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_has_real_totals_safe_cells_dates_and_tenant_isolation(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        Product::factory()->create(['name' => '=HYPERLINK("evil") & Agua', 'cost_price' => 400, 'sale_price' => 1000, 'stock' => 3]);
        $court = Court::factory()->create();
        Booking::factory()->create(['court_id' => $court->id, 'start_time' => '2026-10-12 10:00', 'end_time' => '2026-10-12 11:00', 'total_price' => 20000, 'deposit_paid' => 5000, 'status' => 'confirmed']);
        Booking::factory()->create(['status' => 'canceled', 'start_time' => '2026-10-13', 'total_price' => 99999]);
        $sale = Sale::create(['request_key' => (string) Str::uuid(), 'status' => 'paid', 'total_amount' => 2000, 'cost_amount' => 800]);
        $sale->forceFill(['created_at' => '2026-10-31 23:59:59'])->save();
        Expense::create(['concept' => 'Luz octubre', 'amount' => 3000, 'expense_date' => '2026-10-15']);
        Expense::create(['concept' => 'Fuera del periodo', 'amount' => 99999, 'expense_date' => '2026-11-01']);
        $other = User::factory()->create();
        $this->actingAs($other);
        Product::factory()->create(['name' => 'Producto ajeno']);
        Expense::create(['concept' => 'Gasto ajeno', 'amount' => 88888, 'expense_date' => '2026-10-15']);
        $this->actingAs($owner);
        $response = $this->get(route('reports.excel', ['period' => 'custom', 'from' => '2026-10-01', 'to' => '2026-10-31', 'club_id' => $other->club_id]));
        $response->assertOk()->assertDownload('PuntoCancha-20261001-20261031.xlsx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $workbook = $zip->getFromName('xl/workbook.xml');
            $this->assertStringContainsString('Resumen ganancias', $workbook);
            $this->assertStringContainsString('Inventario actual', $workbook);
            $summary = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertStringContainsString('<v>18200</v>', $summary);
            $this->assertStringContainsString('<v>3000</v>', $summary);
            $this->assertStringContainsString('<v>800</v>', $summary);
            $inventory = $zip->getFromName('xl/worksheets/sheet2.xml');
            $this->assertStringContainsString('=HYPERLINK', $inventory);
            $this->assertStringNotContainsString('<f>', $inventory);
            $this->assertStringNotContainsString('Producto ajeno', $inventory);
            $expenses = $zip->getFromName('xl/worksheets/sheet5.xml');
            $this->assertStringContainsString('Luz octubre', $expenses);
            $this->assertStringNotContainsString('Gasto ajeno', $expenses);
            $this->assertStringNotContainsString('Fuera del periodo', $expenses);
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if (str_ends_with($name, '.xml') || str_ends_with($name, '.rels')) {
                    $this->assertNotFalse(simplexml_load_string($zip->getFromIndex($index)), $name);
                }
            }
            $zip->close();
        } finally {
            unlink($path);
        }
    }

    public function test_exports_reject_guests_players_staff_and_invalid_dates(): void
    {
        $this->get(route('reports.excel'))->assertRedirect('/login');
        $owner = User::factory()->create();
        $this->actingAs($owner)->get(route('reports.excel', ['period' => 'custom', 'from' => '2026-11-10', 'to' => '2026-11-01']))->assertSessionHasErrors('to');
        $staff = User::factory()->create(['role' => 'staff', 'club_id' => $owner->club_id]);
        $this->actingAs($staff)->get(route('reports.excel'))->assertForbidden();
        $player = User::factory()->create(['role' => 'player', 'club_id' => null]);
        $this->actingAs($player)->get(route('reports.excel'))->assertForbidden();
    }

    public function test_empty_club_can_download_workbook(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Exportar Excel');
        $response = $this->get(route('reports.excel'));
        $response->assertOk();
        unlink($response->baseResponse->getFile()->getPathname());
    }
}
