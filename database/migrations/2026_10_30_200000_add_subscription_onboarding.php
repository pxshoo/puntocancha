<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('club_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('role')->default('club_admin');
            $table->boolean('onboarding_completed')->default(false);
        });
        DB::table('users')->update(['onboarding_completed' => true]);
        Schema::table('clubs', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->unsignedSmallInteger('slot_minutes')->default(60);
            $table->timestamp('onboarding_completed_at')->nullable();
        });
        foreach (DB::table('clubs')->get() as $club) {
            DB::table('clubs')->where('id', $club->id)->update(['slug' => (Str::slug($club->name) ?: 'club').'-'.Str::lower(Str::random(8))]);
            DB::table('users')->where('club_id', $club->id)->update(['club_name' => $club->name]);
        }
        Schema::table('courts', function (Blueprint $table) {
            $table->string('sport_type')->default('padel');
            $table->string('surface_type')->default('Sin especificar');
            $table->boolean('is_covered')->default(false);
            $table->uuid('setup_key')->nullable();
            $table->unique(['club_id', 'setup_key']);
        });
        foreach (DB::table('courts')->get() as $court) {
            DB::table('courts')->where('id', $court->id)->update(['setup_key' => (string) Str::uuid()]);
        }
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->string('interval')->default('monthly');
            $table->unsignedInteger('court_limit')->nullable();
            $table->timestamps();
        });
        foreach (['basic' => 2, 'pro' => 6, 'premium' => null] as $key => $limit) {
            DB::table('plans')->insert([
                'slug' => $key, 'name' => config('plans.'.$key.'.name'),
                'price' => (int) str_replace('.', '', config('plans.'.$key.'.price')),
                'interval' => 'monthly', 'court_limit' => $limit,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status');
            $table->string('billing_cycle')->default('monthly');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::table('courts', function (Blueprint $table) {
            $table->dropUnique(['club_id', 'setup_key']);
            $table->dropColumn(['sport_type', 'surface_type', 'is_covered', 'setup_key']);
        });
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'opens_at', 'closes_at', 'slot_minutes', 'onboarding_completed_at']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['club_name', 'phone', 'role', 'onboarding_completed']));
    }
};
