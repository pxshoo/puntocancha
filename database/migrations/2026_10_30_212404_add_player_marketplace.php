<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('club_id')->nullable()->change();
            $table->string('favorite_sport')->nullable();
        });
        Schema::table('clubs', function (Blueprint $table) {
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->boolean('marketplace_enabled')->default(false);
            $table->text('booking_terms')->nullable();
            $table->text('cancellation_policy')->nullable();
        });
        foreach (DB::table('users')->where('role', 'club_admin')->whereNotNull('phone')->orderBy('id')->get() as $user) {
            DB::table('clubs')->where('id', $user->club_id)->whereNull('phone')->update(['phone' => $user->phone]);
        }
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('player_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('request_key')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->unique(['player_id', 'request_key']);
            $table->index(['player_id', 'start_time']);
        });
    }

    public function down(): void
    {
        if (DB::table('users')->whereNull('club_id')->exists()) {
            throw new RuntimeException('Move player accounts to a compatible schema before rolling back nullable club_id.');
        }
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['player_id', 'request_key']);
            $table->dropIndex(['player_id', 'start_time']);
            $table->dropConstrainedForeignId('player_id');
            $table->dropColumn(['request_key', 'payment_terms', 'cancellation_policy']);
        });
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropColumn(['address', 'city', 'phone', 'image_url', 'marketplace_enabled', 'booking_terms', 'cancellation_policy']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('favorite_sport');
            $table->unsignedBigInteger('club_id')->nullable(false)->change();
        });
    }
};
