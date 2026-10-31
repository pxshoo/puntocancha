<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->string('webpay_mode')->default('disabled');
            $table->string('webpay_commerce_code', 12)->nullable();
            $table->text('webpay_api_key')->nullable();
        });
        Schema::table('courts', fn (Blueprint $table) => $table->unsignedInteger('minimum_deposit')->default(0));
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->unique()->constrained()->restrictOnDelete();
            $table->string('request_hash', 64)->unique();
            $table->string('buy_order', 26)->unique();
            $table->string('session_id', 61);
            $table->string('mode');
            $table->unsignedInteger('amount');
            $table->string('choice');
            $table->string('status')->default('initialized');
            $table->text('token')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->text('gateway_url')->nullable();
            $table->string('authorization_code')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
        Schema::table('courts', fn (Blueprint $table) => $table->dropColumn('minimum_deposit'));
        Schema::table('clubs', fn (Blueprint $table) => $table->dropColumn(['webpay_mode', 'webpay_commerce_code', 'webpay_api_key']));
    }
};
