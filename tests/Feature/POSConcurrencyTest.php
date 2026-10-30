<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class POSConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! preg_match('/^puntocancha_pos_test_[a-f0-9]+$/', DB::connection()->getDatabaseName())) {
            $this->markTestSkipped('Concurrency tests require an isolated puntocancha_pos_test_* PostgreSQL database.');
        }
    }

    public function test_simultaneous_sales_cannot_oversell_or_duplicate_a_retry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = Product::factory()->create(['stock' => 3]);

        $results = $this->race($user, $product, [(string) Str::uuid(), (string) Str::uuid()]);
        sort($results);
        $this->assertSame(['ok', 'stock_rejected'], $results);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('stock_movements', 1);

        $second = Product::factory()->create(['stock' => 3]);
        $key = (string) Str::uuid();
        $this->assertSame(['ok', 'ok'], $this->race($user, $second, [$key, $key]));
        $this->assertSame(1, $second->fresh()->stock);
        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    private function race(User $user, Product $product, array $keys): array
    {
        $worker = <<<'PHP'
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(["database.default" => "pgsql", "database.connections.pgsql" => json_decode(getenv("POS_TEST_DATABASE"), true)]);
Illuminate\Support\Facades\DB::purge("pgsql");
Illuminate\Support\Facades\Auth::setUser(App\Models\User::findOrFail((int) getenv("POS_TEST_USER")));
echo "ready\n";
flush();
fgets(STDIN);
try {
    app(App\Services\SaleService::class)->create([
        "request_key" => getenv("POS_TEST_KEY"), "payment_method" => "cash",
        "items" => [["product_id" => (int) getenv("POS_TEST_PRODUCT"), "quantity" => 2]],
    ]);
    echo "ok";
} catch (Illuminate\Validation\ValidationException $exception) {
    echo "stock_rejected";
}
PHP;
        $processes = [];
        $inputs = [];
        foreach ($keys as $key) {
            $input = new InputStream;
            $process = new Process([PHP_BINARY, '-r', $worker], base_path(), [
                'POS_TEST_DATABASE' => json_encode(config('database.connections.pgsql')),
                'POS_TEST_USER' => (string) $user->id, 'POS_TEST_PRODUCT' => (string) $product->id,
                'POS_TEST_KEY' => $key,
            ], $input, 20);
            $process->start();
            $processes[] = $process;
            $inputs[] = $input;
        }
        foreach ($processes as $process) {
            $this->assertTrue($process->waitUntil(fn ($type, $output) => str_contains($output, 'ready')), $process->getErrorOutput());
        }

        DB::beginTransaction();
        DB::table('clubs')->where('id', $user->club_id)->lockForUpdate()->first();
        try {
            foreach ($inputs as $input) {
                $input->write("go\n");
                $input->close();
            }
        } finally {
            DB::commit();
        }

        return array_map(function (Process $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

            return trim(str_replace('ready', '', $process->getOutput()));
        }, $processes);
    }
}
