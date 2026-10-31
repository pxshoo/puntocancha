<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PlayerBookingConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! preg_match('/^puntocancha_pos_test_[a-f0-9]+$/', DB::connection()->getDatabaseName())) {
            $this->markTestSkipped('Requires isolated PostgreSQL test database.');
        }
    }

    public function test_two_players_cannot_confirm_the_same_slot_and_retry_is_idempotent(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $club = $owner->club;
        $club->forceFill(['city' => 'Puerto Montt', 'address' => 'Calle 123', 'phone' => '+56912345678',
            'opens_at' => '08:00', 'closes_at' => '23:00', 'slot_minutes' => 60,
            'marketplace_enabled' => true, 'onboarding_completed_at' => now(),
            'booking_terms' => 'Pago en club', 'cancellation_policy' => 'Contactar al club'])->save();
        $court = Court::factory()->create(['default_hourly_rate' => 20000]);
        $first = User::factory()->create(['role' => 'player', 'phone' => '+56912345678', 'club_id' => null]);
        $second = User::factory()->create(['role' => 'player', 'phone' => '+56912345679', 'club_id' => null]);
        try {
            $results = $this->race($club->slug, $court->id, [
                [$first->id, (string) Str::uuid()], [$second->id, (string) Str::uuid()],
            ]);
            sort($results);
            $this->assertSame(['occupied', 'ok'], $results);
            $this->assertDatabaseCount('bookings', 1);
            $mixed = $this->race($club->slug, $court->id, [
                [$owner->id, (string) Str::uuid()], [$second->id, (string) Str::uuid()],
            ], '09:00');
            sort($mixed);
            $this->assertSame(['occupied', 'ok'], $mixed);
            $this->assertDatabaseCount('bookings', 2);
            $booking = Booking::withoutGlobalScopes()->whereNotNull('player_id')->orderBy('start_time')->firstOrFail();
            $this->assertSame(['ok', 'ok'], $this->race($club->slug, $court->id, [
                [$booking->player_id, $booking->request_key], [$booking->player_id, $booking->request_key],
            ]));
            $this->assertDatabaseCount('bookings', 2);
        } finally {
            DB::table('bookings')->where('club_id', $club->id)->delete();
            DB::table('users')->whereIn('id', [$first->id, $second->id])->delete();
        }
    }

    private function race(string $slug, int $courtId, array $actors, string $time = '08:00'): array
    {
        $worker = <<<'PHP'
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(["database.default" => "pgsql", "database.connections.pgsql" => json_decode(getenv("PC_TEST_DATABASE"), true)]);
Illuminate\Support\Facades\DB::purge("pgsql");
Illuminate\Support\Facades\Auth::setUser(App\Models\User::findOrFail((int) getenv("PC_TEST_PLAYER")));
echo "ready\n"; flush(); fgets(STDIN);
try {
    app(App\Services\BookingService::class)->createBooking([
        "club_slug" => getenv("PC_TEST_CLUB"), "court_id" => (int) getenv("PC_TEST_COURT"),
        "date" => today()->addDay()->toDateString(),
        "start_time" => auth()->user()->role === "player" ? getenv("PC_TEST_TIME") : today()->addDay()->setTimeFromTimeString(getenv("PC_TEST_TIME")),
        "request_key" => getenv("PC_TEST_KEY"), "quoted_price" => "20000.00",
        "user_id" => auth()->id(), "customer_name" => "Reserva en recepción", "customer_phone" => "+56912345678",
        "end_time" => today()->addDay()->setTimeFromTimeString(getenv("PC_TEST_TIME"))->addHour(),
        "total_price" => 20000, "status" => "confirmed",
    ]);
    echo "ok";
} catch (Illuminate\Validation\ValidationException $exception) {
    echo "occupied";
} catch (Exception $exception) {
    if (!str_contains($exception->getMessage(), "ocupado")) { throw $exception; }
    echo "occupied";
}
PHP;
        $processes = [];
        $inputs = [];
        foreach ($actors as [$player, $key]) {
            $input = new InputStream;
            $process = new Process([PHP_BINARY, '-r', $worker], base_path(), [
                'PC_TEST_DATABASE' => json_encode(config('database.connections.pgsql')),
                'PC_TEST_PLAYER' => (string) $player, 'PC_TEST_CLUB' => $slug,
                'PC_TEST_COURT' => (string) $courtId, 'PC_TEST_KEY' => $key,
                'PC_TEST_TIME' => $time,
            ], $input, 20);
            $process->start();
            $processes[] = $process;
            $inputs[] = $input;
        }
        foreach ($processes as $process) {
            $this->assertTrue($process->waitUntil(fn ($type, $out) => str_contains($out, 'ready')), $process->getErrorOutput());
        }
        DB::beginTransaction();
        DB::table('courts')->where('id', $courtId)->lockForUpdate()->first();
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
