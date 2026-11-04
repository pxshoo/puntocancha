<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('subscription_checkout_required')->default(false));
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('plan_name');
            $table->string('billing_cycle');
            $table->unsignedInteger('amount');
            $table->string('mode');
            $table->string('status')->default('initialized');
            $table->string('request_hash', 64)->unique();
            $table->string('buy_order', 26)->unique();
            $table->string('session_id', 61);
            $table->text('token')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->text('gateway_url')->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('terms_version');
            $table->string('privacy_version');
            $table->json('terms_snapshot');
            $table->json('privacy_snapshot');
            $table->timestamp('accepted_at');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['club_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('subscription_checkout_required'));
    }
};
