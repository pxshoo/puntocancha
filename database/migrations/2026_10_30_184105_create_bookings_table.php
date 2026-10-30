<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('total_price', 12, 2);
            $table->decimal('deposit_paid', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['court_id', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
